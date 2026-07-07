<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get user notifications
     */
    public function index(Request $request)
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate($request->get('per_page', 20));

        $unreadCount = auth()->user()->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $notification = auth()->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        auth()->user()
            ->unreadNotifications()
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read.',
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy($id)
    {
        $notification = auth()->user()
            ->notifications()
            ->findOrFail($id);

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted.',
        ]);
    }
}