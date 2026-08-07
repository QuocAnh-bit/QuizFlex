<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\Room;
use App\Models\RoomAssignment;

class HomeworkAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public $room;
    public $assignment;

    public function __construct(Room $room, RoomAssignment $assignment)
    {
        $this->room = [
            'id' => $room->id,
            'name' => $room->name,
        ];
        $this->assignment = [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'quiz_id' => $assignment->quiz_id,
        ];
    }

    
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    
    public function broadcastType(): string
    {
        return 'homework_assigned';
    }

   
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'homework_assigned',
            'title' => 'Bạn có bài tập mới',
            'message' => "Chủ phòng đã giao bài tập {$this->assignment['title']} trong phòng {$this->room['name']}.",
            'action' => 'view',
            'action_link' => "/homework-rooms/{$this->room['id']}",
            'metadata' => [
                'room_id' => $this->room['id'],
                'assignment_id' => $this->assignment['id'],
                'quiz_id' => $this->assignment['quiz_id'],
            ],
        ];
    }
}
