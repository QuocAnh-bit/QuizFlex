<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Room;
use App\Models\RoomMember;

class RoomMemberApproved extends Notification
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
        return 'room_member_approved';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'room_member_approved',
            'title' => 'Yêu cầu tham gia đã được chấp thuận',
            'message' => "Bạn đã được duyệt tham gia phòng {$this->room->name}.",
            'action' => 'view',
            'action_link' => "/homework-rooms/{$this->room->id}",
            'metadata' => [
                'room_id' => $this->room->id,
                'member_id' => $this->member->id,
            ],
        ];
    }
}
