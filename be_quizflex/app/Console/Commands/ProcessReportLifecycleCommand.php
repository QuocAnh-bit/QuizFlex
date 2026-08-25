<?php

namespace App\Console\Commands;

use App\Services\ReportAutomationService;
use Illuminate\Console\Command;

class ProcessReportLifecycleCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'quizflex:process-report-lifecycle';

    /**
     * The console command description.
     */
    protected $description = 'Xử lý vòng đời Reminder (Day 3), Warning (Day 5) và Auto Private (Day 7) cho các câu hỏi bị báo cáo chưa sửa';

    /**
     * Execute the console command.
     */
    public function handle(ReportAutomationService $automationService): int
    {
        $this->info('Đang kiểm tra và xử lý vòng đời báo cáo câu hỏi...');

        try {
            $results = $automationService->processLifecycleRemindersAndAutoPrivate();

            if (!empty($results['locked'])) {
                $this->warn('Tiến trình xử lý vòng đời đang chạy đồng thời ở một luồng khác. Đã hủy bỏ tác vụ trùng lặp.');
                return Command::SUCCESS;
            }

            $this->info("Tổng số báo cáo active: {$results['total_active_reports']}");
            $this->info("Đã gửi Reminders (Day 3): {$results['reminders_sent']}");
            $this->info("Đã gửi Warnings (Day 5): {$results['warnings_sent']}");
            $this->info("Đã Auto Private (Day 7): {$results['auto_privatized']}");
            $this->info("Đã bỏ qua (không thỏa điều kiện): {$results['skipped']}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Lỗi khi xử lý vòng đời báo cáo: {$e->getMessage()}");
            \Illuminate\Support\Facades\Log::error('Lỗi khi thực thi ProcessReportLifecycleCommand: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return Command::FAILURE;
        }
    }
}
