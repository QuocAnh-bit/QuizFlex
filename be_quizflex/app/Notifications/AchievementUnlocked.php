<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Badge;

class AchievementUnlocked extends Notification
{
    use Queueable;

    public $badge;

  
    public function __construct(Badge $badge)
    {
        $this->badge = $badge;
    }

  
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

   
    public function broadcastType(): string
    {
        return 'achievement_unlocked';
    }

  
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'achievement_unlocked',
            'title' => 'Bạn đã nhận được huy hiệu mới',
            'message' => "Chúc mừng! Bạn đã nhận huy hiệu {$this->badge->name}.",
            'action' => 'view',
            'action_link' => '/gamification',
            'metadata' => [
                'badge_id' => $this->badge->id,
                'badge_name' => $this->badge->name,
            ],
        ];
    }
}
