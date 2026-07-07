<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use App\Models\Washerman;
use App\Models\ApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => 'required|string|max:20',
            'role' => 'required|in:customer,washerman',
            'shop_name' => 'required_if:role,washerman|nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => $request->role === 'washerman' ? 'pending' : 'active',
        ]);

        if ($request->role === 'customer') {
            Customer::create([
                'user_id' => $user->id,
            ]);
        } elseif ($request->role === 'washerman') {
            Washerman::create([
                'user_id' => $user->id,
                'shop_name' => $request->shop_name,
                'approval_status' => 'pending',
            ]);

            ApprovalRequest::create([
                'user_id' => $user->id,
                'type' => 'washerman_registration',
                'status' => 'pending',
                'details' => json_encode([
                    'shop_name' => $request->shop_name,
                    'phone' => $request->phone,
                ]),
            ]);
        }

        event(new Registered($user));

        return response()->json([
            'message' => $request->role === 'washerman' 
                ? 'Registration successful! Your account is pending admin approval.' 
                : 'Registration successful! Please check your email to verify your account.',
            'user' => $user,
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // Check if washerman is approved
        if ($user->role === 'washerman') {
            $washerman = $user->washerman;
            
            if ($washerman && $washerman->approval_status === 'pending') {
                return response()->json([
                    'message' => 'Your account is waiting for admin approval.',
                    'status' => 'pending',
                ], 403);
            }

            if ($washerman && $washerman->approval_status === 'rejected') {
                return response()->json([
                    'message' => 'Admin declined your request.',
                    'status' => 'rejected',
                    'reason' => $washerman->rejection_reason,
                ], 403);
            }
        }

        if ($user->status !== 'active' && $user->role !== 'washerman') {
            return response()->json([
                'message' => 'Your account is not active.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user->load('customer', 'washerman'),
            'token' => $token,
            'role' => $user->role,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request)
    {
        $user = $request->user()->load('customer', 'washerman');
        
        return response()->json([
            'user' => $user,
            'role' => $user->role,
        ]);
    }

    /**
     * Forgot password
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link sent to your email.',
            ]);
        }

        return response()->json([
            'message' => 'Unable to send reset link.',
        ], 400);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been reset successfully.',
            ]);
        }

        return response()->json([
            'message' => 'Unable to reset password.',
        ], 400);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }

    /**
     * Update profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|nullable|string',
            'city' => 'sometimes|nullable|string',
            'state' => 'sometimes|nullable|string',
            'zip_code' => 'sometimes|nullable|string',
            'bio' => 'sometimes|nullable|string',
            'avatar' => 'sometimes|nullable|image|max:2048',
        ]);

        $data = $request->only([
            'name', 'phone', 'address', 'city', 'state', 'zip_code', 'bio'
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh(),
        ]);
    }
}