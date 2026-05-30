<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Services\Auth\OtpService;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! $token = $this->apiGuard()->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc mật khẩu không đúng',
            ], 422);
        }

        $user = $this->authenticatedUser();

        // Kiểm tra xem tài khoản đã xác thực email (OTP) chưa
        if ($user->email_verified_at === null) {
            $this->apiGuard()->logout(); // Đăng xuất lập tức để vô hiệu hóa token vừa tạo
            
            // Gửi lại OTP tự động để hỗ trợ người dùng tiếp tục xác thực
            try {
                $otpService = app(OtpService::class);
                $otpService->generateOtp($user->email);
            } catch (\Exception $e) {
                // Ghi log lỗi gửi mail ra laravel.log
                \Illuminate\Support\Facades\Log::error('Login OTP Mail Error: ' . $e->getMessage(), [
                    'exception' => $e
                ]);
            }

            return response()->json([
                'success' => false,
                'needs_verification' => true,
                'email' => $user->email,
                'message' => 'Tài khoản của bạn chưa được kích hoạt. Một mã OTP mới đã được gửi tới email của bạn.',
            ], 403);
        }

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
            'username' => ['nullable', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'role' => ['nullable', Rule::in(['USER', 'VIP', 'user', 'vip'])],
        ]);

        if (Schema::hasColumn('users', 'username')) {
            $request->validate([
                'username' => ['nullable', 'string', 'min:3', 'max:255', Rule::unique('users', 'username')],
            ]);
        }

        $role = strtoupper($data['role'] ?? 'USER');
        $storedRole = $this->roleValueForDatabase($role);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $storedRole,
        ];

        if (Schema::hasColumn('users', 'username')) {
            $payload['username'] = $data['username'] ?? $data['email'];
        }

        if (Schema::hasColumn('users', 'ai_quota_remaining')) {
            $payload['ai_quota_remaining'] = $this->defaultAiQuotaForRole($role);
        }

        if (Schema::hasColumn('users', 'vip_expires_at')) {
            $payload['vip_expires_at'] = $role === 'VIP' ? now()->addMonth() : null;
        }

        $user = User::create($payload);

        // Tự động sinh OTP và gửi qua log/mail
        try {
            $otpService = app(OtpService::class);
            $otpService->generateOtp($user->email);
        } catch (\Exception $e) {
            // Ghi log lỗi gửi mail ra laravel.log để nhà phát triển biết lý do gửi mail thất bại
            \Illuminate\Support\Facades\Log::error('Registration OTP Mail Error: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đăng ký tài khoản thành công! Một mã OTP đã được gửi tới email của bạn để kích hoạt.',
            'data' => $this->formatUser($user),
        ], 201);
    }

    public function refresh()
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Làm mới token thành công',
                'token' => $this->apiGuard()->refresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã bị vô hiệu hóa',
            ], 401);
        }
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        try {
            $otpService = app(OtpService::class);
            $otpService->verifyOtp($data['email'], $data['otp']);

            // Xác thực email của user
            $user = User::where('email', $data['email'])->first();
            $token = null;
            if ($user) {
                $user->email_verified_at = now();
                $user->save();

                // Tự động đăng nhập và sinh JWT Token
                $token = auth('api')->login($user);
            }

            return response()->json([
                'success' => true,
                'message' => 'Xác thực OTP thành công! Tài khoản của bạn đã được kích hoạt.',
                'token' => $token,
                'data' => $user ? $this->formatUser($user) : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function resendOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $otpService = app(OtpService::class);
            $otpService->generateOtp($data['email']);

            return response()->json([
                'success' => true,
                'message' => 'Một mã OTP mới đã được gửi tới email của bạn.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function me()
    {
        return response()->json([
            'success' => true,
            'data' => $this->formatUser($this->authenticatedUser()),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $this->authenticatedUser();

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
        $this->apiGuard()->logout();

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công',
        ]);
    }


    private function apiGuard(): JWTGuard
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        return $guard;
    }

    private function authenticatedUser(): User
    {
        $user = $this->apiGuard()->user();

        if (!$user instanceof User) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }

    private function roleValueForDatabase(string $role): string
    {
        $role = strtoupper($role);

        if (!Schema::hasColumn('users', 'role')) {
            return $role;
        }

        try {
            $column = Schema::getColumns('users');
            $roleColumn = collect($column)->firstWhere('name', 'role');
            $type = strtolower((string) ($roleColumn['type_name'] ?? $roleColumn['type'] ?? ''));

            if (str_contains($type, 'enum') || str_contains($type, 'varchar') || str_contains($type, 'string')) {
                $allowed = (string) ($roleColumn['type'] ?? '');
                if (str_contains($allowed, strtolower($role))) {
                    return strtolower($role);
                }
            }
        } catch (\Throwable) {
            // Fall back to the migration-defined uppercase enum values.
        }

        return $role;
    }

    private function defaultAiQuotaForRole(string $role): int
    {
        return match (strtoupper($role)) {
            'ADMIN' => 9999,
            'VIP' => 500,
            'GUEST' => 0,
            default => 5,
        };
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
