<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\QuizAttempt;
use App\Models\User;

class HomeworkSubmitted extends Notification
{
    use Queueable;

    public $room;
    public $assignment;
    public $attempt;
    public $student;

    
    public function __construct(Room $room, RoomAssignment $assignment, QuizAttempt $attempt, User $student)
    {
        $this->room = $room;
        $this->assignment = $assignment;
        $this->attempt = $attempt;
        $this->student = $student;
    }

   
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    
    public function broadcastType(): string
    {
        return 'homework_submitted';
    }

    
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'homework_submitted',
            'title' => 'Học viên đã nộp bài',
            'message' => "Học viên {$this->student->name} đã nộp bài {$this->assignment->title}.",
            'action' => 'view',
            'action_link' => "/homework-rooms/{$this->room->id}/assignments/{$this->assignment->id}/attempts?attempt_id={$this->attempt->id}",
            'metadata' => [
                'room_id' => $this->room->id,
                'assignment_id' => $this->assignment->id,
                'attempt_id' => $this->attempt->id,
                'user_id' => $this->student->id,
            ],
        ];
    }
}
