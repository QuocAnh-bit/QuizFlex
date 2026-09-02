<?php

namespace App\Http\Controllers;

use App\Events\AccountStatusChanged;
use App\Models\UnlockRequest;
use App\Models\User;
use App\Notifications\AccountModerated;
use App\Notifications\UnlockRequestCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class UnlockRequestController extends Controller
{
    public function index(Request $request)
    {
        $actor = Auth::guard('api')->user();
        if (!$this->isAdminActor($actor)) {
            return response()->json(['success' => false, 'message' => 'Không có quyền truy cập.'], 403);
        }

        $query = UnlockRequest::query()->with(['user', 'reviewer'])->latest();

        if ($request->filled('status') && $request->query('status') !== 'all') {
            $query->where('status', strtolower((string) $request->query('status')));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->query('user_id'));
        }

        if ($search = $request->input('search')) {
            $keyword = trim((string) $search);
            $query->where(function ($q) use ($keyword) {
                $q->where('message', 'like', "%{$keyword}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"));
            });
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $paginated = $query->paginate($perPage);

        $items = collect($paginated->items())->map(function (UnlockRequest $requestItem) {
            return [
                'id' => $requestItem->id,
                'user_id' => $requestItem->user_id,
                'message' => $requestItem->message,
                'status' => $requestItem->status,
                'admin_note' => $requestItem->admin_note,
                'reviewed_by' => $requestItem->reviewer ? [
                    'id' => $requestItem->reviewer->id,
                    'name' => $requestItem->reviewer->name,
                    'email' => $requestItem->reviewer->email,
                ] : null,
                'reviewed_at' => $requestItem->reviewed_at?->toDateTimeString(),
                'created_at' => $requestItem->created_at?->toDateTimeString(),
                'updated_at' => $requestItem->updated_at?->toDateTimeString(),
                'user' => $requestItem->user ? [
                    'id' => $requestItem->user->id,
                    'name' => $requestItem->user->name,
                    'email' => $requestItem->user->email,
                    'role' => $requestItem->user->getRole(),
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Danh sách kháng cáo tài khoản',
            'data' => [
                'items' => $items,
                'total' => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
            ],
            'items' => $items,
            'total' => $paginated->total(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
        ]);
    }

    public function show(UnlockRequest $unlockRequest)
    {
        $actor = Auth::guard('api')->user();
        if (!$this->isAdminActor($actor)) {
            return response()->json(['success' => false, 'message' => 'Không có quyền truy cập.'], 403);
        }

        $unlockRequest->load(['user', 'reviewer']);

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết kháng cáo',
            'data' => [
                'id' => $unlockRequest->id,
                'user' => $unlockRequest->user ? [
                    'id' => $unlockRequest->user->id,
                    'name' => $unlockRequest->user->name,
                    'email' => $unlockRequest->user->email,
                    'role' => $unlockRequest->user->getRole(),
                ] : null,
                'message' => $unlockRequest->message,
                'status' => $unlockRequest->status,
                'admin_note' => $unlockRequest->admin_note,
                'reviewed_by' => $unlockRequest->reviewer ? [
                    'id' => $unlockRequest->reviewer->id,
                    'name' => $unlockRequest->reviewer->name,
                    'email' => $unlockRequest->reviewer->email,
                ] : null,
                'reviewed_at' => $unlockRequest->reviewed_at?->toDateTimeString(),
                'created_at' => $unlockRequest->created_at?->toDateTimeString(),
                'updated_at' => $unlockRequest->updated_at?->toDateTimeString(),
                'locked_reason' => $unlockRequest->user?->locked_reason,
                'locked_at' => $unlockRequest->user?->locked_at?->toDateTimeString(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập để gửi kháng cáo.'], 401);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $existingPending = UnlockRequest::where('user_id', $user->id)->where('status', 'pending')->exists();
        if ($existingPending) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã có một kháng cáo đang chờ xét duyệt.',
            ], 422);
        }

        $unlockRequest = UnlockRequest::create([
            'user_id' => $user->id,
            'message' => trim($data['message']),
            'status' => 'pending',
        ]);

        $admins = User::whereRaw('LOWER(role) IN (?, ?)', ['admin', 'administrator'])
            ->orWhere('is_main_admin', true)
            ->get();
        if ($admins->isNotEmpty()) {
            try {
                Notification::send($admins, new UnlockRequestCreated($unlockRequest, $user));
            } catch (\Throwable $e) {
                Log::warning('Không thể gửi thông báo UnlockRequestCreated cho Admin: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi kháng cáo thành công.',
            'data' => [
                'id' => $unlockRequest->id,
                'user_id' => $unlockRequest->user_id,
                'message' => $unlockRequest->message,
                'status' => $unlockRequest->status,
                'created_at' => $unlockRequest->created_at?->toDateTimeString(),
            ],
        ], 201);
    }

    public function latest(Request $request)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập để tiếp tục.'], 401);
        }

        $latest = UnlockRequest::where('user_id', $user->id)->latest()->first();

        return response()->json([
            'success' => true,
            'message' => 'Thông tin kháng cáo gần nhất',
            'data' => $latest ? [
                'id' => $latest->id,
                'message' => $latest->message,
                'status' => $latest->status,
                'admin_note' => $latest->admin_note,
                'created_at' => $latest->created_at?->toDateTimeString(),
            ] : null,
        ]);
    }

    public function approve(UnlockRequest $unlockRequest)
    {
        $actor = Auth::guard('api')->user();
        if (!$this->isAdminActor($actor)) {
            return response()->json(['success' => false, 'message' => 'Không có quyền truy cập.'], 403);
        }

        $data = request()->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $unlockRequest->forceFill([
            'status' => 'approved',
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'admin_note' => trim((string) ($data['admin_note'] ?? '')) ?: null,
        ])->save();

        $user = $unlockRequest->user;
        if ($user) {
            $user->forceFill([
                'is_locked'     => false,
                'locked_at'     => null,
                'locked_reason' => null,
                'locked_by'     => null,
            ])->save();

            $user->load(['lockedBy']);
            $user->loadCount(['quizzes', 'attempts']);
            broadcast(new AccountStatusChanged($user, 'unlocked'));

            $user->notify(new AccountModerated('unlock_approved'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã duyệt kháng cáo và mở khóa tài khoản.',
        ]);
    }

    public function reject(UnlockRequest $unlockRequest)
    {
        $actor = Auth::guard('api')->user();
        if (!$this->isAdminActor($actor)) {
            return response()->json(['success' => false, 'message' => 'Không có quyền truy cập.'], 403);
        }

        $data = request()->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $unlockRequest->forceFill([
            'status' => 'rejected',
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'admin_note' => trim((string) ($data['admin_note'] ?? '')) ?: null,
        ])->save();

        $user = $unlockRequest->user;
        if ($user) {
            broadcast(new AccountStatusChanged($user, 'appeal_rejected', $unlockRequest->admin_note));
            $user->notify(new AccountModerated('unlock_rejected', $unlockRequest->admin_note));
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã từ chối kháng cáo.',
        ]);
    }

    public function pendingCount()
    {
        $actor = Auth::guard('api')->user();
        if (!$this->isAdminActor($actor)) {
            return response()->json(['success' => false, 'message' => 'Không có quyền truy cập.'], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Số kháng cáo đang chờ',
            'data' => [
                'count' => UnlockRequest::where('status', 'pending')->count(),
            ],
        ]);
    }

    private function isAdminActor($actor): bool
    {
        if (!$actor) {
            return false;
        }

        if ($actor instanceof User && method_exists($actor, 'isAdmin')) {
            return $actor->isAdmin();
        }

        if (method_exists($actor, 'getRole')) {
            return strtolower((string) $actor->getRole()) === 'admin';
        }

        return strtolower((string) ($actor->role ?? '')) === 'admin';
    }
}
