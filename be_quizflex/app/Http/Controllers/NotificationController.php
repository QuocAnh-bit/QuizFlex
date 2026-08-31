<?php

namespace App\Http\Controllers;

use App\Services\NotificationNormalizerService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected NotificationNormalizerService $normalizer;

    public function __construct(NotificationNormalizerService $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        $notifications = $user->notifications()
            ->latest()
            ->paginate($perPage);

        $notifications->through(fn ($n) => $this->normalizer->normalize($n));

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
}