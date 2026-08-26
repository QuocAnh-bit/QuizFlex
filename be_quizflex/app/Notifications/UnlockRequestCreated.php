<?php

namespace App\Notifications;

use App\Models\UnlockRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UnlockRequestCreated extends Notification
{
    use Queueable;

    public UnlockRequest $unlockRequest;
    public User $user;

    public function __construct(UnlockRequest $unlockRequest, User $user)
    {
        $this->unlockRequest = $unlockRequest;
        $this->user = $user;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastType(): string
    {
        return 'unlock_request_created';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'unlock_request_created',
            'title' => '🔔 Yêu cầu mở khóa tài khoản mới',
            'message' => "Người dùng {$this->user->name} ({$this->user->email}) đã gửi đơn kháng cáo mở khóa tài khoản.",
            'action' => 'review',
            'action_link' => '/admin/users?tab=locked',
            'metadata' => [
                'unlock_request_id' => $this->unlockRequest->id,
                'user_id' => $this->user->id,
                'user_name' => $this->user->name,
                'user_email' => $this->user->email,
                'appeal_message' => $this->unlockRequest->message,
            ],
        ];
    }
}
