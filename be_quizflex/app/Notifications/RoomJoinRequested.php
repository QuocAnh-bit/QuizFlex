<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;

class RoomJoinRequested extends Notification
{
    use Queueable;

    public $room;
    public $member;
    public $user;

    public function __construct(Room $room, RoomMember $member, User $user)
    {
        $this->room = $room;
        $this->member = $member;
        $this->user = $user;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastType(): string
    {
        return 'room_join_request';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'room_join_request',
            'title' => 'Yêu cầu tham gia phòng mới',
            'message' => "{$this->user->name} đã yêu cầu tham gia phòng {$this->room->name}.",
            'action' => 'view',
            'action_link' => "/homework-rooms/{$this->room->id}?tab=members&status=pending",
            'metadata' => [
                'room_id' => $this->room->id,
                'member_id' => $this->member->id,
                'user_id' => $this->user->id,
            ],
        ];
    }
}
