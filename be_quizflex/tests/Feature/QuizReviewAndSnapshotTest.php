<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizReviewRequest;
use App\Models\User;
use App\Services\QuestionSnapshotService;
use App\Services\QuizReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizReviewAndSnapshotTest extends TestCase
{
    protected User $normalUser;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalUser = User::firstOrCreate(
            ['email' => 'testuser@quizflex.local'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        $this->adminUser = User::firstOrCreate(
            ['email' => 'testadmin@quizflex.local'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );
    }

    /**
     * 1. Kiểm tra: Tạo manual Quiz → Bắt buộc PRIVATE
     */
    public function test_manual_quiz_creation_forces_private_status()
    {
        $question = Question::create([
            'user_id' => $this->normalUser->id,
            'content' => 'Câu hỏi cá nhân kiểm tra 1',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => false,
        ]);
        Answer::create(['question_id' => $question->id, 'content' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $question->id, 'content' => 'B', 'is_correct' => false, 'order' => 1]);

        $response = $this->actingAs($this->normalUser, 'api')->postJson('/api/quizzes/from-bank', [
            'title' => 'Bài Quiz thủ công 1',
            'question_ids' => [$question->id],
            'is_public' => true, // Cố tình gửi public = true
        ]);

        $response->assertStatus(201);
        $quizData = $response->json('data');

        $this->assertFalse($quizData['is_public']);
        $this->assertEquals('manual', $quizData['creation_mode']);
        $this->assertEquals('draft', $quizData['review_status']);
    }

    /**
     * 2. Kiểm tra: Tạo auto Quiz từ Ngân hàng → Cho phép PUBLIC
     */
    public function test_auto_quiz_creation_allows_public_status()
    {
        $bankQuestion = Question::create([
            'user_id' => $this->adminUser->id,
            'content' => 'Câu hỏi ngân hàng chuẩn 1',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => true,
        ]);
        Answer::create(['question_id' => $bankQuestion->id, 'content' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $bankQuestion->id, 'content' => 'B', 'is_correct' => false, 'order' => 1]);

        $response = $this->actingAs($this->normalUser, 'api')->postJson('/api/quizzes/from-bank', [
            'title' => 'Bài Quiz tự động 1',
            'easy_count' => 1,
            'is_public' => true,
        ]);

        $response->assertStatus(201);
        $quizData = $response->json('data');

        $this->assertTrue($quizData['is_public']);
        $this->assertEquals('auto', $quizData['creation_mode']);
        $this->assertEquals('approved', $quizData['review_status']);
    }

    /**
     * 3. Kiểm tra: User không thể tự ý công khai Quiz thủ công qua API Update
     */
    public function test_user_cannot_self_publish_manual_quiz_via_update()
    {
        $quiz = Quiz::create([
            'user_id' => $this->normalUser->id,
            'title' => 'Manual Quiz',
            'creation_mode' => 'manual',
            'review_status' => 'draft',
            'is_public' => false,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->normalUser, 'api')->putJson("/api/quizzes/{$quiz->id}", [
            'title' => 'Manual Quiz Renamed',
            'is_public' => true,
            'visibility' => 'public',
        ]);

        $response->assertStatus(200);
        $quiz->refresh();

        $this->assertFalse((bool) $quiz->is_public);
        $this->assertEquals('draft', $quiz->review_status);
        $this->assertEquals('draft', $quiz->status);
    }

    /**
     * 4. Kiểm tra: Tạo review request cho Quiz của chính mình
     */
    public function test_user_can_create_review_request()
    {
        $quiz = Quiz::create([
            'user_id' => $this->normalUser->id,
            'title' => 'Quiz Chờ Duyệt',
            'creation_mode' => 'manual',
            'review_status' => 'draft',
            'is_public' => false,
            'status' => 'draft',
        ]);

        $question = Question::create([
            'user_id' => $this->normalUser->id,
            'content' => 'Nội dung câu hỏi',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => false,
        ]);
        $quiz->questions()->sync([$question->id => ['order' => 0, 'points' => 10]]);

        $response = $this->actingAs($this->normalUser, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review", [
            'request_note' => 'Nhờ admin duyệt đề thi này',
        ]);

        $response->assertStatus(201);
        $quiz->refresh();

        $this->assertEquals('pending_review', $quiz->review_status);
        $this->assertDatabaseHas('quiz_review_requests', [
            'quiz_id' => $quiz->id,
            'user_id' => $this->normalUser->id,
            'status' => 'pending',
            'request_note' => 'Nhờ admin duyệt đề thi này',
        ]);
    }

    /**
     * 5. Kiểm tra: Không tạo được 2 request PENDING đồng thời cho 1 Quiz
     */
    public function test_cannot_create_two_pending_review_requests()
    {
        $quiz = Quiz::create([
            'user_id' => $this->normalUser->id,
            'title' => 'Quiz Chờ Duyệt 2',
            'creation_mode' => 'manual',
            'review_status' => 'pending_review',
            'is_public' => false,
            'status' => 'draft',
        ]);

        $question = Question::create([
            'user_id' => $this->normalUser->id,
            'content' => 'Nội dung câu hỏi',
            'type' => 'single_choice',
            'is_public' => false,
        ]);
        $quiz->questions()->sync([$question->id => ['order' => 0, 'points' => 10]]);

        QuizReviewRequest::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->normalUser->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->normalUser, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review", [
            'request_note' => 'Gửi lần 2',
        ]);

        // Phải bị từ chối 403 / 422
        $this->assertTrue(in_array($response->status(), [403, 422]));
    }

    /**
     * 6. Kiểm tra: Admin phê duyệt Quiz, tạo Snapshot vào Bank và độc lập dữ liệu
     */
    public function test_admin_approve_and_snapshot_mechanism()
    {
        $uid = uniqid();
        $quiz = Quiz::create([
            'user_id' => $this->normalUser->id,
            'title' => "Quiz Cần Snapshot {$uid}",
            'creation_mode' => 'manual',
            'review_status' => 'pending_review',
            'is_public' => false,
            'status' => 'draft',
        ]);

        $personalQuestion = Question::create([
            'user_id' => $this->normalUser->id,
            'content' => "1 + 1 bằng mấy? {$uid}",
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => false,
        ]);
        Answer::create(['question_id' => $personalQuestion->id, 'content' => '2', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $personalQuestion->id, 'content' => '3', 'is_correct' => false, 'order' => 1]);

        $quiz->questions()->sync([$personalQuestion->id => ['order' => 0, 'points' => 10]]);

        $reviewRequest = QuizReviewRequest::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->normalUser->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->adminUser, 'api')->postJson("/api/admin/quiz-review-requests/{$reviewRequest->id}/approve");

        $response->assertStatus(200);
        $quiz->refresh();

        // 1. Quiz phải được công khai
        $this->assertTrue((bool) $quiz->is_public);
        $this->assertEquals('approved', $quiz->review_status);
        $this->assertEquals('published', $quiz->status);

        // 2. Câu hỏi cá nhân gốc của User vẫn giữ nguyên không bị sửa is_public hay xóa
        $personalQuestion->refresh();
        $this->assertFalse((bool) $personalQuestion->is_public);

        // 3. Phải sinh ra 1 snapshot Question trong Ngân hàng
        $snapshotQuestion = Question::where('origin_question_id', $personalQuestion->id)->first();
        $this->assertNotNull($snapshotQuestion);
        $this->assertTrue((bool) $snapshotQuestion->is_public);
        $this->assertEquals('approved', $snapshotQuestion->bank_submission_status);
        $this->assertEquals($personalQuestion->content, $snapshotQuestion->content);
        $this->assertCount(2, $snapshotQuestion->answers);

        // 4. Quiz hiện tại phải liên kết với snapshotQuestion thay vì personalQuestion
        $this->assertTrue($quiz->questions->contains('id', $snapshotQuestion->id));
        $this->assertFalse($quiz->questions->contains('id', $personalQuestion->id));

        // 5. Thử nghiệm thay đổi câu hỏi gốc trong Kho của User -> Snapshot và Quiz công khai không bị ảnh hưởng
        $personalQuestion->update(['content' => "1 + 1 bằng mấy? (Đã bị user sửa) {$uid}"]);
        $snapshotQuestion->refresh();
        $quiz->refresh();

        $this->assertEquals("1 + 1 bằng mấy? {$uid}", $snapshotQuestion->content);
        $this->assertEquals("1 + 1 bằng mấy? {$uid}", $quiz->questions()->first()->content);
    }

    /**
     * 7. Kiểm tra: Admin từ chối phê duyệt và lưu rejection_reason
     */
    public function test_admin_reject_quiz_with_reason()
    {
        $quiz = Quiz::create([
            'user_id' => $this->normalUser->id,
            'title' => 'Quiz Bị Từ Chối',
            'creation_mode' => 'manual',
            'review_status' => 'pending_review',
            'is_public' => false,
            'status' => 'draft',
        ]);

        $reviewRequest = QuizReviewRequest::create([
            'quiz_id' => $quiz->id,
            'user_id' => $this->normalUser->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->adminUser, 'api')->postJson("/api/admin/quiz-review-requests/{$reviewRequest->id}/reject", [
            'reason' => 'Đề thi chứa câu hỏi sai kiến thức căn bản.',
        ]);

        $response->assertStatus(200);
        $quiz->refresh();
        $reviewRequest->refresh();

        $this->assertFalse((bool) $quiz->is_public);
        $this->assertEquals('rejected', $quiz->review_status);
        $this->assertEquals('Đề thi chứa câu hỏi sai kiến thức căn bản.', $quiz->rejection_reason);
        $this->assertEquals('rejected', $reviewRequest->status);
        $this->assertEquals('Đề thi chứa câu hỏi sai kiến thức căn bản.', $reviewRequest->rejection_reason);
    }

    /**
     * 8. Kiểm tra: Chống Duplicate Fingerprint khi câu hỏi đã có sẵn trong Ngân hàng
     */
    public function test_duplicate_fingerprint_reuses_existing_bank_question()
    {
        $snapshotService = app(QuestionSnapshotService::class);
        $uid = uniqid();

        // Giả sử Ngân hàng đã có câu hỏi này
        $existingBankQuestion = Question::create([
            'user_id' => $this->adminUser->id,
            'content' => "Thủ đô của Việt Nam là gì? {$uid}",
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => true,
            'bank_submission_status' => 'approved',
        ]);
        Answer::create(['question_id' => $existingBankQuestion->id, 'content' => 'Hà Nội', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $existingBankQuestion->id, 'content' => 'TP.HCM', 'is_correct' => false, 'order' => 1]);

        $existingBankQuestion->update([
            'fingerprint' => $snapshotService->computeFingerprint($existingBankQuestion),
        ]);

        // User tạo 1 câu hỏi cá nhân với nội dung và đáp án y hệt (nhưng thứ tự đáp án đảo ngược)
        $userPersonalQuestion = Question::create([
            'user_id' => $this->normalUser->id,
            'content' => "Thủ đô của Việt Nam là gì? {$uid}  ", // khoảng trắng thừa
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => false,
        ]);
        Answer::create(['question_id' => $userPersonalQuestion->id, 'content' => 'TP.HCM', 'is_correct' => false, 'order' => 0]);
        Answer::create(['question_id' => $userPersonalQuestion->id, 'content' => 'Hà Nội', 'is_correct' => true, 'order' => 1]);

        $quiz = Quiz::create([
            'user_id' => $this->normalUser->id,
            'title' => "Quiz Trùng Khớp {$uid}",
            'creation_mode' => 'manual',
            'review_status' => 'pending_review',
            'is_public' => false,
        ]);
        $quiz->questions()->sync([$userPersonalQuestion->id => ['order' => 0, 'points' => 10]]);

        $reviewService = app(QuizReviewService::class);
        $reviewService->approveQuiz($quiz, $this->adminUser);

        $quiz->refresh();

        // Quiz phải tái sử dụng ID của existingBankQuestion, không tạo thêm snapshot mới
        $this->assertEquals($existingBankQuestion->id, $quiz->questions()->first()->id);
        $this->assertEquals(1, Question::where('content', 'like', "%Thủ đô của Việt Nam%{$uid}%")->where('is_public', true)->count());
    }

    /**
     * 9. Kiểm tra: Chế độ AUTO từ chối nhận ID câu hỏi cá nhân chưa kiểm duyệt
     */
    public function test_auto_quiz_rejects_personal_question_ids()
    {
        $personalQuestion = Question::create([
            'user_id' => $this->normalUser->id,
            'content' => 'Câu hỏi cá nhân unmoderated',
            'type' => 'single_choice',
            'is_public' => false,
        ]);

        $response = $this->actingAs($this->normalUser, 'api')->postJson('/api/quizzes/from-bank', [
            'title' => 'Cố tình inject My Question vào Auto Quiz',
            'mode' => 'auto',
            'question_ids' => [$personalQuestion->id],
            'is_public' => true,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Chế độ tạo tự động chỉ chấp nhận câu hỏi từ Ngân hàng', $response->json('message'));
    }

    /**
     * 10. Kiểm tra: Tạo Quiz AUTO ở chế độ Riêng tư (Private) hoạt động đúng
     */
    public function test_auto_quiz_creation_can_be_private()
    {
        $bankQuestion = Question::create([
            'user_id' => $this->adminUser->id,
            'content' => 'Câu hỏi ngân hàng chuẩn 2',
            'type' => 'single_choice',
            'difficulty' => 'medium',
            'is_public' => true,
        ]);
        Answer::create(['question_id' => $bankQuestion->id, 'content' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $bankQuestion->id, 'content' => 'B', 'is_correct' => false, 'order' => 1]);

        $response = $this->actingAs($this->normalUser, 'api')->postJson('/api/quizzes/from-bank', [
            'title' => 'Bài Quiz tự động Private',
            'mode' => 'auto',
            'medium_count' => 1,
            'is_public' => false,
        ]);

        $response->assertStatus(201);
        $quizData = $response->json('data');

        $this->assertFalse($quizData['is_public']);
        $this->assertEquals('auto', $quizData['creation_mode']);
        $this->assertEquals('draft', $quizData['review_status']);
    }

    /**
     * 11. Kiểm tra: Quiz bị từ chối (Rejected) có thể sửa và gửi lại yêu cầu duyệt (Pending_review mới)
     */
    public function test_rejected_quiz_can_be_edited_and_resubmitted_for_review()
    {
        $quiz = Quiz::create([
            'user_id' => $this->normalUser->id,
            'title' => 'Quiz Từng Bị Từ Chối',
            'creation_mode' => 'manual',
            'review_status' => 'rejected',
            'rejection_reason' => 'Câu hỏi thiếu đáp án',
            'is_public' => false,
            'status' => 'draft',
        ]);

        $question = Question::create([
            'user_id' => $this->normalUser->id,
            'content' => 'Câu hỏi đã sửa lại chuẩn',
            'type' => 'single_choice',
            'is_public' => false,
        ]);
        Answer::create(['question_id' => $question->id, 'content' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $question->id, 'content' => 'B', 'is_correct' => false, 'order' => 1]);
        $quiz->questions()->sync([$question->id => ['order' => 0, 'points' => 10]]);

        // Tác giả sửa lại Quiz
        $this->actingAs($this->normalUser, 'api')->putJson("/api/quizzes/{$quiz->id}", [
            'title' => 'Quiz Đã Sửa Nội Dung Hoàn Chỉnh',
        ])->assertStatus(200);

        // Tác giả gửi lại yêu cầu duyệt
        $res = $this->actingAs($this->normalUser, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review", [
            'request_note' => 'Em đã sửa lại các câu hỏi, nhờ admin duyệt lại ạ',
        ]);

        $res->assertStatus(201);
        $quiz->refresh();

        $this->assertEquals('pending_review', $quiz->review_status);
        $this->assertNull($quiz->rejection_reason);
        $this->assertDatabaseHas('quiz_review_requests', [
            'quiz_id' => $quiz->id,
            'user_id' => $this->normalUser->id,
            'status' => 'pending',
            'request_note' => 'Em đã sửa lại các câu hỏi, nhờ admin duyệt lại ạ',
        ]);
    }

    /**
     * 12. Kiểm tra: Phê duyệt Quiz chỉ chứa Bank Questions (Không sinh thêm snapshot thừa)
     */
    public function test_approve_quiz_with_only_bank_questions()
    {
        $uid = uniqid();
        $bankQ1 = Question::create([
            'user_id' => $this->adminUser->id,
            'content' => "Bank Q1 {$uid}",
            'type' => 'single_choice',
            'is_public' => true,
            'bank_submission_status' => 'approved',
        ]);
        Answer::create(['question_id' => $bankQ1->id, 'content' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $bankQ1->id, 'content' => 'B', 'is_correct' => false, 'order' => 1]);

        $quiz = Quiz::create([
            'user_id' => $this->normalUser->id,
            'title' => "Quiz Toàn Bank {$uid}",
            'creation_mode' => 'manual',
            'review_status' => 'pending_review',
            'is_public' => false,
        ]);
        $quiz->questions()->sync([$bankQ1->id => ['order' => 1, 'points' => 10]]);

        $countBefore = Question::count();

        $reviewService = app(QuizReviewService::class);
        $reviewService->approveQuiz($quiz, $this->adminUser);

        $quiz->refresh();
        $this->assertTrue((bool) $quiz->is_public);
        $this->assertEquals('approved', $quiz->review_status);
        // Không sinh thêm bản ghi Question nào
        $this->assertEquals($countBefore, Question::count());
    }

    /**
     * 13. Kiểm tra: Question khác nội dung/đáp án vẫn tạo snapshot mới bình thường
     */
    public function test_different_content_creates_new_bank_question()
    {
        $uid = uniqid();
        $personalQ1 = Question::create([
            'user_id' => $this->normalUser->id,
            'content' => "Câu hỏi độc nhất A {$uid}",
            'type' => 'single_choice',
            'is_public' => false,
        ]);
        Answer::create(['question_id' => $personalQ1->id, 'content' => 'Đáp án A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $personalQ1->id, 'content' => 'Đáp án B', 'is_correct' => false, 'order' => 1]);

        $quiz = Quiz::create([
            'user_id' => $this->normalUser->id,
            'title' => "Quiz Độc Nhất {$uid}",
            'creation_mode' => 'manual',
            'review_status' => 'pending_review',
            'is_public' => false,
        ]);
        $quiz->questions()->sync([$personalQ1->id => ['order' => 1, 'points' => 10]]);

        $reviewService = app(QuizReviewService::class);
        $reviewService->approveQuiz($quiz, $this->adminUser);

        $snapshot = Question::where('origin_question_id', $personalQ1->id)->first();
        $this->assertNotNull($snapshot);
        $this->assertTrue((bool) $snapshot->is_public);
        $this->assertEquals("Câu hỏi độc nhất A {$uid}", $snapshot->content);
    }

    /**
     * 14. Kiểm tra: Reject bắt buộc phải có reason
     */
    public function test_reject_requires_reason()
    {
        $quiz = Quiz::create([
            'user_id' => $this->normalUser->id,
            'title' => 'Quiz Test Reject No Reason',
            'creation_mode' => 'manual',
            'review_status' => 'pending_review',
            'is_public' => false,
        ]);

        $response = $this->actingAs($this->adminUser, 'api')->postJson("/api/admin/quiz-review-requests/{$quiz->id}/reject", [
            'reason' => '',
        ]);

        $response->assertStatus(422);
    }

    /**
     * 15. Kiểm tra: Không cho Approve nếu có câu hỏi không hợp lệ (Không có đáp án đúng)
     */
    public function test_cannot_approve_quiz_with_invalid_questions()
    {
        $uid = uniqid();
        $invalidQuestion = Question::create([
            'user_id' => $this->normalUser->id,
            'content' => "Câu hỏi lỗi không có đáp án đúng {$uid}",
            'type' => 'single_choice',
            'is_public' => false,
        ]);
        // Cả 2 đáp án đều là false
        Answer::create(['question_id' => $invalidQuestion->id, 'content' => 'A', 'is_correct' => false, 'order' => 0]);
        Answer::create(['question_id' => $invalidQuestion->id, 'content' => 'B', 'is_correct' => false, 'order' => 1]);

        $quiz = Quiz::create([
            'user_id' => $this->normalUser->id,
            'title' => "Quiz Lỗi {$uid}",
            'creation_mode' => 'manual',
            'review_status' => 'pending_review',
            'is_public' => false,
        ]);
        $quiz->questions()->sync([$invalidQuestion->id => ['order' => 1, 'points' => 10]]);

        $response = $this->actingAs($this->adminUser, 'api')->postJson("/api/admin/quiz-review-requests/{$quiz->id}/approve");

        $response->assertStatus(422);
        $quiz->refresh();
        $this->assertFalse((bool) $quiz->is_public);
        $this->assertEquals('pending_review', $quiz->review_status);
    }

    /**
     * 16. Kiểm tra: Sau khi duyệt, Quiz giữ nguyên đúng thứ tự (order) và điểm số (points) của từng câu hỏi
     */
    public function test_approved_quiz_preserves_question_order_and_points()
    {
        $uid = uniqid();
        $q1 = Question::create([
            'user_id' => $this->normalUser->id,
            'content' => "Q1 {$uid}",
            'type' => 'single_choice',
            'is_public' => false,
        ]);
        Answer::create(['question_id' => $q1->id, 'content' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $q1->id, 'content' => 'B', 'is_correct' => false, 'order' => 1]);

        $q2 = Question::create([
            'user_id' => $this->normalUser->id,
            'content' => "Q2 {$uid}",
            'type' => 'single_choice',
            'is_public' => false,
        ]);
        Answer::create(['question_id' => $q2->id, 'content' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $q2->id, 'content' => 'B', 'is_correct' => false, 'order' => 1]);

        $quiz = Quiz::create([
            'user_id' => $this->normalUser->id,
            'title' => "Quiz Thứ Tự {$uid}",
            'creation_mode' => 'manual',
            'review_status' => 'pending_review',
            'is_public' => false,
        ]);
        $quiz->questions()->sync([
            $q1->id => ['order' => 1, 'points' => 15],
            $q2->id => ['order' => 2, 'points' => 25],
        ]);

        $reviewService = app(QuizReviewService::class);
        $reviewService->approveQuiz($quiz, $this->adminUser);

        $quiz->refresh();
        $questions = $quiz->questions()->orderBy('pivot_order')->get();

        $this->assertCount(2, $questions);
        $this->assertEquals(1, $questions[0]->pivot->order);
        $this->assertEquals(15, $questions[0]->pivot->points);
        $this->assertEquals("Q1 {$uid}", $questions[0]->content);

        $this->assertEquals(2, $questions[1]->pivot->order);
        $this->assertEquals(25, $questions[1]->pivot->points);
        $this->assertEquals("Q2 {$uid}", $questions[1]->content);
    }

    /**
     * 17. Security: User A không thể lấy câu hỏi riêng tư của User B vào Manual Quiz
     */
    public function test_user_cannot_use_another_users_private_questions_in_manual_quiz()
    {
        $otherUser = User::firstOrCreate(
            ['email' => 'otheruser@quizflex.local'],
            ['name' => 'Other User', 'password' => bcrypt('password'), 'role' => 'user']
        );

        $privateQuestionB = Question::create([
            'user_id' => $otherUser->id,
            'content' => 'Câu hỏi bí mật của User B',
            'type' => 'single_choice',
            'is_public' => false,
        ]);
        Answer::create(['question_id' => $privateQuestionB->id, 'content' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $privateQuestionB->id, 'content' => 'B', 'is_correct' => false, 'order' => 1]);

        $response = $this->actingAs($this->normalUser, 'api')->postJson('/api/quizzes/from-bank', [
            'title' => 'Cố tình lấy câu hỏi của User B',
            'mode' => 'manual',
            'question_ids' => [$privateQuestionB->id],
        ]);

        $response->assertStatus(403);
    }

    /**
     * 18. Security: User A không thể gửi review request cho bài Quiz của User B
     */
    public function test_user_cannot_submit_review_request_for_another_users_quiz()
    {
        $otherUser = User::firstOrCreate(
            ['email' => 'otheruser2@quizflex.local'],
            ['name' => 'Other User 2', 'password' => bcrypt('password'), 'role' => 'user']
        );

        $quizB = Quiz::create([
            'user_id' => $otherUser->id,
            'title' => 'Quiz của User B',
            'creation_mode' => 'manual',
            'review_status' => 'draft',
            'is_public' => false,
        ]);

        $response = $this->actingAs($this->normalUser, 'api')->postJson("/api/quizzes/{$quizB->id}/request-review");

        $response->assertStatus(403);
    }

    /**
     * 19. Security: User thường không thể gọi API duyệt/từ chối của Admin
     */
    public function test_non_admin_cannot_approve_or_reject_quiz_review_request()
    {
        $quiz = Quiz::create([
            'user_id' => $this->normalUser->id,
            'title' => 'Quiz Chờ Duyệt Security',
            'creation_mode' => 'manual',
            'review_status' => 'pending_review',
            'is_public' => false,
        ]);

        // Thử gọi API approve bằng tài khoản user thường
        $resApprove = $this->actingAs($this->normalUser, 'api')->postJson("/api/admin/quiz-review-requests/{$quiz->id}/approve");
        $this->assertEquals(403, $resApprove->status());

        // Thử gọi API reject bằng tài khoản user thường
        $resReject = $this->actingAs($this->normalUser, 'api')->postJson("/api/admin/quiz-review-requests/{$quiz->id}/reject", [
            'reason' => 'Lý do từ chối trái phép',
        ]);
        $this->assertEquals(403, $resReject->status());
    }

    /**
     * 20. Kiểm tra: Manual Quiz chứa cả Bank Question và My Question -> Phê duyệt đúng nghiệp vụ
     */
    public function test_manual_quiz_with_mixed_sources_preserves_bank_and_snapshots_my_questions()
    {
        $uid = uniqid();

        // 1 câu từ Ngân hàng công khai
        $bankQ = Question::create([
            'user_id' => $this->adminUser->id,
            'content' => "Bank Q Mixed {$uid}",
            'type' => 'single_choice',
            'is_public' => true,
            'bank_submission_status' => 'approved',
        ]);
        Answer::create(['question_id' => $bankQ->id, 'content' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $bankQ->id, 'content' => 'B', 'is_correct' => false, 'order' => 1]);

        // 1 câu từ Kho cá nhân của user
        $myQ = Question::create([
            'user_id' => $this->normalUser->id,
            'content' => "My Personal Q Mixed {$uid}",
            'type' => 'single_choice',
            'is_public' => false,
        ]);
        Answer::create(['question_id' => $myQ->id, 'content' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $myQ->id, 'content' => 'B', 'is_correct' => false, 'order' => 1]);

        // User tạo Manual Quiz kết hợp 2 nguồn
        $resCreate = $this->actingAs($this->normalUser, 'api')->postJson('/api/quizzes/from-bank', [
            'title' => "Quiz Nguồn Hỗn Hợp {$uid}",
            'mode' => 'manual',
            'question_ids' => [$bankQ->id, $myQ->id],
            'is_public' => true, // Cố tình gửi public
        ]);

        $resCreate->assertStatus(201);
        $quizId = $resCreate->json('data.id');
        $quiz = Quiz::find($quizId);

        // Ban đầu bắt buộc PRIVATE
        $this->assertFalse((bool) $quiz->is_public);
        $this->assertEquals('manual', $quiz->creation_mode);

        // Admin duyệt Quiz
        $reviewService = app(QuizReviewService::class);
        $reviewService->approveQuiz($quiz, $this->adminUser);

        $quiz->refresh();
        $this->assertTrue((bool) $quiz->is_public);
        $this->assertEquals('approved', $quiz->review_status);

        // Kiểm tra tất cả câu hỏi trong Quiz sau duyệt đều là public
        $quizQuestions = $quiz->questions()->get();
        $this->assertCount(2, $quizQuestions);
        foreach ($quizQuestions as $q) {
            $this->assertTrue((bool) $q->is_public);
        }

        // Bank question ban đầu vẫn giữ nguyên id trong Quiz
        $this->assertTrue($quizQuestions->contains('id', $bankQ->id));

        // My Question gốc vẫn là private trong kho user
        $myQ->refresh();
        $this->assertFalse((bool) $myQ->is_public);

        // Quiz liên kết với snapshot của My Question
        $snapshot = Question::where('origin_question_id', $myQ->id)->first();
        $this->assertNotNull($snapshot);
        $this->assertTrue($quizQuestions->contains('id', $snapshot->id));
    }
}
