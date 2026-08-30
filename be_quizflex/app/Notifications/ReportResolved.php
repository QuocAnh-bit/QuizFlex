<?php

namespace App\Notifications;

use App\Models\ReportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReportResolved extends Notification implements ShouldQueue
{
    use Queueable;

    public $report;
    public $status;
    public $action;

    /**
     * Create a new notification instance.
     * $status: 'resolved', 'dismissed'
     * $action: 'approved', 'hidden', 'deleted', 'dismissed'
     */
    public function __construct(ReportTicket $report, string $status = 'resolved', ?string $action = null)
    {
        $this->report = $report;
        $this->status = $status;
        $this->action = $action ?? ($status === 'dismissed' ? 'dismissed' : 'approved');
    }

    /**
     * Get the notification's delivery channels.
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
        return 'report_resolved';
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $content = $this->report->question?->content ?? $this->report->question?->text ?? "Câu hỏi #{$this->report->question_id}";
        $snippet = mb_substr($content, 0, 40, 'UTF-8');
        if (mb_strlen($content, 'UTF-8') > 40) {
            $snippet .= '...';
        }
        $itemTitle = "câu hỏi \"{$snippet}\"";
        $actionLink = null;

        if ($this->action === 'keep') {
            $title = 'ℹ️ Báo cáo của bạn đã được kiểm duyệt — Nội dung hợp lệ';
            $message = "Cảm ơn bạn đã phản hồi! Báo cáo đối với {$itemTitle} đã được Admin kiểm tra và xác nhận nội dung phù hợp quy định.";
        } elseif ($this->action === 'hidden' || $this->action === 'hide') {
            $title = '🔒 Báo cáo của bạn đã được xử lý — Nội dung đã được gỡ công khai';
            $message = "Cảm ơn bạn đã báo cáo! {$itemTitle} đã được Admin gỡ công khai và yêu cầu tác giả đính chính.";
        } elseif ($this->action === 'deleted' || $this->action === 'delete') {
            $title = '🗑️ Báo cáo của bạn đã được xử lý — Nội dung vi phạm đã bị xóa';
            $message = "Cảm ơn bạn đã báo cáo! {$itemTitle} vi phạm quy định đã bị Admin gỡ bỏ khỏi hệ thống.";
        } elseif ($this->status === 'dismissed' || $this->action === 'dismissed') {
            $title = 'ℹ️ Báo cáo của bạn đã được kiểm duyệt — Không có vi phạm';
            $message = "Báo cáo vi phạm đối với {$itemTitle} đã được Admin kiểm tra và xác nhận nội dung hợp lệ.";
        } elseif ($this->status === 'resolved') {
            $title = '✅ Báo cáo của bạn đã được xử lý thành công';
            $message = "Cảm ơn bạn đã hỗ trợ nâng cao chất lượng QuizFlex! Báo cáo vi phạm đối với {$itemTitle} đã được Admin kiểm duyệt và xử lý thành công.";
        } else {
            $title = 'ℹ️ Kết quả kiểm duyệt báo cáo';
            $message = "Báo cáo vi phạm đối với {$itemTitle} đã được Admin kiểm tra và ghi nhận.";
        }

        return [
            'type' => 'report_resolved',
            'category' => 'report',
            'title' => $title,
            'message' => $message,
            'action' => 'view',
            'action_link' => $actionLink,
            'metadata' => [
                'category' => 'report',
                'action' => $this->action,
                'status' => $this->status,
                'report_id' => $this->report->id,
                'question_id' => $this->report->question_id,
            ],
        ];

    }
}
