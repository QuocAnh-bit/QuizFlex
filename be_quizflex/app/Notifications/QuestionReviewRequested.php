<?php

namespace App\Notifications;

use App\Models\Question;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuestionReviewRequested extends Notification
{
    use Queueable;

    public $question;
    public $author;
    public $revisionNumber;

    public function __construct(Question $question, User $author, int $revisionNumber = 1)
    {
        $this->question = $question;
        $this->author = $author;
        $this->revisionNumber = $revisionNumber;
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

        $title = $this->revisionNumber > 1
            ? '🔄 Yêu cầu duyệt lại câu hỏi vào Ngân hàng'
            : '🔔 Yêu cầu duyệt câu hỏi vào Ngân hàng';

        $message = $this->revisionNumber > 1
            ? "Tác giả {$this->author->name} đã cập nhật và gửi duyệt lại câu hỏi #{$this->question->id} (\"{$snippet}\") (Lần #{$this->revisionNumber})."
            : "Tác giả {$this->author->name} đã gửi yêu cầu duyệt câu hỏi #{$this->question->id} (\"{$snippet}\") vào Ngân hàng.";

        return [
            'type' => 'question_review_requested',
            'title' => $title,
            'message' => $message,
            'action' => 'review',
            'action_link' => "/admin/question-bank-requests?question_id={$this->question->id}",
            'metadata' => [
                'question_id' => $this->question->id,
                'author_id' => $this->author->id,
                'author_name' => $this->author->name,
                'revision_number' => $this->revisionNumber,
            ],
        ];
    }
}
