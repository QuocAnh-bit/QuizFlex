<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayOSService
{
    protected $clientId;
    protected $apiKey;
    protected $checksumKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->clientId = config('services.payos.client_id');
        $this->apiKey = config('services.payos.api_key');
        $this->checksumKey = config('services.payos.checksum_key');
        $this->baseUrl = 'https://api-merchant.payos.vn';
    }

    /**
     * Create PayOS payment link
     */
    public function createPaymentLink(Payment $payment, string $planId)
    {
        $orderCode = intval($payment->order_code);
        $amount = intval($payment->amount);
        $description = "QuizFlex " . $planId; // Keep it clean and ASCII only

        // Giới hạn mô tả ở 25 ký tự để đảm bảo tương thích với PayOS nếu cần.
        $description = substr($description, 0, 25);

        $cancelUrl = config('services.momo.redirect_url'); // Reusing the redirect URL for cancel
        $returnUrl = config('services.momo.redirect_url'); // Reusing the redirect URL for success redirect

        // 1. Chuẩn bị dữ liệu để ký
        $dataToSign = [
            'amount' => $amount,
            'cancelUrl' => $cancelUrl, // link quay về khi hủy
            'description' => $description,
            'orderCode' => $orderCode,
            'returnUrl' => $returnUrl, // link quay về sau thanh toán
        ];

        // 2. Compute signature
        $signature = $this->computeSignature($dataToSign, $this->checksumKey);

        // 3. Prepare payload
        $payload = array_merge($dataToSign, [
            'signature' => $signature
        ]);

        Log::info('PayOS payment request payload', ['payload' => $payload]);

        // Gửi request HTTP POST sang Endpoint của PayOS để lấy checkoutUrl vì link này chứa QR để user quét
        $response = Http::withHeaders([
            'x-client-id' => $this->clientId,
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/v2/payment-requests', $payload);

        $resData = $response->json();

        if ($response->failed() || (isset($resData['code']) && $resData['code'] !== '00')) {
            Log::error('PayOS connection failed or returned error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception("PayOS trả về lỗi: " . ($resData['desc'] ?? 'Không thể kết nối cổng thanh toán.'));
        }

        return [
            'payUrl' => $resData['data']['checkoutUrl'],
            'order_code' => $payment->order_code,
            'payment' => $payment
        ];
    }

    /**
     * Get payment link details to verify status */
    // Hàm `getPaymentDetails` gửi request GET sang PayOS để lấy trạng thái thực tế.
    public function getPaymentDetails(string $orderCode)
    {
        $response = Http::withHeaders([
            'x-client-id' => $this->clientId,
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->get($this->baseUrl . '/v2/payment-requests/' . $orderCode);

        $resData = $response->json();

        if ($response->failed() || (isset($resData['code']) && $resData['code'] !== '00')) {
            Log::error('PayOS query failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception("PayOS trả về lỗi: " . ($resData['desc'] ?? 'Không thể kết nối cổng thanh toán.'));
        }
        return $resData['data'];
    }

    /**
     * Compute checksum signature based on alphabetically sorted keys
     */
    private function computeSignature(array $data, string $checksumKey): string
    {
        ksort($data);
        $fields = [];
        foreach ($data as $key => $val) {
            $fields[] = $key . '=' . (is_null($val) ? '' : $val);
        }
        $rawHash = implode('&', $fields);
        return hash_hmac('sha256', $rawHash, $checksumKey);
    }
}
