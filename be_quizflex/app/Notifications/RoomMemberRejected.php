<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Room;

class RoomMemberRejected extends Notification
{
    use Queueable;

    public $room;
    public $memberId;

    public function __construct(Room $room, $memberId)
    {
        $this->room = $room;
        $this->memberId = $memberId;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastType(): string
    {
        return 'room_member_rejected';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'room_member_rejected',
            'title' => 'Yêu cầu tham gia bị từ chối',
            'message' => "Yêu cầu tham gia phòng {$this->room->name} của bạn đã bị từ chối.",
            'action' => 'view',
            'action_link' => "/homework-rooms",
            'metadata' => [
                'room_id' => $this->room->id,
                'member_id' => $this->memberId,
            ],
        ];
    }
}
