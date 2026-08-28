<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentSuccess;
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
     * Liên kết mã định danh gói dịch vụ với giá cả, thời lượng và các lợi ích về hạn mức AI.
     */
    public function getPlans()
    {
        return [
            'plus_1m' => [
                'name' => 'Gói Plus',
                'amount' => 50000,
                'days' => 30,
                'quota' => 100,
                'tier' => 1
            ],
            'pro_1m' => [
                'name' => 'Gói Pro',
                'amount' => 120000,
                'days' => 30,
                'quota' => 350,
                'tier' => 2
            ],
            'ultra_1m' => [
                'name' => 'Gói Ultra',
                'amount' => 250000,
                'days' => 30,
                'quota' => 1500,
                'tier' => 3
            ]
        ];
    }

    /**
     * Ánh xạ vai trò (role) người dùng sang ID gói cước tương ứng.
     */
    public function getPlanIdFromRole(?string $role): ?string
    {
        if (!$role) {
            return null;
        }
        return match (strtoupper($role)) {
            'PLUS' => 'plus_1m',
            'PRO' => 'pro_1m',
            'ULTRA' => 'ultra_1m',
            default => null,
        };
    }

    /**
     * Tính toán số tiền nâng cấp thực tế (Prorated Upgrade).
     */
    public function calculateUpgradeCost(User $user, string $targetPlanId): array
    {
        $plans = $this->getPlans();
        if (!isset($plans[$targetPlanId])) {
            return ['allowed' => false, 'message' => 'Gói nâng cấp không hợp lệ.', 'amount' => 0];
        }

        $targetPlan = $plans[$targetPlanId];
        $currentRole = $user->role;
        $currentPlanId = $this->getPlanIdFromRole($currentRole);

        // Trường hợp không có gói cũ hoặc gói đã hết hạn
        if (!$currentPlanId || !$user->vip_expires_at || Carbon::parse($user->vip_expires_at)->isPast()) {
            return [
                'allowed' => true,
                'amount' => $targetPlan['amount'],
                'unused_value' => 0,
                'remaining_days' => 0,
                'message' => 'Đăng ký mới gói cước.'
            ];
        }

        $currentPlan = $plans[$currentPlanId];

        // Kiểm tra hạ cấp (downgrade) hoặc mua trùng gói hiện tại
        if ($targetPlan['tier'] <= $currentPlan['tier']) {
            return [
                'allowed' => false,
                'message' => 'Bạn không thể nâng cấp xuống gói thấp hơn hoặc bằng gói hiện tại.',
                'amount' => 0
            ];
        }

        // Kiểm tra xem gói VIP hiện tại của người dùng có phải là gói dùng thử (TRIAL) hay không
        $lastPayment = Payment::where('user_id', $user->id)
            ->where('status', 'success')
            ->orderBy('paid_at', 'desc')
            ->first();

        $isTrial = $lastPayment && ($lastPayment->provider === 'trial' || floatval($lastPayment->amount) < 0.01);

        // Tính số ngày sử dụng còn lại của gói cũ dạng số thực (float) để chính xác đến từng giờ
        $now = Carbon::now();
        $expiry = Carbon::parse($user->vip_expires_at);
        $remainingDays = max(0, $now->diffInSeconds($expiry, false) / 86400.0);

        $unusedValue = 0;
        $message = 'Nâng cấp từ ' . $currentPlan['name'] . ' lên ' . $targetPlan['name'] . '.';

        if ($isTrial) {
            $message = 'Nâng cấp từ gói Dùng thử lên ' . $targetPlan['name'] . ' (Không áp dụng khấu trừ).';
        } elseif ($remainingDays > 0) {
            // Giá trị mỗi ngày = Tổng tiền gói / Tổng ngày của gói
            $pricePerDay = $currentPlan['amount'] / $currentPlan['days'];
            $unusedValue = $pricePerDay * $remainingDays;
        }

        // Làm tròn đồng bộ giá trị khấu trừ cũ về hàng nghìn đồng
        $unusedValueRounded = round($unusedValue / 1000) * 1000;

        // Số tiền cần đóng nâng cấp = Giá gói mới - Giá trị gói cũ đã làm tròn
        $upgradeCost = max(0, $targetPlan['amount'] - $unusedValueRounded);

        return [
            'allowed' => true,
            'amount' => (int) $upgradeCost,
            'unused_value' => (int) $unusedValueRounded,
            'remaining_days' => $remainingDays,
            'message' => $message
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

        // Tính toán số tiền thực tế cần thanh toán sau khi khấu trừ nâng cấp
        $upgradeInfo = $this->calculateUpgradeCost($user, $planId);
        if (!$upgradeInfo['allowed']) {
            throw new \Exception($upgradeInfo['message']);
        }

        $amount = $upgradeInfo['amount'];
        $orderCode = 'QF_' . strtoupper(uniqid()) . '_' . $user->id;

        // 1. Tạo bản ghi pending thanh toán trong CSDL với số tiền đã tính toán khấu trừ
        $payment = Payment::create([
            'user_id' => $user->id,
            'order_code' => $orderCode,
            'amount' => $amount,
            'provider' => 'momo',
            'status' => 'pending',
            'provider_response' => ['target_plan_id' => $planId],
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
     * Xử lý logic thanh toán thành công và nâng hạng người dùng lên VIP.
     */
    public function processSuccessPayment(Payment $payment, string $transactionId, array $rawResponse)
    {
        if ($payment->status === 'success') {
            Log::info('Payment already processed successfully', ['order_code' => $payment->order_code]);
            return $payment;
        }

        // Đọc target_plan_id từ bản ghi cũ trước khi cập nhật đè response mới
        $targetPlanId = null;
        if (is_array($payment->provider_response) && isset($payment->provider_response['target_plan_id'])) {
            $targetPlanId = $payment->provider_response['target_plan_id'];
        }

        // 1. Cập nhật bản ghi giao dịch thành công, chuyển status thành success
        $payment->update([
            'status' => 'success',
            'transaction_id' => $transactionId,
            'provider_response' => $rawResponse,
            'paid_at' => Carbon::now(),
        ]);

        // 2. Tìm gói VIP tương ứng
        $plans = $this->getPlans();
        $matchedPlan = null;

        if ($targetPlanId && isset($plans[$targetPlanId])) {
            $matchedPlan = $plans[$targetPlanId];
            $matchedPlan['id'] = $targetPlanId;
        } else {
            // Hướng giải quyết phụ nếu không có target_plan_id: khớp theo giá trị số tiền (Backward compatibility)
            foreach ($plans as $id => $p) {
                if (abs($payment->amount - $p['amount']) < 100) {
                    $matchedPlan = $p;
                    $matchedPlan['id'] = $id;
                    break;
                }
            }
        }

        if (!$matchedPlan) {
            Log::warning("Could not automatically map payment amount to VIP plan", [
                'payment_id' => $payment->id,
                'amount' => $payment->amount
            ]);
            // Gói mặc định nếu không khớp
            $matchedPlan = $plans['plus_1m'];
            $matchedPlan['id'] = 'plus_1m';
        }

        // 3. Tiến hành nâng cấp và cấp Quota
        $user = $payment->user;

        // Ánh xạ gói dịch vụ sang vai trò người dùng (PLUS, PRO, ULTRA)
        $newRole = match ($matchedPlan['id']) {
            'ultra_1m' => 'ULTRA',
            'pro_1m' => 'PRO',
            default => 'PLUS',
        };

        // Kiểm tra xem đây có phải là nâng cấp lên gói cao hơn không
        $plans = $this->getPlans();
        $currentPlanId = $this->getPlanIdFromRole($user->role);

        $isUpgrade = false;
        if ($currentPlanId && isset($plans[$currentPlanId]) && isset($plans[$matchedPlan['id']])) {
            $currentExpiry = $user->vip_expires_at;
            // Nếu gói mới có tier cao hơn và gói cũ vẫn đang còn hạn sử dụng
            if ($plans[$matchedPlan['id']]['tier'] > $plans[$currentPlanId]['tier'] && $currentExpiry && $currentExpiry->isFuture()) {
                $isUpgrade = true;
            }
        }

        $days = $matchedPlan['days'];
        $quota = $matchedPlan['quota'];

        // Cập nhật VIP expires
        $currentExpiry = $user->vip_expires_at;
        if ($isUpgrade) {
            // Trường hợp NÂNG CẤP: Tiền gói cũ còn dư đã được quy đổi khấu trừ vào tiền thanh toán.
            // Vì vậy gói cũ bị thu hồi, hạn dùng của gói mới đặt lại là 30 ngày kể từ thời điểm nâng cấp.
            $newExpiry = Carbon::now()->addDays($days);
        } else {
            // Trường hợp MUA MỚI hoặc GIA HẠN cùng gói: Cộng dồn thời gian sử dụng
            if ($currentExpiry && $currentExpiry->isFuture()) {
                $newExpiry = $currentExpiry->addDays($days);
            } else {
                $newExpiry = Carbon::now()->addDays($days);
            }
        }

        // Kiểm tra xem người dùng có đang trong trạng thái dùng thử (Trial 0đ) hay không
        $lastPayment = Payment::where('user_id', $user->id)
            ->where('status', 'success')
            ->orderBy('paid_at', 'desc')
            ->first();

        $isFromTrial = $lastPayment && ($lastPayment->provider === 'trial' || floatval($lastPayment->amount) < 0.01);

        $user->role = $newRole;
        $user->vip_expires_at = $newExpiry;
        $user->plan = strtolower($newRole);
        $user->plan_expires_at = $newExpiry;

        if ($isUpgrade || $isFromTrial) {
            // Khi NÂNG CẤP GÓI hoặc CHUYỂN TỪ DÙNG THỬ 0Đ SANG GÓI TRẢ PHÍ:
            // Quota AI được đặt lại chuẩn theo định mức của gói cước mới mua (không cộng dồn lượt dùng thử 0đ hoặc gói cũ đã khấu trừ).
            $user->ai_quota_remaining = $quota;
        } else {
            // Khi GIA HẠN CÙNG GÓI TRẢ PHÍ (VD: Đang dùng Pro trả phí mua thêm 1 tháng Pro):
            // Cộng dồn Quota AI vào số dư hiện có.
            $user->ai_quota_remaining = ($user->ai_quota_remaining ?? 0) + $quota;
        }

        $user->save();

        try {
            $user->notify(new PaymentSuccess($payment, $matchedPlan));
        } catch (\Exception $e) {
            Log::error('Failed to notify payment success', ['error' => $e->getMessage()]);
        }

        Log::info('User upgraded to VIP successfully', [
            'user_id' => $user->id,
            'plan' => $matchedPlan['name'],
            'new_expiry' => $newExpiry->toDateTimeString(),
            'new_quota' => $user->ai_quota_remaining
        ]);

        return $payment;
    }
}