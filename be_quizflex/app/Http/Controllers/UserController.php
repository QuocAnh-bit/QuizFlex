<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->withCount(['quizzes', 'attempts'])
            ->latest();

        if ($request->filled('search')) {
            $keyword = trim((string) $request->query('search'));
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('role') && $request->query('role') !== 'all') {
            $role = strtoupper((string) $request->query('role'));
            if ($role === 'VIP') {
                $query->whereIn('role', ['PLUS', 'PRO', 'ULTRA']);
            } elseif ($role === 'USER') {
                $query->whereIn('role', ['FREE', 'GUEST']);
            } else {
                $query->where('role', $role);
            }
        }

        $perPage = min(max((int) $request->query('per_page', 50), 1), 100);
        $users = $query->paginate($perPage)->through(fn (User $user) => $this->formatUser($user));

        return response()->json([
            'success' => true,
            'message' => 'Danh sách người dùng',
            'data' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'role' => ['nullable', Rule::in(['GUEST', 'FREE', 'PLUS', 'PRO', 'ULTRA', 'ADMIN', 'guest', 'free', 'plus', 'pro', 'ultra', 'admin'])],
            'avatar' => ['nullable', 'string', 'max:255'],
            'ai_quota_remaining' => ['nullable', 'integer', 'min:0'],
            'vip_expires_at' => ['nullable', 'date'],
        ]);

        $role = strtoupper($data['role'] ?? 'FREE');

        if ($role === 'ADMIN' && User::where('role', 'ADMIN')->whereNull('deleted_at')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có admin khác. Không thể tạo admin mới.',
            ], 422);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $role,
            'avatar' => $data['avatar'] ?? null,
            'ai_quota_remaining' => $data['ai_quota_remaining'] ?? $this->defaultAiQuotaForRole($role),
            'vip_expires_at' => $data['vip_expires_at'] ?? (in_array($role, ['PLUS', 'PRO', 'ULTRA']) ? now()->addMonth() : null),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo người dùng thành công',
            'data' => $this->formatUser($user),
        ], 201);
    }

    public function show(User $user)
    {
        $user->loadCount(['quizzes', 'attempts']);

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết người dùng',
            'data' => $this->formatUser($user),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'role' => ['sometimes', Rule::in(['GUEST', 'FREE', 'PLUS', 'PRO', 'ULTRA', 'ADMIN', 'guest', 'free', 'plus', 'pro', 'ultra', 'admin'])],
            'avatar' => ['nullable', 'string', 'max:255'],
            'ai_quota_remaining' => ['nullable', 'integer', 'min:0'],
            'vip_expires_at' => ['nullable', 'date'],
        ]);

        $payload = collect($data)->except('password')->all();
        if (isset($payload['role'])) {
            $payload['role'] = strtoupper($payload['role']);

            if (strtoupper($user->role) === 'ADMIN' && $payload['role'] !== 'ADMIN') {
                $payload['role'] = 'ADMIN';
            }

            $isBecomingAdmin = $payload['role'] === 'ADMIN' && strtoupper($user->role) !== 'ADMIN';
            if ($isBecomingAdmin && $this->anotherAdminExists($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đã có admin khác. Không thể chuyển user này thành admin.',
                ], 422);
            }

            if (!array_key_exists('ai_quota_remaining', $payload)) {
                $payload['ai_quota_remaining'] = $this->defaultAiQuotaForRole($payload['role']);
            }

            if (in_array($payload['role'], ['PLUS', 'PRO', 'ULTRA']) && !array_key_exists('vip_expires_at', $payload) && !$user->vip_expires_at) {
                $payload['vip_expires_at'] = now()->addMonth();
            }

            if (!in_array($payload['role'], ['PLUS', 'PRO', 'ULTRA']) && $payload['role'] !== 'ADMIN' && !array_key_exists('vip_expires_at', $payload)) {
                $payload['vip_expires_at'] = null;
            }
        }
        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $user->loadCount(['quizzes', 'attempts']);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật người dùng thành công',
            'data' => $this->formatUser($user),
        ]);
    }

    public function destroy(User $user)
    {
        if (strtoupper($user->role) === 'ADMIN') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa tài khoản admin.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa người dùng',
        ]);
    }

    public function trashed(Request $request)
    {
        $query = User::onlyTrashed()->withCount(['quizzes', 'attempts'])->latest('deleted_at');

        if ($request->filled('search')) {
            $keyword = trim((string) $request->query('search'));
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('role') && $request->query('role') !== 'all') {
            $role = strtoupper((string) $request->query('role'));
            if ($role === 'VIP') {
                $query->whereIn('role', ['PLUS', 'PRO', 'ULTRA']);
            } elseif ($role === 'USER') {
                $query->whereIn('role', ['FREE', 'GUEST']);
            } else {
                $query->where('role', $role);
            }
        }

        $perPage = min(max((int) $request->query('per_page', 50), 1), 100);
        $users = $query->paginate($perPage)->through(fn (User $user) => $this->formatUser($user));

        return response()->json([
            'success' => true,
            'message' => 'Danh sách người dùng đã xóa',
            'data' => $users,
        ]);
    }

    public function restore(int $id)
    {
        $user = User::onlyTrashed()->findOrFail($id);

        if (strtoupper($user->role) === 'ADMIN' && $this->anotherAdminExists($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể khôi phục admin khi đã có admin khác.',
            ], 403);
        }

        $user->restore();
        $user->loadCount(['quizzes', 'attempts']);

        return response()->json([
            'success' => true,
            'message' => 'Đã khôi phục người dùng',
            'data' => $this->formatUser($user),
        ]);
    }

    public function forceDelete(int $id)
    {
        $user = User::onlyTrashed()->findOrFail($id);

        if (strtoupper($user->role) === 'ADMIN') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa vĩnh viễn admin.',
            ], 403);
        }

        $user->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa vĩnh viễn người dùng',
        ]);
    }

    private function defaultAiQuotaForRole(string $role): int
    {
        return match (strtoupper($role)) {
            'ADMIN' => 9999,
            'ULTRA' => 1500,
            'PRO' => 350,
            'PLUS' => 100,
            'GUEST' => 0,
            default => 5,
        };
    }

    private function anotherAdminExists(?int $ignoreUserId = null): bool
    {
        $query = User::where('role', 'ADMIN')->whereNull('deleted_at');
        if ($ignoreUserId) {
            $query->where('id', '!=', $ignoreUserId);
        }

        return $query->exists();
    }

    private function formatUser(User $user): array
    {
        $tier = $user->getSubscriptionTier();
        
        $isTrial = false;
        if ($user->trial_used_at && $user->vip_expires_at) {
            $trialExpiry = \Carbon\Carbon::parse($user->trial_used_at)->addDays(7);
            $isTrial = $trialExpiry->isFuture() && \Carbon\Carbon::parse($user->vip_expires_at)->lte($trialExpiry);
        }

        $roleLabel = match ($tier) {
            'admin' => 'Admin',
            'plus' => $isTrial ? 'Plus (Dùng thử)' : 'Plus',
            'pro' => 'Pro',
            'ultra' => 'Ultra',
            default => 'Free',
        };

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $tier,
            'role_label' => $roleLabel,
            'avatar' => $this->resolveAvatarForResponse($user->avatar),
            'ai_quota_remaining' => $user->ai_quota_remaining,
            'vip_expires_at' => $user->vip_expires_at ? $user->vip_expires_at->toDateTimeString() : null,
            'trial_used_at' => $user->trial_used_at ? $user->trial_used_at->toDateTimeString() : null,
            'quizzes_count' => $user->quizzes_count ?? $user->quizzes()->count(),
            'attempts_count' => $user->attempts_count ?? $user->attempts()->count(),
            'status' => 'active',
            'joined_at' => $user->created_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'deleted_at' => $user->deleted_at ? $user->deleted_at->toDateTimeString() : null,
        ];
    }

    private function resolveAvatarForResponse(?string $avatar): ?string
    {
        if (!$avatar) {
            return null;
        }

        if (str_starts_with($avatar, '/storage/')) {
            return url($avatar);
        }

        return $avatar;
    }

}
