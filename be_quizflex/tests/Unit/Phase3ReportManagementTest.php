<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Quiz;
use App\Models\ReportTicket;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class Phase3ReportManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $author;
    protected User $reporter1;
    protected User $reporter2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_report_test@quizflex.com'],
            ['name' => 'Admin Report Tester', 'password' => bcrypt('password'), 'role' => 'admin']
        );
        $this->admin->role = 'admin';
        $this->admin->save();

        $this->author = User::firstOrCreate(
            ['email' => 'author_report_test@quizflex.com'],
            ['name' => 'Author Report Tester', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->reporter1 = User::firstOrCreate(
            ['email' => 'reporter1_test@quizflex.com'],
            ['name' => 'Reporter 1', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->reporter2 = User::firstOrCreate(
            ['email' => 'reporter2_test@quizflex.com'],
            ['name' => 'Reporter 2', 'password' => bcrypt('password'), 'role' => 'free']
        );
    }

    private function createSampleQuestionWithQuiz(string $content = 'Câu hỏi có báo cáo vi phạm'): array
    {
        $question = Question::create([
            'user_id' => $this->author->id,
            'content' => $content,
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'points' => 10,
            'is_public' => true,
            'bank_submission_status' => 'approved',
        ]);

        Answer::create(['question_id' => $question->id, 'content' => 'Đáp án 1 đúng', 'is_correct' => true, 'order' => 1]);
        Answer::create(['question_id' => $question->id, 'content' => 'Đáp án 2 sai', 'is_correct' => false, 'order' => 2]);

        $quiz = Quiz::create([
            'user_id' => $this->author->id,
            'title' => 'Đề thi chứa câu hỏi báo cáo',
            'is_public' => true,
            'status' => 'published',
            'review_status' => 'approved',
        ]);

        $quiz->questions()->attach($question->id, ['order' => 1, 'points' => 10]);

        return [$question, $quiz];
    }

    /**
     * Test A & B: Question có 1 report và Question có nhiều report
     */
    public function test_a_and_b_create_single_and_multiple_reports_for_question()
    {
        [$question, $quiz] = $this->createSampleQuestionWithQuiz('Question A-B Test');

        // Reporter 1 gửi báo cáo
        $ticket1 = ReportTicket::create([
            'user_id' => $this->reporter1->id,
            'question_id' => $question->id,
            'reason' => 'Sai đáp án',
            'description' => 'Đáp án A không chính xác theo SGK',
            'status' => 'pending',
        ]);

        // Reporter 2 gửi báo cáo cùng câu hỏi
        $ticket2 = ReportTicket::create([
            'user_id' => $this->reporter2->id,
            'question_id' => $question->id,
            'reason' => 'Nội dung chưa rõ ràng',
            'description' => 'Đề bài bị thiếu dữ kiện',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('report_tickets', ['id' => $ticket1->id, 'status' => 'pending']);
        $this->assertDatabaseHas('report_tickets', ['id' => $ticket2->id, 'status' => 'pending']);
        $this->assertEquals(2, $question->reports()->count());
    }

    /**
     * Test C: Report List API loads WITHOUT ERROR (No using_quizzes relationship error)
     */
    public function test_c_report_list_api_loads_successfully_without_using_quizzes_error()
    {
        [$question, $quiz] = $this->createSampleQuestionWithQuiz('Question C Test');

        ReportTicket::create([
            'user_id' => $this->reporter1->id,
            'question_id' => $question->id,
            'reason' => 'Sai kiến thức',
            'description' => 'Cần xem lại công thức',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'api')->getJson('/api/admin/report-tickets');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'reason',
                    'description',
                    'status',
                    'user',
                    'question' => [
                        'id',
                        'content',
                        'answers',
                        'quizzes',
                    ]
                ]
            ],
            'stats' => [
                'total',
                'pending',
                'resolved',
                'dismissed',
                'questions_count',
            ]
        ]);
    }

    /**
     * Test D, E & F: Report Detail API loads Question, Answers (with is_correct), and Related Quizzes
     */
    public function test_d_e_f_report_detail_loads_question_answers_and_related_quizzes()
    {
        [$question, $quiz] = $this->createSampleQuestionWithQuiz('Question D-E-F Test');

        $ticket = ReportTicket::create([
            'user_id' => $this->reporter1->id,
            'question_id' => $question->id,
            'reason' => 'Lỗi chính tả',
            'description' => 'Sai chính tả ở câu hỏi',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'api')->getJson("/api/admin/report-tickets/{$ticket->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals($ticket->id, $data['id']);
        $this->assertEquals($question->id, $data['question']['id']);
        $this->assertCount(2, $data['question']['answers']);

        // Verify correct answer badge
        $correctAnswers = array_filter($data['question']['answers'], fn($a) => !empty($a['is_correct']));
        $this->assertCount(1, $correctAnswers);

        // Verify related quizzes exist
        $this->assertNotEmpty($data['question']['quizzes']);
        $this->assertEquals($quiz->id, $data['question']['quizzes'][0]['id']);
    }

    /**
     * Test G & K: Pending -> Resolve Question Reports (Independent from Question Review Status)
     */
    public function test_g_and_k_resolve_question_reports_independent_from_bank_submission_status()
    {
        [$question, $quiz] = $this->createSampleQuestionWithQuiz('Question G-K Test');

        $t1 = ReportTicket::create(['user_id' => $this->reporter1->id, 'question_id' => $question->id, 'reason' => 'R1', 'status' => 'pending']);
        $t2 = ReportTicket::create(['user_id' => $this->reporter2->id, 'question_id' => $question->id, 'reason' => 'R2', 'status' => 'pending']);

        // Admin resolves report and keeps question
        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/report-tickets/resolve-question', [
            'question_id' => $question->id,
            'status' => 'resolved',
            'action' => 'keep',
            'admin_note' => 'Đã xác minh câu hỏi đạt chuẩn',
        ]);

        $response->assertStatus(200);

        // Check report tickets are now resolved
        $this->assertEquals('resolved', $t1->fresh()->status);
        $this->assertEquals('resolved', $t2->fresh()->status);

        // CRITICAL CHECK: Question review status is NOT altered
        $question->refresh();
        $this->assertEquals('approved', $question->bank_submission_status);
        $this->assertTrue((bool)$question->is_public);
    }

    /**
     * Test H: Pending -> Dismissed
     */
    public function test_h_dismiss_question_reports()
    {
        [$question, $quiz] = $this->createSampleQuestionWithQuiz('Question H Test');

        $t = ReportTicket::create(['user_id' => $this->reporter1->id, 'question_id' => $question->id, 'reason' => 'Báo cáo spam', 'status' => 'pending']);

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/report-tickets/resolve-question', [
            'question_id' => $question->id,
            'status' => 'dismissed',
            'action' => 'keep',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('dismissed', $t->fresh()->status);
    }

    /**
     * Test I: Single Ticket status update (Pending -> Dismissed & Pending -> Resolved)
     */
    public function test_i_single_ticket_status_update_workflow()
    {
        [$question, $quiz] = $this->createSampleQuestionWithQuiz('Question I Test');

        $ticket1 = ReportTicket::create(['user_id' => $this->reporter1->id, 'question_id' => $question->id, 'reason' => 'Cần xem xét 1', 'status' => 'pending']);
        $ticket2 = ReportTicket::create(['user_id' => $this->reporter2->id, 'question_id' => $question->id, 'reason' => 'Cần xem xét 2', 'status' => 'pending']);

        // 1. Move to dismissed
        $res1 = $this->actingAs($this->admin, 'api')->patchJson("/api/admin/report-tickets/{$ticket1->id}/status", [
            'status' => 'dismissed',
        ]);
        $res1->assertStatus(200);
        $this->assertEquals('dismissed', $ticket1->fresh()->status);

        // 2. Move to resolved
        $res2 = $this->actingAs($this->admin, 'api')->patchJson("/api/admin/report-tickets/{$ticket2->id}/status", [
            'status' => 'resolved',
        ]);
        $res2->assertStatus(200);
        $this->assertEquals('resolved', $ticket2->fresh()->status);
    }

    /**
     * Test L: Full End-to-End Workflow:
     * User Report (pending) -> Author Fix & Submit (author_updated) -> Admin Approve Revision -> Reports Auto Resolved
     */
    public function test_l_end_to_end_report_to_author_fix_and_admin_approve_auto_resolves_reports()
    {
        $reviewService = app(\App\Services\QuestionReviewService::class);

        // 1. Author creates question and submits to bank
        [$question, $quiz] = $this->createSampleQuestionWithQuiz('Câu hỏi chuẩn bị báo cáo');
        $question->update(['bank_submission_status' => 'none', 'is_public' => false]);

        $rev1 = $reviewService->submitToBank($question, $this->author, 'Gửi lần đầu');
        $reviewService->approveQuestion($question, $this->admin);

        // 2. User 1 and User 2 report the question
        $report1 = ReportTicket::create([
            'user_id' => $this->reporter1->id,
            'question_id' => $question->id,
            'reason' => 'Sai đáp án C',
            'description' => 'Đáp án C bị nhầm dấu âm',
            'status' => 'pending',
        ]);

        $report2 = ReportTicket::create([
            'user_id' => $this->reporter2->id,
            'question_id' => $question->id,
            'reason' => 'Hình ảnh mờ',
            'description' => 'Không đọc được số liệu trên hình',
            'status' => 'pending',
        ]);

        // Unrelated report for another question
        $unrelatedQuestion = Question::create([
            'user_id' => $this->author->id,
            'content' => 'Câu hỏi không liên quan',
            'type' => 'single_choice',
            'points' => 10,
            'is_public' => false,
            'bank_submission_status' => 'none',
        ]);
        Answer::create(['question_id' => $unrelatedQuestion->id, 'content' => 'A', 'is_correct' => true]);
        Answer::create(['question_id' => $unrelatedQuestion->id, 'content' => 'B', 'is_correct' => false]);
        $unrelatedReport = ReportTicket::create([
            'user_id' => $this->reporter1->id,
            'question_id' => $unrelatedQuestion->id,
            'reason' => 'Lỗi không liên quan',
            'status' => 'pending',
        ]);

        $this->assertEquals('pending', $report1->fresh()->status);
        $this->assertEquals('pending', $report2->fresh()->status);
        $this->assertEquals('pending', $unrelatedReport->fresh()->status);

        // 3. Author fixes question content via controller/update
        $this->actingAs($this->author, 'api')->putJson("/api/questions/{$question->id}", [
            'content' => 'Câu hỏi đã sửa đáp án C và đổi hình rõ nét',
            'answers' => [
                ['content' => 'Đáp án 1 đúng', 'is_correct' => true],
                ['content' => 'Đáp án 2 sửa sai', 'is_correct' => false],
            ],
        ]);

        // Verify report tickets transitioned to author_updated
        $this->assertEquals('author_updated', $report1->fresh()->status);
        $this->assertEquals('author_updated', $report2->fresh()->status);
        // Unrelated report MUST still be pending
        $this->assertEquals('pending', $unrelatedReport->fresh()->status);

        // 4. Author submits revision 2 to bank
        $question->refresh();
        $rev2 = $reviewService->submitToBank($question, $this->author, 'Đã sửa theo phản ánh của người học');
        $this->assertEquals(2, $rev2->revision_number);
        $this->assertTrue((bool)$rev2->is_priority);
        $this->assertEquals('high', $rev2->review_priority);

        // 5. Admin reviews and approves revision 2
        $approvedRev = $reviewService->approveQuestion($question, $this->admin);
        $this->assertEquals('approved', $approvedRev->status);

        // 6. CRITICAL VERIFICATION:
        // All reports for this question must now be AUTO RESOLVED!
        $this->assertEquals('resolved', $report1->fresh()->status);
        $this->assertEquals('resolved', $report2->fresh()->status);

        // Unrelated report MUST NOT be resolved!
        $this->assertEquals('pending', $unrelatedReport->fresh()->status);

        // Question bank snapshot must have the new content
        $bankSnapshot = Question::where('origin_question_id', $question->id)->first();
        $this->assertNotNull($bankSnapshot);
        $this->assertEquals('Câu hỏi đã sửa đáp án C và đổi hình rõ nét', $bankSnapshot->content);
    }

    /**
     * Test M: Standard report (e.g. 'Sai đáp án') starts as 'pending' (stays in owner fix queue)
     */
    public function test_m_standard_report_remains_pending_not_forced_to_admin_review_required()
    {
        [$question] = $this->createSampleQuestionWithQuiz('Question M Standard');

        $res = $this->actingAs($this->reporter1, 'api')->postJson('/api/report-tickets', [
            'question_id' => $question->id,
            'reason' => 'Sai đáp án',
            'description' => 'Đáp án B có vẻ không chính xác',
        ]);

        $res->assertStatus(201);
        $this->assertEquals('pending', $res->json('data.status'));
    }

    /**
     * Test N: Critical reason report (e.g. 'Nội dung nhạy cảm') auto elevates to 'admin_review_required'
     */
    public function test_n_critical_reason_report_auto_elevates_to_admin_review_required()
    {
        [$question] = $this->createSampleQuestionWithQuiz('Question N Critical');

        $res = $this->actingAs($this->reporter1, 'api')->postJson('/api/report-tickets', [
            'question_id' => $question->id,
            'reason' => 'Nội dung nhạy cảm',
            'description' => 'Có từ ngữ không phù hợp thuần phong mỹ tục',
        ]);

        $res->assertStatus(201);
        $this->assertEquals('admin_review_required', $res->json('data.status'));
    }

    /**
     * Test O: Multiple reports threshold (>= 3 reports) auto elevates all pending reports of the question to 'admin_review_required'
     */
    public function test_o_threshold_three_reports_auto_elevates_question_reports_to_admin_review_required()
    {
        [$question] = $this->createSampleQuestionWithQuiz('Question O Multi Reports');

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // 1st report -> pending
        $r1 = $this->actingAs($user1, 'api')->postJson('/api/report-tickets', [
            'question_id' => $question->id,
            'reason' => 'Lỗi chính tả',
        ]);
        $r1->assertStatus(201);
        $this->assertEquals('pending', $r1->json('data.status'));

        // 2nd report -> pending
        $r2 = $this->actingAs($user2, 'api')->postJson('/api/report-tickets', [
            'question_id' => $question->id,
            'reason' => 'Thiếu dữ kiện',
        ]);
        $r2->assertStatus(201);
        $this->assertEquals('pending', $r2->json('data.status'));

        // 3rd report -> triggers threshold >= 3 -> auto elevates all tickets to 'admin_review_required'
        $r3 = $this->actingAs($user3, 'api')->postJson('/api/report-tickets', [
            'question_id' => $question->id,
            'reason' => 'Công thức sai',
        ]);
        $r3->assertStatus(201);
        $this->assertEquals('admin_review_required', $r3->json('data.status'));

        // Verify previous reports are also elevated
        $this->assertEquals('admin_review_required', ReportTicket::find($r1->json('data.id'))->status);
        $this->assertEquals('admin_review_required', ReportTicket::find($r2->json('data.id'))->status);
    }
}
