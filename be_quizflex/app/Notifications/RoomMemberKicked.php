<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Room;
use App\Models\RoomMember;

class RoomMemberKicked extends Notification
{
    use Queueable;

    public $room;
    public $member;

    public function __construct(Room $room, RoomMember $member)
    {
        $this->room = $room;
        $this->member = $member;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastType(): string
    {
        return 'room_member_kicked';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'room_member_kicked',
            'title' => 'Bạn đã bị xóa khỏi phòng',
            'message' => "Bạn đã bị chủ phòng xóa khỏi phòng {$this->room->name}.",
            'action' => 'view',
            'action_link' => "/homework-rooms",
            'metadata' => [
                'room_id' => $this->room->id,
                'member_id' => $this->member->id,
            ],
        ];
    }
}
