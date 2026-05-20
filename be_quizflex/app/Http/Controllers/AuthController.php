<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc mật khẩu không đúng',
            ], 422);
        }

        $user = auth('api')->user();

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data' => $this->formatUser($user),
            'token' => $token,
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'USER',
            'ai_quota_remaining' => 5,
        ]);

        $token = auth('api')->login($user);

        return response()->json([
            'success' => true,
            'message' => 'Tạo tài khoản thành công',
            'data' => $this->formatUser($user),
            'token' => $token,
        ], 201);
    }

    public function refresh()
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Làm mới token thành công',
                'token' => auth('api')->refresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã bị vô hiệu hóa',
            ], 401);
        }
    }

    public function me()
    {
        return response()->json([
            'success' => true,
            'data' => $this->formatUser(auth('api')->user()),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'avatar_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $payload = [];

        if (array_key_exists('name', $data)) {
            $payload['name'] = trim((string) $data['name']);
        }

        if ($request->hasFile('avatar_file')) {
            $this->deleteStoredAvatar($user->avatar);
            $path = $request->file('avatar_file')->store('avatars', 'public');
            $payload['avatar'] = $this->storedAvatarPublicUrl($path);
        } elseif ($request->boolean('remove_avatar')) {
            $this->deleteStoredAvatar($user->avatar);
            $payload['avatar'] = null;
        }

        if (!empty($payload)) {
            $user->update($payload);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật hồ sơ thành công',
            'data' => $this->formatUser($user->fresh()),
        ]);
    }

    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công',
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
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    private function storedAvatarPublicUrl(string $path): string
    {
        return url('/storage/' . ltrim($path, '/'));
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

    private function deleteStoredAvatar(?string $avatar): void
    {
        if (!$avatar) {
            return;
        }

        $path = parse_url($avatar, PHP_URL_PATH) ?: $avatar;

        if (!str_starts_with($path, '/storage/avatars/')) {
            return;
        }

        Storage::disk('public')->delete(str_replace('/storage/', '', $path));
    }
}
