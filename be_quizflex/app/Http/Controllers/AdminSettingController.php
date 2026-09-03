<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    /**
     * Default resource quotas fallback.
     */
    private function defaultLimits(): array
    {
        return [
            ['roleKey' => 'admin', 'role' => 'Admin', 'desc' => 'Quản trị viên toàn quyền', 'ai' => 'Unlimited', 'ocr' => 'Unlimited'],
            ['roleKey' => 'ultra', 'role' => 'Ultra VIP', 'desc' => 'Giáo viên & Luyện thi chuyên sâu', 'ai' => '1500 lượt/tháng', 'ocr' => '150 lượt/tháng'],
            ['roleKey' => 'pro', 'role' => 'Pro VIP', 'desc' => 'Học sinh ôn thi THPT & Đại học', 'ai' => '350 lượt/tháng', 'ocr' => '50 lượt/tháng'],
            ['roleKey' => 'plus', 'role' => 'Plus VIP', 'desc' => 'Học sinh luyện đề tiêu chuẩn', 'ai' => '100 lượt/tháng', 'ocr' => '10 lượt/tháng'],
            ['roleKey' => 'free', 'role' => 'Free', 'desc' => 'Tài khoản thành viên thường', 'ai' => '10 lượt/tháng', 'ocr' => 'Yêu cầu gói Plus'],
        ];
    }

    /**
     * Get all system settings for Admin.
     */
    public function index(): JsonResponse
    {
        $limits = SystemSetting::get('ai_ocr_limits', $this->defaultLimits());
        $selectedVisibility = SystemSetting::get('default_quiz_visibility', 'Private');

        return response()->json([
            'success' => true,
            'data' => [
                'limits' => $limits,
                'selectedVisibility' => $selectedVisibility,
            ],
        ]);
    }

    /**
     * Update system settings.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'limits' => 'nullable|array',
            'limits.*.roleKey' => 'required|string',
            'limits.*.role' => 'required|string',
            'limits.*.desc' => 'nullable|string',
            'limits.*.ai' => 'required|string|max:100',
            'limits.*.ocr' => 'required|string|max:100',
            'selectedVisibility' => 'nullable|string|in:Private,Public,Group,private,public,group',
        ]);

        if (isset($data['limits'])) {
            SystemSetting::set('ai_ocr_limits', $data['limits'], 'quota', 'Định mức tài nguyên AI & OCR');
        }

        if (isset($data['selectedVisibility'])) {
            $visibility = ucfirst(strtolower($data['selectedVisibility']));
            SystemSetting::set('default_quiz_visibility', $visibility, 'quiz', 'Quyền riêng tư mặc định khi tạo Quiz');
        }

        $limits = SystemSetting::get('ai_ocr_limits', $this->defaultLimits());
        $selectedVisibility = SystemSetting::get('default_quiz_visibility', 'Private');

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật cài đặt hệ thống thành công!',
            'data' => [
                'limits' => $limits,
                'selectedVisibility' => $selectedVisibility,
            ],
        ]);
    }
}
