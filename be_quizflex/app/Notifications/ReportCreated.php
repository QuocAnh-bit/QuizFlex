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
    public $quizId;

   
    public function __construct(ReportTicket $report, User $reporter)
    {
        $this->reportId = $report->id;
        $this->reporterName = $reporter->name;
        $this->quizId = $report->quiz_id;
    }

    
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

 
    public function broadcastType(): string
    {
        return 'report_created';
    }

 
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'report_created',
            'title' => 'Có báo cáo mới',
            'message' => "{$this->reporterName} đã gửi báo cáo cần xử lý.",
            'action' => 'view',
            'action_link' => '/admin/report-tickets',
            'metadata' => [
                'report_id' => $this->reportId,
                'quiz_id' => $this->quizId,
            ],
        ];
    }
}
