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
            $query->where('role', strtoupper((string) $request->query('role')));
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
            'role' => ['nullable', Rule::in(['GUEST', 'USER', 'VIP', 'ADMIN', 'guest', 'user', 'vip', 'admin'])],
            'avatar' => ['nullable', 'string', 'max:255'],
            'ai_quota_remaining' => ['nullable', 'integer', 'min:0'],
            'vip_expires_at' => ['nullable', 'date'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => strtoupper($data['role'] ?? 'USER'),
            'avatar' => $data['avatar'] ?? null,
            'ai_quota_remaining' => $data['ai_quota_remaining'] ?? 5,
            'vip_expires_at' => $data['vip_expires_at'] ?? null,
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
            'role' => ['sometimes', Rule::in(['GUEST', 'USER', 'VIP', 'ADMIN', 'guest', 'user', 'vip', 'admin'])],
            'avatar' => ['nullable', 'string', 'max:255'],
            'ai_quota_remaining' => ['nullable', 'integer', 'min:0'],
            'vip_expires_at' => ['nullable', 'date'],
        ]);

        $payload = collect($data)->except('password')->all();
        if (isset($payload['role'])) {
            $payload['role'] = strtoupper($payload['role']);
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
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa người dùng',
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => strtolower($user->role ?? 'user'),
            'role_label' => match (strtoupper($user->role ?? 'USER')) {
                'ADMIN' => 'Admin',
                'VIP' => 'VIP',
                'GUEST' => 'Guest',
                default => 'Thường',
            },
            'avatar' => $this->resolveAvatarForResponse($user->avatar),
            'ai_quota_remaining' => $user->ai_quota_remaining,
            'vip_expires_at' => $user->vip_expires_at,
            'quizzes_count' => $user->quizzes_count ?? $user->quizzes()->count(),
            'attempts_count' => $user->attempts_count ?? $user->attempts()->count(),
            'status' => 'active',
            'joined_at' => $user->created_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
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
