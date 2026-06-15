<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create checkout URL
     */
    public function create(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Bạn cần đăng nhập để thực hiện thanh toán.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|string|in:vip_1m,vip_3m,vip_1y',
            'provider' => 'required|string|in:momo,vnpay',
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
                    'role' => $payment->user->role,
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

        $history = $query->get()->map(fn (Payment $payment) => $this->formatPaymentForHistory($payment));

        return response()->json([
            'success' => true,
            'scope' => $isAdmin ? 'all' : 'self',
            'data' => $history,
        ]);
    }

    private function formatPaymentForHistory(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'user_id' => $payment->user_id,
            'user_name' => $payment->user?->name ?? 'Không rõ',
            'user_email' => $payment->user?->email ?? '',
            'user_role' => strtolower((string) ($payment->user?->role ?? 'user')),
            'order_code' => $payment->order_code,
            'amount' => (float) $payment->amount,
            'plan_name' => $this->resolvePlanName((float) $payment->amount),
            'provider' => $payment->provider,
            'status' => $payment->status,
            'transaction_id' => $payment->transaction_id,
            'paid_at' => $payment->paid_at?->toDateTimeString(),
            'created_at' => $payment->created_at?->toDateTimeString(),
            'updated_at' => $payment->updated_at?->toDateTimeString(),
        ];
    }

    private function resolvePlanName(float $amount): string
    {
        foreach ($this->paymentService->getPlans() as $plan) {
            if (abs($amount - (float) $plan['amount']) < 100) {
                return $plan['name'];
            }
        }

        return 'Gói VIP tùy chỉnh';
    }
}
