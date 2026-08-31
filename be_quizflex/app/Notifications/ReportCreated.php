<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\ReportTicket;
use App\Models\User;

class ReportCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public $reportId;
    public $reporterName;
    public $questionId;

    public function __construct(ReportTicket $report, User $reporter)
    {
        $this->afterCommit = true;
        $this->reportId = $report->id;
        $this->reporterName = $reporter->name;
        $this->questionId = $report->question_id;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastType(): string
    {
        return 'report.created';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'report.created',
            'category' => 'report',
            'title' => 'Có báo cáo câu hỏi mới',
            'message' => "{$this->reporterName} đã gửi báo cáo vi phạm câu hỏi #{$this->questionId}.",
            'action' => 'view',
            'action_link' => "/admin/reports?question_id={$this->questionId}",
            'metadata' => [
                'type' => 'report.created',
                'category' => 'report',
                'action' => 'view',
                'report_id' => $this->reportId,
                'question_id' => $this->questionId,
            ],
        ];
    }
}

