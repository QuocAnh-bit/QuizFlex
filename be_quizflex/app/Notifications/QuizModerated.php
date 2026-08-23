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

    /**
     * Create a new notification instance.
     */
    public function __construct($quiz, $action, $reason = null)
    {
        $this->quiz = $quiz;
        $this->action = $action;
        $this->reason = $reason;
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
            $reasonText = $this->reason ? " Lý do: \"{$this->reason}\"." : '';
            $message = "Bài Quiz '{$this->quiz->title}' của bạn đã bị Admin gỡ công khai (chuyển về chế độ riêng tư) do vi phạm quy định.{$reasonText}";
        } elseif ($this->action === 'shown') {
            $message = "Bài Quiz '{$this->quiz->title}' của bạn đã được admin hiển thị công khai trở lại.";
        } elseif ($this->action === 'approved') {
            $message = "🎉 Chúc mừng! Bài Quiz '{$this->quiz->title}' của bạn đã được Admin phê duyệt và chính thức công khai cho cộng đồng.";
        } elseif ($this->action === 'rejected') {
            $reasonText = $this->reason ? " Lý do: \"{$this->reason}\"." : '';
            $message = "Yêu cầu công khai bài Quiz '{$this->quiz->title}' của bạn đã bị từ chối.{$reasonText} Bạn có thể chỉnh sửa nội dung và gửi lại yêu cầu duyệt.";
        }

        return [
            'type' => 'quiz_moderated',
            'title' => 'Thông báo kiểm duyệt bài Quiz',
            'message' => $message,

            'action' => 'view',
            'action_link' => "/quizzes/{$this->quiz->id}",
            'metadata' => [
                'quiz_id' => $this->quiz->id,
                'action' => $this->action,
                'reason' => $this->reason,
            ],
        ];
    }
}

