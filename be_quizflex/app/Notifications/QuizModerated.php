<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuizModerated extends Notification
{
    use Queueable;

    public $quiz;
    public $action;
    public $reason;

    public function __construct($quiz, $action, $reason = null)
    {
        $this->quiz = $quiz;
        $this->action = $action;
        $this->reason = $reason;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastType(): string
    {
        return 'quiz_moderated';
    }

    public function toArray(object $notifiable): array
    {
        $message = "Admin đã tác động lên bài Quiz '{$this->quiz->title}' của bạn.";
        $actionLink = "/quizzes/{$this->quiz->id}";

        if ($this->action === 'deleted') {
            $message = "Bài Quiz '{$this->quiz->title}' của bạn đã bị gỡ bỏ do vi phạm quy định.";
        } elseif ($this->action === 'hidden') {
            $message = "Bài Quiz '{$this->quiz->title}' của bạn đã bị ẩn do vi phạm quy định.";
        } elseif ($this->action === 'shown') {
            $message = "Bài Quiz '{$this->quiz->title}' của bạn đã được admin hiển thị lại.";
        } elseif ($this->action === 'resolved') {
            $message = "Báo cáo vi phạm về bài Quiz '{$this->quiz->title}' của bạn đã được xử lý.";
        } elseif ($this->action === 'needs_fix') {
            $message = "Bài Quiz '{$this->quiz->title}' của bạn bị báo cáo vi phạm"
                . ($this->reason ? " (lý do: {$this->reason})" : '')
                . ". Vui lòng vào chỉnh sửa lại nội dung.";
            $actionLink = "/dashboard/questions/edit/{$this->quiz->id}";
        }

        return [
            'type' => 'quiz_moderated',
            'title' => 'Bài quiz của bạn đã được kiểm duyệt',
            'message' => $message,
            'action' => 'view',
            'action_link' => $actionLink,
            'metadata' => [
                'quiz_id' => $this->quiz->id,
                'action' => $this->action,
            ],
        ];
    }
}