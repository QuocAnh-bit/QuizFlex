<?php

namespace App\Services\Auth;

use App\Mail\SendOtpMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class OtpService
{
    protected int $maxAttempts = 5;
    protected int $expireMinutes = 10;

    /**
     * Tạo mã OTP mới và gửi qua Email
     */
    public function generateOtp(string $email): string
    {
        // 1. Dọn dẹp tất cả các OTP cũ của email này để tránh xung đột
        DB::table('otp_verifications')->where('email', $email)->delete();

        // 2. Tạo mã OTP ngẫu nhiên gồm 6 chữ số
        $rawOtp = sprintf("%06d", mt_rand(0, 999999));

        // 3. Lưu OTP (mã hóa bcrypt) vào cơ sở dữ liệu
        DB::table('otp_verifications')->insert([
            'email' => $email,
            'otp_code' => Hash::make($rawOtp),
            'attempts' => 0,
            'expires_at' => Carbon::now()->addMinutes($this->expireMinutes),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 4. Gửi email chứa OTP cho người dùng
        Mail::to($email)->send(new SendOtpMail($rawOtp));

        return $rawOtp;
    }

    /**
     * Xác thực mã OTP người dùng nhập vào
     */
    public function verifyOtp(string $email, string $otp): array
    {
        // 1. Tìm bản ghi OTP mới nhất của email này
        $record = DB::table('otp_verifications')
            ->where('email', $email)
            ->first();

        if (!$record) {
            return [
                'status' => false,
                'message' => 'Không tìm thấy yêu cầu xác thực OTP hoặc mã đã hết hạn.'
            ];
        }

        // 2. Kiểm tra xem OTP đã hết hạn chưa
        if (Carbon::parse($record->expires_at)->isPast()) {
            DB::table('otp_verifications')->where('id', $record->id)->delete();
            return [
                'status' => false,
                'message' => 'Mã OTP này đã hết hạn. Vui lòng nhấn gửi lại mã mới.'
            ];
        }

        // 3. Kiểm tra số lần nhập sai (Chống bruteforce)
        if ($record->attempts >= $this->maxAttempts) {
            DB::table('otp_verifications')->where('id', $record->id)->delete();
            return [
                'status' => false,
                'message' => 'Bạn đã nhập sai quá 5 lần. Mã OTP này đã bị vô hiệu hóa, vui lòng lấy mã mới.'
            ];
        }

        // 4. So khớp mã OTP (sử dụng Hash::check để an toàn bảo mật)
        if (Hash::check($otp, $record->otp_code)) {
            // Đúng OTP -> Dọn sạch bảng ghi và báo thành công
            DB::table('otp_verifications')->where('email', $email)->delete();
            return [
                'status' => true,
                'message' => 'Xác thực OTP thành công!'
            ];
        }

        // 5. Sai OTP -> Tăng số lần thử sai lên 1
        $newAttempts = $record->attempts + 1;
        
        if ($newAttempts >= $this->maxAttempts) {
            DB::table('otp_verifications')->where('id', $record->id)->delete();
            return [
                'status' => false,
                'message' => 'Bạn đã nhập sai quá 5 lần. Mã OTP này đã bị vô hiệu hóa, vui lòng lấy mã mới.'
            ];
        }

        DB::table('otp_verifications')->where('id', $record->id)->update([
            'attempts' => $newAttempts,
            'updated_at' => Carbon::now(),
        ]);

        $remaining = $this->maxAttempts - $newAttempts;
        return [
            'status' => false,
            'message' => "Mã xác thực không chính xác. Bạn còn {$remaining} lần thử."
        ];
    }
}
