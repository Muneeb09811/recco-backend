<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WashermanApprovalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'washerman') {
            $washerman = $user->washerman;

            if (!$washerman) {
                return response()->json([
                    'message' => 'Washerman profile not found.',
                ], 403);
            }

            if ($washerman->approval_status === 'pending') {
                return response()->json([
                    'message' => 'Your account is waiting for admin approval.',
                    'status' => 'pending',
                ], 403);
            }

            if ($washerman->approval_status === 'rejected') {
                return response()->json([
                    'message' => 'Admin declined your request.',
                    'status' => 'rejected',
                    'reason' => $washerman->rejection_reason,
                ], 403);
            }
        }

        return $next($request);
    }
}