<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Question;
use App\Models\QuestionReviewRequest;
use App\Models\ReportTicket;

return new class extends Migration
{
    /**
     * Run the data migrations.
     */
    public function up(): void
    {
        Log::info('Bắt đầu Data Migration cho ReportTicket và QuestionReviewRequest Lifecycle...');

        // -------------------------------------------------------------
        // 1. AUDIT & MIGRATE QuestionReviewRequest MULTIPLE PENDING
        // -------------------------------------------------------------
        $questionsWithMultiPending = QuestionReviewRequest::where('status', 'pending')
            ->select('question_id', DB::raw('count(*) as count'))
            ->groupBy('question_id')
            ->havingRaw('count > 1')
            ->get();

        foreach ($questionsWithMultiPending as $item) {
            $pendingRequests = QuestionReviewRequest::where('question_id', $item->question_id)
                ->where('status', 'pending')
                ->orderBy('revision_number', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            // Giữ bản ghi mới nhất là 'pending', các bản ghi cũ hơn chuyển sang 'superseded'
            $latest = $pendingRequests->pop();
            foreach ($pendingRequests as $oldReq) {
                $oldReq->update([
                    'status' => 'superseded',
                    'rejection_reason' => $oldReq->rejection_reason ?? 'Đã được thay thế bởi Revision #' . $latest->revision_number,
                ]);
            }
        }

        // -------------------------------------------------------------
        // 2. AUDIT & MIGRATE ReportTicket LEGACY & TRANSITION STATUSES
        // -------------------------------------------------------------
        // Xử lý status cũ 'investigating' nếu có tồn tại từ các phiên bản trước
        $legacyInvestigatingReports = DB::table('report_tickets')
            ->where('status', 'investigating')
            ->get();

        foreach ($legacyInvestigatingReports as $rep) {
            $question = Question::find($rep->question_id);
            $newStatus = 'pending';

            if ($question) {
                $hasPendingReview = QuestionReviewRequest::where('question_id', $question->id)
                    ->where('status', 'pending')
                    ->exists();

                $isEditedAfterReport = $question->updated_at && $question->updated_at > $rep->created_at;

                if ($hasPendingReview || $isEditedAfterReport) {
                    $newStatus = 'author_updated';
                }
            }

            // Kiểm tra lý do nghiêm trọng
            if (preg_match('/(nhạy cảm|xúc phạm|bản quyền|chính sách|nghiêm trọng|phản động|khiêu dâm)/ui', $rep->reason)) {
                $newStatus = 'admin_review_required';
            }

            DB::table('report_tickets')
                ->where('id', $rep->id)
                ->update(['status' => $newStatus]);
        }

        // -------------------------------------------------------------
        // 3. ELEVATE CRITICAL REASONS & THRESHOLDS (>= 3 REPORTS)
        // -------------------------------------------------------------
        // 3.1. Các report có lý do nhạy cảm / nghiêm trọng đang ở pending -> admin_review_required
        $criticalKeywords = ['nhạy cảm', 'xúc phạm', 'bản quyền', 'chính sách', 'nghiêm trọng', 'phản động', 'khiêu dâm'];
        foreach ($criticalKeywords as $kw) {
            ReportTicket::where('status', 'pending')
                ->where('reason', 'like', "%{$kw}%")
                ->update(['status' => 'admin_review_required']);
        }

        // 3.2. Các câu hỏi có từ 3 report chưa giải quyết trở lên -> admin_review_required
        $highReportQuestions = ReportTicket::whereIn('status', ['pending', 'author_updated', 'admin_review_required'])
            ->select('question_id', DB::raw('count(*) as count'))
            ->groupBy('question_id')
            ->havingRaw('count >= 3')
            ->pluck('question_id');

        if ($highReportQuestions->isNotEmpty()) {
            ReportTicket::whereIn('question_id', $highReportQuestions)
                ->where('status', 'pending')
                ->update(['status' => 'admin_review_required']);
        }

        // -------------------------------------------------------------
        // 4. SYNC QUESTION BANK SUBMISSION STATUS CONSISTENCY
        // -------------------------------------------------------------
        $questionsWithPendingReview = QuestionReviewRequest::where('status', 'pending')
            ->pluck('question_id')
            ->unique();

        if ($questionsWithPendingReview->isNotEmpty()) {
            Question::whereIn('id', $questionsWithPendingReview)
                ->where('bank_submission_status', '!=', 'pending')
                ->update(['bank_submission_status' => 'pending']);
        }

        Log::info('Data Migration cho ReportTicket và QuestionReviewRequest hoàn tất thành công.');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data migration is non-destructive and preserves all historical records.
    }
};
