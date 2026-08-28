<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PayOSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $payOSService;

    public function __construct(PaymentService $paymentService, PayOSService $payOSService)
    {
        $this->paymentService = $paymentService;
        $this->payOSService = $payOSService;
    }

    /**
     * Create checkout URL
     */
    public function create(Request $request)
    {
        // lấy thông tin đối tượng user hiện tại qua token xác thực JWT
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Bạn cần đăng nhập để thực hiện thanh toán.'], 401);
        }
        // Xác thực tính hợp lệ của tham số gửi lên.
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|string|in:plus_1m,pro_1m,ultra_1m',
            'provider' => 'required|string|in:momo,vnpay,payos',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Thông tin thanh toán không hợp lệ.',
                'errors' => $validator->errors()
            ], 422);
        }

        $planId = $request->input('plan_id');
        $provider = $request->input('provider');

        try {
            if ($provider === 'momo') {
                $result = $this->paymentService->createMomoPayment($user, $planId);
                return response()->json([
                    'success' => true,
                    'payUrl' => $result['payUrl'],
                    'order_code' => $result['order_code']
                ]);
            } elseif ($provider === 'payos') {
                // Tính toán số tiền thực tế sau khấu trừ nâng cấp
                $upgradeInfo = $this->paymentService->calculateUpgradeCost($user, $planId);
                if (!$upgradeInfo['allowed']) {
                    throw new \Exception($upgradeInfo['message']);
                }
                $amount = $upgradeInfo['amount'];

                // PayOS yêu cầu mã đơn hàng (orderCode) dạng số nguyên, Mỗi giao dịch phải có mã riêng
                $orderCode = time() . $user->id;

                // tạo hóa đơn nội bộ và lưu dữ liệu vào bảng payment
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'order_code' => $orderCode,
                    'amount' => $amount,
                    'provider' => 'payos',
                    'status' => 'pending',
                    'provider_response' => ['target_plan_id' => $planId],
                ]);

                // Gọi lớp nghiệp vụ kết nối PayOS, chuyển giao Model hóa đơn mới tạo và ID gói cước để tạo liên kết thanh toán.
                $result = $this->payOSService->createPaymentLink($payment, $planId);
                return response()->json([
                    'success' => true,
                    'payUrl' => $result['payUrl'],
                    'order_code' => $result['order_code']
                ]);
            }

            return response()->json(['message' => 'Cổng thanh toán không hỗ trợ.'], 400);
        } catch (\Throwable $e) {
            Log::error('Payment creation controller failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Không thể khởi tạo thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle MoMo Webhook (IPN - Instant Payment Notification)
     */
    public function webhookMomo(Request $request)
    {
        $data = $request->all();
        Log::info('Received MoMo Webhook (IPN) callback', ['data' => $data]);

        // 1. Xác minh chữ ký bảo mật từ MoMo
        if (!$this->paymentService->verifyMomoSignature($data)) {
            Log::warning('MoMo Webhook invalid signature.');
            return response()->json(['message' => 'Chữ ký không hợp lệ.'], 400);
        }

        // 2. Tìm bản ghi giao dịch trong CSDL
        $orderId = $data['orderId'] ?? '';
        $payment = Payment::where('order_code', $orderId)->first();

        if (!$payment) {
            Log::error('MoMo Webhook: Payment record not found.', ['orderId' => $orderId]);
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }

        // 3. Xử lý trạng thái giao dịch
        $resultCode = (int) ($data['resultCode'] ?? -1);

        try {
            if ($resultCode === 0) {
                // Thanh toán thành công -> cập nhật trạng thái & nâng cấp VIP
                $transId = $data['transId'] ?? '';
                $this->paymentService->processSuccessPayment($payment, $transId, $data);
            } else {
                // Thanh toán thất bại
                Log::info('MoMo payment failed via webhook', ['orderId' => $orderId, 'resultCode' => $resultCode]);
                $payment->update([
                    'status' => 'failed',
                    'provider_response' => $data
                ]);
            }

            // Phản hồi lại cho MoMo xác nhận đã xử lý thành công
            return response()->json(null, 204);
        } catch (\Throwable $e) {
            Log::error('Webhook processing failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Lỗi xử lý Webhook: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Frontend redirects back here to verify signature and sync status
     */
    public function callback(Request $request)
    {
        $data = $request->all();
        Log::info('Received Payment Callback redirect check', ['data' => $data]);

        // 1. Xác minh chữ ký phản hồi
        if (!$this->paymentService->verifyMomoSignature($data)) {
            return response()->json(['message' => 'Chữ ký phản hồi không hợp lệ.'], 400);
        }

        // 2. Tìm đơn hàng
        $orderId = $data['orderId'] ?? '';
        $payment = Payment::with('user')->where('order_code', $orderId)->first();

        if (!$payment) {
            return response()->json(['message' => 'Giao dịch không tồn tại.'], 404);
        }

        // 3. Nếu webhook chưa kịp chạy, đồng bộ nhanh cho người dùng nếu resultCode = 0
        $resultCode = (int) ($data['resultCode'] ?? -1);

        try {
            if ($resultCode === 0 && $payment->status === 'pending') {
                $transId = $data['transId'] ?? '';
                $this->paymentService->processSuccessPayment($payment, $transId, $data);

                // Refresh model
                $payment->refresh();
            } elseif ($resultCode !== 0 && $payment->status === 'pending') {
                $payment->update([
                    'status' => 'failed',
                    'provider_response' => $data
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'order_code' => $payment->order_code,
                'paid_at' => $payment->paid_at ? $payment->paid_at->toDateTimeString() : null,
                'user' => [
                    'id' => $payment->user->id,
                    'name' => $payment->user->name,
                    'role' => $payment->user->getSubscriptionTier(),
                    'ai_quota_remaining' => $payment->user->ai_quota_remaining,
                    'vip_expires_at' => $payment->user->vip_expires_at ? $payment->user->vip_expires_at->toDateTimeString() : null,
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Callback processing failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Lỗi xử lý Callback: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Check payment status on PayOS (Polling)
     */
    public function checkStatus(Request $request, string $orderCode)
    {
        $payment = Payment::with('user')->where('order_code', $orderCode)->first();

        if (!$payment) {
            return response()->json(['message' => 'Giao dịch không tồn tại.'], 404);
        }

        if ($payment->status === 'success') {
            return response()->json([
                'success' => true,
                'status' => 'success',
                'amount' => $payment->amount,
                'order_code' => $payment->order_code,
                'paid_at' => $payment->paid_at ? $payment->paid_at->toDateTimeString() : null,
                'user' => [
                    'id' => $payment->user->id,
                    'name' => $payment->user->name,
                    'role' => $payment->user->getSubscriptionTier(),
                    'ai_quota_remaining' => $payment->user->ai_quota_remaining,
                    'vip_expires_at' => $payment->user->vip_expires_at ? $payment->user->vip_expires_at->toDateTimeString() : null,
                ]
            ]);
        }

        try {
            // Hàm getPaymentDetails gửi request GET sang PayOS để lấy trạng thái thực tế
            $details = $this->payOSService->getPaymentDetails($orderCode);
            $payosStatus = $details['status'] ?? 'PENDING';

            if ($payosStatus === 'PAID' && $payment->status === 'pending') {
                $transId = $details['transactions'][0]['reference'] ?? 'payos_' . time();
                $this->paymentService->processSuccessPayment($payment, $transId, $details);
                // payos trả về paid controller lập tức gọi processSuccessPayment để thực hiện cập nhật db nạp tiền
                // Refresh model
                $payment->refresh();
            } elseif (in_array($payosStatus, ['CANCELLED', 'EXPIRED']) && $payment->status === 'pending') {
                $payment->update([
                    'status' => 'failed',
                    'provider_response' => $details
                ]);
            }

            return response()->json([
                'success' => true,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'order_code' => $payment->order_code,
                'paid_at' => $payment->paid_at ? $payment->paid_at->toDateTimeString() : null,
                'user' => $payment->status === 'success' ? [
                    'id' => $payment->user->id,
                    'name' => $payment->user->name,
                    'role' => $payment->user->getSubscriptionTier(),
                    'ai_quota_remaining' => $payment->user->ai_quota_remaining,
                    'vip_expires_at' => $payment->user->vip_expires_at ? $payment->user->vip_expires_at->toDateTimeString() : null,
                ] : null
            ]);
        } catch (\Throwable $e) {
            Log::error('Check PayOS status failed', [
                'orderCode' => $orderCode,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể kiểm tra trạng thái thanh toán.'
            ], 500);
        }
    }

    /**
     * Lấy danh sách các gói kèm giá nâng cấp động của người dùng hiện tại
     */
    public function getUpgradeCosts()
    {
        $user = auth('api')->user();
        $plans = $this->paymentService->getPlans();

        $result = [];
        foreach ($plans as $id => $plan) {
            $costInfo = [];
            if ($user) {
                $costInfo = $this->paymentService->calculateUpgradeCost($user, $id);
            } else {
                $costInfo = [
                    'allowed' => true,
                    'amount' => $plan['amount'],
                    'unused_value' => 0,
                    'remaining_days' => 0,
                    'message' => 'Đăng ký mới gói cước.'
                ];
            }

            $result[$id] = array_merge($plan, [
                'upgrade_info' => $costInfo
            ]);
        }

        return response()->json([
            'success' => true,
            'plans' => $result
        ]);
    }

    /**
     * Get transaction history.
     * Normal users and VIP users only see their own payments.
     * Admin users see every payment in the system.
     */
    public function history()
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Bạn cần đăng nhập.'], 401);
        }

        $isAdmin = strtolower((string) ($user->role ?? 'user')) === 'admin';

        $query = Payment::query()
            ->with('user:id,name,email,role')
            ->orderBy('created_at', 'desc');

        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        $history = $query->get()->map(fn(Payment $payment) => $this->formatPaymentForHistory($payment));

        return response()->json([
            'success' => true,
            'scope' => $isAdmin ? 'all' : 'self',
            'data' => $history,
        ]);
    }

    private function formatPaymentForHistory(Payment $payment): array
    {
        $plans = $this->paymentService->getPlans();

        // Đọc target_plan_id từ provider_response
        $targetPlanId = null;
        if (is_array($payment->provider_response) && isset($payment->provider_response['target_plan_id'])) {
            $targetPlanId = $payment->provider_response['target_plan_id'];
        }

        $planName = 'Gói nâng cấp';
        $originalAmount = (float) $payment->amount;
        $discountAmount = 0.0;
        $isUpgrade = false;

        if ($targetPlanId && isset($plans[$targetPlanId])) {
            $plan = $plans[$targetPlanId];
            $planName = $plan['name'];
            $originalAmount = (float) $plan['amount'];

            // Nếu là nâng cấp (số tiền thực trả nhỏ hơn giá gốc gói cước và không phải dùng thử)
            if ((float) $payment->amount < $originalAmount && $payment->provider !== 'trial') {
                $discountAmount = $originalAmount - (float) $payment->amount;
                $isUpgrade = true;
            }
        } else {
            // Hướng giải quyết phụ nếu không có target_plan_id: khớp theo giá trị số tiền
            foreach ($plans as $id => $p) {
                if (abs($payment->amount - $p['amount']) < 100) {
                    $planName = $p['name'];
                    break;
                }
            }
        }

        // Nếu là dùng thử 0đ
        if ($payment->provider === 'trial') {
            $planName = 'Dùng thử Plus (7 ngày)';
        }

        return [
            'id' => $payment->id,
            'user_id' => $payment->user_id,
            'user_name' => $payment->user?->name ?? 'Không rõ',
            'user_email' => $payment->user?->email ?? '',
            'user_role' => strtolower((string) ($payment->user?->role ?? 'user')),
            'order_code' => $payment->order_code,
            'amount' => (float) $payment->amount,
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'is_upgrade' => $isUpgrade,
            'plan_name' => $planName,
            'provider' => $payment->provider,
            'status' => $payment->status,
            'transaction_id' => $payment->transaction_id,
            'paid_at' => $payment->paid_at?->toDateTimeString(),
            'created_at' => $payment->created_at?->toDateTimeString(),
            'updated_at' => $payment->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * Kích hoạt dùng thử Plus 7 ngày chủ động
     */
    public function activateTrial(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Bạn cần đăng nhập để thực hiện.'], 401);
        }

        if ($user->trial_used_at !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Mỗi tài khoản chỉ được kích hoạt dùng thử 1 lần duy nhất.'
            ], 400);
        }

        // 1. Kích hoạt dùng thử Plus trong 7 ngày
        $user->role = 'PLUS';
        $user->vip_expires_at = now()->addDays(7);
        $user->plan = 'plus';
        $user->plan_expires_at = $user->vip_expires_at;
        $user->trial_used_at = now();
        $user->ai_quota_remaining = ($user->ai_quota_remaining ?? 0) + 20;
        $user->save();

        // 2. Tạo lịch sử giao dịch cho gói dùng thử
        Payment::create([
            'user_id' => $user->id,
            'order_code' => 'TRIAL_' . strtoupper(uniqid()) . '_' . $user->id,
            'amount' => 0,
            'provider' => 'trial',
            'status' => 'success',
            'transaction_id' => 'trial_' . time(),
            'provider_response' => ['message' => 'Kích hoạt dùng thử 7 ngày gói Plus'],
            'paid_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã kích hoạt 7 ngày dùng thử gói Plus thành công!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getSubscriptionTier(),
                'role_label' => 'Plus (Dùng thử)',
                'avatar' => $this->resolveAvatarForResponse($user->avatar),
                'ai_quota_remaining' => $user->ai_quota_remaining,
                'vip_expires_at' => $user->vip_expires_at ? $user->vip_expires_at->toDateTimeString() : null,
                'trial_used_at' => $user->trial_used_at ? $user->trial_used_at->toDateTimeString() : null,
            ]
        ]);
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

    private function resolvePlanName(float $amount): string
    {
        if (abs($amount) < 0.01) {
            return 'Gói dùng thử Plus';
        }

        foreach ($this->paymentService->getPlans() as $plan) {
            if (abs($amount - (float) $plan['amount']) < 100) {
                return $plan['name'];
            }
        }

        return 'Gói nâng cấp';
    }
}
