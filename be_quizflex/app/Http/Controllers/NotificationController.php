<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->take(20)
            ->get()
            ->map(fn ($n) => $this->transform($n));

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    private function transform($notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'type' => $data['action'] ?? 'system',
            'title' => $data['title'] ?? '',
            'message' => $data['message'] ?? '',
            'is_read' => ! is_null($notification->read_at),
            'created_at' => $notification->created_at,
            'action_link' => isset($data['quiz_id']) ? "/quiz/{$data['quiz_id']}" : null,
        ];
    }
}