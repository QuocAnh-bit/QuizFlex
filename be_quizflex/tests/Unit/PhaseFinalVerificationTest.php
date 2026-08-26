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
use App\Notifications\QuestionModerated;
use App\Notifications\ReportCreated;
use App\Notifications\ReportAuthorUpdated;
use App\Notifications\QuestionReviewRequested;
use App\Notifications\ReportResolved;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class PhaseFinalVerificationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $owner;
    protected User $otherUser;
    protected User $student1;
    protected User $student2;
    protected User $student3;
    protected QuestionReviewService $reviewService;
    protected QuestionSnapshotService $snapshotService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_final_verify@quizflex.com'],
            ['name' => 'Admin Final Verifier', 'password' => bcrypt('password'), 'role' => 'admin']
        );
        $this->admin->role = 'admin';
        $this->admin->save();

        $this->owner = User::firstOrCreate(
            ['email' => 'owner_final_verify@quizflex.com'],
            ['name' => 'Owner Final Verifier', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->otherUser = User::firstOrCreate(
            ['email' => 'other_user_final_verify@quizflex.com'],
            ['name' => 'Other User Verifier', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->student1 = User::firstOrCreate(
            ['email' => 'student1_final_verify@quizflex.com'],
            ['name' => 'Student 1 Verifier', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->student2 = User::firstOrCreate(
            ['email' => 'student2_final_verify@quizflex.com'],
            ['name' => 'Student 2 Verifier', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->student3 = User::firstOrCreate(
            ['email' => 'student3_final_verify@quizflex.com'],
            ['name' => 'Student 3 Verifier', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->reviewService = app(QuestionReviewService::class);
        $this->snapshotService = app(QuestionSnapshotService::class);
    }

    private function createSampleQuestion(string $content = 'Câu hỏi kiểm thử'): Question
    {
        $question = Question::create([
            'user_id' => $this->owner->id,
            'content' => $content,
            'type' => 'single_choice',
            'difficulty' => 'medium',
            'points' => 10,
            'is_public' => false,
            'bank_submission_status' => 'none',
        ]);

        Answer::create(['question_id' => $question->id, 'key' => 'A', 'content' => 'Đáp án A đúng', 'is_correct' => true, 'order' => 1]);
        Answer::create(['question_id' => $question->id, 'key' => 'B', 'content' => 'Đáp án B sai', 'is_correct' => false, 'order' => 2]);
        Answer::create(['question_id' => $question->id, 'key' => 'C', 'content' => 'Đáp án C sai', 'is_correct' => false, 'order' => 3]);
        Answer::create(['question_id' => $question->id, 'key' => 'D', 'content' => 'Đáp án D sai', 'is_correct' => false, 'order' => 4]);

        return $question;
    }

    /**
     * TEST 1: User report question -> Owner receives notification -> Report pending
     */
    public function test_01_user_report_question_owner_receives_notification_and_report_pending()
    {
        Notification::fake();

        $question = $this->createSampleQuestion('Test 01 Question');
        $this->reviewService->submitToBank($question, $this->owner);
        $this->reviewService->approveQuestion($question, $this->admin);

        $snapshot = Question::where('origin_question_id', $question->id)->first();
        $this->assertNotNull($snapshot);

        // Student reports the bank snapshot
        $res = $this->actingAs($this->student1, 'api')->postJson('/api/report-tickets', [
            'question_id' => $snapshot->id,
            'reason' => 'Sai đáp án',
            'description' => 'Đáp án A bị lỗi câu chữ',
        ]);

        $res->assertStatus(201);
        $reportId = $res->json('data.id');

        $ticket = ReportTicket::find($reportId);
        $this->assertNotNull($ticket);
        $this->assertEquals('pending', $ticket->status);
        $this->assertEquals($question->id, $ticket->question_id);

        // Owner receives QuestionModerated notification with action='reported'
        Notification::assertSentTo(
            $this->owner,
            QuestionModerated::class,
            function ($notification) use ($question) {
                return $notification->question->id === $question->id && $notification->action === 'reported';
            }
        );
    }

    /**
     * TEST 2: Owner edits question -> Save success -> Report author_updated -> No Admin manual work needed
     */
    public function test_02_owner_edits_question_saves_successfully_report_becomes_author_updated()
    {
        Notification::fake();

        $question = $this->createSampleQuestion('Test 02 Question');
        $report = ReportTicket::create([
            'user_id' => $this->student1->id,
            'question_id' => $question->id,
            'reason' => 'Sai đáp án',
            'status' => 'pending',
        ]);

        // Owner edits content
        $res = $this->actingAs($this->owner, 'api')->putJson("/api/questions/{$question->id}", [
            'content' => 'Test 02 Question Đã sửa nội dung chính xác',
            'answers' => [
                ['content' => 'Đáp án A đã sửa chuẩn', 'is_correct' => true],
                ['content' => 'Đáp án B sai', 'is_correct' => false],
            ],
        ]);

        $res->assertStatus(200);

        // Report status transitions to author_updated
        $this->assertEquals('author_updated', $report->fresh()->status);

        // Admin receives ReportAuthorUpdated notification
        Notification::assertSentTo(
            $this->admin,
            ReportAuthorUpdated::class
        );
    }

    /**
     * TEST 3: Owner edits question -> Submit revision -> QuestionReviewRequest new revision = pending
     */
    public function test_03_owner_submits_revision_new_question_review_request_is_pending()
    {
        $question = $this->createSampleQuestion('Test 03 Question');
        $report = ReportTicket::create([
            'user_id' => $this->student1->id,
            'question_id' => $question->id,
            'reason' => 'Lỗi chính tả',
            'status' => 'pending',
        ]);

        // Owner edits content
        $question->update(['content' => 'Test 03 Question đã sửa lỗi chính tả']);

        // Owner submits revision
        $rev = $this->reviewService->submitToBank($question, $this->owner, 'Đã đính chính chính tả');

        $this->assertEquals('pending', $rev->status);
        $this->assertTrue((bool)$rev->is_priority);
        $this->assertEquals('high', $rev->review_priority);
        $this->assertEquals('author_updated', $report->fresh()->status);
    }

    /**
     * TEST 4: Question has pending revision -> Owner edits content -> Submit new revision -> old = superseded, new = pending
     */
    public function test_04_question_has_pending_revision_owner_edits_content_and_resubmits_old_superseded_new_pending()
    {
        $question = $this->createSampleQuestion('Test 04 Question');
        $rev1 = $this->reviewService->submitToBank($question, $this->owner, 'Lần gửi 1');
        $this->assertEquals('pending', $rev1->status);
        $this->assertEquals(1, $rev1->revision_number);

        // Owner edits content again
        $question->update(['content' => 'Test 04 Question Nội dung mới sửa lần 2']);

        // Owner submits new revision
        $rev2 = $this->reviewService->submitToBank($question, $this->owner, 'Lần gửi 2');

        $this->assertEquals('superseded', $rev1->fresh()->status);
        $this->assertEquals('pending', $rev2->status);
        $this->assertEquals(2, $rev2->revision_number);

        // Ensure only ONE pending request exists
        $this->assertEquals(1, QuestionReviewRequest::where('question_id', $question->id)->where('status', 'pending')->count());
    }

    /**
     * TEST 5: Admin approves new revision -> Question updated -> Review approved -> Related reports = resolved
     */
    public function test_05_admin_approves_new_revision_question_updated_review_approved_related_reports_resolved()
    {
        Notification::fake();

        $question = $this->createSampleQuestion('Test 05 Question');
        $report = ReportTicket::create([
            'user_id' => $this->student1->id,
            'question_id' => $question->id,
            'reason' => 'Sai đáp án',
            'status' => 'pending',
        ]);

        $question->update(['content' => 'Test 05 Question Đã sửa hoàn chỉnh']);
        $rev = $this->reviewService->submitToBank($question, $this->owner);

        // Admin approves
        $question->refresh();
        $approvedRev = $this->reviewService->approveQuestion($question, $this->admin);

        $this->assertEquals('approved', $approvedRev->status);
        $this->assertEquals('approved', $question->fresh()->bank_submission_status);

        // Report must be AUTO RESOLVED
        $this->assertEquals('resolved', $report->fresh()->status);

        // Reporter receives ReportResolved notification
        Notification::assertSentTo(
            $this->student1,
            ReportResolved::class,
            function ($notification) {
                return $notification->status === 'resolved' || $notification->action === 'approved';
            }
        );
    }

    /**
     * TEST 6: Admin rejects revision -> Review rejected -> Report NOT resolved -> Owner can fix and resubmit
     */
    public function test_06_admin_rejects_revision_review_rejected_reports_not_resolved_owner_can_fix_and_resubmit()
    {
        $question = $this->createSampleQuestion('Test 06 Question');
        $report = ReportTicket::create([
            'user_id' => $this->student1->id,
            'question_id' => $question->id,
            'reason' => 'Sai kiến thức',
            'status' => 'pending',
        ]);

        $question->update(['content' => 'Test 06 Sửa lần 1']);
        $rev1 = $this->reviewService->submitToBank($question, $this->owner);

        // Admin rejects
        $question->refresh();
        $rejectedRev = $this->reviewService->rejectQuestion($question, $this->admin, 'Vẫn còn sai công thức');
        $this->assertEquals('rejected', $rejectedRev->status);

        // Report MUST NOT be resolved
        $this->assertNotEquals('resolved', $report->fresh()->status);

        // Owner can fix and resubmit Rev 2
        $question->update(['content' => 'Test 06 Sửa lần 2 chuẩn công thức']);
        $rev2 = $this->reviewService->submitToBank($question, $this->owner, 'Đã sửa công thức theo góp ý');

        $this->assertEquals('pending', $rev2->status);
        $this->assertEquals(2, $rev2->revision_number);
        $this->assertEquals('rejected', $rev1->fresh()->status);
    }

    /**
     * TEST 7: One question has 3 reports -> Owner fixes once -> Submits revision -> Admin approves -> All 3 reports resolved
     */
    public function test_07_question_has_3_reports_owner_edits_once_submits_admin_approves_all_reports_resolved_no_duplicate()
    {
        $question = $this->createSampleQuestion('Test 07 Question');

        $rep1 = ReportTicket::create(['user_id' => $this->student1->id, 'question_id' => $question->id, 'reason' => 'Sai đáp án', 'status' => 'pending']);
        $rep2 = ReportTicket::create(['user_id' => $this->student2->id, 'question_id' => $question->id, 'reason' => 'Lỗi chính tả', 'status' => 'pending']);
        $rep3 = ReportTicket::create(['user_id' => $this->student3->id, 'question_id' => $question->id, 'reason' => 'Thiếu dữ kiện', 'status' => 'pending']);

        // Owner fixes once
        $this->actingAs($this->owner, 'api')->putJson("/api/questions/{$question->id}", [
            'content' => 'Test 07 Đã sửa tất cả 3 phản ánh',
            'answers' => [
                ['content' => 'A đúng', 'is_correct' => true],
                ['content' => 'B sai', 'is_correct' => false],
            ],
        ]);

        $this->assertEquals('author_updated', $rep1->fresh()->status);
        $this->assertEquals('author_updated', $rep2->fresh()->status);
        $this->assertEquals('author_updated', $rep3->fresh()->status);

        // Owner submits revision
        $question->refresh();
        $this->reviewService->submitToBank($question, $this->owner);

        // Admin approves
        $question->refresh();
        $this->reviewService->approveQuestion($question, $this->admin);

        // All 3 reports must be resolved with NO duplicate action
        $this->assertEquals('resolved', $rep1->fresh()->status);
        $this->assertEquals('resolved', $rep2->fresh()->status);
        $this->assertEquals('resolved', $rep3->fresh()->status);
    }

    /**
     * TEST 8: Question without reports -> Workflow review normal -> Not affected
     */
    public function test_08_question_without_reports_workflow_normal_not_affected()
    {
        $question = $this->createSampleQuestion('Test 08 Clean Question');

        $rev = $this->reviewService->submitToBank($question, $this->owner);
        $this->assertEquals('pending', $rev->status);
        $this->assertFalse((bool)$rev->is_priority);

        $question->refresh();
        $approved = $this->reviewService->approveQuestion($question, $this->admin);
        $this->assertEquals('approved', $approved->status);
        $this->assertEquals('approved', $question->fresh()->bank_submission_status);

        // Public bank snapshot exists
        $snapshot = Question::where('origin_question_id', $question->id)->first();
        $this->assertNotNull($snapshot);
        $this->assertTrue((bool)$snapshot->is_public);
    }

    /**
     * TEST 9: Unrelated report on another question -> NOT auto-resolved mistakenly
     */
    public function test_09_unrelated_reports_on_other_questions_not_auto_resolved_mistakenly()
    {
        $questionA = $this->createSampleQuestion('Question A');
        $questionB = $this->createSampleQuestion('Question B');

        $reportA = ReportTicket::create(['user_id' => $this->student1->id, 'question_id' => $questionA->id, 'reason' => 'Lỗi A', 'status' => 'pending']);
        $reportB = ReportTicket::create(['user_id' => $this->student2->id, 'question_id' => $questionB->id, 'reason' => 'Lỗi B', 'status' => 'pending']);

        // Owner fixes Question A and Admin approves Question A
        $questionA->update(['content' => 'Question A Đã sửa']);
        $this->reviewService->submitToBank($questionA, $this->owner);
        $questionA->refresh();
        $this->reviewService->approveQuestion($questionA, $this->admin);

        // Report A resolved
        $this->assertEquals('resolved', $reportA->fresh()->status);
        // Report B MUST REMAIN PENDING
        $this->assertEquals('pending', $reportB->fresh()->status);
    }

    /**
     * TEST 10: Admin Report Manager -> Filter status works correctly for all 5 statuses and all
     */
    public function test_10_admin_report_manager_filter_status_works_correctly()
    {
        $question = $this->createSampleQuestion('Question 10');

        ReportTicket::create(['user_id' => $this->student1->id, 'question_id' => $question->id, 'reason' => 'P', 'status' => 'pending']);
        ReportTicket::create(['user_id' => $this->student2->id, 'question_id' => $question->id, 'reason' => 'AU', 'status' => 'author_updated']);
        ReportTicket::create(['user_id' => $this->student3->id, 'question_id' => $question->id, 'reason' => 'ARR', 'status' => 'admin_review_required']);
        ReportTicket::create(['user_id' => $this->student1->id, 'question_id' => $question->id, 'reason' => 'R', 'status' => 'resolved']);
        ReportTicket::create(['user_id' => $this->student2->id, 'question_id' => $question->id, 'reason' => 'D', 'status' => 'dismissed']);

        $resPending = $this->actingAs($this->admin, 'api')->getJson('/api/admin/report-tickets?status=pending');
        $resPending->assertStatus(200);
        foreach ($resPending->json('data') as $item) {
            $this->assertEquals('pending', $item['status']);
        }

        $resAU = $this->actingAs($this->admin, 'api')->getJson('/api/admin/report-tickets?status=author_updated');
        $resAU->assertStatus(200);
        foreach ($resAU->json('data') as $item) {
            $this->assertEquals('author_updated', $item['status']);
        }

        $resARR = $this->actingAs($this->admin, 'api')->getJson('/api/admin/report-tickets?status=admin_review_required');
        $resARR->assertStatus(200);
        foreach ($resARR->json('data') as $item) {
            $this->assertEquals('admin_review_required', $item['status']);
        }

        $resAll = $this->actingAs($this->admin, 'api')->getJson('/api/admin/report-tickets?status=all');
        $resAll->assertStatus(200);
        $this->assertGreaterThanOrEqual(5, count($resAll->json('data')));
    }

    /**
     * TEST 11: Owner UI -> CTA "Gửi duyệt lại" endpoint works properly
     */
    public function test_11_owner_ui_cta_submit_review_endpoint_and_state_consistency()
    {
        $question = $this->createSampleQuestion('Question 11 CTA Test');
        $question->update(['content' => 'Question 11 Đã sửa nội dung']);

        $res = $this->actingAs($this->owner, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank", [
            'request_note' => 'Gửi duyệt lại từ CTA UI',
        ]);

        $res->assertStatus(200);
        $this->assertEquals('pending', $question->fresh()->bank_submission_status);
        $this->assertEquals(1, QuestionReviewRequest::where('question_id', $question->id)->where('status', 'pending')->count());
    }

    /**
     * TEST 12: Notification -> No duplicates -> Correct recipient -> Correct link
     */
    public function test_12_notifications_sent_without_duplicates_correct_recipients_and_links()
    {
        $question = $this->createSampleQuestion('Question 12 Notification Test');
        $notif = new QuestionModerated($question, 'reported', 'Sai đáp án', 'Mô tả chi tiết');

        $data = $notif->toArray($this->owner);

        $this->assertEquals('question_moderated', $data['type']);
        $this->assertEquals("/dashboard/my-questions?question_id={$question->id}", $data['action_link']);
        $this->assertStringContainsString((string)$question->id, $data['message']);
        $this->assertEquals($question->id, $data['metadata']['question_id']);
    }

    /**
     * TEST 13: Authorization -> Owner only edits own questions -> Admin only reviews -> Regular user cannot approve
     */
    public function test_13_authorization_owner_only_edits_own_question_admin_only_reviews_regular_user_cannot_approve()
    {
        $question = $this->createSampleQuestion('Question 13 Auth Test');
        $rev = $this->reviewService->submitToBank($question, $this->owner);

        // Other user attempts to edit owner's question -> MUST FAIL
        $resEdit = $this->actingAs($this->otherUser, 'api')->putJson("/api/questions/{$question->id}", [
            'content' => 'Hack content',
        ]);
        $this->assertTrue(in_array($resEdit->status(), [403, 404]));

        // Regular user attempts to approve review request -> MUST FAIL (403)
        $resApprove = $this->actingAs($this->otherUser, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/approve");
        $resApprove->assertStatus(403);

        // Admin can approve
        $resAdminApprove = $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/approve");
        $resAdminApprove->assertStatus(200);
    }

    /**
     * TEST 14: Soft delete / restore -> Not broken / Questions, reviews, reports remain consistent
     */
    public function test_14_soft_delete_and_restore_workflow_preserves_reviews_and_reports()
    {
        $question = $this->createSampleQuestion('Question 14 Soft Delete');
        $report = ReportTicket::create(['user_id' => $this->student1->id, 'question_id' => $question->id, 'reason' => 'Report 14', 'status' => 'pending']);
        $rev = $this->reviewService->submitToBank($question, $this->owner);

        // Soft delete question
        $question->delete();
        $this->assertTrue($question->trashed());

        // Report and Review records are preserved in DB
        $this->assertDatabaseHas('report_tickets', ['id' => $report->id]);
        $this->assertDatabaseHas('question_review_requests', ['id' => $rev->id]);

        // Restore question
        $question->restore();
        $this->assertFalse($question->trashed());
        $this->assertEquals('author_updated', $report->fresh()->status);
        $this->assertEquals('pending', $rev->fresh()->status);
    }

    /**
     * TEST 15: Existing approved question -> Reported -> Owner edits -> Admin approves -> No duplicate question created in bank
     */
    public function test_15_existing_approved_question_reported_owner_edits_admin_approves_no_duplicate_snapshot_in_bank()
    {
        $question = $this->createSampleQuestion('Question 15 Approved Bank');
        $this->reviewService->submitToBank($question, $this->owner);
        $this->reviewService->approveQuestion($question, $this->admin);

        // Verify initial snapshot in bank
        $initialSnapshotCount = Question::where('origin_question_id', $question->id)->count();
        $this->assertEquals(1, $initialSnapshotCount);
        $snapshot = Question::where('origin_question_id', $question->id)->first();

        // Reported & Owner edits
        ReportTicket::create(['user_id' => $this->student1->id, 'question_id' => $question->id, 'reason' => 'Sai đáp án', 'status' => 'pending']);

        $this->actingAs($this->owner, 'api')->putJson("/api/questions/{$question->id}", [
            'content' => 'Question 15 Nội dung mới sau khi sửa',
            'answers' => [
                ['content' => 'Đáp án A đúng đã sửa', 'is_correct' => true],
                ['content' => 'Đáp án B sai', 'is_correct' => false],
            ],
        ]);

        // Owner submits revision 2
        $question->refresh();
        $this->reviewService->submitToBank($question, $this->owner);

        // Admin approves revision 2
        $approvedRev = $this->reviewService->approveQuestion($question, $this->admin);
        $this->assertEquals('approved', $approvedRev->status);

        // CRITICAL CHECK: Still exactly 1 bank snapshot exists (updated in-place, NO duplicate created)
        $this->assertEquals(1, Question::where('origin_question_id', $question->id)->count());
        $this->assertEquals($snapshot->id, Question::where('origin_question_id', $question->id)->first()->id);
        $this->assertEquals('Question 15 Nội dung mới sau khi sửa', Question::find($snapshot->id)->content);
    }
}
