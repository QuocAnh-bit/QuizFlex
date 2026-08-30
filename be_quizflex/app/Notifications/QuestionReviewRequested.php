<?php

namespace App\Notifications;

use App\Models\Question;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class QuestionReviewRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public $question;
    public $author;
    public $revisionNumber;
    public $isPriority;

    public function __construct(Question $question, User $author, int $revisionNumber = 1, bool $isPriority = false)
    {
        $this->question = $question;
        $this->author = $author;
        $this->revisionNumber = $revisionNumber;
        $this->isPriority = $isPriority;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastType(): string
    {
        return 'question_review_requested';
    }

    public function toArray(object $notifiable): array
    {
        $snippet = mb_substr($this->question->content ?? '', 0, 50, 'UTF-8');
        if (mb_strlen($this->question->content ?? '', 'UTF-8') > 50) {
            $snippet .= '...';
        }

        if ($this->isPriority) {
            $title = '🔴 [ƯU TIÊN] Yêu cầu duyệt câu hỏi đính chính sau báo cáo vi phạm';
            $message = "Tác giả {$this->author->name} vừa đính chính và gửi duyệt lại câu hỏi #{$this->question->id} (\"{$snippet}\") từng bị báo cáo. Vui lòng ưu tiên xét duyệt!";
        } else {
            $title = $this->revisionNumber > 1
                ? '🔄 Yêu cầu duyệt lại câu hỏi vào Ngân hàng'
                : '🔔 Yêu cầu duyệt câu hỏi vào Ngân hàng';

            $message = $this->revisionNumber > 1
                ? "Tác giả {$this->author->name} đã cập nhật và gửi duyệt lại câu hỏi #{$this->question->id} (\"{$snippet}\") (Lần #{$this->revisionNumber})."
                : "Tác giả {$this->author->name} đã gửi yêu cầu duyệt câu hỏi #{$this->question->id} (\"{$snippet}\") vào Ngân hàng.";
        }

        $category = $this->isPriority ? 'report' : 'question_review';

        return [
            'type' => 'question_review_requested',
            'category' => $category,
            'title' => $title,
            'message' => $message,
            'action' => 'review',
            'action_link' => $this->isPriority
                ? "/admin/reports?question_id={$this->question->id}"
                : "/admin/question-bank-requests?question_id={$this->question->id}",
            'metadata' => [
                'category' => $category,
                'action' => 'review',
                'question_id' => $this->question->id,
                'author_id' => $this->author->id,
                'author_name' => $this->author->name,
                'revision_number' => $this->revisionNumber,
                'is_priority' => $this->isPriority,
                'priority' => $this->isPriority ? 'high' : 'normal',
            ],
        ];
    }
}

