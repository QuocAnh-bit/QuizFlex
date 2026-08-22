<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionReviewRequest;
use App\Models\User;
use App\Notifications\QuestionModerated;
use App\Notifications\QuestionReviewRequested;
use App\Notifications\ReportAuthorUpdated;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuestionBankReviewHistoryTest extends TestCase
{
    protected User $author;
    protected User $admin;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::firstOrCreate(
            ['email' => 'author_review_test@quizflex.local'],
            [
                'name' => 'Author Review Test',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_review_test@quizflex.local'],
            [
                'name' => 'Admin Review Test',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        $this->otherUser = User::firstOrCreate(
            ['email' => 'other_review_test@quizflex.local'],
            [
                'name' => 'Other Review Test',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        // Dọn dẹp câu hỏi của các test users trước mỗi bài test
        Question::withTrashed()
            ->whereIn('user_id', [$this->author->id, $this->admin->id, $this->otherUser->id])
            ->forceDelete();
    }

    /**
     * Helper tạo câu hỏi kèm đáp án
     */
    protected function createSampleQuestion(array $questionOverrides = [], array $answers = []): Question
    {
        $question = Question::create(array_merge([
            'user_id' => $this->author->id,
            'content' => 'Thủ đô của Việt Nam là gì?',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => false,
            'bank_submission_status' => 'none',
        ], $questionOverrides));

        if (empty($answers)) {
            $answers = [
                ['content' => 'Hà Nội', 'is_correct' => true, 'order' => 0],
                ['content' => 'TP.HCM', 'is_correct' => false, 'order' => 1],
                ['content' => 'Đà Nẵng', 'is_correct' => false, 'order' => 2],
                ['content' => 'Cần Thơ', 'is_correct' => false, 'order' => 3],
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
     * 1. Lần đầu User gửi duyệt: Tạo revision #1 status pending, snapshot đầy đủ, gửi notification cho Admin
     */
    public function test_user_can_submit_question_to_bank_first_time()
    {
        Notification::fake();

        $question = $this->createSampleQuestion();

        $response = $this->actingAs($this->author, 'api')
            ->postJson("/api/user/my-questions/{$question->id}/submit-to-bank", [
                'note' => 'Nhờ admin duyệt câu hỏi này vào ngân hàng.',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Kiểm tra câu hỏi được chuyển pending
        $question->refresh();
        $this->assertEquals('pending', $question->bank_submission_status);
        $this->assertFalse($question->is_public);

        // Kiểm tra có đúng 1 bản ghi QuestionReviewRequest (Revision #1)
        $requests = QuestionReviewRequest::where('question_id', $question->id)->get();
        $this->assertCount(1, $requests);

        $rev1 = $requests->first();
        $this->assertEquals(1, $rev1->revision_number);
        $this->assertEquals('pending', $rev1->status);
        $this->assertEquals('Thủ đô của Việt Nam là gì?', $rev1->snapshot_content);
        $this->assertEquals('Nhờ admin duyệt câu hỏi này vào ngân hàng.', $rev1->request_note);
        $this->assertCount(4, $rev1->snapshot_answers);
        $this->assertEquals('Hà Nội', $rev1->snapshot_answers[0]['content']);
        $this->assertTrue($rev1->snapshot_answers[0]['is_correct']);

        // Kiểm tra Admin nhận notification QuestionReviewRequested
        Notification::assertSentTo($this->admin, QuestionReviewRequested::class);
    }

    /**
     * 2. Admin từ chối: Revision #1 chuyển rejected kèm lý do, Question lưu rejected note, tác giả nhận notification
     */
    public function test_admin_can_reject_question_bank_request_with_reason()
    {
        Notification::fake();

        $question = $this->createSampleQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$question->id}/reject", [
                'note' => 'Đáp án B cần viết hoa đầy đủ và bổ sung gợi ý.',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Kiểm tra Revision #1
        $rev1 = QuestionReviewRequest::where('question_id', $question->id)->first();
        $this->assertEquals('rejected', $rev1->status);
        $this->assertEquals('Đáp án B cần viết hoa đầy đủ và bổ sung gợi ý.', $rev1->rejection_reason);
        $this->assertEquals($this->admin->id, $rev1->reviewed_by);
        $this->assertNotNull($rev1->reviewed_at);

        // Kiểm tra Question
        $question->refresh();
        $this->assertEquals('rejected', $question->bank_submission_status);
        $this->assertEquals('Đáp án B cần viết hoa đầy đủ và bổ sung gợi ý.', $question->bank_submission_note);
        $this->assertFalse($question->is_public);

        // Tác giả nhận notification QuestionModerated (rejected)
        Notification::assertSentTo($this->author, QuestionModerated::class);
    }

    /**
     * 3. User sửa Question sau khi bị reject:
     * - Chỉ update nội dung và đáp án Question hiện tại.
     * - KHÔNG tạo revision mới.
     * - KHÔNG chuyển sang pending (vẫn giữ rejected).
     * - KHÔNG gửi notification duyệt cho Admin.
     */
    public function test_user_editing_question_after_rejection_does_not_create_revision_nor_trigger_pending()
    {
        Notification::fake();

        $question = $this->createSampleQuestion();
        // Submit lần 1 & Admin Reject
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Lý do reject lần 1']);

        // Reset notification fake để kiểm tra ở bước sửa
        Notification::fake();

        // User thực hiện sửa nội dung câu hỏi
        $response = $this->actingAs($this->author, 'api')
            ->putJson("/api/user/my-questions/{$question->id}", [
                'content' => 'Thủ đô của nước Việt Nam là thành phố nào?',
                'difficulty' => 'easy',
                'answers' => [
                    ['content' => 'Thành phố Hà Nội', 'is_correct' => true, 'key' => 'A'],
                    ['content' => 'Thành phố Hồ Chí Minh', 'is_correct' => false, 'key' => 'B'],
                    ['content' => 'Thành phố Đà Nẵng', 'is_correct' => false, 'key' => 'C'],
                    ['content' => 'Thành phố Cần Thơ', 'is_correct' => false, 'key' => 'D'],
                ],
            ]);

        $response->assertStatus(200);

        // Kiểm tra Question đã được sửa
        $question->refresh();
        $this->assertEquals('Thủ đô của nước Việt Nam là thành phố nào?', $question->content);
        // Trạng thái vẫn là 'rejected', không tự động chuyển thành 'pending'
        $this->assertEquals('rejected', $question->bank_submission_status);

        // Vẫn chỉ có 1 bản ghi QuestionReviewRequest (không tạo bản ghi mới ở bước này)
        $this->assertEquals(1, QuestionReviewRequest::where('question_id', $question->id)->count());

        // KHÔNG gửi notification ReportAuthorUpdated hoặc QuestionReviewRequested cho Admin
        Notification::assertNothingSent();
    }

    /**
     * 4. User bấm "Gửi duyệt" lần 2:
     * - Tạo Revision #2 (status = pending) với snapshot MỚI.
     * - Revision #1 VẪN NGUYÊN VẸN (chứa nội dung CŨ, lý do từ chối CŨ).
     */
    public function test_user_can_submit_question_second_time_and_preserves_first_revision_history()
    {
        Notification::fake();

        $question = $this->createSampleQuestion(['content' => 'Nội dung lần 1']);
        // Vòng 1: Submit & Reject
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Lỗi sai lần 1']);

        // User sửa nội dung lần 2
        $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$question->id}", [
            'content' => 'Nội dung lần 2 đã sửa chuẩn',
            'difficulty' => 'medium',
            'answers' => [
                ['content' => 'Đáp án A mới', 'is_correct' => true, 'key' => 'A'],
                ['content' => 'Đáp án B mới', 'is_correct' => false, 'key' => 'B'],
            ],
        ]);

        // User chủ động bấm "Gửi duyệt" lần 2
        $response = $this->actingAs($this->author, 'api')
            ->postJson("/api/user/my-questions/{$question->id}/submit-to-bank", [
                'note' => 'Em đã sửa lại theo góp ý của Admin.',
            ]);

        $response->assertStatus(200);

        // Kiểm tra Question: status = pending, note = null
        $question->refresh();
        $this->assertEquals('pending', $question->bank_submission_status);
        $this->assertNull($question->bank_submission_note);

        // Kiểm tra Database: Có đúng 2 revisions
        $revisions = QuestionReviewRequest::where('question_id', $question->id)->orderBy('revision_number')->get();
        $this->assertCount(2, $revisions);

        // Revision #1 (Cũ): Vẫn giữ nguyên vẹn nội dung cũ và lý do từ chối cũ
        $rev1 = $revisions[0];
        $this->assertEquals(1, $rev1->revision_number);
        $this->assertEquals('rejected', $rev1->status);
        $this->assertEquals('Nội dung lần 1', $rev1->snapshot_content);
        $this->assertEquals('Lỗi sai lần 1', $rev1->rejection_reason);

        // Revision #2 (Mới): Chứa nội dung mới và status pending
        $rev2 = $revisions[1];
        $this->assertEquals(2, $rev2->revision_number);
        $this->assertEquals('pending', $rev2->status);
        $this->assertEquals('Nội dung lần 2 đã sửa chuẩn', $rev2->snapshot_content);
        $this->assertEquals('medium', $rev2->snapshot_difficulty);
        $this->assertEquals('Em đã sửa lại theo góp ý của Admin.', $rev2->request_note);
        $this->assertCount(2, $rev2->snapshot_answers);
        $this->assertEquals('Đáp án A mới', $rev2->snapshot_answers[0]['content']);

        // Admin nhận notification duyệt lại lần 2
        Notification::assertSentTo($this->admin, QuestionReviewRequested::class);
    }

    /**
     * 5. Vòng đời 3 lần (Submit -> Reject -> Edit -> Submit -> Reject -> Edit -> Submit -> Approve):
     * Kiểm tra toàn bộ 3 revisions đều được bảo toàn.
     */
    public function test_full_three_round_review_cycle_and_approval()
    {
        $question = $this->createSampleQuestion(['content' => 'Vòng 1']);

        // Vòng 1: Submit & Reject
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Từ chối lần 1']);

        // Sửa vòng 2 & Submit & Reject
        $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$question->id}", [
            'content' => 'Vòng 2',
            'answers' => [
                ['content' => 'A2', 'is_correct' => true],
                ['content' => 'B2', 'is_correct' => false],
            ]
        ]);
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Từ chối lần 2']);

        // Sửa vòng 3 & Submit
        $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$question->id}", [
            'content' => 'Vòng 3 hoàn hảo',
            'answers' => [
                ['content' => 'A3 đúng', 'is_correct' => true],
                ['content' => 'B3 sai', 'is_correct' => false],
            ]
        ]);
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");

        // Admin Approve lần 3
        $approveRes = $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/approve");
        $approveRes->assertStatus(200);

        // Kiểm tra Question gốc
        $question->refresh();
        $this->assertEquals('approved', $question->bank_submission_status);
        $this->assertFalse((bool) $question->is_public); // Câu hỏi gốc của user vẫn là private

        // Kiểm tra tạo Question snapshot độc lập trong Bank
        $bankQuestion = Question::where('origin_question_id', $question->id)->where('is_public', true)->first();
        $this->assertNotNull($bankQuestion);
        $this->assertNotEquals($question->id, $bankQuestion->id);
        $this->assertTrue((bool) $bankQuestion->is_public);
        $this->assertEquals('approved', $bankQuestion->bank_submission_status);
        $this->assertEquals('Vòng 3 hoàn hảo', $bankQuestion->content);
        $this->assertCount(2, $bankQuestion->answers);

        // Kiểm tra Database có đủ 3 revisions
        $allRevs = QuestionReviewRequest::where('question_id', $question->id)->orderBy('revision_number')->get();
        $this->assertCount(3, $allRevs);

        $this->assertEquals('rejected', $allRevs[0]->status);
        $this->assertEquals('Vòng 1', $allRevs[0]->snapshot_content);
        $this->assertEquals('Từ chối lần 1', $allRevs[0]->rejection_reason);

        $this->assertEquals('rejected', $allRevs[1]->status);
        $this->assertEquals('Vòng 2', $allRevs[1]->snapshot_content);
        $this->assertEquals('Từ chối lần 2', $allRevs[1]->rejection_reason);

        $this->assertEquals('approved', $allRevs[2]->status);
        $this->assertEquals('Vòng 3 hoàn hảo', $allRevs[2]->snapshot_content);
        $this->assertEquals($this->admin->id, $allRevs[2]->reviewed_by);
    }

    /**
     * 6. Admin gọi API lấy chi tiết yêu cầu kèm Diff (Current Revision vs Previous Revision)
     */
    public function test_admin_can_view_review_request_with_diff_between_current_and_previous()
    {
        $question = $this->createSampleQuestion(['content' => 'Nội dung lần 1 cũ']);

        // Rev 1 Reject
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Sai chính tả']);

        // Sửa sang Rev 2 & Submit
        $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$question->id}", [
            'content' => 'Nội dung lần 2 đã sửa',
            'difficulty' => 'hard',
            'answers' => [
                ['content' => 'Đáp án mới A', 'is_correct' => true],
                ['content' => 'Đáp án mới B', 'is_correct' => false],
            ]
        ]);
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");

        // Admin gọi API xem chi tiết request
        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/question-bank-requests/{$question->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Kiểm tra Current Revision (Rev 2)
        $response->assertJsonPath('data.current_revision.revision_number', 2);
        $response->assertJsonPath('data.current_revision.status', 'pending');
        $response->assertJsonPath('data.current_revision.content', 'Nội dung lần 2 đã sửa');

        // Kiểm tra Previous Revision (Rev 1)
        $response->assertJsonPath('data.previous_revision.revision_number', 1);
        $response->assertJsonPath('data.previous_revision.status', 'rejected');
        $response->assertJsonPath('data.previous_revision.rejection_reason', 'Sai chính tả');
        $response->assertJsonPath('data.previous_revision.content', 'Nội dung lần 1 cũ');

        // Kiểm tra có đầy đủ mảng history 2 phần tử
        $this->assertCount(2, $response->json('data.history'));
    }

    /**
     * 7. User / Admin xem lịch sử các lần duyệt qua API review-history
     */
    public function test_user_can_view_own_question_review_history()
    {
        $question = $this->createSampleQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");

        $response = $this->actingAs($this->author, 'api')
            ->getJson("/api/user/my-questions/{$question->id}/review-history");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // Người dùng khác không được xem lịch sử
        $otherResponse = $this->actingAs($this->otherUser, 'api')
            ->getJson("/api/user/my-questions/{$question->id}/review-history");
        $otherResponse->assertStatus(403);
    }

    /**
     * 8. Không cho phép gửi duyệt lặp lại khi request đang PENDING
     */
    public function test_user_cannot_submit_duplicate_while_request_is_pending()
    {
        $question = $this->createSampleQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");

        // Gửi lại lần thứ 2 khi chưa được duyệt
        $duplicateResponse = $this->actingAs($this->author, 'api')
            ->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");

        $duplicateResponse->assertStatus(422);
    }

    /**
     * 9. Gửi duyệt HÀNG LOẠT (Bulk submit) tạo riêng từng snapshot cho mỗi câu hỏi
     */
    public function test_bulk_submit_creates_separate_revisions_for_each_question()
    {
        $q1 = $this->createSampleQuestion(['content' => 'Câu hỏi 1']);
        $q2 = $this->createSampleQuestion(['content' => 'Câu hỏi 2']);

        $response = $this->actingAs($this->author, 'api')
            ->postJson('/api/user/my-questions/bulk-submit-to-bank', [
                'ids' => [$q1->id, $q2->id],
            ]);

        $response->assertStatus(200);

        $this->assertEquals('pending', $q1->fresh()->bank_submission_status);
        $this->assertEquals('pending', $q2->fresh()->bank_submission_status);

        $this->assertEquals(1, QuestionReviewRequest::where('question_id', $q1->id)->count());
        $this->assertEquals(1, QuestionReviewRequest::where('question_id', $q2->id)->count());
    }

    /**
     * 10. Admin Bulk Approve và Bulk Reject
     */
    public function test_admin_bulk_approve_and_bulk_reject()
    {
        $q1 = $this->createSampleQuestion(['content' => 'Câu bulk 1']);
        $q2 = $this->createSampleQuestion(['content' => 'Câu bulk 2']);

        $this->actingAs($this->author, 'api')->postJson('/api/user/my-questions/bulk-submit-to-bank', ['ids' => [$q1->id, $q2->id]]);

        // Bulk Approve q1
        $approveRes = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/question-bank-requests/bulk-approve', ['ids' => [$q1->id]]);
        $approveRes->assertStatus(200);

        // Bulk Reject q2
        $rejectRes = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/question-bank-requests/bulk-reject', ['ids' => [$q2->id], 'note' => 'Không đạt chuẩn']);
        $rejectRes->assertStatus(200);

        $this->assertEquals('approved', $q1->fresh()->bank_submission_status);
        $this->assertFalse((bool) $q1->fresh()->is_public);

        $bankQ1 = Question::where('origin_question_id', $q1->id)->where('is_public', true)->first();
        $this->assertNotNull($bankQ1);
        $this->assertTrue((bool) $bankQ1->is_public);
        $this->assertEquals('approved', QuestionReviewRequest::where('question_id', $q1->id)->first()->status);

        $this->assertEquals('rejected', $q2->fresh()->bank_submission_status);
        $this->assertFalse($q2->fresh()->is_public);
        $this->assertEquals('rejected', QuestionReviewRequest::where('question_id', $q2->id)->first()->status);
        $this->assertEquals('Không đạt chuẩn', QuestionReviewRequest::where('question_id', $q2->id)->first()->rejection_reason);
    }

    /**
     * 11. Không cho phép Admin duyệt câu hỏi đang ở trạng thái REJECTED
     */
    public function test_admin_cannot_approve_rejected_question()
    {
        $question = $this->createSampleQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Chưa đạt']);

        $this->assertEquals('rejected', $question->fresh()->bank_submission_status);

        // Gọi trực tiếp API approve khi đang rejected
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$question->id}/approve");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['question']);

        // Câu hỏi vẫn giữ nguyên trạng thái rejected, không được chuyển sang approved
        $this->assertEquals('rejected', $question->fresh()->bank_submission_status);
        $this->assertFalse($question->fresh()->is_public);
    }

    /**
     * 12. Không cho phép Admin duyệt lại câu hỏi đã APPROVED
     */
    public function test_admin_cannot_approve_already_approved_question()
    {
        $question = $this->createSampleQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/approve");

        $this->assertEquals('approved', $question->fresh()->bank_submission_status);

        // Gọi trực tiếp API approve lần 2
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$question->id}/approve");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['question']);
    }

    /**
     * 13. Không cho phép Admin duyệt câu hỏi chưa từng gửi duyệt (trạng thái NONE)
     */
    public function test_admin_cannot_approve_unsubmitted_question()
    {
        $question = $this->createSampleQuestion(['bank_submission_status' => 'none']);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$question->id}/approve");

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['question']);
    }

    /**
     * 14. Không cho phép Admin từ chối câu hỏi không ở trạng thái PENDING
     */
    public function test_admin_cannot_reject_non_pending_question()
    {
        $question = $this->createSampleQuestion(['bank_submission_status' => 'none']);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Lý do']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['question']);
    }

    /**
     * 15. Pending -> Approve -> Tạo đúng 1 Question snapshot có ID khác và origin_question_id trỏ về câu hỏi gốc
     */
    public function test_approve_creates_independent_question_snapshot_in_bank()
    {
        $question = $this->createSampleQuestion([
            'content' => 'Câu hỏi snapshot ban đầu',
            'difficulty' => 'hard',
        ]);

        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");

        $approveRes = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$question->id}/approve");

        $approveRes->assertStatus(200);

        // 1. Câu hỏi gốc của User
        $question->refresh();
        $this->assertEquals('approved', $question->bank_submission_status);
        $this->assertFalse((bool) $question->is_public);

        // 2. Tạo đúng 1 Question snapshot trong Bank
        $snapshots = Question::where('origin_question_id', $question->id)->where('is_public', true)->get();
        $this->assertCount(1, $snapshots);

        $bankQuestion = $snapshots->first();
        $this->assertNotEquals($question->id, $bankQuestion->id);
        $this->assertEquals($question->id, $bankQuestion->origin_question_id);
        $this->assertTrue((bool) $bankQuestion->is_public);
        $this->assertEquals('approved', $bankQuestion->bank_submission_status);
        $this->assertEquals('Câu hỏi snapshot ban đầu', $bankQuestion->content);
        $this->assertEquals('hard', $bankQuestion->difficulty);
        $this->assertNull($bankQuestion->quiz_id);

        // Đáp án của snapshot là bản sao độc lập
        $this->assertCount(4, $bankQuestion->answers);
        $originalAnswerIds = $question->answers->pluck('id')->toArray();
        $snapshotAnswerIds = $bankQuestion->answers->pluck('id')->toArray();
        $this->assertEmpty(array_intersect($originalAnswerIds, $snapshotAnswerIds));
    }

    /**
     * 16. Snapshot sử dụng đúng dữ liệu tại thời điểm gửi duyệt (bất biến) ngay cả khi User đã sửa câu hỏi gốc sau đó
     */
    public function test_snapshot_uses_data_from_review_request_even_if_author_modified_original_question_after_submission()
    {
        $question = $this->createSampleQuestion([
            'content' => 'Nội dung lúc gửi duyệt A',
        ]);

        // Gửi duyệt lúc nội dung là A
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");

        // Sau đó User sửa câu hỏi gốc thành B (nhưng chưa gửi duyệt lại)
        $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$question->id}", [
            'content' => 'Nội dung sửa đổi B',
            'answers' => [
                ['content' => 'Đáp án B1', 'is_correct' => true],
                ['content' => 'Đáp án B2', 'is_correct' => false],
            ],
        ]);

        // Admin Approve yêu cầu ban đầu
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$question->id}/approve");

        // Câu hỏi gốc là B
        $question->refresh();
        $this->assertEquals('Nội dung sửa đổi B', $question->content);

        // Question trong Bank BẮT BUỘC là A (lấy từ snapshot của request)
        $bankQuestion = Question::where('origin_question_id', $question->id)->where('is_public', true)->first();
        $this->assertNotNull($bankQuestion);
        $this->assertEquals('Nội dung lúc gửi duyệt A', $bankQuestion->content);
        $this->assertEquals('Hà Nội', $bankQuestion->answers()->first()->content);
    }

    /**
     * 17. User sửa câu hỏi gốc sau khi Admin approve -> Question trong Bank KHÔNG bị thay đổi
     */
    public function test_author_modifying_original_question_after_approve_does_not_affect_bank_snapshot()
    {
        $question = $this->createSampleQuestion(['content' => 'Nội dung chuẩn gốc']);
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/approve");

        $bankQuestion = Question::where('origin_question_id', $question->id)->where('is_public', true)->first();
        $this->assertEquals('Nội dung chuẩn gốc', $bankQuestion->content);

        // User sửa câu hỏi gốc trong kho cá nhân
        $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$question->id}", [
            'content' => 'Nội dung đã bị user thay đổi hoàn toàn',
            'answers' => [
                ['content' => 'Đáp án mới', 'is_correct' => true],
                ['content' => 'Đáp án mới 2', 'is_correct' => false],
            ],
        ]);

        // Question trong Bank KHÔNG thay đổi
        $bankQuestion->refresh();
        $this->assertEquals('Nội dung chuẩn gốc', $bankQuestion->content);
    }

    /**
     * 18. User xóa câu hỏi gốc -> Question trong Bank vẫn tồn tại
     */
    public function test_author_deleting_original_question_keeps_bank_snapshot_intact()
    {
        $question = $this->createSampleQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/approve");

        $bankQuestion = Question::where('origin_question_id', $question->id)->where('is_public', true)->first();
        $this->assertNotNull($bankQuestion);

        // User xóa câu hỏi gốc
        $this->actingAs($this->author, 'api')->deleteJson("/api/user/my-questions/{$question->id}");

        // Câu hỏi trong Bank vẫn tồn tại
        $bankQuestion->refresh();
        $this->assertNull($bankQuestion->deleted_at);
        $this->assertTrue((bool) $bankQuestion->is_public);
    }

    /**
     * 19. Không tạo duplicate snapshot khi gọi approve trùng lặp
     */
    public function test_cannot_create_duplicate_snapshot_for_same_review_request()
    {
        $question = $this->createSampleQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");

        // Lần 1: Approve thành công
        $res1 = $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/approve");
        $res1->assertStatus(200);

        $countSnapshots = Question::where('origin_question_id', $question->id)->where('is_public', true)->count();
        $this->assertEquals(1, $countSnapshots);

        // Lần 2: Bị chặn bởi guard status
        $res2 = $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/approve");
        $res2->assertStatus(422);

        // Số lượng snapshot trong ngân hàng vẫn chính xác là 1
        $countSnapshotsAfter = Question::where('origin_question_id', $question->id)->where('is_public', true)->count();
        $this->assertEquals(1, $countSnapshotsAfter);
    }

    /**
     * 20. Admin xóa thành công Question Bank Snapshot đã approved
     */
    public function test_admin_can_delete_approved_bank_snapshot_question()
    {
        $question = $this->createSampleQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/approve");

        $bankQuestion = Question::where('origin_question_id', $question->id)->where('is_public', true)->first();
        $this->assertNotNull($bankQuestion);

        $response = $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/questions/{$bankQuestion->id}");
        $response->assertStatus(200);

        $this->assertTrue($bankQuestion->fresh()->trashed());
    }

    /**
     * 21. Admin KHÔNG được xóa Question đang ở trạng thái PENDING
     */
    public function test_admin_cannot_delete_pending_question()
    {
        $question = $this->createSampleQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");

        $this->assertEquals('pending', $question->fresh()->bank_submission_status);

        $response = $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/questions/{$question->id}");
        $response->assertStatus(403);

        $this->assertFalse($question->fresh()->trashed());
    }

    /**
     * 22. Admin KHÔNG được xóa Question đang ở trạng thái REJECTED
     */
    public function test_admin_cannot_delete_rejected_question()
    {
        $question = $this->createSampleQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Không đạt']);

        $this->assertEquals('rejected', $question->fresh()->bank_submission_status);

        $response = $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/questions/{$question->id}");
        $response->assertStatus(403);

        $this->assertFalse($question->fresh()->trashed());
    }

    /**
     * 23. Admin KHÔNG được xóa Question gốc của User (kể cả khi đã approve)
     */
    public function test_admin_cannot_delete_original_user_question()
    {
        $question = $this->createSampleQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/approve");

        // Câu hỏi gốc có origin_question_id = null và is_public = false
        $this->assertNull($question->origin_question_id);
        $this->assertFalse((bool)$question->fresh()->is_public);

        $response = $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/questions/{$question->id}");
        $response->assertStatus(403);

        $this->assertFalse($question->fresh()->trashed());
    }

    /**
     * 24. User không thể dùng Admin endpoint để xóa trái phép Bank Question
     */
    public function test_user_cannot_use_admin_delete_endpoint()
    {
        $question = $this->createSampleQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/approve");

        $bankQuestion = Question::where('origin_question_id', $question->id)->where('is_public', true)->first();

        $response = $this->actingAs($this->author, 'api')->deleteJson("/api/admin/questions/{$bankQuestion->id}");
        $response->assertStatus(403);

        $this->assertFalse($bankQuestion->fresh()->trashed());
    }

    /**
     * 25. Admin xóa Bank Snapshot -> Question gốc của User không bị xóa
     */
    public function test_admin_deleting_bank_snapshot_does_not_delete_original_user_question()
    {
        $question = $this->createSampleQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/approve");

        $bankQuestion = Question::where('origin_question_id', $question->id)->where('is_public', true)->first();

        // Admin xóa Bank Question
        $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/questions/{$bankQuestion->id}");

        $this->assertTrue($bankQuestion->fresh()->trashed());
        $this->assertFalse($question->fresh()->trashed()); // Câu hỏi gốc của user vẫn tồn tại nguyên vẹn
    }

    /**
     * 26. Bulk delete danh sách approved Bank Snapshots thành công
     */
    public function test_admin_bulk_delete_approved_snapshots()
    {
        $q1 = $this->createSampleQuestion(['content' => 'Bank Q1']);
        $q2 = $this->createSampleQuestion(['content' => 'Bank Q2']);

        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$q1->id}/submit-to-bank");
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$q2->id}/submit-to-bank");

        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$q1->id}/approve");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$q2->id}/approve");

        $b1 = Question::where('origin_question_id', $q1->id)->where('is_public', true)->first();
        $b2 = Question::where('origin_question_id', $q2->id)->where('is_public', true)->first();

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/questions/bulk-delete', [
            'question_ids' => [$b1->id, $b2->id],
        ]);

        $response->assertStatus(200);
        $this->assertTrue($b1->fresh()->trashed());
        $this->assertTrue($b2->fresh()->fresh()->trashed());
    }

    /**
     * 27. Bulk delete chứa danh sách hỗn hợp (approved snapshot + pending + rejected + original) -> Chỉ xóa approved snapshot
     */
    public function test_admin_bulk_delete_with_mixed_questions_only_deletes_approved_snapshots()
    {
        // 1. Approved snapshot
        $q1 = $this->createSampleQuestion(['content' => 'Bank Q1']);
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$q1->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$q1->id}/approve");
        $bankQ1 = Question::where('origin_question_id', $q1->id)->where('is_public', true)->first();

        // 2. Pending question
        $qPending = $this->createSampleQuestion(['content' => 'Pending Q']);
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$qPending->id}/submit-to-bank");

        // 3. Rejected question
        $qRejected = $this->createSampleQuestion(['content' => 'Rejected Q']);
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$qRejected->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$qRejected->id}/reject", ['note' => 'Chưa chuẩn']);

        // 4. Original Question
        $qOriginal = $q1;

        // Gọi bulk-delete gửi cả 4 ID
        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/questions/bulk-delete', [
            'question_ids' => [$bankQ1->id, $qPending->id, $qRejected->id, $qOriginal->id],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.deleted_count', 1);
        $response->assertJsonPath('data.skipped_count', 3);

        // Chỉ Bank snapshot bị xóa
        $this->assertTrue($bankQ1->fresh()->trashed());

        // Các câu hỏi khác không bị xóa
        $this->assertFalse($qPending->fresh()->trashed());
        $this->assertFalse($qRejected->fresh()->trashed());
        $this->assertFalse($qOriginal->fresh()->trashed());
    }

    /**
     * 28. Bulk delete toàn bộ câu hỏi không hợp lệ -> Bị từ chối HTTP 403
     */
    public function test_admin_bulk_delete_all_invalid_returns_403()
    {
        $qPending = $this->createSampleQuestion(['content' => 'Pending Q']);
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$qPending->id}/submit-to-bank");

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/questions/bulk-delete', [
            'question_ids' => [$qPending->id],
        ]);

        $response->assertStatus(403);
        $this->assertFalse($qPending->fresh()->trashed());
    }
}



