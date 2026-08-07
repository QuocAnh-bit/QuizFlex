<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuizModerated extends Notification
{
    use Queueable;

    public $quiz;
    public $action;

    /**
     * Create a new notification instance.
     */
    public function __construct($quiz, $action)
    {
        $this->quiz = $quiz;
        $this->action = $action;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the type of the notification being broadcast.
     */
    public function broadcastType(): string
    {
        return 'quiz_moderated';
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Tùy chỉnh câu thông báo dựa theo hành động của Admin
        $message = "Admin đã tác động lên bài Quiz '{$this->quiz->title}' của bạn.";

        if ($this->action === 'deleted') {
            $message = "Bài Quiz '{$this->quiz->title}' của bạn đã bị gỡ bỏ do vi phạm quy định.";
        } elseif ($this->action === 'edited') {
            $message = "Admin đã chỉnh sửa nội dung bài Quiz '{$this->quiz->title}' của bạn.";
        } elseif ($this->action === 'hidden') {
            $message = "Bài Quiz '{$this->quiz->title}' của bạn đã bị ẩn do vi phạm quy định.";
        } elseif ($this->action === 'shown') {
            $message = "Bài Quiz '{$this->quiz->title}' của bạn đã được admin hiển thị lại.";
        } elseif ($this->action === 'resolved') {
            $message = "Báo cáo vi phạm về bài Quiz '{$this->quiz->title}' của bạn đã được xử lý.";
        }

        return [
            'type' => 'quiz_moderated',
            'title' => 'Bài quiz của bạn đã được kiểm duyệt',
            'message' => $message,
            'action' => 'view',
            'action_link' => "/quizzes/{$this->quiz->id}",
            'metadata' => [
                'quiz_id' => $this->quiz->id,
                'action' => $this->action,
            ],
        ];
    }
}

