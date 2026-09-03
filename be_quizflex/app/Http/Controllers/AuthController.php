<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Services\Auth\OtpService;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
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

        $user = $this->apiGuard()->user();

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
        $data = $request->validate([ // dữ liệu gốc được gửi lên vd như mật khẩu là 123456 chưa mã hóa
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'], // email không được trùng trong bảng user
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'role' => ['nullable', Rule::in(['FREE', 'PLUS', 'PRO', 'ULTRA', 'ADMIN', 'free', 'plus', 'pro', 'ultra', 'admin', 'USER', 'user'])],
        ]);

        if (Schema::hasColumn('users', 'username')) {
            $request->validate([
                'username' => ['nullable', 'string', 'min:3', 'max:255', Rule::unique('users', 'username')],
            ]);
        }

        $role = strtoupper($data['role'] ?? 'FREE');
        if ($role === 'USER') {
            $role = 'FREE';
        }
        $storedRole = $this->roleValueForDatabase($role);

        $payload = [ // mật khẩu db cần lưu là mật khẩu được mã hóa bởi vì mật khẩu được gửi lên đang ở local nên
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

        if (Schema::hasColumn('users', 'vip_expires_at')) { // thời gian hết hạn vip
            $payload['vip_expires_at'] = in_array(strtoupper($role), ['PLUS', 'PRO', 'ULTRA']) ? now()->addMonth() : null;
        }

        $user = User::create($payload); // ghi xuống database

        // Tự động sinh OTP và gửi qua log/mail
        try {
            $otpService = app(OtpService::class); // hệ thống sẽ gọi service để tạo mã OTP
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
            $otpService = app(OtpService::class); // gội otpservice để kiểm tra otp
            $res = $otpService->verifyOtp($data['email'], $data['otp']); // sau khi get otp từ database, nó sẽ so sánh

            if (!$res['status']) {
                return response()->json([
                    'success' => false,
                    'message' => $res['message'],
                ], 422);
            }

            // Xác thực email của user
            $user = User::where('email', $data['email'])->first();
            $token = null;
            if ($user) {
                // kích hoạt vĩnh viễn tài khoản email đã xác minh
                $user->email_verified_at = now();
                $user->save();

                // Tự động đăng nhập và sinh JWT Token thông qua JWT-Auth Guard
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

    public function forgotPasswordSendOtp(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email này chưa được đăng ký trong hệ thống.',
            ], 422);
        }

        try {
            $otpService = app(OtpService::class);
            $otpService->generateOtp($user->email, 'forgot_password');

            return response()->json([
                'success' => true,
                'message' => 'Mã OTP khôi phục mật khẩu đã được gửi tới email của bạn.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi mã OTP: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function forgotPasswordReset(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email này chưa được đăng ký trong hệ thống.',
            ], 422);
        }

        try {
            $otpService = app(OtpService::class);
            $res = $otpService->verifyOtp($data['email'], $data['otp']);

            if (!$res['status']) {
                return response()->json([
                    'success' => false,
                    'message' => $res['message'],
                ], 422);
            }

            $user->password = Hash::make($data['password']);

            if ($user->email_verified_at === null) {
                $user->email_verified_at = now();
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập ngay bằng mật khẩu mới.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function me()
    {
        $user = $this->apiGuard()->user();

        if (!$user instanceof User) {
            abort(401, 'Unauthenticated.');
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatUser($user),
        ]);
    }

    public function lockedInfo()
    {
        $user = $this->apiGuard()->user();

        if (!$user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để tiếp tục.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_locked' => (bool) $user->is_locked,
                'locked_at' => $user->locked_at ? $user->locked_at->toDateTimeString() : null,
                'locked_reason' => $user->locked_reason,
            ],
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

        if ($user->isLocked()) {
            abort(403, 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.');
        }

        return $user;
    }

    private function roleValueForDatabase(string $role): string
    {
        $normalized = strtolower(trim($role));

        if ($normalized === '' || in_array($normalized, ['user', 'guest', 'member', 'basic', 'default'], true)) {
            return 'free';
        }

        if ($normalized === 'administrator') {
            return 'admin';
        }

        $role = strtoupper($normalized);

        if (!Schema::hasColumn('users', 'role')) {
            return strtolower($role);
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

        return strtolower($role);
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

    private function formatUser(User $user): array
    {
        $tier = $user->getSubscriptionTier();
        $ocrQuota = $this->resolveOcrQuota($user, $tier);

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
            'ai_allowed' => $user->isAdmin() || ($tier !== 'free' && (int) ($user->ai_quota_remaining ?? 0) > 0),
            'ocr_allowed' => $user->isAdmin() || $ocrQuota['allowed'],
            'ocr_quota_limit' => $ocrQuota['limit'],
            'ocr_quota_used' => $ocrQuota['used'],
            'ocr_quota_remaining' => $ocrQuota['remaining'],
            'ocr_quota_unlimited' => $ocrQuota['unlimited'],
            'is_locked' => (bool) $user->is_locked,
            'locked_at' => $user->locked_at ? $user->locked_at->toDateTimeString() : null,
            'locked_reason' => $user->locked_reason,
            'vip_expires_at' => $user->vip_expires_at ? $user->vip_expires_at->toDateTimeString() : null,
            'trial_used_at' => $user->trial_used_at ? $user->trial_used_at->toDateTimeString() : null,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    private function resolveOcrQuota(User $user, string $tier): array
    {
        if (in_array($tier, ['admin', 'ultra'], true)) {
            return ['allowed' => true, 'limit' => null, 'used' => 0, 'remaining' => null, 'unlimited' => true];
        }

        $limit = match ($tier) {
            'pro' => 50,
            'plus' => 10,
            default => 0,
        };
        $used = $limit > 0
            ? \App\Models\AiLog::query()->where('user_id', $user->id)->where('action_type', 'ocr_upload')->where('created_at', '>=', now()->startOfMonth())->count()
            : 0;

        return ['allowed' => $limit > $used, 'limit' => $limit, 'used' => $used, 'remaining' => max(0, $limit - $used), 'unlimited' => false];
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
