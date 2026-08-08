<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccountModerated extends Notification
{
    use Queueable;

    public $action; // 'lock', 'unlock', 'unlock_approved', 'unlock_rejected'
    public $reason;

  
    public function __construct($action, $reason = null)
    {
        $this->action = $action;
        $this->reason = $reason;
    }

    
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

   
    public function broadcastType(): string
    {
        switch ($this->action) {
            case 'lock':
                return 'account_locked';
            case 'unlock':
                return 'account_unlocked';
            case 'unlock_approved':
                return 'unlock_request_approved';
            case 'unlock_rejected':
                return 'unlock_request_rejected';
        }
        return 'account_moderated';
    }

   
    public function toArray(object $notifiable): array
    {
        $type = $this->broadcastType();
        $title = '';
        $message = '';
        
        switch ($this->action) {
            case 'lock':
                $title = 'Tài khoản đã bị khóa';
                $message = 'Tài khoản của bạn đã bị quản trị viên khóa.';
                break;
            case 'unlock':
                $title = 'Tài khoản đã được mở khóa';
                $message = 'Tài khoản của bạn đã được quản trị viên mở khóa.';
                break;
            case 'unlock_approved':
                $title = 'Yêu cầu mở khóa được chấp thuận';
                $message = 'Yêu cầu mở khóa tài khoản của bạn đã được chấp thuận.';
                break;
            case 'unlock_rejected':
                $title = 'Yêu cầu mở khóa bị từ chối';
                $message = 'Yêu cầu mở khóa tài khoản của bạn đã bị từ chối.' . ($this->reason ? " Lý do: {$this->reason}" : '');
                break;
        }

        return [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action' => 'view',
            'action_link' => '/profile',
            'metadata' => [
                'action' => $this->action,
                'reason' => $this->reason,
            ],
        ];
    }
}
