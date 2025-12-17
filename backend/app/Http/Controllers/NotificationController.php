<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get paginated list of user's notifications
     */
    public function index(Request $request)
    {
        $query = $request->user()->notifications()->orderBy('created_at', 'desc');

        // Filter by read status
        if ($request->has('read')) {
            $query->where('read', $request->boolean('read'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Get count of unread notifications
     */
    public function unreadCount(Request $request)
    {
        $count = $request->user()->notifications()->unread()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark a notification as read
     */
    public function markRead(Request $request, Notification $notification)
    {
        $this->authorize('update', $notification);

        $notification->update([
            'read' => true,
            'read_at' => now(),
        ]);

        return response()->json($notification);
    }

    /**
     * Mark all user's notifications as read
     */
    public function markAllRead(Request $request)
    {
        $request->user()->notifications()->unread()->update([
            'read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues']);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, Notification $notification)
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return response()->json(null, 204);
    }

    /**
     * Delete all user's notifications
     */
    public function clearAll(Request $request)
    {
        $request->user()->notifications()->delete();

        return response()->json(['message' => 'Toutes les notifications ont été supprimées']);
    }
}
