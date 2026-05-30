<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $partnerCode;
    protected $accessKey;
    protected $secretKey;
    protected $endpoint;
    protected $redirectUrl;
    protected $ipnUrl;

    public function __construct()
    {
        $this->partnerCode = config('services.momo.partner_code');
        $this->accessKey = config('services.momo.access_key');
        $this->secretKey = config('services.momo.secret_key');
        $this->endpoint = config('services.momo.endpoint');
        $this->redirectUrl = config('services.momo.redirect_url');
        $this->ipnUrl = config('services.momo.ipn_url');
    }

    /**
     * Map plan IDs to pricing, duration and AI quota benefits
     */
    public function getPlans()
    {
        return [
            'vip_1m' => [
                'name' => 'VIP 1 Tháng',
                'amount' => 50000,
                'days' => 30,
                'quota' => 100
            ],
            'vip_3m' => [
                'name' => 'VIP 3 Tháng',
                'amount' => 120000,
                'days' => 90,
                'quota' => 350
            ],
            'vip_1y' => [
                'name' => 'VIP 1 Năm',
                'amount' => 40000,
                'days' => 365,
                'quota' => 1500
            ]
        ];
    }

    /**
     * Create MoMo Sandbox checkout URL
     */
    public function createMomoPayment(User $user, string $planId)
    {
        $plans = $this->getPlans();
        if (!isset($plans[$planId])) {
            throw new \Exception("Gói nạp không hợp lệ.");
        }

        $plan = $plans[$planId];
        $amount = $plan['amount'];
        $orderCode = 'QF_' . strtoupper(uniqid()) . '_' . $user->id;

        // 1. Tạo bản ghi pending thanh toán trong CSDL
        $payment = Payment::create([
            'user_id' => $user->id,
            'order_code' => $orderCode,
            'amount' => $amount,
            'provider' => 'momo',
            'status' => 'pending',
        ]);

        // MOCK MODE FOR LOCAL DEVELOPMENT AND TESTING
        if (env('MOMO_MOCK', false) === true) {
            $requestId = $orderCode . '_' . time();
            $extraData = base64_encode(json_encode(['plan_id' => $planId, 'user_id' => $user->id]));
            
            $mockParams = [
                'partnerCode' => 'MOMO_MOCK',
                'orderId' => $orderCode,
                'requestId' => $requestId,
                'amount' => $amount,
                'orderInfo' => "Nang cap VIP QuizFlex - " . $planId,
                'orderType' => 'momo_wallet',
                'transId' => 'mock_trans_' . time(),
                'resultCode' => 0,
                'message' => 'Giao dịch thành công qua Mock Mode',
                'payType' => 'webApp',
                'responseTime' => time() * 1000,
                'extraData' => $extraData,
            ];
            
            $rawHash = "accessKey=mock_access_key" .
                "&amount=" . $mockParams['amount'] .
                "&extraData=" . $mockParams['extraData'] .
                "&message=" . $mockParams['message'] .
                "&orderId=" . $mockParams['orderId'] .
                "&orderInfo=" . $mockParams['orderInfo'] .
                "&partnerCode=" . $mockParams['partnerCode'] .
                "&requestId=" . $mockParams['requestId'] .
                "&responseTime=" . $mockParams['responseTime'] .
                "&resultCode=" . $mockParams['resultCode'] .
                "&transId=" . $mockParams['transId'] .
                "&payType=" . $mockParams['payType'];
            $mockParams['signature'] = hash_hmac("sha256", $rawHash, "mock_secret_key");
            
            $mockPayUrl = $this->redirectUrl . '?' . http_build_query($mockParams);

            Log::info('MoMo Mock checkout created successfully', ['orderId' => $orderCode, 'payUrl' => $mockPayUrl]);

            return [
                'payUrl' => $mockPayUrl,
                'order_code' => $orderCode,
                'payment' => $payment
            ];
        }

        $requestId = $orderCode . '_' . time();
        $orderInfo = "Nang cap VIP QuizFlex - " . $planId;
        $extraData = base64_encode(json_encode(['plan_id' => $planId, 'user_id' => $user->id]));
        $requestType = "payWithATM";

        // 2. Tạo chuỗi chữ ký raw theo đúng thứ tự bảng chữ cái của khóa (Alphabetical order of keys)
        $rawHash = "accessKey=" . $this->accessKey .
            "&amount=" . $amount .
            "&extraData=" . $extraData .
            "&ipnUrl=" . $this->ipnUrl .
            "&orderId=" . $orderCode .
            "&orderInfo=" . $orderInfo .
            "&partnerCode=" . $this->partnerCode .
            "&redirectUrl=" . $this->redirectUrl .
            "&requestId=" . $requestId .
            "&requestType=" . $requestType;

        // 3. Tính toán chữ ký HMAC SHA256 với Secret Key
        $signature = hash_hmac("sha256", $rawHash, $this->secretKey);

        // 4. Chuẩn bị payload gửi lên MoMo
        $payload = [
            'partnerCode' => $this->partnerCode,
            'partnerName' => 'QuizFlex',
            'storeId' => 'QuizFlex_Store',
            'requestId' => $requestId,
            'amount' => (int) $amount,
            'orderId' => $orderCode,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $this->redirectUrl,
            'ipnUrl' => $this->ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature
        ];

        Log::info('MoMo payment request details', ['orderId' => $orderCode, 'endpoint' => $this->endpoint]);

        // 5. Gửi request POST lên MoMo
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($this->endpoint, $payload);

        $resData = $response->json();

        if ($response->failed()) {
            Log::error('MoMo connection failed', ['body' => $response->body()]);
            
            if (isset($resData['message'])) {
                throw new \Exception("MoMo trả về lỗi: " . $resData['message']);
            }
            
            throw new \Exception("Không thể kết nối đến cổng thanh toán MoMo.");
        }
        
        if (isset($resData['resultCode']) && $resData['resultCode'] == 0 && isset($resData['payUrl'])) {
            return [
                'payUrl' => $resData['payUrl'],
                'order_code' => $orderCode,
                'payment' => $payment
            ];
        }

        Log::error('MoMo order creation returned error', ['response' => $resData]);
        throw new \Exception("MoMo trả về lỗi: " . ($resData['message'] ?? 'Không rõ lý do'));
    }

    /**
     * Verify MoMo response signature (IPN / Redirect callback)
     */
    public function verifyMomoSignature(array $data): bool
    {
        if (($data['partnerCode'] ?? '') === 'MOMO_MOCK') {
            Log::info('MoMo Signature verified via MOCK mode.');
            return true;
        }

        if (!isset($data['signature'])) {
            return false;
        }

        $receivedSignature = $data['signature'];

        // Các trường cần thiết để verify signature của MoMo phản hồi
        $partnerCode = $data['partnerCode'] ?? '';
        $orderId = $data['orderId'] ?? '';
        $requestId = $data['requestId'] ?? '';
        $amount = $data['amount'] ?? '';
        $orderInfo = $data['orderInfo'] ?? '';
        $orderType = $data['orderType'] ?? '';
        $transId = $data['transId'] ?? '';
        $resultCode = $data['resultCode'] ?? '';
        $message = $data['message'] ?? '';
        $payType = $data['payType'] ?? '';
        $responseTime = $data['responseTime'] ?? '';
        $extraData = $data['extraData'] ?? '';

        // Tạo chuỗi raw theo format chuẩn của MoMo
        $rawHash = "accessKey=" . $this->accessKey .
            "&amount=" . $amount .
            "&extraData=" . $extraData .
            "&message=" . $message .
            "&orderId=" . $orderId .
            "&orderInfo=" . $orderInfo .
            "&orderType=" . $orderType .
            "&partnerCode=" . $partnerCode .
            "&payType=" . $payType .
            "&requestId=" . $requestId .
            "&responseTime=" . $responseTime .
            "&resultCode=" . $resultCode .
            "&transId=" . $transId;

        $computedSignature = hash_hmac("sha256", $rawHash, $this->secretKey);

        $match = hash_equals($computedSignature, $receivedSignature);

        if (!$match) {
            Log::warning('MoMo signature verification failed', [
                'received' => $receivedSignature,
                'computed' => $computedSignature,
                'rawHash' => $rawHash
            ]);
        }

        return $match;
    }

    /**
     * Process successful payment logic and upgrade user to VIP
     */
    public function processSuccessPayment(Payment $payment, string $transactionId, array $rawResponse)
    {
        if ($payment->status === 'success') {
            Log::info('Payment already processed successfully', ['order_code' => $payment->order_code]);
            return $payment;
        }

        // 1. Cập nhật bản ghi giao dịch thành công
        $payment->update([
            'status' => 'success',
            'transaction_id' => $transactionId,
            'provider_response' => $rawResponse,
            'paid_at' => Carbon::now(),
        ]);

        // 2. Tìm gói VIP tương ứng dựa trên số tiền giao dịch
        $plans = $this->getPlans();
        $matchedPlan = null;

        foreach ($plans as $id => $p) {
            // Cho phép lệch nhẹ khoảng tiền do làm tròn/phí nếu có
            if (abs($payment->amount - $p['amount']) < 100) {
                $matchedPlan = $p;
                $matchedPlan['id'] = $id;
                break;
            }
        }

        if (!$matchedPlan) {
            Log::warning("Could not automatically map payment amount to VIP plan", [
                'payment_id' => $payment->id,
                'amount' => $payment->amount
            ]);
            // Gói mặc định nếu không khớp
            $matchedPlan = $plans['vip_1m'];
            $matchedPlan['id'] = 'vip_1m';
        }

        // 3. Tiến hành nâng cấp VIP và cấp Quota
        $user = $payment->user;
        
        $days = $matchedPlan['days'];
        $quota = $matchedPlan['quota'];

        // Cập nhật VIP expires (Tính cộng dồn)
        $currentExpiry = $user->vip_expires_at;
        if ($currentExpiry && $currentExpiry->isFuture()) {
            $newExpiry = $currentExpiry->addDays($days);
        } else {
            $newExpiry = Carbon::now()->addDays($days);
        }

        $user->role = 'VIP';
        $user->vip_expires_at = $newExpiry;
        $user->ai_quota_remaining = ($user->ai_quota_remaining ?? 0) + $quota;
        $user->save();

        Log::info('User upgraded to VIP successfully', [
            'user_id' => $user->id,
            'plan' => $matchedPlan['name'],
            'new_expiry' => $newExpiry->toDateTimeString(),
            'new_quota' => $user->ai_quota_remaining
        ]);

        return $payment;
    }
}
