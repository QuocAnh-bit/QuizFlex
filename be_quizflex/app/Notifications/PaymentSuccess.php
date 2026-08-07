<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Payment;

class PaymentSuccess extends Notification
{
    use Queueable;

    public $payment;
    public $plan;


    public function __construct(Payment $payment, $plan)
    {
        $this->payment = $payment;
        $this->plan = $plan;
    }

    
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    
    public function broadcastType(): string
    {
        return 'payment_success';
    }

   
    public function toArray(object $notifiable): array
    {
        $planName = $this->plan ? ($this->plan['name'] ?? '') : '';
        return [
            'type' => 'payment_success',
            'title' => 'Nâng cấp VIP thành công',
            'message' => 'Thanh toán thành công. Tài khoản của bạn đã được nâng cấp VIP.',
            'action' => 'view',
            'action_link' => '/upgrade',
            'metadata' => [
                'payment_id' => $this->payment->id,
                'plan' => $planName,
            ],
        ];
    }
}
