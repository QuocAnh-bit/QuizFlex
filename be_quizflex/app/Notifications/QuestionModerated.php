<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuestionModerated extends Notification
{
    use Queueable;

    public $question;
    public $action;
    public $reason;
    public $description;

    /**
     * Create a new notification instance.
     * $action: 'reported', 'hidden', 'shown', 'resolved', 'dismissed', 'edited', 'deleted', 'approved', 'rejected'
     */
    public function __construct($question, string $action, ?string $reason = null, ?string $description = null)
    {
        $this->question = $question;
        $this->action = $action;
        $this->reason = $reason;
        $this->description = $description;
    }

    /**
     * Delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Broadcast type identifier.
     */
    public function broadcastType(): string
    {
        return 'question_moderated';
    }

    /**
     * Array representation for database and broadcast.
     */
    public function toArray(object $notifiable): array
    {
        $content = $this->question->content ?? $this->question->text ?? 'Câu hỏi';
        $snippet = mb_substr($content, 0, 50, 'UTF-8');
        if (mb_strlen($content, 'UTF-8') > 50) {
            $snippet .= '...';
        }

        $title = 'Thông báo kiểm duyệt Câu hỏi';
        $message = "Admin đã tác động lên câu hỏi '#{$this->question->id}' của bạn.";
        $reasonText = $this->reason ? $this->reason : 'Nội dung chưa phù hợp quy định';

        if ($this->action === 'reported') {
            $title = '🚩 Câu hỏi của bạn bị báo cáo vi phạm';
            $descText = $this->description ? " (Mô tả chi tiết: \"{$this->description}\")" : '';
            $message = "Câu hỏi #{$this->question->id} (\"{$snippet}\") của bạn vừa nhận báo cáo vi phạm. Lý do: \"{$reasonText}\"{$descText}. Vui lòng bấm vào đây để kiểm tra và chỉnh sửa.";
        } elseif ($this->action === 'hidden') {

            $title = '⚠️ Admin đã gỡ công khai câu hỏi của bạn';
            $message = "Admin đã gỡ công khai (khóa) câu hỏi #{$this->question->id} (\"{$snippet}\") của bạn do vi phạm. Lý do: \"{$reasonText}\". Vui lòng nhấp vào đây để chỉnh sửa và yêu cầu duyệt lại.";
        } elseif ($this->action === 'shown') {
            $title = '🎉 Câu hỏi của bạn đã được công khai trở lại';
            $message = "Câu hỏi #{$this->question->id} (\"{$snippet}\") của bạn đã được Admin duyệt và mở công khai trở lại trên hệ thống.";
        } elseif ($this->action === 'resolved') {
            $title = '✅ Báo cáo câu hỏi đã được xử lý';
            $message = "Báo cáo vi phạm đối với câu hỏi #{$this->question->id} của bạn đã được Admin xử lý.";
        } elseif ($this->action === 'dismissed') {
            $title = 'ℹ️ Báo cáo câu hỏi đã được bỏ qua';
            $message = "Báo cáo vi phạm đối với câu hỏi #{$this->question->id} của bạn đã được kiểm duyệt và bỏ qua (không có vi phạm).";
        } elseif ($this->action === 'approved') {
            $title = '🎉 Câu hỏi của bạn đã được duyệt vào Ngân hàng câu hỏi';
            $message = "Câu hỏi #{$this->question->id} (\"{$snippet}\") của bạn đã được Admin phê duyệt và đưa vào Ngân hàng câu hỏi dùng chung.";
        } elseif ($this->action === 'rejected') {
            $title = '❌ Yêu cầu duyệt câu hỏi vào Ngân hàng bị từ chối';
            $message = "Yêu cầu đưa câu hỏi #{$this->question->id} (\"{$snippet}\") vào Ngân hàng đã bị từ chối. Lý do: \"{$reasonText}\".";
        } elseif ($this->action === 'deleted') {
            $title = '❌ Câu hỏi của bạn đã bị xóa';
            $message = "Câu hỏi #{$this->question->id} của bạn đã bị gỡ bỏ vĩnh viễn do vi phạm nghiêm trọng quy định.";
        }

        return [
            'type' => 'question_moderated',
            'title' => $title,
            'message' => $message,
            'action' => 'view',
            'action_link' => "/dashboard/my-questions?question_id={$this->question->id}",
            'metadata' => [
                'question_id' => $this->question->id,
                'quiz_id' => $this->question->quiz_id,
                'action' => $this->action,
                'reason' => $this->reason,
                'report_reason' => $this->reason,
                'description' => $this->description,
                'report_description' => $this->description,
            ],
        ];
    }
}

