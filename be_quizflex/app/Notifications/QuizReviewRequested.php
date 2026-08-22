<?php

namespace App\Notifications;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuizReviewRequested extends Notification
{
    use Queueable;

    public $quiz;
    public $author;
    public int $revisionNumber;

    public function __construct(Quiz $quiz, User $author, int $revisionNumber = 1)
    {
        $this->quiz = $quiz;
        $this->author = $author;
        $this->revisionNumber = $revisionNumber;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastType(): string
    {
        return 'quiz_review_requested';
    }

    public function toArray(object $notifiable): array
    {
        $revText = $this->revisionNumber > 1 ? " (Lần #{$this->revisionNumber})" : "";

        return [
            'type' => 'quiz_review_requested',
            'title' => '🔔 Yêu cầu duyệt Quiz công khai',
            'message' => "Tác giả {$this->author->name} đã gửi yêu cầu duyệt công khai cho bài Quiz '{$this->quiz->title}'{$revText}.",
            'action' => 'review',
            'action_link' => "/admin/quiz-review-requests?quiz_id={$this->quiz->id}",
            'metadata' => [
                'quiz_id' => $this->quiz->id,
                'author_id' => $this->author->id,
                'author_name' => $this->author->name,
                'quiz_title' => $this->quiz->title,
                'revision_number' => $this->revisionNumber,
            ],
        ];
    }
}
