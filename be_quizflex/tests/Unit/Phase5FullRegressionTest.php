<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Quiz;
use App\Models\QuestionReviewRequest;
use App\Models\QuizReviewRequest;
use App\Models\ReportTicket;
use App\Services\QuestionReviewService;
use App\Services\QuizReviewService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;

class Phase5FullRegressionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $teacher;
    protected User $student;
    protected QuestionReviewService $questionReviewService;
    protected QuizReviewService $quizReviewService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_p5_regression@quizflex.com'],
            ['name' => 'Admin P5 Tester', 'password' => bcrypt('password'), 'role' => 'admin']
        );
        $this->admin->role = 'admin';
        $this->admin->save();

        $this->teacher = User::firstOrCreate(
            ['email' => 'teacher_p5_regression@quizflex.com'],
            ['name' => 'Teacher P5 Tester', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->student = User::firstOrCreate(
            ['email' => 'student_p5_regression@quizflex.com'],
            ['name' => 'Student P5 Tester', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->questionReviewService = app(QuestionReviewService::class);
        $this->quizReviewService = app(QuizReviewService::class);
    }

    private function createValidQuestion(string $content = 'Câu hỏi hợp lệ'): Question
    {
        $question = Question::create([
            'user_id' => $this->teacher->id,
            'content' => $content,
            'type' => 'single_choice',
            'difficulty' => 'medium',
            'points' => 10,
            'is_public' => false,
            'bank_submission_status' => 'none',
        ]);

        Answer::create(['question_id' => $question->id, 'content' => 'Đáp án A đúng', 'is_correct' => true, 'order' => 1]);
        Answer::create(['question_id' => $question->id, 'content' => 'Đáp án B sai', 'is_correct' => false, 'order' => 2]);

        return $question;
    }

    private function createValidQuiz(string $title = 'Bài Quiz hợp lệ'): Quiz
    {
        $quiz = Quiz::create([
            'user_id' => $this->teacher->id,
            'title' => $title,
            'description' => 'Mô tả bài thi',
            'is_public' => false,
            'status' => 'draft',
            'review_status' => 'draft',
        ]);

        for ($i = 1; $i <= 2; $i++) {
            $q = $this->createValidQuestion("Câu hỏi {$i} của {$title}");
            $quiz->questions()->attach($q->id, ['order' => $i, 'points' => 10]);
        }

        return $quiz;
    }

    /* =========================================================================
     * QUESTION REGRESSION TESTS (TEST 1 to TEST 6)
     * ========================================================================= */

    /**
     * TEST 1: Question -> Create -> Submit -> Pending -> Review -> Approve
     */
    public function test_1_question_create_submit_pending_review_approve()
    {
        // 1. Create
        $question = $this->createValidQuestion('Câu hỏi Test 1 Regression');

        // 2. Submit
        $reviewRequest = $this->questionReviewService->submitToBank($question, $this->teacher, 'Gửi duyệt lần đầu');
        $question->refresh();
        $this->assertEquals('pending', $question->bank_submission_status);
        $this->assertEquals('pending', $reviewRequest->status);

        // 3. Review & Approve
        $approvedRev = $this->questionReviewService->approveQuestion($question, $this->admin, 'Đạt chuẩn ngân hàng');
        $question->refresh();

        $this->assertEquals('approved', $question->bank_submission_status);
        $this->assertEquals('approved', $approvedRev->status);

        $bankSnapshot = Question::where('origin_question_id', $question->id)->first();
        $this->assertNotNull($bankSnapshot);
        $this->assertTrue((bool)$bankSnapshot->is_public);
        $this->assertEquals('approved', $bankSnapshot->bank_submission_status);
    }

    /**
     * TEST 2: Question -> Create -> Submit -> Pending -> Reject
     */
    public function test_2_question_create_submit_pending_reject()
    {
        $question = $this->createValidQuestion('Câu hỏi Test 2 Regression');

        $this->questionReviewService->submitToBank($question, $this->teacher, 'Gửi duyệt lần 1');
        $this->questionReviewService->rejectQuestion($question, $this->admin, 'Nội dung chưa đủ chi tiết');

        $question->refresh();
        $this->assertEquals('rejected', $question->bank_submission_status);
        $this->assertEquals('Nội dung chưa đủ chi tiết', $question->bank_submission_note);
    }

    /**
     * TEST 3: Question -> Reject -> Edit -> Revision 2 -> Pending -> Review (Compare Rev 2 VS Rev 1)
     */
    public function test_3_question_reject_edit_revision_2_pending_compare_rev2_vs_rev1()
    {
        $question = $this->createValidQuestion('Câu hỏi Rev 1 gốc');

        // Rev 1
        $this->questionReviewService->submitToBank($question, $this->teacher);
        $this->questionReviewService->rejectQuestion($question, $this->admin, 'Sai nội dung');

        // Edit
        $question->update(['content' => 'Câu hỏi Rev 2 đã chỉnh sửa hoàn chỉnh']);

        // Rev 2
        $req2 = $this->questionReviewService->submitToBank($question, $this->teacher);
        $question->refresh();
        $this->assertEquals(2, $req2->revision_number);
        $this->assertEquals('pending', $req2->status);

        // Calculate Diff Rev 2 vs Rev 1
        $diff = $this->questionReviewService->getReviewDetailsWithDiff($question);
        $this->assertNotNull($diff['current_revision']);
        $this->assertNotNull($diff['previous_revision']);
        $this->assertEquals(2, $diff['current_revision']['revision_number']);
        $this->assertEquals('Câu hỏi Rev 2 đã chỉnh sửa hoàn chỉnh', $diff['current_revision']['content']);
        $this->assertEquals(1, $diff['previous_revision']['revision_number']);
        $this->assertEquals('Câu hỏi Rev 1 gốc', $diff['previous_revision']['content']);
    }

    /**
     * TEST 4: Question -> Revision 2 -> Reject -> Revision 3 -> Pending (Compare Rev 3 VS Rev 2)
     */
    public function test_4_question_revision_2_reject_revision_3_compare_rev3_vs_rev2()
    {
        $question = $this->createValidQuestion('Question Rev 1');

        // Rev 1
        $this->questionReviewService->submitToBank($question, $this->teacher);
        $this->questionReviewService->rejectQuestion($question, $this->admin, 'Lý do 1');

        // Rev 2
        $question->update(['content' => 'Question Rev 2']);
        $this->questionReviewService->submitToBank($question, $this->teacher);
        $this->questionReviewService->rejectQuestion($question, $this->admin, 'Lý do 2');

        // Rev 3
        $question->update(['content' => 'Question Rev 3']);
        $req3 = $this->questionReviewService->submitToBank($question, $this->teacher);
        $question->refresh();

        $this->assertEquals(3, $req3->revision_number);

        // Compare Rev 3 vs Rev 2
        $diff = $this->questionReviewService->getReviewDetailsWithDiff($question);
        $this->assertEquals(3, $diff['current_revision']['revision_number']);
        $this->assertEquals('Question Rev 3', $diff['current_revision']['content']);
        $this->assertEquals(2, $diff['previous_revision']['revision_number']);
        $this->assertEquals('Question Rev 2', $diff['previous_revision']['content']);
    }

    /**
     * TEST 5: Question -> Approved -> View Detail API
     */
    public function test_5_question_approved_view_detail()
    {
        $question = Question::create([
            'user_id' => $this->teacher->id,
            'content' => 'Câu hỏi Test 5 Approved Detail',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'points' => 10,
            'is_public' => true,
            'bank_submission_status' => 'approved',
        ]);
        Answer::create(['question_id' => $question->id, 'content' => 'A đúng', 'is_correct' => true, 'order' => 1]);
        Answer::create(['question_id' => $question->id, 'content' => 'B sai', 'is_correct' => false, 'order' => 2]);

        $quiz = Quiz::create([
            'user_id' => $this->teacher->id,
            'title' => 'Quiz chứa câu hỏi 5',
            'is_public' => true,
            'status' => 'published',
            'review_status' => 'approved',
        ]);
        $quiz->questions()->attach($question->id, ['order' => 1, 'points' => 10]);

        $response = $this->actingAs($this->admin, 'api')->getJson("/api/admin/questions/{$question->id}");
        $response->assertStatus(200);

        $data = $response->json('data') ?? $response->json();
        $this->assertEquals('approved', $data['bank_submission_status']);
        $this->assertCount(2, $data['answers']);
        $this->assertTrue($data['answers'][0]['is_correct']);
        $this->assertNotEmpty($data['using_quizzes']);
    }

    /**
     * TEST 6: Report Question -> Question review status remains intact
     */
    public function test_6_report_question_does_not_change_bank_submission_status()
    {
        $question = $this->createValidQuestion('Câu hỏi Test 6 Report Check');
        $question->bank_submission_status = 'approved';
        $question->is_public = true;
        $question->save();

        // Student reports question
        ReportTicket::create([
            'user_id' => $this->student->id,
            'question_id' => $question->id,
            'reason' => 'Sai đáp án',
            'status' => 'pending',
        ]);

        $question->refresh();
        $this->assertEquals('approved', $question->bank_submission_status);
        $this->assertTrue((bool)$question->is_public);
    }

    /* =========================================================================
     * QUIZ REGRESSION TESTS (TEST 7 to TEST 11)
     * ========================================================================= */

    /**
     * TEST 7: Quiz -> Create -> Submit -> Pending -> Approve
     */
    public function test_7_quiz_create_submit_pending_approve()
    {
        $quiz = $this->createValidQuiz('Quiz Test 7 Regression');

        // Submit
        $req = $this->quizReviewService->requestReview($quiz, $this->teacher);
        $quiz->refresh();
        $this->assertEquals('pending_review', $quiz->review_status);

        // Approve
        $this->quizReviewService->approveQuiz($quiz, $this->admin, 'Đạt tiêu chuẩn xuất bản');
        $quiz->refresh();

        $this->assertEquals('approved', $quiz->review_status);
        $this->assertTrue((bool)$quiz->is_public);
        $this->assertEquals('published', $quiz->status);
    }

    /**
     * TEST 8: Quiz -> Pending -> Reject
     */
    public function test_8_quiz_pending_reject()
    {
        $quiz = $this->createValidQuiz('Quiz Test 8 Regression');
        $this->quizReviewService->requestReview($quiz, $this->teacher);
        $this->quizReviewService->rejectQuiz($quiz, $this->admin, 'Cần bổ sung thêm câu hỏi');

        $quiz->refresh();
        $this->assertEquals('rejected', $quiz->review_status);
        $this->assertEquals('Cần bổ sung thêm câu hỏi', $quiz->rejection_reason);
    }

    /**
     * TEST 9: Quiz -> Rejected -> Calling Approve is blocked by Backend Guard (HTTP 422)
     */
    public function test_9_quiz_rejected_detail_guard_blocks_approve()
    {
        $quiz = $this->createValidQuiz('Quiz Test 9 Regression');
        $quiz->review_status = 'rejected';
        $quiz->rejection_reason = 'Bị từ chối trước đó';
        $quiz->save();

        $this->expectException(ValidationException::class);
        $this->quizReviewService->approveQuiz($quiz, $this->admin);
    }

    /**
     * TEST 10: Quiz -> Rejected -> Edit -> Revision 2 -> Pending (Compare Rev 2 VS Rev 1)
     */
    public function test_10_quiz_rejected_edit_revision_2_compare_rev2_vs_rev1()
    {
        $quiz = $this->createValidQuiz('Quiz Rev 1');
        $this->quizReviewService->requestReview($quiz, $this->teacher);
        $this->quizReviewService->rejectQuiz($quiz, $this->admin, 'Tiêu đề chưa rõ');

        // Edit
        $quiz->update(['title' => 'Quiz Rev 2 Đã Sửa Đổi']);

        // Submit Rev 2
        $rev2 = $this->quizReviewService->requestReview($quiz, $this->teacher);
        $this->assertEquals(2, $rev2->revision_number);

        $diff = $this->quizReviewService->getReviewDetailsWithDiff($quiz);
        $this->assertEquals(2, $diff['current_revision']['revision_number']);
        $this->assertEquals('Quiz Rev 2 Đã Sửa Đổi', $diff['current_revision']['title']);
        $this->assertEquals(1, $diff['previous_revision']['revision_number']);
        $this->assertEquals('Quiz Rev 1', $diff['previous_revision']['title']);
    }

    /**
     * TEST 11: Quiz -> Revision 2 -> Reject -> Revision 3 -> Pending (Compare Rev 3 VS Rev 2)
     */
    public function test_11_quiz_revision_2_reject_revision_3_compare_rev3_vs_rev2()
    {
        $quiz = $this->createValidQuiz('Quiz Rev 1');
        $this->quizReviewService->requestReview($quiz, $this->teacher);
        $this->quizReviewService->rejectQuiz($quiz, $this->admin, 'Reason 1');

        $quiz->update(['title' => 'Quiz Rev 2']);
        $this->quizReviewService->requestReview($quiz, $this->teacher);
        $this->quizReviewService->rejectQuiz($quiz, $this->admin, 'Reason 2');

        $quiz->update(['title' => 'Quiz Rev 3']);
        $rev3 = $this->quizReviewService->requestReview($quiz, $this->teacher);

        $this->assertEquals(3, $rev3->revision_number);
        $diff = $this->quizReviewService->getReviewDetailsWithDiff($quiz);
        $this->assertEquals(3, $diff['current_revision']['revision_number']);
        $this->assertEquals('Quiz Rev 3', $diff['current_revision']['title']);
        $this->assertEquals(2, $diff['previous_revision']['revision_number']);
        $this->assertEquals('Quiz Rev 2', $diff['previous_revision']['title']);
    }

    /* =========================================================================
     * REPORT REGRESSION TESTS (TEST 12 to TEST 17)
     * ========================================================================= */

    /**
     * TEST 12: Question -> 1 Report -> Report Manager
     */
    public function test_12_question_1_report_loads_in_report_manager()
    {
        $question = $this->createValidQuestion('Câu hỏi có 1 report');
        $question->is_public = true;
        $question->save();

        $ticket = ReportTicket::create([
            'user_id' => $this->student->id,
            'question_id' => $question->id,
            'reason' => 'Nội dung gây hiểu lầm',
            'status' => 'pending',
        ]);

        $res = $this->actingAs($this->admin, 'api')->getJson('/api/admin/report-tickets');
        $res->assertStatus(200);
        $this->assertNotEmpty($res->json('data'));
    }

    /**
     * TEST 13: Question -> Nhiều Report -> Group theo Question
     */
    public function test_13_question_multiple_reports_group_by_question()
    {
        $question = $this->createValidQuestion('Câu hỏi có nhiều report');
        $question->is_public = true;
        $question->save();

        ReportTicket::create(['user_id' => $this->student->id, 'question_id' => $question->id, 'reason' => 'Lý do 1', 'status' => 'pending']);
        ReportTicket::create(['user_id' => $this->teacher->id, 'question_id' => $question->id, 'reason' => 'Lý do 2', 'status' => 'pending']);

        $res = $this->actingAs($this->admin, 'api')->getJson("/api/admin/report-tickets?question_id={$question->id}");
        $res->assertStatus(200);
        $this->assertCount(2, $res->json('data'));
    }

    /**
     * TEST 14: Report -> Pending -> Resolved
     */
    public function test_14_report_pending_to_resolved()
    {
        $question = $this->createValidQuestion('Question 14');
        $question->is_public = true;
        $question->save();

        $ticket = ReportTicket::create([
            'user_id' => $this->student->id,
            'question_id' => $question->id,
            'reason' => 'Lỗi nhỏ',
            'status' => 'pending',
        ]);

        $res = $this->actingAs($this->admin, 'api')->postJson('/api/admin/report-tickets/resolve-question', [
            'question_id' => $question->id,
            'status' => 'resolved',
            'action' => 'keep',
            'admin_note' => 'Đã xử lý xong',
        ]);
        $res->assertStatus(200);
        $this->assertEquals('resolved', $ticket->fresh()->status);
    }

    /**
     * TEST 15: Report -> Pending -> Dismissed
     */
    public function test_15_report_pending_to_dismissed()
    {
        $question = $this->createValidQuestion('Question 15');
        $question->is_public = true;
        $question->save();

        $ticket = ReportTicket::create([
            'user_id' => $this->student->id,
            'question_id' => $question->id,
            'reason' => 'Báo cáo không đúng',
            'status' => 'pending',
        ]);

        $res = $this->actingAs($this->admin, 'api')->postJson('/api/admin/report-tickets/resolve-question', [
            'question_id' => $question->id,
            'status' => 'dismissed',
            'action' => 'keep',
        ]);
        $res->assertStatus(200);
        $this->assertEquals('dismissed', $ticket->fresh()->status);
    }

    /**
     * TEST 16: Report -> Resolved -> Single Ticket Update & View Check
     */
    public function test_16_report_resolved_view_check()
    {
        $question = $this->createValidQuestion('Question 16');
        $question->is_public = true;
        $question->save();

        $ticket = ReportTicket::create([
            'user_id' => $this->student->id,
            'question_id' => $question->id,
            'reason' => 'Đã giải quyết',
            'status' => 'resolved',
        ]);

        $res = $this->actingAs($this->admin, 'api')->getJson("/api/admin/report-tickets/{$ticket->id}");
        $res->assertStatus(200);
        $this->assertEquals('resolved', $res->json('data.status'));
    }

    /**
     * TEST 17: Report Detail -> NO Redirect to Question Review & Independent Operation
     */
    public function test_17_report_detail_independent_from_question_review()
    {
        $question = $this->createValidQuestion('Question 17');
        $question->is_public = true;
        $question->bank_submission_status = 'approved';
        $question->save();

        $ticket = ReportTicket::create([
            'user_id' => $this->student->id,
            'question_id' => $question->id,
            'reason' => 'Báo cáo độc lập',
            'status' => 'pending',
        ]);

        $res = $this->actingAs($this->admin, 'api')->getJson("/api/admin/report-tickets/{$ticket->id}");
        $res->assertStatus(200);

        // Verify that Question is returned for context, but Question bank submission status is untouched
        $this->assertEquals('approved', $res->json('data.question.bank_submission_status'));
    }

    /**
     * TEST 18: Question was approved, reported -> Owner edits & submits -> Revision 2 pending -> Admin approves -> Reports auto resolved
     */
    public function test_18_approved_question_reported_and_resubmitted_by_owner()
    {
        $question = $this->createValidQuestion('Question 18 Approved');
        $this->questionReviewService->submitToBank($question, $this->teacher);
        $this->questionReviewService->approveQuestion($question, $this->admin);

        // Student reports question
        $report = ReportTicket::create([
            'user_id' => $this->student->id,
            'question_id' => $question->id,
            'reason' => 'Sai đáp án',
            'status' => 'pending',
        ]);

        // Owner edits question content
        $this->actingAs($this->teacher, 'api')->putJson("/api/questions/{$question->id}", [
            'content' => 'Question 18 Đã sửa đúng chuẩn',
            'answers' => [
                ['content' => 'Đáp án A đúng đã sửa', 'is_correct' => true],
                ['content' => 'Đáp án B sai', 'is_correct' => false],
            ],
        ]);

        // Check report transitioned to author_updated
        $this->assertEquals('author_updated', $report->fresh()->status);

        // Owner submits revision 2
        $question->refresh();
        $rev2 = $this->questionReviewService->submitToBank($question, $this->teacher, 'Đã sửa');
        $this->assertEquals(2, $rev2->revision_number);
        $this->assertTrue((bool)$rev2->is_priority);

        // Admin approves revision 2
        $this->questionReviewService->approveQuestion($question, $this->admin);

        // Verify report is auto-resolved
        $this->assertEquals('resolved', $report->fresh()->status);
    }

    /**
     * TEST 19: Question pending -> Owner edits multiple times -> Resubmits -> Rev 1 superseded, Rev 2 pending
     */
    public function test_19_pending_question_edited_multiple_times_and_resubmitted()
    {
        $question = $this->createValidQuestion('Question 19 Pending');
        $rev1 = $this->questionReviewService->submitToBank($question, $this->teacher, 'Rev 1');

        // Owner edits content (Edit 1)
        $this->actingAs($this->teacher, 'api')->putJson("/api/questions/{$question->id}", [
            'content' => 'Question 19 Edit Lần 1',
            'answers' => [
                ['content' => 'A1', 'is_correct' => true],
                ['content' => 'B1', 'is_correct' => false],
            ],
        ]);

        // Owner edits content again (Edit 2) before resubmitting
        $this->actingAs($this->teacher, 'api')->putJson("/api/questions/{$question->id}", [
            'content' => 'Question 19 Edit Lần 2 Hoàn chỉnh',
            'answers' => [
                ['content' => 'A2 Đúng', 'is_correct' => true],
                ['content' => 'B2 Sai', 'is_correct' => false],
            ],
        ]);

        // Owner resubmits
        $question->refresh();
        $rev2 = $this->questionReviewService->submitToBank($question, $this->teacher, 'Rev 2 đã hoàn chỉnh');

        $this->assertEquals('superseded', $rev1->fresh()->status);
        $this->assertEquals('pending', $rev2->status);
        $this->assertEquals(2, $rev2->revision_number);

        // Verify only 1 pending request exists
        $this->assertEquals(1, QuestionReviewRequest::where('question_id', $question->id)->where('status', 'pending')->count());
    }

    /**
     * TEST 20: Question rejected -> Owner fixes & resubmits -> Rev 2 pending -> Admin rejects -> Rev 2 rejected -> Owner fixes & resubmits Rev 3
     */
    public function test_20_rejected_question_resubmitted_and_multiple_revisions_chain()
    {
        $question = $this->createValidQuestion('Question 20 Chain');
        $rev1 = $this->questionReviewService->submitToBank($question, $this->teacher, 'Rev 1');
        $this->questionReviewService->rejectQuestion($question, $this->admin, 'Từ chối Rev 1');

        // Owner edits and submits Rev 2
        $question->update(['content' => 'Question 20 Rev 2']);
        $rev2 = $this->questionReviewService->submitToBank($question, $this->teacher, 'Rev 2');
        $this->questionReviewService->rejectQuestion($question, $this->admin, 'Từ chối Rev 2');

        // Owner edits and submits Rev 3
        $question->update(['content' => 'Question 20 Rev 3 hoàn thiện']);
        $rev3 = $this->questionReviewService->submitToBank($question, $this->teacher, 'Rev 3');

        $this->assertEquals(3, $rev3->revision_number);
        $this->assertEquals('pending', $rev3->status);
        $this->assertEquals('rejected', $rev1->fresh()->status);
        $this->assertEquals('rejected', $rev2->fresh()->status);

        // History count must be 3
        $diff = $this->questionReviewService->getReviewDetailsWithDiff($question);
        $this->assertCount(3, $diff['history']);
    }
}
