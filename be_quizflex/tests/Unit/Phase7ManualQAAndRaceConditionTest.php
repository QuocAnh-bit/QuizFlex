<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Question;
use App\Models\Answer;
use App\Models\QuestionReviewRequest;
use App\Models\ReportTicket;
use App\Services\QuestionReviewService;
use App\Services\QuestionSnapshotService;
use App\Services\ReportAutomationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;

class Phase7ManualQAAndRaceConditionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $author;
    protected User $student;

    protected QuestionReviewService $reviewService;
    protected QuestionSnapshotService $snapshotService;
    protected ReportAutomationService $automationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_p7_qa@quizflex.com'],
            ['name' => 'Admin P7 Tester', 'password' => bcrypt('password'), 'role' => 'admin']
        );
        $this->admin->role = 'admin';
        $this->admin->save();

        $this->author = User::firstOrCreate(
            ['email' => 'author_p7_qa@quizflex.com'],
            ['name' => 'Author P7 Tester', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->student = User::firstOrCreate(
            ['email' => 'student_p7_qa@quizflex.com'],
            ['name' => 'Student P7 Tester', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->reviewService = app(QuestionReviewService::class);
        $this->snapshotService = app(QuestionSnapshotService::class);
        $this->automationService = app(ReportAutomationService::class);
    }

    private function createSampleApprovedQuestion(string $content = 'Câu hỏi QA Race Condition P7'): Question
    {
        $question = Question::create([
            'user_id' => $this->author->id,
            'content' => $content,
            'type' => 'single_choice',
            'difficulty' => 'medium',
            'points' => 10,
            'is_public' => false,
            'bank_submission_status' => 'none',
        ]);

        Answer::create(['question_id' => $question->id, 'content' => 'Đáp án A đúng', 'is_correct' => true, 'order' => 1]);
        Answer::create(['question_id' => $question->id, 'content' => 'Đáp án B sai', 'is_correct' => false, 'order' => 2]);

        $this->reviewService->submitToBank($question, $this->author);
        $this->reviewService->approveQuestion($question, $this->admin);

        QuestionReviewRequest::where('question_id', $question->id)->update([
            'created_at' => Carbon::now()->subDays(30),
            'updated_at' => Carbon::now()->subDays(30),
            'reviewed_at' => Carbon::now()->subDays(30),
        ]);

        return $question;
    }

    private function createReportWithAge(Question $question, int $daysAgo, string $reason = 'Sai đáp án', string $status = 'pending'): ReportTicket
    {
        $report = new ReportTicket([
            'user_id' => $this->student->id,
            'question_id' => $question->id,
            'reason' => $reason,
            'status' => $status,
        ]);
        $report->timestamps = false;
        $report->created_at = Carbon::now()->subDays($daysAgo);
        $report->updated_at = Carbon::now()->subDays($daysAgo);
        $report->save();

        return $report;
    }

    public function test_race_condition_01_author_fixes_just_as_scheduler_runs()
    {
        $question = $this->createSampleApprovedQuestion('Race 1 Question');
        $bankSnapshot = $this->snapshotService->findBankSnapshotByOriginId($question->id);
        $this->assertTrue((bool)$bankSnapshot->is_public);

        $report = $this->createReportWithAge($question, 7);

        Notification::fake();

        $question->update(['content' => 'Race 1 Question Đã Sửa Nội Dung Hoàn Toàn']);
        $rev = $this->reviewService->submitToBank($question, $this->author);
        $this->assertEquals('approved', $rev->status);

        $res = $this->automationService->processLifecycleRemindersAndAutoPrivate(Carbon::now());

        $this->assertEquals(0, $res['auto_privatized']);
        $this->assertTrue((bool)$bankSnapshot->fresh()->is_public);
        $this->assertEquals(ReportTicket::STATUS_RESOLVED, $report->fresh()->status);
    }

    public function test_race_condition_02_admin_handling_case_as_scheduler_runs()
    {
        $question = $this->createSampleApprovedQuestion('Race 2 Question');
        $bankSnapshot = $this->snapshotService->findBankSnapshotByOriginId($question->id);

        $report = $this->createReportWithAge($question, 7);
        $report->update(['status' => ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED]);

        Notification::fake();

        $res = $this->automationService->processLifecycleRemindersAndAutoPrivate(Carbon::now());

        $this->assertEquals(0, $res['auto_privatized']);
        $this->assertNull($report->fresh()->auto_privatized_at);
        $this->assertTrue((bool)$bankSnapshot->fresh()->is_public);

        Notification::assertNothingSent();
    }

    public function test_race_condition_03_auto_approve_after_manual_resolve()
    {
        $question = $this->createSampleApprovedQuestion('Race 3 Question');
        $report = $this->createReportWithAge($question, 2, 'Lỗi nhỏ', ReportTicket::STATUS_RESOLVED);

        Notification::fake();

        $question->update(['content' => 'Race 3 Question Nội Dung Đã Sửa']);
        $rev = $this->reviewService->submitToBank($question, $this->author);

        $this->assertEquals('pending', $rev->status);
        $this->assertEquals(ReportTicket::STATUS_RESOLVED, $report->fresh()->status);
    }

    public function test_ux_check_01_resolved_report_cannot_transition_to_pending()
    {
        $question = $this->createSampleApprovedQuestion('UX Check 01 Question');
        $report = $this->createReportWithAge($question, 1, 'Lỗi', ReportTicket::STATUS_RESOLVED);

        $this->assertFalse($report->canTransitionTo(ReportTicket::STATUS_PENDING));
        $this->assertFalse($report->canTransitionTo(ReportTicket::STATUS_AUTHOR_UPDATED));
    }

    public function test_ux_check_02_trashed_question_cannot_be_auto_approved()
    {
        $question = $this->createSampleApprovedQuestion('UX Check 02 Question');
        $report = $this->createReportWithAge($question, 1);

        $rev = QuestionReviewRequest::where('question_id', $question->id)->latest('id')->first();
        $question->delete();
        $this->assertTrue($question->trashed());

        $eval = $this->automationService->evaluateRevisionForAutoApprove($question, $rev);
        $this->assertFalse($eval['pass']);
    }
}
