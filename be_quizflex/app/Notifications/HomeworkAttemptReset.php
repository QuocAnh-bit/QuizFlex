<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Room;
use App\Models\RoomAssignment;

class HomeworkAttemptReset extends Notification
{
    use Queueable;

    public $room;
    public $assignment;
    public $attemptId;


    public function __construct(Room $room, RoomAssignment $assignment, $attemptId)
    {
        $this->room = $room;
        $this->assignment = $assignment;
        $this->attemptId = $attemptId;
    }

    
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    
    public function broadcastType(): string
    {
        return 'homework_attempt_reset';
    }

    
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'homework_attempt_reset',
            'title' => 'Lượt làm bài đã được reset',
            'message' => "Chủ phòng đã reset lượt làm bài {$this->assignment->title} của bạn. Bạn có thể làm lại bài.",
            'action' => 'view',
            'action_link' => "/homework-rooms/{$this->room->id}/assignments/{$this->assignment->id}/take",
            'metadata' => [
                'room_id' => $this->room->id,
                'assignment_id' => $this->assignment->id,
                'attempt_id' => $this->attemptId,
            ],
        ];
    }
}
