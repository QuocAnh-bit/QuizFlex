<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionReviewRequest;
use App\Models\User;
use App\Models\Subject;
use App\Models\Grade;
use Tests\TestCase;

class AdminQuestionBankManagementTest extends TestCase
{
    protected User $admin;
    protected User $userA;
    protected User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_bank_mgr@quizflex.local'],
            [
                'name' => 'Admin Bank Manager',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        $this->userA = User::firstOrCreate(
            ['email' => 'user_a_bank@quizflex.local'],
            [
                'name' => 'User A',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        $this->userB = User::firstOrCreate(
            ['email' => 'user_b_bank@quizflex.local'],
            [
                'name' => 'User B',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        // Dọn dẹp câu hỏi của các test users trước mỗi bài test
        Question::withTrashed()
            ->whereIn('user_id', [$this->admin->id, $this->userA->id, $this->userB->id])
            ->forceDelete();
    }

    protected function createQuestionWithAnswers(User $user, array $data = [], array $answers = []): Question
    {
        $uid = uniqid();
        $defaultContent = 'Câu hỏi mẫu ' . $uid;
        if (isset($data['content'])) {
            $data['content'] = $data['content'] . ' ' . $uid;
        }

        $question = Question::create(array_merge([
            'user_id' => $user->id,
            'content' => $defaultContent,
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => false,
            'bank_submission_status' => 'none',
        ], $data));

        if (empty($answers)) {
            $answers = [
                ['content' => 'Đáp án A (đúng)', 'is_correct' => true, 'order' => 0],
                ['content' => 'Đáp án B (sai)', 'is_correct' => false, 'order' => 1],
            ];
        }

        foreach ($answers as $ans) {
            Answer::create([
                'question_id' => $question->id,
                'content' => $ans['content'],
                'is_correct' => (bool)$ans['is_correct'],
                'order' => $ans['order'] ?? 0,
            ]);
        }

        return $question->fresh('answers');
    }

    /**
     * TEST 1 — ADMIN BANK LIST
     * GET /api/admin/questions-management chỉ trả về Question Bank Snapshots.
     * Không trả về câu hỏi cá nhân của User A (status none) hay User B (status rejected/pending).
     */
    public function test_1_admin_bank_list_only_returns_approved_snapshots()
    {
        // 1. Question cá nhân của User A (status = none, is_public = false)
        $qUserA = $this->createQuestionWithAnswers($this->userA, [
            'content' => 'Câu hỏi cá nhân của User A - bí mật',
            'origin_question_id' => null,
            'bank_submission_status' => 'none',
            'is_public' => false,
        ]);

        // 2. Question cá nhân của User B (status = rejected, is_public = false)
        $qUserB = $this->createQuestionWithAnswers($this->userB, [
            'content' => 'Câu hỏi của User B bị reject',
            'origin_question_id' => null,
            'bank_submission_status' => 'rejected',
            'is_public' => false,
        ]);

        // 3. Question gốc của User A vừa được approve (status = approved, is_public = false, origin_question_id = null)
        $qOriginalApproved = $this->createQuestionWithAnswers($this->userA, [
            'content' => 'Câu hỏi gốc đã được approve nhưng là bản cá nhân',
            'origin_question_id' => null,
            'bank_submission_status' => 'approved',
            'is_public' => false,
        ]);

        // 4. Question Bank Snapshot đã approved (origin_question_id = original.id, bank_submission_status = approved, is_public = true)
        $bankSnapshot = Question::create([
            'user_id' => $this->userA->id,
            'origin_question_id' => $qOriginalApproved->id,
            'content' => 'Câu hỏi Ngân hàng Snapshot công khai chuẩn',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => true,
            'bank_submission_status' => 'approved',
        ]);
        Answer::create(['question_id' => $bankSnapshot->id, 'content' => 'Đáp án Bank 1', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $bankSnapshot->id, 'content' => 'Đáp án Bank 2', 'is_correct' => false, 'order' => 1]);

        // Gọi API Admin Question Bank
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/questions-management');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $items = $response->json('data.items');
        $itemIds = collect($items)->pluck('id')->toArray();

        // Chỉ snapshot xuất hiện
        $this->assertContains($bankSnapshot->id, $itemIds);

        // Các câu hỏi cá nhân KHÔNG được xuất hiện
        $this->assertNotContains($qUserA->id, $itemIds);
        $this->assertNotContains($qUserB->id, $itemIds);
        $this->assertNotContains($qOriginalApproved->id, $itemIds);
    }

    /**
     * TEST 2 — APPROVE QUESTION TẠO SNAPSHOT
     * Submit Question cá nhân -> Admin approve.
     * Assert: original.id !== snapshot.id, snapshot.origin_question_id === original.id,
     * snapshot.bank_submission_status === 'approved', snapshot.is_public === true,
     * original vẫn không phải Bank Snapshot.
     */
    public function test_2_approve_question_creates_separate_snapshot_in_bank()
    {
        $original = $this->createQuestionWithAnswers($this->userA, [
            'content' => 'Câu hỏi địa lý Việt Nam',
            'difficulty' => 'easy',
        ]);

        // User gửi duyệt
        $this->actingAs($this->userA, 'api')
            ->postJson("/api/user/my-questions/{$original->id}/submit-to-bank", [
                'note' => 'Nhờ admin duyệt',
            ]);

        // Admin duyệt
        $approveRes = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$original->id}/approve");

        $approveRes->assertStatus(200);

        // Lấy snapshot vừa tạo trong Bank
        $snapshot = Question::where('origin_question_id', $original->id)->where('is_public', true)->first();

        $this->assertNotNull($snapshot);
        $this->assertNotEquals($original->id, $snapshot->id);
        $this->assertEquals($original->id, $snapshot->origin_question_id);
        $this->assertEquals('approved', $snapshot->bank_submission_status);
        $this->assertTrue((bool)$snapshot->is_public);

        // Question gốc vẫn thuộc user, origin_question_id = null, is_public = false
        $original->refresh();
        $this->assertNull($original->origin_question_id);
        $this->assertFalse((bool)$original->is_public);
        $this->assertEquals('approved', $original->bank_submission_status);
    }

    /**
     * TEST 3 — ANSWERS ĐỘC LẬP
     * Assert: originalAnswer.id !== snapshotAnswer.id,
     * snapshotAnswer.question_id === snapshot.id,
     * originalAnswer.question_id === original.id
     */
    public function test_3_snapshot_answers_are_completely_independent()
    {
        $original = $this->createQuestionWithAnswers($this->userA, [
            'content' => 'Câu hỏi độc lập đáp án',
        ], [
            ['content' => 'Đáp án Gốc 1', 'is_correct' => true, 'order' => 0],
            ['content' => 'Đáp án Gốc 2', 'is_correct' => false, 'order' => 1],
        ]);

        $this->actingAs($this->userA, 'api')
            ->postJson("/api/user/my-questions/{$original->id}/submit-to-bank");

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$original->id}/approve");

        $snapshot = Question::where('origin_question_id', $original->id)->where('is_public', true)->first();
        $this->assertNotNull($snapshot);

        $originalAnswers = $original->answers()->get();
        $snapshotAnswers = $snapshot->answers()->get();

        $this->assertCount(2, $originalAnswers);
        $this->assertCount(2, $snapshotAnswers);

        $originalIds = $originalAnswers->pluck('id')->all();
        $snapshotIds = $snapshotAnswers->pluck('id')->all();

        // Không có Answer ID nào trùng nhau
        $this->assertEmpty(array_intersect($originalIds, $snapshotIds));

        // Mỗi answer gắn đúng question_id tương ứng
        foreach ($originalAnswers as $ans) {
            $this->assertEquals($original->id, $ans->question_id);
        }

        foreach ($snapshotAnswers as $ans) {
            $this->assertEquals($snapshot->id, $ans->question_id);
        }
    }

    /**
     * TEST 4 — USER SỬA CONTENT
     * Approve. Sau đó sửa Question gốc.
     * Assert Snapshot content không thay đổi.
     */
    public function test_4_author_modifying_content_after_approval_does_not_affect_bank_snapshot()
    {
        $original = $this->createQuestionWithAnswers($this->userA, [
            'content' => 'Nội dung nguyên bản khi duyệt',
        ]);
        $originalContentBefore = $original->content;

        $this->actingAs($this->userA, 'api')
            ->postJson("/api/user/my-questions/{$original->id}/submit-to-bank");

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$original->id}/approve");

        $snapshot = Question::where('origin_question_id', $original->id)->where('is_public', true)->first();
        $this->assertEquals($originalContentBefore, $snapshot->content);

        // User sửa nội dung câu hỏi gốc
        $this->actingAs($this->userA, 'api')
            ->putJson("/api/user/my-questions/{$original->id}", [
                'content' => 'Nội dung đã bị User thay đổi 180 độ ' . uniqid(),
                'answers' => [
                    ['content' => 'Đáp án mới A', 'is_correct' => true],
                    ['content' => 'Đáp án mới B', 'is_correct' => false],
                ],
            ]);

        $original->refresh();
        $this->assertNotEquals($originalContentBefore, $original->content);

        // Snapshot trong Bank KHÔNG thay đổi
        $snapshot->refresh();
        $this->assertEquals($originalContentBefore, $snapshot->content);
    }

    /**
     * TEST 5 — USER SỬA ANSWER
     * Approve. Lấy Answer gốc. Sửa content, is_correct.
     * Assert Answer Snapshot: content không đổi, is_correct không đổi.
     */
    public function test_5_author_modifying_answers_does_not_affect_snapshot_answers()
    {
        $original = $this->createQuestionWithAnswers($this->userA, [
            'content' => 'Câu hỏi kiểm tra đáp án bất biến',
        ], [
            ['content' => 'Phương án A đúng', 'is_correct' => true, 'order' => 0],
            ['content' => 'Phương án B sai', 'is_correct' => false, 'order' => 1],
        ]);

        $this->actingAs($this->userA, 'api')
            ->postJson("/api/user/my-questions/{$original->id}/submit-to-bank");

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$original->id}/approve");

        $snapshot = Question::where('origin_question_id', $original->id)->where('is_public', true)->first();
        $snapshotAnswerA = $snapshot->answers()->where('order', 0)->first();
        $snapshotAnswerB = $snapshot->answers()->where('order', 1)->first();

        $this->assertEquals('Phương án A đúng', $snapshotAnswerA->content);
        $this->assertTrue((bool)$snapshotAnswerA->is_correct);

        $this->assertEquals('Phương án B sai', $snapshotAnswerB->content);
        $this->assertFalse((bool)$snapshotAnswerB->is_correct);

        // User sửa đảo ngược đáp án: A thành sai, B thành đúng và đổi text
        $this->actingAs($this->userA, 'api')
            ->putJson("/api/user/my-questions/{$original->id}", [
                'content' => $original->content,
                'answers' => [
                    ['content' => 'Phương án A bị sửa thành sai', 'is_correct' => false, 'key' => 'A'],
                    ['content' => 'Phương án B bị sửa thành đúng', 'is_correct' => true, 'key' => 'B'],
                ],
            ]);

        // Kiểm tra Answer của snapshot vẫn giữ nguyên vẹn 100%
        $snapshotAnswerA->refresh();
        $snapshotAnswerB->refresh();

        $this->assertEquals('Phương án A đúng', $snapshotAnswerA->content);
        $this->assertTrue((bool)$snapshotAnswerA->is_correct);

        $this->assertEquals('Phương án B sai', $snapshotAnswerB->content);
        $this->assertFalse((bool)$snapshotAnswerB->is_correct);
    }

    /**
     * TEST 6 — USER THÊM / XÓA ANSWER
     * Sau approve: Thêm Answer vào Question gốc hoặc xóa Answer gốc.
     * Assert số lượng Answer của Snapshot không thay đổi.
     */
    public function test_6_author_adding_or_removing_answers_does_not_affect_snapshot_answers_count()
    {
        $original = $this->createQuestionWithAnswers($this->userA, [
            'content' => 'Câu hỏi 2 đáp án',
        ], [
            ['content' => 'Đáp án 1', 'is_correct' => true, 'order' => 0],
            ['content' => 'Đáp án 2', 'is_correct' => false, 'order' => 1],
        ]);

        $this->actingAs($this->userA, 'api')
            ->postJson("/api/user/my-questions/{$original->id}/submit-to-bank");

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$original->id}/approve");

        $snapshot = Question::where('origin_question_id', $original->id)->where('is_public', true)->first();
        $this->assertCount(2, $snapshot->answers);

        // User thêm đáp án 3 và 4 vào câu hỏi gốc (tổng cộng 4 đáp án)
        $this->actingAs($this->userA, 'api')
            ->putJson("/api/user/my-questions/{$original->id}", [
                'content' => $original->content,
                'answers' => [
                    ['content' => 'Đáp án 1', 'is_correct' => true, 'key' => 'A'],
                    ['content' => 'Đáp án 2', 'is_correct' => false, 'key' => 'B'],
                    ['content' => 'Đáp án 3 mới', 'is_correct' => false, 'key' => 'C'],
                    ['content' => 'Đáp án 4 mới', 'is_correct' => false, 'key' => 'D'],
                ],
            ]);

        $this->assertCount(4, $original->fresh()->answers);

        // Snapshot trong Bank VẪN chỉ có đúng 2 đáp án ban đầu
        $this->assertCount(2, $snapshot->fresh()->answers);
    }

    /**
     * TEST 7 — ADMIN DELETE SNAPSHOT
     * Admin xóa Question Bank Snapshot.
     * Assert: snapshot bị delete theo cơ chế hiện tại. Question gốc vẫn tồn tại. Answer gốc vẫn tồn tại.
     */
    public function test_7_admin_delete_snapshot_keeps_original_question_and_answers_intact()
    {
        $original = $this->createQuestionWithAnswers($this->userA, [
            'content' => 'Câu hỏi test admin delete',
        ], [
            ['content' => 'Đáp án Gốc 1', 'is_correct' => true, 'order' => 0],
            ['content' => 'Đáp án Gốc 2', 'is_correct' => false, 'order' => 1],
        ]);

        $this->actingAs($this->userA, 'api')
            ->postJson("/api/user/my-questions/{$original->id}/submit-to-bank");

        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$original->id}/approve");

        $snapshot = Question::where('origin_question_id', $original->id)->where('is_public', true)->first();
        $this->assertNotNull($snapshot);

        // Admin xóa Question Bank Snapshot
        $deleteRes = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/questions/{$snapshot->id}");

        $deleteRes->assertStatus(200);

        // Snapshot bị soft deleted
        $this->assertTrue($snapshot->fresh()->trashed());

        // Question gốc VẪN TỒN TẠI (không bị xóa)
        $this->assertFalse($original->fresh()->trashed());

        // Answer gốc VẪN TỒN TẠI đầy đủ
        $this->assertCount(2, $original->fresh()->answers);
    }

    /**
     * TEST 8 — ADMIN FILTERS VÀ STATS
     * Bộ lọc và Stats trong adminIndex hoạt động chính xác trên tập Question Bank Snapshot.
     */
    public function test_8_admin_filters_and_stats_work_on_bank_snapshots()
    {
        $initialRes = $this->actingAs($this->admin, 'api')->getJson('/api/admin/questions-management');
        $initialTotal = $initialRes->json('data.stats.total') ?? 0;
        $initialPublic = $initialRes->json('data.stats.public') ?? 0;
        $initialPrivate = $initialRes->json('data.stats.private') ?? 0;

        $uniqueToken1 = 'token_math_' . uniqid();
        $uniqueToken2 = 'token_phys_' . uniqid();

        // Tạo 1 câu hỏi cá nhân không duyệt -> Không làm tăng stats bank
        $this->createQuestionWithAnswers($this->userA, ['content' => 'Personal Q ' . uniqid()]);

        // Tạo 2 Question Bank Snapshots (1 Public, 1 Private)
        $orig1 = $this->createQuestionWithAnswers($this->userA, ['content' => $uniqueToken1, 'difficulty' => 'easy']);
        $snap1 = Question::create([
            'user_id' => $this->userA->id,
            'origin_question_id' => $orig1->id,
            'content' => $uniqueToken1,
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => true,
            'bank_submission_status' => 'approved',
        ]);
        Answer::create(['question_id' => $snap1->id, 'content' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $snap1->id, 'content' => 'B', 'is_correct' => false, 'order' => 1]);

        $orig2 = $this->createQuestionWithAnswers($this->userB, ['content' => $uniqueToken2, 'difficulty' => 'hard']);
        $snap2 = Question::create([
            'user_id' => $this->userB->id,
            'origin_question_id' => $orig2->id,
            'content' => $uniqueToken2,
            'type' => 'single_choice',
            'difficulty' => 'hard',
            'is_public' => false,
            'bank_submission_status' => 'approved',
        ]);
        Answer::create(['question_id' => $snap2->id, 'content' => 'A', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $snap2->id, 'content' => 'B', 'is_correct' => false, 'order' => 1]);

        // 1. Kiểm tra Stats tăng đúng 2 (1 public, 1 private)
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/questions-management');

        $response->assertStatus(200);
        $this->assertEquals($initialTotal + 2, $response->json('data.stats.total'));
        $this->assertEquals($initialPublic + 1, $response->json('data.stats.public'));
        $this->assertEquals($initialPrivate + 1, $response->json('data.stats.private'));

        // 2. Lọc search
        $searchRes = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/questions-management?search=' . $uniqueToken1);
        $searchRes->assertStatus(200);
        $this->assertEquals(1, $searchRes->json('data.total'));
        $this->assertEquals($snap1->id, $searchRes->json('data.items.0.id'));

        // 3. Lọc search + visibility = public
        $pubRes = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/questions-management?search=' . $uniqueToken1 . '&visibility=public');
        $pubRes->assertStatus(200);
        $this->assertEquals(1, $pubRes->json('data.total'));
        $this->assertEquals($snap1->id, $pubRes->json('data.items.0.id'));

        // 4. Lọc search + visibility = private
        $privRes = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/questions-management?search=' . $uniqueToken2 . '&visibility=private');
        $privRes->assertStatus(200);
        $this->assertEquals(1, $privRes->json('data.total'));
        $this->assertEquals($snap2->id, $privRes->json('data.items.0.id'));

        // 5. Lọc search + difficulty = hard
        $diffRes = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/questions-management?search=' . $uniqueToken2 . '&difficulty=hard');
        $diffRes->assertStatus(200);
        $this->assertEquals(1, $diffRes->json('data.total'));
        $this->assertEquals($snap2->id, $diffRes->json('data.items.0.id'));
    }
}
