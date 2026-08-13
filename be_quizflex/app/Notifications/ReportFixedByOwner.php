<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\ReportTicket;
use App\Models\Quiz;

class ReportFixedByOwner extends Notification implements ShouldQueue
{
    use Queueable;

    public $reportId;
    public $quizId;
    public $quizTitle;

    public function __construct(ReportTicket $report, Quiz $quiz)
    {
        $this->reportId = $report->id;
        $this->quizId = $quiz->id;
        $this->quizTitle = $quiz->title;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastType(): string
    {
        return 'report_quiz_fixed';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'report_quiz_fixed',
            'title' => 'Quiz bị báo cáo đã được chỉnh sửa',
            'message' => "Chủ quiz đã cập nhật bài '{$this->quizTitle}', mời admin xem lại báo cáo.",
            'action' => 'view',
            'action_link' => '/admin/report-tickets',
            'metadata' => [
                'report_id' => $this->reportId,
                'quiz_id' => $this->quizId,
            ],
        ];
    }
}