<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    /**
     * Submit contact form (Public)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $message = ContactMessage::create($validated);

        return response()->json([
            'message' => 'Thank you for contacting us! We\'ll get back to you soon.',
            'data' => $message,
        ], 201);
    }

    /**
     * Get all contact messages (Admin)
     */
    public function index(Request $request): JsonResponse
    {
        $messages = ContactMessage::latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($messages);
    }

    /**
     * Get single contact message (Admin)
     */
    public function show(int $id): JsonResponse
    {
        $message = ContactMessage::findOrFail($id);

        return response()->json($message);
    }

    /**
     * Reply to contact message (Admin)
     */
    public function reply(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reply' => 'required|string',
        ]);

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $message = ContactMessage::findOrFail($id);

        $message->update([
            'reply' => $request->reply,
            'status' => 'replied',
            'replied_by' => $currentUser->id,
            'replied_at' => now(),
        ]);

        return response()->json([
            'message' => 'Reply sent successfully.',
            'data' => $message,
        ]);
    }

    /**
     * Delete contact message (Admin)
     */
    public function destroy(int $id): JsonResponse
    {
        $message = ContactMessage::findOrFail($id);
        $message->delete();

        return response()->json([
            'message' => 'Contact message deleted successfully.',
        ]);
    }

    /**
     * Mark message as read (Admin)
     */
    public function markAsRead(int $id): JsonResponse
    {
        $message = ContactMessage::findOrFail($id);

        $message->update([
            'status' => 'read',
        ]);

        return response()->json([
            'message' => 'Message marked as read.',
            'data' => $message,
        ]);
    }

    /**
     * Get unread messages count (Admin)
     */
    public function unreadCount(): JsonResponse
    {
        $count = ContactMessage::where('status', 'new')->count();

        return response()->json([
            'count' => $count,
        ]);
    }
}