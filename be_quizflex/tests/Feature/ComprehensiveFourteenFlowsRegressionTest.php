<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionReviewRequest;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\ReportTicket;
use App\Models\User;
use App\Notifications\QuestionModerated;
use App\Notifications\QuestionReviewRequested;
use App\Notifications\QuizModerated;
use App\Notifications\ReportResolved;
use App\Services\QuestionReviewService;
use App\Services\QuizReviewService;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ComprehensiveFourteenFlowsRegressionTest extends TestCase
{
    protected User $author;
    protected User $learner;
    protected User $admin;
    protected QuestionReviewService $questionReviewService;
    protected QuizReviewService $quizReviewService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->questionReviewService = app(QuestionReviewService::class);
        $this->quizReviewService = app(QuizReviewService::class);

        $this->author = User::firstOrCreate(
            ['email' => 'regression_author@quizflex.local'],
            ['name' => 'Regression Author', 'password' => bcrypt('password'), 'role' => 'user']
        );

        $this->learner = User::firstOrCreate(
            ['email' => 'regression_learner@quizflex.local'],
            ['name' => 'Regression Learner', 'password' => bcrypt('password'), 'role' => 'user']
        );

        $this->admin = User::firstOrCreate(
            ['email' => 'regression_admin@quizflex.local'],
            ['name' => 'Regression Admin', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        ReportTicket::whereIn('user_id', [$this->author->id, $this->learner->id, $this->admin->id])->delete();
        QuestionReviewRequest::whereIn('user_id', [$this->author->id, $this->learner->id, $this->admin->id])->delete();
        Question::withTrashed()->whereIn('user_id', [$this->author->id, $this->learner->id, $this->admin->id])->forceDelete();
    }

    protected function createValidQuestion(array $overrides = []): Question
    {
        $q = Question::create(array_merge([
            'user_id' => $this->author->id,
            'content' => 'Câu hỏi mẫu regression flow ' . uniqid(),
            'type' => 'single_choice',
            'difficulty' => 'medium',
            'points' => 10,
            'is_public' => false,
            'bank_submission_status' => 'none',
        ], $overrides));

        Answer::create(['question_id' => $q->id, 'content' => 'Đáp án đúng 100%', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $q->id, 'content' => 'Đáp án sai 1', 'is_correct' => false, 'order' => 1]);
        Answer::create(['question_id' => $q->id, 'content' => 'Đáp án sai 2', 'is_correct' => false, 'order' => 2]);
        Answer::create(['question_id' => $q->id, 'content' => 'Đáp án sai 3', 'is_correct' => false, 'order' => 3]);

        return $q->fresh('answers');
    }

    /**
     * FLOW 1: User tạo Question -> Lưu thành công
     */
    public function test_flow_1_create_question_success()
    {
        $question = $this->createValidQuestion([
            'content' => 'Trái Đất quay quanh Mặt Trời mất bao lâu?',
        ]);

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'user_id' => $this->author->id,
            'content' => 'Trái Đất quay quanh Mặt Trời mất bao lâu?',
            'is_public' => false,
            'bank_submission_status' => 'none',
        ]);
        $this->assertCount(4, $question->answers);
    }

    /**
     * FLOW 2: Gửi Question vào Bank -> submit review -> Admin review bình thường (normal priority)
     */
    public function test_flow_2_submit_question_to_bank_normal_review()
    {
        Notification::fake();
        $question = $this->createValidQuestion();

        $response = $this->actingAs($this->author, 'api')
            ->postJson("/api/user/my-questions/{$question->id}/submit-to-bank", [
                'request_note' => 'Gửi duyệt câu hỏi kiến thức thiên văn',
            ]);

        $response->assertStatus(200);

        $question->refresh();
        $this->assertEquals('pending', $question->bank_submission_status);

        $reviewReq = QuestionReviewRequest::where('question_id', $question->id)->latest()->first();
        $this->assertNotNull($reviewReq);
        $this->assertEquals('normal', $reviewReq->review_priority);
        $this->assertFalse((bool)$reviewReq->is_priority);

        Notification::assertSentTo($this->admin, QuestionReviewRequested::class, function ($n) {
            return $n->isPriority === false;
        });
    }

    /**
     * FLOW 3: Publish Question -> Admin approve -> Snapshot public trong Ngân hàng
     */
    public function test_flow_3_admin_approve_creates_public_snapshot()
    {
        $question = $this->createValidQuestion();
        $this->questionReviewService->submitToBank($question, $this->author);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$question->id}/approve");

        $response->assertStatus(200);

        $question->refresh();
        $this->assertEquals('approved', $question->bank_submission_status);

        // Snapshot public được tạo
        $snapshot = Question::where('origin_question_id', $question->id)->where('is_public', true)->first();
        $this->assertNotNull($snapshot);
        $this->assertEquals($question->content, $snapshot->content);
        $this->assertTrue((bool)$snapshot->is_public);
    }

    /**
     * FLOW 4 & 5: Report Question -> User report public Snapshot -> ReportTicket tạo thành công -> Đúng Owner nhận notification
     */
    public function test_flow_4_and_5_report_public_snapshot_and_owner_notification()
    {
        Notification::fake();
        $original = $this->createValidQuestion(['bank_submission_status' => 'approved']);
        $snapshot = $this->createValidQuestion([
            'origin_question_id' => $original->id,
            'is_public' => true,
            'bank_submission_status' => 'approved',
            'content' => $original->content,
        ]);

        $response = $this->actingAs($this->learner, 'api')
            ->postJson('/api/report-tickets', [
                'question_id' => $snapshot->id,
                'reason' => 'Sai đáp án / Phương án đúng không chính xác',
                'description' => 'Đáp án B mới là đáp án đúng theo SGK',
            ]);

        $response->assertStatus(201);

        // Report ticket được tạo gắn với Question gốc
        $this->assertDatabaseHas('report_tickets', [
            'user_id' => $this->learner->id,
            'question_id' => $original->id,
            'reason' => 'Sai đáp án / Phương án đúng không chính xác',
            'status' => 'pending',
        ]);

        // Đúng Owner nhận notification kèm link trực tiếp
        Notification::assertSentTo($this->author, QuestionModerated::class, function (QuestionModerated $notif) use ($original) {
            $arr = $notif->toArray($this->author);
            return $notif->action === 'reported'
                && $arr['action_link'] === "/dashboard/my-questions?question_id={$original->id}"
                && $notif->question->id === $original->id;
        });
    }

    /**
     * FLOW 6: Owner Edit -> Mở đúng Question gốc -> Sửa thành công
     */
    public function test_flow_6_owner_edits_original_question_successfully()
    {
        $original = $this->createValidQuestion(['bank_submission_status' => 'approved']);

        $response = $this->actingAs($this->author, 'api')
            ->putJson("/api/user/my-questions/{$original->id}", [
                'content' => 'Nội dung câu hỏi đã được sửa chuẩn xác 100%',
                'answers' => [
                    ['content' => 'Đáp án A (Mới đính chính)', 'is_correct' => true, 'order' => 0],
                    ['content' => 'Đáp án B (Mới)', 'is_correct' => false, 'order' => 1],
                ],
            ]);

        $response->assertStatus(200);

        $original->refresh();
        $this->assertEquals('Nội dung câu hỏi đã được sửa chuẩn xác 100%', $original->content);
        $this->assertEquals('Đáp án A (Mới đính chính)', $original->answers()->where('is_correct', true)->first()->content);
    }

    /**
     * FLOW 7 & 8: Submit Review -> Question quay lại review -> Admin thấy 🔴 PRIORITY
     */
    public function test_flow_7_and_8_submit_review_with_priority()
    {
        Notification::fake();
        $original = $this->createValidQuestion(['bank_submission_status' => 'approved']);
        
        ReportTicket::create([
            'user_id' => $this->learner->id,
            'question_id' => $original->id,
            'reason' => 'Sai đáp án',
            'description' => 'Cần sửa lại phương án',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->author, 'api')
            ->postJson("/api/user/my-questions/{$original->id}/submit-to-bank", [
                'request_note' => 'Đã sửa đáp án theo phản ánh của học sinh',
            ]);

        $response->assertStatus(200);

        $reviewReq = QuestionReviewRequest::where('question_id', $original->id)->latest()->first();
        $this->assertNotNull($reviewReq);
        $this->assertTrue((bool)$reviewReq->is_priority);
        $this->assertEquals('high', $reviewReq->review_priority);

        // Admin thấy cờ PRIORITY trong API danh sách
        $resAdmin = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/question-bank-requests?priority=high');

        $resAdmin->assertStatus(200);
        $items = $resAdmin->json('data.items');
        $found = collect($items)->firstWhere('id', $original->id);
        $this->assertNotNull($found);
        $this->assertTrue((bool)$found['is_priority']);
        $this->assertEquals('high', $found['review_priority']);
    }

    /**
     * FLOW 9: Admin Approve -> Trạng thái đúng -> Public Snapshot mới cập nhật -> Report ticket resolved
     */
    public function test_flow_9_admin_approve_flow_with_report_resolution()
    {
        Notification::fake();
        $original = $this->createValidQuestion();
        $report = ReportTicket::create([
            'user_id' => $this->learner->id,
            'question_id' => $original->id,
            'reason' => 'Lỗi chính tả',
            'status' => 'pending',
        ]);

        $this->questionReviewService->submitToBank($original, $this->author);

        $resApprove = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$original->id}/approve");

        $resApprove->assertStatus(200);

        $original->refresh();
        $this->assertEquals('approved', $original->bank_submission_status);

        $report->refresh();
        $this->assertEquals('resolved', $report->status);

        Notification::assertSentTo($this->learner, ReportResolved::class);
    }

    /**
     * FLOW 10: Admin Reject -> Trạng thái rejected -> Owner nhận notification -> Câu hỏi KHÔNG bị xóa
     */
    public function test_flow_10_admin_reject_flow()
    {
        Notification::fake();
        $original = $this->createValidQuestion();
        $this->questionReviewService->submitToBank($original, $this->author);

        $resReject = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$original->id}/reject", [
                'note' => 'Chưa đạt chuẩn nội dung chương trình GDPT',
            ]);

        $resReject->assertStatus(200);

        $original->refresh();
        $this->assertEquals('rejected', $original->bank_submission_status);
        $this->assertEquals('Chưa đạt chuẩn nội dung chương trình GDPT', $original->bank_submission_note);
        $this->assertNull($original->deleted_at);

        Notification::assertSentTo($this->author, QuestionModerated::class, function ($n) {
            return $n->action === 'rejected';
        });
    }

    /**
     * FLOW 11: Duplicate Report -> Không tạo duplicate pending report của cùng 1 user trên cùng 1 question
     */
    public function test_flow_11_duplicate_report_rejected()
    {
        $original = $this->createValidQuestion(['is_public' => true]);

        // Lần 1: Thành công
        $this->actingAs($this->learner, 'api')
            ->postJson('/api/report-tickets', [
                'question_id' => $original->id,
                'reason' => 'Lỗi đề bài',
            ])->assertStatus(201);

        // Lần 2: 409 Conflict
        $resDup = $this->actingAs($this->learner, 'api')
            ->postJson('/api/report-tickets', [
                'question_id' => $original->id,
                'reason' => 'Lỗi đề bài gửi lặp',
            ]);

        $resDup->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Bạn đã báo cáo câu hỏi này và báo cáo đang được xử lý.',
            ]);

        $this->assertEquals(1, ReportTicket::where('question_id', $original->id)->count());
    }

    /**
     * FLOW 12: Tạo Quiz -> Chọn Question -> Submit Quiz -> Admin approve -> Quiz public
     */
    public function test_flow_12_quiz_creation_and_approval_flow()
    {
        $q1 = $this->createValidQuestion();
        $q2 = $this->createValidQuestion();

        $quiz = Quiz::create([
            'user_id' => $this->author->id,
            'title' => 'Bài Quiz Lịch sử Thế giới',
            'description' => 'Mô tả bài quiz test',
            'creation_mode' => 'manual',
            'is_public' => false,
            'status' => 'draft',
            'review_status' => 'draft',
        ]);


        QuizQuestion::create(['quiz_id' => $quiz->id, 'question_id' => $q1->id, 'order' => 0]);
        QuizQuestion::create(['quiz_id' => $quiz->id, 'question_id' => $q2->id, 'order' => 1]);

        // Submit quiz review
        $this->quizReviewService->requestReview($quiz, $this->author, 'Gửi duyệt bài quiz Lịch sử');
        $quiz->refresh();
        $this->assertEquals('pending_review', $quiz->review_status);

        // Admin approve
        $this->quizReviewService->approveQuiz($quiz, $this->admin);
        $quiz->refresh();
        $this->assertEquals('approved', $quiz->review_status);
        $this->assertEquals('published', $quiz->status);
        $this->assertTrue((bool)$quiz->is_public);
    }



    /**
     * FLOW 13: Quiz KHÔNG có Report API -> Request không có question_id bị 422
     */
    public function test_flow_13_quiz_report_api_does_not_exist_requires_question_id()
    {
        $quiz = Quiz::create([
            'user_id' => $this->author->id,
            'title' => 'Bài Quiz Public',
            'is_public' => true,
            'status' => 'published',
        ]);

        // Gửi report_tickets với quiz_id mà không có question_id sẽ bị từ chối
        $response = $this->actingAs($this->learner, 'api')
            ->postJson('/api/report-tickets', [
                'quiz_id' => $quiz->id,
                'reason' => 'Báo cáo bài quiz',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question_id']);
    }

    /**
     * FLOW 14: Question trong Quiz -> Người học report trực tiếp Question trong Quiz
     */
    public function test_flow_14_report_question_inside_quiz()
    {
        $q = $this->createValidQuestion(['is_public' => false]);
        $quiz = Quiz::create([
            'user_id' => $this->author->id,
            'title' => 'Bài Quiz Công Khai Đang Làm',
            'is_public' => true,
            'status' => 'published',
        ]);
        QuizQuestion::create(['quiz_id' => $quiz->id, 'question_id' => $q->id, 'order' => 0]);

        $response = $this->actingAs($this->learner, 'api')
            ->postJson('/api/report-tickets', [
                'question_id' => $q->id,
                'reason' => 'Sai đáp án trong câu hỏi bài thi',
                'description' => 'Câu hỏi có trong quiz nhưng đáp án bị ngược',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('report_tickets', [
            'user_id' => $this->learner->id,
            'question_id' => $q->id,
            'reason' => 'Sai đáp án trong câu hỏi bài thi',
            'status' => 'pending',
        ]);
    }
}
