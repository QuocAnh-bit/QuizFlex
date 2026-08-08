<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Room;

class HomeworkEvaluated extends Notification
{
    use Queueable;

    public $room;
    public $assignment;
    public $attempt;
    public $evaluation;
    public $isMemberEvaluation;

    
    public function __construct(Room $room, $assignment = null, $attempt = null, $evaluation = null, bool $isMemberEvaluation = false)
    {
        $this->room = $room;
        $this->assignment = $assignment;
        $this->attempt = $attempt;
        $this->evaluation = $evaluation;
        $this->isMemberEvaluation = $isMemberEvaluation;
    }

    
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

  
    public function broadcastType(): string
    {
        return 'homework_evaluated';
    }

    
    public function toArray(object $notifiable): array
    {
        if ($this->isMemberEvaluation) {
            return [
                'type' => 'homework_evaluated',
                'title' => 'Đánh giá học tập mới',
                'message' => "Chủ phòng đã đánh giá học tập của bạn trong phòng {$this->room->name}.",
                'action' => 'view',
                'action_link' => "/homework-rooms/{$this->room->id}?open_member_evaluation=1",
                'metadata' => [
                    'room_id' => $this->room->id,
                    'evaluation_id' => $this->evaluation ? $this->evaluation->id : null,
                ],
            ];
        }

        $assignmentTitle = $this->assignment ? $this->assignment->title : 'Bài tập';
        return [
            'type' => 'homework_evaluated',
            'title' => 'Bài làm đã được đánh giá',
            'message' => "Bài {$assignmentTitle} của bạn đã được chủ phòng đánh giá.",
            'action' => 'view',
            'action_link' => "/homework-rooms/{$this->room->id}?open_member_evaluation=1",
            'metadata' => [
                'room_id' => $this->room->id,
                'assignment_id' => $this->assignment ? $this->assignment->id : null,
                'attempt_id' => $this->attempt ? $this->attempt->id : null,
                'evaluation_id' => $this->evaluation ? $this->evaluation->id : null,
            ],
        ];
    }
}
