<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RoomModerated extends Notification
{
    use Queueable;

    public $roomId;
    public $roomName;
    public $roomType; // 'homework' or 'live'
    public $actionType; // 'ban' or 'unban'

    /**
     * Create a new notification instance.
     */
    public function __construct($roomId, $roomName, $roomType, $actionType)
    {
        $this->roomId = $roomId;
        $this->roomName = $roomName;
        $this->roomType = $roomType;
        $this->actionType = $actionType;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the type of the notification being broadcast.
     */
    public function broadcastType(): string
    {
        return $this->actionType === 'ban' ? 'room_banned' : 'room_unbanned';
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $typeLabel = $this->roomType === 'live' ? 'Trận đấu trực tuyến' : 'Phòng học';
        $title = $this->actionType === 'ban' ? "{$typeLabel} đã bị khóa" : "{$typeLabel} đã được mở khóa";
        $statusLabel = $this->actionType === 'ban' ? 'khóa' : 'mở khóa';
        
        $actionLink = $this->roomType === 'live' ? '/live-rooms' : "/homework-rooms/{$this->roomId}";

        return [
            'type' => $this->actionType === 'ban' ? 'room_banned' : 'room_unbanned',
            'title' => $title,
            'message' => "Phòng {$this->roomName} đã bị quản trị viên {$statusLabel}.",
            'action' => 'view',
            'action_link' => $actionLink,
            'metadata' => [
                'room_id' => $this->roomId,
                'room_type' => $this->roomType,
                'action' => $this->actionType,
            ],
        ];
    }
}
