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
        // Trả về 'database' để lưu vào CSDL thay vì gửi email
        return ['database']; 
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        // Tùy chỉnh câu thông báo dựa theo hành động của Admin
        $message = "Admin đã tác động lên bài Quiz '{$this->quiz->title}' của bạn.";
        
        if ($this->action === 'deleted') {
            $message = "Bài Quiz '{$this->quiz->title}' của bạn đã bị gỡ bỏ do vi phạm quy định.";
        } elseif ($this->action === 'edited') {
            $message = "Admin đã chỉnh sửa nội dung bài Quiz '{$this->quiz->title}' của bạn.";
        } elseif ($this->action === 'resolved') {
            $message = "Báo cáo vi phạm về bài Quiz '{$this->quiz->title}' của bạn đã được xử lý.";
        }

        return [
            'title' => 'Bài quiz của bạn đã được kiểm duyệt',
            'message' => $message,
            'action' => $this->action,
            'quiz_id' => $this->quiz->id,
        ];
    }
}