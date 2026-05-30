<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleSsoController extends Controller
{
    /**
     * Chuyển hướng người dùng sang trang đăng nhập của Google
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Nhận phản hồi từ Google sau khi người dùng xác thực thành công
     */
    public function handleGoogleCallback()
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        try {
            // Tắt SSL Verification cho Guzzle (Tránh lỗi cURL 60: SSL certificate problem phổ biến trên Windows/Laragon)
            $guzzleClient = new \GuzzleHttp\Client([
                'verify' => false,
            ]);
            $socialiteDriver = Socialite::driver('google')->stateless();
            $socialiteDriver->setHttpClient($guzzleClient);

            // Lấy thông tin user từ Google
            $googleUser = $socialiteDriver->user();
        } catch (\Exception $e) {
            // Ghi log lỗi chi tiết ra laravel.log để nhà phát triển kiểm tra
            \Illuminate\Support\Facades\Log::error('Google SSO Error: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            // Chuyển hướng kèm lỗi chi tiết về Frontend
            return redirect($frontendUrl . '/login?error_message=' . urlencode('Đăng nhập bằng Google thất bại: ' . $e->getMessage()));
        }

        // Tìm kiếm User trong hệ thống bằng email nhận được từ Google
        $user = User::where('email', $googleUser->getEmail())->first();

        // NẾU TÀI KHOẢN CHƯA TỒN TẠI (Theo yêu cầu: không tự động đăng ký vì phi lý)
        if (!$user) {
            return redirect($frontendUrl . '/login?error_message=' . urlencode('Tài khoản email này chưa được đăng ký trên hệ thống QuizFlex. Vui lòng tạo tài khoản trước.'));
        }

        // Nếu tài khoản tồn tại nhưng chưa được kích hoạt OTP, tự động kích hoạt luôn vì Google đã xác thực email
        if ($user->email_verified_at === null) {
            $user->email_verified_at = now();
            $user->save();
        }

        // Tạo JWT Token xác thực đăng nhập thông qua Guard 'api'
        $token = auth('api')->login($user);

        // Chuyển hướng về Frontend kèm theo Token trên thanh URL để Frontend tự động đăng nhập
        return redirect($frontendUrl . '/login?token=' . $token);
    }
}
