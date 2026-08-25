<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class QuestionModerated extends Notification
{
    use Queueable;

    public $question;
    public $action;
    public $reason;
    public $description;
    public $note;

    /**
     * Create a new notification instance.
     * $action: 'reported', 'hidden', 'shown', 'resolved', 'dismissed', 'edited', 'deleted', 'approved', 'rejected'
     */
    public function __construct($question, string $action, ?string $reason = null, ?string $description = null)
    {
        $this->question = $question;
        $this->action = $action;
        $this->reason = $reason;
        $this->note = $reason;
        $this->description = $description;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $qid = $this->question->id ?? $this->question;
        $qContent = is_object($this->question) ? ($this->question->content ?? $this->question->question ?? '') : '';
        $snippet = Str::limit(strip_tags($qContent), 40);
        $noteText = !empty($this->reason) ? " Lý do / Chi tiết: \"{$this->reason}\"." : (!empty($this->description) ? " (\"{$this->description}\")" : '');

        $title = 'Thông báo kiểm duyệt Câu hỏi';
        $message = "Admin đã tác động lên câu hỏi '#{$qid}' của bạn.";
        $reasonText = $this->reason ? $this->reason : 'Nội dung chưa phù hợp quy định';

        if (in_array($this->action, ['approve', 'approved'], true)) {
            $title = '🎉 Câu hỏi của bạn đã được duyệt vào Ngân hàng câu hỏi';
            $message = "Câu hỏi #{$qid} (\"{$snippet}\") của bạn đã được Admin phê duyệt và đưa vào Ngân hàng câu hỏi dùng chung.";
        } elseif ($this->action === 'shown') {
            $title = '🎉 Câu hỏi của bạn đã được công khai trở lại trên Ngân hàng câu hỏi';
            $message = "Admin đã duyệt nội dung đính chính. Câu hỏi #{$qid} (\"{$snippet}\") của bạn đã được mở công khai trở lại trên Ngân hàng câu hỏi.";
        } elseif ($this->action === 'reported') {
            $title = '🚩 Câu hỏi của bạn nhận báo cáo vi phạm trên Ngân hàng câu hỏi';
            $descText = $this->description ? " (Mô tả chi tiết: \"{$this->description}\")" : '';
            $message = "Câu hỏi #{$qid} (\"{$snippet}\") của bạn vừa nhận báo cáo vi phạm. Lý do: \"{$reasonText}\"{$descText}. Vui lòng bấm vào đây để đính chính và gửi Admin duyệt công khai lại.";
        } elseif (in_array($this->action, ['hidden', 'reject', 'rejected', 'unpublish'], true)) {
            $title = '⚠️ Admin đã gỡ công khai câu hỏi khỏi Ngân hàng câu hỏi';
            $message = "Admin đã gỡ công khai câu hỏi #{$qid} (\"{$snippet}\") khỏi Ngân hàng câu hỏi. Lý do: \"{$reasonText}\". Vui lòng nhấp vào đây để đính chính và gửi nộp duyệt lại.";
        } elseif ($this->action === 'resolved') {
            $title = "✓ Đã duyệt đính chính câu hỏi #{$qid}";
            $message = "Admin đã duyệt nội dung đính chính liên quan đến Câu hỏi #{$qid} của bạn để mở công khai lại trên Ngân hàng câu hỏi.{$noteText}";
        } elseif ($this->action === 'dismissed') {
            $title = '🛡️ Báo cáo câu hỏi đã được gỡ bỏ';
            $message = "Báo cáo vi phạm đối với câu hỏi #{$qid} của bạn đã được Admin kiểm duyệt và gỡ bỏ. Trạng thái công khai trên Ngân hàng câu hỏi được giữ nguyên.";
        } elseif ($this->action === 'deleted') {
            $title = '❌ Câu hỏi của bạn đã bị xóa khỏi Ngân hàng câu hỏi';
            $message = "Câu hỏi #{$qid} của bạn đã bị gỡ bỏ vĩnh viễn khỏi Ngân hàng câu hỏi do vi phạm quy định.";
        } else {
            $title = "Thông báo từ Admin về Câu hỏi #{$qid}";
            $message = "Có cập nhật mới từ Admin về Câu hỏi #{$qid} của bạn trên Ngân hàng câu hỏi.{$noteText}";
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
                'note' => $this->reason,
                'reason' => $this->reason,
                'report_reason' => $this->reason,
                'description' => $this->description,
                'report_description' => $this->description,
            ],
        ];
    }
}
