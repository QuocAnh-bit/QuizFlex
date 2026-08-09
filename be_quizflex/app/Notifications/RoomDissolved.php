<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\Room;
use App\Models\RoomMember;

class RoomDissolved extends Notification implements ShouldQueue
{
    use Queueable;

    public $room;
    public $member;

   
    public function __construct(Room $room, RoomMember $member)
    {
        $this->room = [
            'id' => $room->id,
            'name' => $room->name,
        ];
        $this->member = $member;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastType(): string
    {
        return 'room_dissolved';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'room_dissolved',
            'title' => 'Phòng đã được giải tán',
            'message' => "Phòng {$this->room['name']} đã được chủ phòng giải tán.",
            'action' => 'view',
            'action_link' => "/homework-rooms",
            'metadata' => [
                'room_id' => $this->room['id'],
                'member_id' => $this->member->id,
            ],
        ];
    }
}
