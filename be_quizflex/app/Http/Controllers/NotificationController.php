<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        $notifications = $user->notifications()
            ->latest()
            ->paginate($perPage);

        $notifications->through(fn ($n) => $this->transform($n));

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

    public function destroyAll(Request $request)
    {
        $request->user()->notifications()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa tất cả thông báo.'
        ]);
    }

    private function transform($notification): array
    {
        $data = $notification->data;

        // Backward compatibility mapping for old records
        $type = $data['type'] ?? ($data['action'] ?? 'system');

        // If type is still 'system' and we have quiz_id, it is likely a quiz moderation action from legacy data
        if ($type === 'system' && isset($data['quiz_id'])) {
            $type = 'quiz_moderated';
        }

        $action = $data['action'] ?? 'view';

        $actionLink = $data['action_link'] ?? null;
        if (is_null($actionLink) && isset($data['quiz_id'])) {
            $actionLink = "/quizzes/{$data['quiz_id']}";
        }

        $metadata = $data['metadata'] ?? [];
        if (empty($metadata)) {
            // Populate legacy fields into metadata
            if (isset($data['quiz_id'])) {
                $metadata['quiz_id'] = $data['quiz_id'];
            }
            if (isset($data['action'])) {
                $metadata['action'] = $data['action'];
            }
        }

        return [
            'id' => $notification->id,
            'type' => $type,
            'title' => $data['title'] ?? 'Thông báo',
            'message' => $data['message'] ?? '',
            'action' => $action,
            'action_link' => $actionLink,
            'metadata' => (object) $metadata,
            'is_read' => ! is_null($notification->read_at),
            'created_at' => $notification->created_at,
        ];
    }
}