<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuestionModerated extends Notification
{
    use Queueable;

    public $question;
    public $action;
    public $note;

    public function __construct($question, $action, $note = null)
    {
        $this->question = $question;
        $this->action = $action;
        $this->note = $note;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $qid = $this->question->id;
        $noteText = !empty($this->note) ? " Lý do / Chi tiết lỗi: \"{$this->note}\"." : '';

        if (in_array($this->action, ['approve', 'approved', 'shown'])) {
            $title = "Admin đã duyệt công khai Câu hỏi #{$qid}";
            $message = "Câu hỏi #{$qid} của bạn đã được Admin duyệt và hiển thị công khai trên Ngân hàng dùng chung!";
        } elseif ($this->action === 'reported') {
            $title = "⚠️ Câu hỏi #{$qid} bị báo cáo vi phạm";
            $message = "Câu hỏi #{$qid} vừa nhận báo cáo vi phạm từ người dùng.{$noteText} Vui lòng kiểm tra và đính chính lại.";
        } elseif (in_array($this->action, ['hidden', 'reject', 'rejected', 'unpublish'])) {
            $title = "Admin đã gỡ công khai Câu hỏi #{$qid}";
            if (!empty($this->note)) {
                $message = "Admin đã gỡ công khai Câu hỏi #{$qid} do chưa đính chính đúng yêu cầu. Lý do: \"{$this->note}\". Vui lòng sửa lại nội dung để nộp duyệt lại.";
            } else {
                $message = "Admin đã gỡ công khai Câu hỏi #{$qid} về Kho cá nhân. Vui lòng kiểm tra và đính chính lại nội dung để nộp duyệt lại.";
            }
        } elseif ($this->action === 'resolved') {
            $title = "Thông báo: Admin đã xử lý báo cáo Câu hỏi #{$qid}";
            $message = "Admin đã xử lý xong báo cáo vi phạm liên quan đến Câu hỏi #{$qid} của bạn.{$noteText}";
        } elseif ($this->action === 'dismissed') {
            $title = "Thông báo: Admin đã bỏ qua báo cáo Câu hỏi #{$qid}";
            $message = "Admin đã xem xét báo cáo vi phạm đối với Câu hỏi #{$qid} và xác nhận câu hỏi hợp lệ.";
        } else {
            $title = "Thông báo từ Admin về Câu hỏi #{$qid}";
            $message = "Có cập nhật mới từ Admin về Câu hỏi #{$qid} của bạn.{$noteText}";
        }

        $isApprovedAction = in_array($this->action, ['approve', 'approved', 'shown', 'resolved', 'dismissed'], true);
        $actionLink = $isApprovedAction
            ? "/dashboard/my-questions?highlight={$qid}"
            : "/dashboard/my-questions?question_id={$qid}";

        return [
            'type' => 'question_moderated',
            'title' => $title,
            'message' => $message,
            'action' => 'edit',
            'action_link' => $actionLink,
            'metadata' => [
                'question_id' => $qid,
                'action' => $this->action,
                'note' => $this->note,
            ],
        ];
    }
}
