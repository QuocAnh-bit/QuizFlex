<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Quiz;
use App\Models\QuestionReviewRequest;
use App\Models\ReportTicket;
use App\Services\QuestionReviewService;
use App\Services\QuestionSnapshotService;
use App\Services\ReportAutomationService;
use App\Notifications\QuestionModerated;
use App\Notifications\QuestionReviewRequested;
use App\Notifications\ReportCreated;
use App\Notifications\ReportResolved;
use App\Http\Controllers\ReportTicketController;
use App\Http\Controllers\QuestionController;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class Phase6ComprehensiveWorkflowIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $author;
    protected User $student1;
    protected User $student2;
    protected User $stranger;

    protected QuestionReviewService $reviewService;
    protected QuestionSnapshotService $snapshotService;
    protected ReportAutomationService $automationService;
    protected ReportTicketController $reportController;
    protected QuestionController $questionController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_p6_full@quizflex.com'],
            ['name' => 'Admin P6 Tester', 'password' => bcrypt('password'), 'role' => 'admin']
        );
        $this->admin->role = 'admin';
        $this->admin->save();

        $this->author = User::firstOrCreate(
            ['email' => 'author_p6_full@quizflex.com'],
            ['name' => 'Author P6 Tester', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->student1 = User::firstOrCreate(
            ['email' => 'student1_p6_full@quizflex.com'],
            ['name' => 'Student 1 P6 Tester', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->student2 = User::firstOrCreate(
            ['email' => 'student2_p6_full@quizflex.com'],
            ['name' => 'Student 2 P6 Tester', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->stranger = User::firstOrCreate(
            ['email' => 'stranger_p6_full@quizflex.com'],
            ['name' => 'Stranger P6 Tester', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->reviewService = app(QuestionReviewService::class);
        $this->snapshotService = app(QuestionSnapshotService::class);
        $this->automationService = app(ReportAutomationService::class);
        $this->reportController = app(ReportTicketController::class);
        $this->questionController = app(QuestionController::class);
    }

    private function createSampleApprovedQuestion(string $content = 'Câu hỏi gốc P6 Test'): Question
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

    private function createReportWithAge(Question $question, int $daysAgo, string $reason = 'Sai đáp án', string $status = 'pending', ?Carbon $reminderAt = null, ?Carbon $warningAt = null): ReportTicket
    {
        $report = new ReportTicket([
            'user_id' => $this->student1->id,
            'question_id' => $question->id,
            'reason' => $reason,
            'status' => $status,
            'reminder_sent_at' => $reminderAt,
            'warning_sent_at' => $warningAt,
        ]);
        $report->timestamps = false;
        $report->created_at = Carbon::now()->subDays($daysAgo);
        $report->updated_at = Carbon::now()->subDays($daysAgo);
        $report->save();

        return $report;
    }

    public function test_group_a_normal_report_complete_workflow()
    {
        $question = $this->createSampleApprovedQuestion('Group A Question');
        $bankSnapshot = $this->snapshotService->findBankSnapshotByOriginId($question->id);
        $this->assertTrue((bool)$bankSnapshot->is_public);

        Notification::fake();

        $req = new Request([
            'question_id' => $bankSnapshot->id,
            'reason' => 'Lỗi chính tả câu hỏi',
            'description' => 'Chữ cái đầu câu bị viết sai',
        ]);
        $req->setUserResolver(fn() => $this->student1);
        $res = $this->reportController->store($req);
        $this->assertEquals(201, $res->getStatusCode());

        $report = ReportTicket::where('question_id', $question->id)->latest('id')->first();
        $this->assertEquals(ReportTicket::STATUS_PENDING, $report->status);

        Notification::assertSentTo($this->author, QuestionModerated::class, function ($n) {
            return $n->action === 'reported';
        });

        $question->update(['content' => 'Group A Question đã sửa lỗi chính tả']);

        $rev = $this->reviewService->submitToBank($question, $this->author);
        $this->assertEquals('approved', $rev->status);
        $this->assertTrue((bool)($rev->snapshot_metadata['auto_approved'] ?? false));

        $this->assertEquals(ReportTicket::STATUS_RESOLVED, $report->fresh()->status);
        $this->assertTrue((bool)$bankSnapshot->fresh()->is_public);

        Notification::assertSentTo($this->student1, ReportResolved::class);
    }

    public function test_group_b_auto_review_fail_complete_workflow()
    {
        $question = $this->createSampleApprovedQuestion('Group B Question');
        $report = $this->createReportWithAge($question, 1, 'Lỗi đáp án sai');

        Notification::fake();

        Answer::where('question_id', $question->id)->update(['content' => 'Đáp án giống nhau']);

        $rev = $this->reviewService->submitToBank($question, $this->author);

        $this->assertEquals('pending', $rev->status);
        $this->assertTrue((bool)$rev->is_priority);

        $this->assertEquals(ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED, $report->fresh()->status);

        Notification::assertSentTo($this->admin, QuestionReviewRequested::class, function ($n) {
            return $n->isPriority === true;
        });

        Notification::assertNotSentTo($this->author, QuestionModerated::class, function ($n) {
            return $n->action === 'approved';
        });
    }

    public function test_group_c_critical_report_complete_workflow()
    {
        $question = $this->createSampleApprovedQuestion('Group C Question');
        $bankSnapshot = $this->snapshotService->findBankSnapshotByOriginId($question->id);

        Notification::fake();

        $req = new Request([
            'question_id' => $bankSnapshot->id,
            'reason' => 'Vi phạm bản quyền nghiêm trọng',
            'description' => 'Sao chép nguyên văn sách giáo khoa',
        ]);
        $req->setUserResolver(fn() => $this->student1);
        $this->reportController->store($req);

        $report = ReportTicket::where('question_id', $question->id)->latest('id')->first();

        $this->assertEquals(ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED, $report->status);

        Notification::assertSentTo($this->admin, ReportCreated::class);

        $question->update(['content' => 'Group C Question Nội Dung Mới']);
        $rev = $this->reviewService->submitToBank($question, $this->author);
        $this->assertEquals('pending', $rev->status);
    }

    public function test_group_d_no_author_action_lifecycle()
    {
        $question = $this->createSampleApprovedQuestion('Group D Question');
        $bankSnapshot = $this->snapshotService->findBankSnapshotByOriginId($question->id);

        $report = $this->createReportWithAge($question, 0);

        Notification::fake();
        $report->timestamps = false;
        $report->created_at = Carbon::now()->subDays(3);
        $report->save();
        $this->automationService->processLifecycleRemindersAndAutoPrivate(Carbon::now());

        $this->assertNotNull($report->fresh()->reminder_sent_at);
        Notification::assertSentTo($this->author, QuestionModerated::class, function ($n) {
            return $n->action === 'reminder';
        });

        Notification::fake();
        $report->timestamps = false;
        $report->created_at = Carbon::now()->subDays(5);
        $report->save();
        $this->automationService->processLifecycleRemindersAndAutoPrivate(Carbon::now());

        $this->assertNotNull($report->fresh()->warning_sent_at);
        Notification::assertSentTo($this->author, QuestionModerated::class, function ($n) {
            return $n->action === 'warning';
        });

        Notification::fake();
        $report->timestamps = false;
        $report->created_at = Carbon::now()->subDays(7);
        $report->save();
        $this->automationService->processLifecycleRemindersAndAutoPrivate(Carbon::now());

        $this->assertNotNull($report->fresh()->auto_privatized_at);
        $this->assertFalse((bool)$bankSnapshot->fresh()->is_public);
        Notification::assertSentTo($this->author, QuestionModerated::class, function ($n) {
            return $n->action === 'auto_privatized';
        });
    }

    public function test_group_e_author_fixes_before_deadline()
    {
        $question = $this->createSampleApprovedQuestion('Group E Question');
        $bankSnapshot = $this->snapshotService->findBankSnapshotByOriginId($question->id);

        $report = $this->createReportWithAge($question, 2);

        Notification::fake();

        $question->update(['content' => 'Group E Question Đã sửa hợp lệ']);
        $rev = $this->reviewService->submitToBank($question, $this->author);

        $this->assertEquals('approved', $rev->status);
        $this->assertEquals(ReportTicket::STATUS_RESOLVED, $report->fresh()->status);

        $res = $this->automationService->processLifecycleRemindersAndAutoPrivate(Carbon::now()->addDays(5));

        $this->assertEquals(0, $res['auto_privatized']);
        $this->assertTrue((bool)$bankSnapshot->fresh()->is_public);
    }

    public function test_group_f_already_resolved_guard_flow()
    {
        $question = $this->createSampleApprovedQuestion('Group F Question');
        $bankSnapshot = $this->snapshotService->findBankSnapshotByOriginId($question->id);

        $report = $this->createReportWithAge($question, 10, 'Sai nhẹ', ReportTicket::STATUS_RESOLVED);

        Notification::fake();

        $res = $this->automationService->processLifecycleRemindersAndAutoPrivate(Carbon::now());

        $this->assertEquals(0, $res['auto_privatized']);
        $this->assertEquals(0, $res['reminders_sent']);
        $this->assertTrue((bool)$bankSnapshot->fresh()->is_public);

        Notification::assertNothingSent();
    }

    public function test_group_g_already_private_guard_flow()
    {
        $question = $this->createSampleApprovedQuestion('Group G Question');
        $bankSnapshot = $this->snapshotService->findBankSnapshotByOriginId($question->id);
        $bankSnapshot->update(['is_public' => false]);
        $question->update(['is_public' => false]);

        $report = $this->createReportWithAge($question, 7);

        $res = $this->automationService->processLifecycleRemindersAndAutoPrivate(Carbon::now());

        $this->assertEquals(1, $res['auto_privatized']);
        $this->assertNotNull($report->fresh()->auto_privatized_at);
        $this->assertFalse((bool)$bankSnapshot->fresh()->is_public);
    }

    public function test_group_h_admin_is_handling_exclusion_flow()
    {
        $question = $this->createSampleApprovedQuestion('Group H Question');
        $bankSnapshot = $this->snapshotService->findBankSnapshotByOriginId($question->id);

        $report = $this->createReportWithAge($question, 10, 'Nội dung phản cảm', ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED);

        Notification::fake();

        $res = $this->automationService->processLifecycleRemindersAndAutoPrivate(Carbon::now());

        $this->assertEquals(0, $res['auto_privatized']);
        $this->assertNull($report->fresh()->auto_privatized_at);
        $this->assertTrue((bool)$bankSnapshot->fresh()->is_public);

        Notification::assertNothingSent();
    }

    public function test_group_i_multiple_reports_consolidated_resolution()
    {
        $question = $this->createSampleApprovedQuestion('Group I Question');

        $report1 = $this->createReportWithAge($question, 2, 'Lỗi chính tả 1');
        $report2 = $this->createReportWithAge($question, 1, 'Lỗi chính tả 2');

        $question->update(['content' => 'Group I Question Đã Đính Chính']);
        $rev = $this->reviewService->submitToBank($question, $this->author);

        $this->assertEquals('approved', $rev->status);

        $this->assertEquals(ReportTicket::STATUS_RESOLVED, $report1->fresh()->status);
        $this->assertEquals(ReportTicket::STATUS_RESOLVED, $report2->fresh()->status);
    }

    public function test_group_j_duplicate_execution_idempotency()
    {
        $question = $this->createSampleApprovedQuestion('Group J Question');
        $report = $this->createReportWithAge($question, 7);

        Notification::fake();

        $res1 = $this->automationService->processLifecycleRemindersAndAutoPrivate(Carbon::now());
        $this->assertEquals(1, $res1['auto_privatized']);

        $res2 = $this->automationService->processLifecycleRemindersAndAutoPrivate(Carbon::now());
        $this->assertEquals(0, $res2['auto_privatized']);
        $this->assertEquals(0, $res2['reminders_sent']);
        $this->assertEquals(0, $res2['warnings_sent']);

        Notification::assertSentTimes(QuestionModerated::class, 1);
    }

    public function test_group_k_authorization_policies()
    {
        $question = $this->createSampleApprovedQuestion('Group K Question');

        $this->expectException(\Exception::class);
        $this->reviewService->submitToBank($question, $this->stranger);
    }

    public function test_group_l_regression_core_modules()
    {
        $quiz = Quiz::create([
            'user_id' => $this->author->id,
            'title' => 'Test Quiz Regression L',
            'status' => 'published',
            'is_public' => true,
        ]);

        $question = $this->createSampleApprovedQuestion('Question Regression L');
        $quiz->questions()->attach($question->id, ['order' => 1, 'points' => 10]);

        $this->assertCount(1, $quiz->fresh()->questions);

        $report = $this->createReportWithAge($question, 1, 'Lỗi tìm kiếm test');

        $req = new Request(['search' => 'Regression L']);
        $indexRes = $this->reportController->index($req);
        $data = $indexRes->getData(true);

        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['data']);
    }
}
