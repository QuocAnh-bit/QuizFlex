<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizReviewRequest;
use App\Models\User;
use App\Notifications\QuizModerated;
use App\Notifications\QuizReviewRequested;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuizReviewRevisionAndAuthorizationTest extends TestCase
{
    protected User $author;
    protected User $admin;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::firstOrCreate(
            ['email' => 'author_revision_test@quizflex.local'],
            [
                'name' => 'Author Revision Test',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_revision_test@quizflex.local'],
            [
                'name' => 'Admin Revision Test',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        $this->otherUser = User::firstOrCreate(
            ['email' => 'other_user_revision_test@quizflex.local'],
            [
                'name' => 'Other User Revision Test',
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
     * Helper tạo Quiz kèm câu hỏi và đáp án hoàn chỉnh
     */
    protected function createSampleQuiz(array $quizOverrides = [], int $questionCount = 2): Quiz
    {
        $quiz = Quiz::create(array_merge([
            'user_id' => $this->author->id,
            'title' => 'Bài Quiz Kiểm Thử Revision',
            'description' => 'Mô tả ban đầu của Quiz',
            'topic_name' => 'Toán học',
            'creation_mode' => 'manual',
            'review_status' => 'draft',
            'is_public' => false,
            'status' => 'draft',
            'time_limit_seconds' => 900,
        ], $quizOverrides));

        $syncData = [];
        $uid = uniqid();
        for ($i = 1; $i <= $questionCount; $i++) {
            $question = Question::create([
                'user_id' => $this->author->id,
                'content' => "Câu hỏi số {$i} nội dung chuẩn {$uid}",
                'type' => 'single_choice',
                'difficulty' => 'medium',
                'is_public' => false,
            ]);

            Answer::create(['question_id' => $question->id, 'content' => "Đáp án {$i}A đúng {$uid}", 'is_correct' => true, 'order' => 0]);
            Answer::create(['question_id' => $question->id, 'content' => "Đáp án {$i}B sai {$uid}", 'is_correct' => false, 'order' => 1]);

            $syncData[$question->id] = ['order' => $i - 1, 'points' => 10];
        }

        $quiz->questions()->sync($syncData);

        return $quiz->fresh(['questions.answers', 'user']);
    }

    // =========================================================================
    // I. QUIZ EDIT FLOW & REJECTION HISTORY PRESERVATION
    // =========================================================================

    /**
     * 1. User edit Quiz sau khi bị reject:
     * - Chỉ update Quiz hiện tại.
     * - KHÔNG tạo QuizReviewRequest mới.
     * - KHÔNG tự động chuyển sang pending_review (vẫn giữ rejected).
     * - KHÔNG xóa rejection history / không sửa previous request.
     * - KHÔNG gửi notification cho Admin.
     */
    public function test_user_edit_rejected_quiz_preserves_review_request_history_and_does_not_auto_pending()
    {
        Notification::fake();

        $quiz = $this->createSampleQuiz();

        // Submit lần 1
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev1 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();

        // Admin Reject lần 1
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/quiz-review-requests/{$rev1->id}/reject", [
            'reason' => 'Đề thi cần bổ sung giải thích chi tiết.',
        ]);

        $quiz->refresh();
        $this->assertEquals('rejected', $quiz->review_status);
        $this->assertEquals('Đề thi cần bổ sung giải thích chi tiết.', $quiz->rejection_reason);

        // Reset notification fake để kiểm tra thao tác Edit của User
        Notification::fake();

        // User thực hiện Edit Quiz
        $response = $this->actingAs($this->author, 'api')->putJson("/api/quizzes/{$quiz->id}", [
            'title' => 'Bài Quiz Đã Chỉnh Sửa Tiêu Đề',
            'description' => 'Mô tả mới sau khi tiếp thu góp ý',
        ]);

        $response->assertStatus(200);

        $quiz->refresh();
        $this->assertEquals('Bài Quiz Đã Chỉnh Sửa Tiêu Đề', $quiz->title);
        // Trạng thái review_status vẫn giữ nguyên là 'rejected', không tự động chuyển thành 'pending_review'
        $this->assertEquals('rejected', $quiz->review_status);

        // Database vẫn chỉ có đúng 1 bản ghi QuizReviewRequest
        $requests = QuizReviewRequest::where('quiz_id', $quiz->id)->get();
        $this->assertCount(1, $requests);

        // Bản ghi cũ không bị sửa hoặc xóa
        $this->assertEquals('rejected', $requests[0]->status);
        $this->assertEquals('Đề thi cần bổ sung giải thích chi tiết.', $requests[0]->rejection_reason);

        // Không gửi notification cho Admin khi user sửa bài nháp/bài bị từ chối
        Notification::assertNothingSent();
    }

    // =========================================================================
    // II. REQUEST REVIEW & REVISION NUMBER INCREMENT & SNAPSHOT CREATION
    // =========================================================================

    /**
     * 2. User gửi duyệt (Submit Review):
     * - Tạo QuizReviewRequest mới (Revision #1).
     * - Tự động snapshot tiêu đề, mô tả, danh sách câu hỏi, đáp án, điểm số.
     * - status = 'pending', quiz.review_status = 'pending_review'.
     * - Gửi thông báo QuizReviewRequested đến Admin.
     */
    public function test_user_submit_review_creates_new_revision_and_increments_revision_number()
    {
        Notification::fake();

        $quiz = $this->createSampleQuiz(['title' => 'Đề Thi Toán Học Đại Số 10']);

        $response = $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review", [
            'request_note' => 'Nhờ Admin duyệt đề thi Toán 10 này ạ.',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        $quiz->refresh();
        $this->assertEquals('pending_review', $quiz->review_status);
        $this->assertFalse((bool) $quiz->is_public);

        // Kiểm tra Revision #1 được tạo với snapshot đầy đủ
        $request = QuizReviewRequest::where('quiz_id', $quiz->id)->first();
        $this->assertNotNull($request);
        $this->assertEquals(1, $request->revision_number);
        $this->assertEquals('pending', $request->status);
        $this->assertEquals('Nhờ Admin duyệt đề thi Toán 10 này ạ.', $request->request_note);
        $this->assertEquals('Đề Thi Toán Học Đại Số 10', $request->snapshot_title);
        $this->assertCount(2, $request->snapshot_questions);
        $this->assertEquals($quiz->questions[0]->content, $request->snapshot_questions[0]['content']);
        $this->assertCount(2, $request->snapshot_questions[0]['answers']);
        $this->assertTrue($request->snapshot_questions[0]['answers'][0]['is_correct']);

        // Kiểm tra thông báo được gửi đến Admin
        Notification::assertSentTo($this->admin, QuizReviewRequested::class);
    }

    /**
     * 3. Chống gửi duyệt lặp lại khi Quiz đang có yêu cầu PENDING
     */
    public function test_cannot_submit_duplicate_while_quiz_review_is_pending()
    {
        $quiz = $this->createSampleQuiz();

        // Gửi lần 1 -> Thành công
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review")->assertStatus(201);

        // Gửi lần 2 khi chưa được xử lý -> 403 / 422
        $res = $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $this->assertTrue(in_array($res->status(), [403, 422]));
    }

    // =========================================================================
    // III. IMMUTABILITY OF SNAPSHOTS
    // =========================================================================

    /**
     * 4. Tính bất biến của Snapshot:
     * Tác giả sửa đổi câu hỏi sau khi gửi duyệt không làm thay đổi dữ liệu Snapshot đã lưu
     */
    public function test_snapshot_is_immutable_even_if_author_edits_quiz_after_submission()
    {
        $quiz = $this->createSampleQuiz(['title' => 'Tiêu đề Snapshot Gốc']);
        $initialQContent = $quiz->questions[0]->content;

        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $req = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();

        // Giả sử câu hỏi trong Quiz bị sửa
        $q = $quiz->questions()->first();
        $q->update(['content' => 'Nội dung câu hỏi đã bị User sửa đổi sau khi nộp']);

        // Snapshot trong QuizReviewRequest vẫn giữ nguyên nội dung gốc tại thời điểm nộp
        $req->refresh();
        $this->assertEquals($initialQContent, $req->snapshot_questions[0]['content']);
        $this->assertNotEquals($q->fresh()->content, $req->snapshot_questions[0]['content']);
        $this->assertEquals('Tiêu đề Snapshot Gốc', $req->snapshot_title);
    }

    // =========================================================================
    // IV. MULTI-ROUND REVIEW CYCLE (3 REVISIONS) & PREVIOUS DIFF
    // =========================================================================

    /**
     * 5. Vòng đời duyệt 3 lần hoàn chỉnh:
     * Rev 1 (Reject) -> Rev 2 (Reject) -> Rev 3 (Approve)
     * Toàn bộ 3 revisions và lịch sử được bảo toàn.
     */
    public function test_three_round_quiz_review_cycle()
    {
        Notification::fake();

        $quiz = $this->createSampleQuiz(['title' => 'Đề Thi Vòng 1']);

        // VÒNG 1: Submit & Reject
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review", ['request_note' => 'Nộp lần 1']);
        $rev1 = QuizReviewRequest::where('quiz_id', $quiz->id)->where('revision_number', 1)->first();
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/quiz-review-requests/{$rev1->id}/reject", ['reason' => 'Từ chối lần 1']);

        // Sửa nội dung vòng 2
        $this->actingAs($this->author, 'api')->putJson("/api/quizzes/{$quiz->id}", [
            'title' => 'Đề Thi Vòng 2 Đã Sửa',
        ]);

        // VÒNG 2: Submit & Reject
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review", ['request_note' => 'Nộp lần 2']);
        $rev2 = QuizReviewRequest::where('quiz_id', $quiz->id)->where('revision_number', 2)->first();
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/quiz-review-requests/{$rev2->id}/reject", ['reason' => 'Từ chối lần 2']);

        // Sửa nội dung vòng 3
        $this->actingAs($this->author, 'api')->putJson("/api/quizzes/{$quiz->id}", [
            'title' => 'Đề Thi Vòng 3 Hoàn Chỉnh',
        ]);

        // VÒNG 3: Submit & Approve
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review", ['request_note' => 'Nộp lần 3']);
        $rev3 = QuizReviewRequest::where('quiz_id', $quiz->id)->where('revision_number', 3)->first();
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/quiz-review-requests/{$rev3->id}/approve");

        // Kiểm tra Quiz sau khi duyệt vòng 3
        $quiz->refresh();
        $this->assertEquals('approved', $quiz->review_status);
        $this->assertTrue((bool) $quiz->is_public);
        $this->assertEquals('published', $quiz->status);

        // Kiểm tra Database có đủ 3 revisions độc lập
        $allRevs = QuizReviewRequest::where('quiz_id', $quiz->id)->orderBy('revision_number')->get();
        $this->assertCount(3, $allRevs);

        $this->assertEquals(1, $allRevs[0]->revision_number);
        $this->assertEquals('rejected', $allRevs[0]->status);
        $this->assertEquals('Đề Thi Vòng 1', $allRevs[0]->snapshot_title);
        $this->assertEquals('Từ chối lần 1', $allRevs[0]->rejection_reason);

        $this->assertEquals(2, $allRevs[1]->revision_number);
        $this->assertEquals('rejected', $allRevs[1]->status);
        $this->assertEquals('Đề Thi Vòng 2 Đã Sửa', $allRevs[1]->snapshot_title);
        $this->assertEquals('Từ chối lần 2', $allRevs[1]->rejection_reason);

        $this->assertEquals(3, $allRevs[2]->revision_number);
        $this->assertEquals('approved', $allRevs[2]->status);
        $this->assertEquals('Đề Thi Vòng 3 Hoàn Chỉnh', $allRevs[2]->snapshot_title);
        $this->assertEquals($this->admin->id, $allRevs[2]->reviewed_by);
    }

    /**
     * 6. Admin gọi API lấy chi tiết yêu cầu kèm Diff (Current vs Previous)
     */
    public function test_admin_detail_review_request_returns_current_previous_and_diff()
    {
        $quiz = $this->createSampleQuiz(['title' => 'Đề Thi Cũ V1']);

        // Rev 1 Reject
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev1 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/quiz-review-requests/{$rev1->id}/reject", ['reason' => 'Tiêu đề chưa rõ']);

        // Sửa và nộp Rev 2
        $this->actingAs($this->author, 'api')->putJson("/api/quizzes/{$quiz->id}", ['title' => 'Đề Thi Mới V2']);
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev2 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();

        // Admin gọi API xem chi tiết
        $res = $this->actingAs($this->admin, 'api')->getJson("/api/admin/quiz-review-requests/{$rev2->id}");

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);

        // Kiểm tra Current Revision
        $res->assertJsonPath('data.current_revision.revision_number', 2);
        $res->assertJsonPath('data.current_revision.title', 'Đề Thi Mới V2');
        $res->assertJsonPath('data.current_revision.status', 'pending');

        // Kiểm tra Previous Revision
        $res->assertJsonPath('data.previous_revision.revision_number', 1);
        $res->assertJsonPath('data.previous_revision.title', 'Đề Thi Cũ V1');
        $res->assertJsonPath('data.previous_revision.status', 'rejected');
        $res->assertJsonPath('data.previous_rejection_reason', 'Tiêu đề chưa rõ');

        // Kiểm tra Diff
        $res->assertJsonPath('data.diff.has_previous', true);
        $res->assertJsonPath('data.diff.metadata_changed', true);
        $res->assertJsonPath('data.diff.changes.title.old', 'Đề Thi Cũ V1');
        $res->assertJsonPath('data.diff.changes.title.new', 'Đề Thi Mới V2');

        // Kiểm tra history
        $this->assertCount(2, $res->json('data.history'));
    }

    /**
     * 7. User và Admin có thể xem lịch sử gửi duyệt; người dùng khác bị 403
     */
    public function test_user_and_admin_can_view_quiz_review_history()
    {
        $quiz = $this->createSampleQuiz();
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");

        // Tác giả xem -> 200
        $authorRes = $this->actingAs($this->author, 'api')->getJson("/api/quizzes/{$quiz->id}/review-history");
        $authorRes->assertStatus(200);
        $this->assertCount(1, $authorRes->json('data'));

        // Admin xem -> 200
        $adminRes = $this->actingAs($this->admin, 'api')->getJson("/api/quizzes/{$quiz->id}/review-history");
        $adminRes->assertStatus(200);
        $this->assertCount(1, $adminRes->json('data'));

        // Người dùng khác xem -> 403
        $otherRes = $this->actingAs($this->otherUser, 'api')->getJson("/api/quizzes/{$quiz->id}/review-history");
        $otherRes->assertStatus(403);
    }

    // =========================================================================
    // V. ADMIN AUTHORIZATION MATRIX (ALL 403 RESTRICTIONS)
    // =========================================================================

    /**
     * 8. Admin KHÔNG ĐƯỢC: POST /quizzes -> 403
     */
    public function test_admin_cannot_create_quiz_via_post_quizzes()
    {
        $res = $this->actingAs($this->admin, 'api')->postJson('/api/quizzes', [
            'title' => 'Admin Cố Tình Tạo Quiz',
        ]);
        $res->assertStatus(403);
    }

    /**
     * 9. Admin KHÔNG ĐƯỢC: POST /quizzes/from-bank -> 403
     */
    public function test_admin_cannot_create_quiz_from_bank()
    {
        $res = $this->actingAs($this->admin, 'api')->postJson('/api/quizzes/from-bank', [
            'title' => 'Admin Cố Tình Tạo Quiz Từ Bank',
            'easy_count' => 1,
        ]);
        $res->assertStatus(403);
    }

    /**
     * 10. Admin KHÔNG ĐƯỢC: PUT/PATCH /quizzes/{quiz} -> 403
     */
    public function test_admin_cannot_update_quiz_via_put_quizzes()
    {
        $quiz = $this->createSampleQuiz();

        $res = $this->actingAs($this->admin, 'api')->putJson("/api/quizzes/{$quiz->id}", [
            'title' => 'Admin Cố Tình Sửa Quiz Của User',
        ]);
        $res->assertStatus(403);
    }

    /**
     * 11. Admin KHÔNG ĐƯỢC: DELETE /quizzes/{quiz} -> 403
     */
    public function test_admin_cannot_delete_quiz_via_delete_quizzes()
    {
        $quiz = $this->createSampleQuiz();

        $res = $this->actingAs($this->admin, 'api')->deleteJson("/api/quizzes/{$quiz->id}");
        $res->assertStatus(403);
    }

    /**
     * 12. Admin KHÔNG ĐƯỢC: POST /questions -> 403
     */
    public function test_admin_cannot_create_question_via_store_question()
    {
        $res = $this->actingAs($this->admin, 'api')->postJson('/api/questions', [
            'content' => 'Admin Cố Tình Tạo Câu Hỏi',
            'answers' => [
                ['content' => 'A', 'is_correct' => true],
                ['content' => 'B', 'is_correct' => false],
            ],
        ]);
        $res->assertStatus(403);
    }

    /**
     * 13. Admin KHÔNG ĐƯỢC: PUT /user/my-questions/{id} -> 403
     */
    public function test_admin_cannot_update_question_via_update_question()
    {
        $question = Question::create([
            'user_id' => $this->author->id,
            'content' => 'Câu hỏi cá nhân',
            'is_public' => false,
        ]);

        $res = $this->actingAs($this->admin, 'api')->putJson("/api/user/my-questions/{$question->id}", [
            'content' => 'Admin Cố Tình Sửa Câu Hỏi Cá Nhân',
            'answers' => [
                ['content' => 'A', 'is_correct' => true],
                ['content' => 'B', 'is_correct' => false],
            ],
        ]);
        $res->assertStatus(403);
    }

    /**
     * 14. Admin KHÔNG ĐƯỢC: PUT /questions/{question} -> 403
     */
    public function test_admin_cannot_update_question_via_questions_endpoint()
    {
        $quiz = $this->createSampleQuiz();
        $question = $quiz->questions()->first();

        $res = $this->actingAs($this->admin, 'api')->putJson("/api/questions/{$question->id}", [
            'content' => 'Admin Cố Tình Sửa Câu Hỏi',
        ]);
        $res->assertStatus(403);
    }

    /**
     * 15. Admin KHÔNG ĐƯỢC: DELETE /questions/{question} -> 403
     */
    public function test_admin_cannot_delete_question_via_delete_question()
    {
        $quiz = $this->createSampleQuiz();
        $question = $quiz->questions()->first();

        $res = $this->actingAs($this->admin, 'api')->deleteJson("/api/questions/{$question->id}");
        $res->assertStatus(403);
    }

    /**
     * 16. Admin KHÔNG ĐƯỢC: POST /quizzes/{quiz}/questions -> 403
     */
    public function test_admin_cannot_add_question_to_quiz()
    {
        $quiz = $this->createSampleQuiz();

        $res = $this->actingAs($this->admin, 'api')->postJson("/api/quizzes/{$quiz->id}/questions", [
            'content' => 'Admin Cố Tình Thêm Câu Hỏi Vào Quiz',
            'answers' => [
                ['content' => 'A', 'is_correct' => true],
                ['content' => 'B', 'is_correct' => false],
            ],
        ]);
        $res->assertStatus(403);
    }

    /**
     * 17. Admin KHÔNG ĐƯỢC: PUT /api/admin/questions/{id} (sửa trực tiếp Bank Question) -> 403
     */
    public function test_admin_cannot_directly_edit_question_bank_question()
    {
        $bankQ = Question::create([
            'user_id' => $this->author->id,
            'content' => 'Câu hỏi Bank',
            'is_public' => true,
            'bank_submission_status' => 'approved',
        ]);

        $res = $this->actingAs($this->admin, 'api')->putJson("/api/admin/questions/{$bankQ->id}", [
            'content' => 'Admin Cố Tình Sửa Nội Dung Bank Question',
            'answers' => [
                ['content' => 'A', 'is_correct' => true],
                ['content' => 'B', 'is_correct' => false],
            ],
        ]);
        $res->assertStatus(403);
    }

    /**
     * 18. Admin KHÔNG ĐƯỢC: Tạo Quiz bằng AI hoặc Import OCR Quiz -> 403
     */
    public function test_admin_cannot_create_ai_or_ocr_quiz()
    {
        $aiRes = $this->actingAs($this->admin, 'api')->postJson('/api/ai/generate', [
            'prompt' => 'Tạo 5 câu hỏi Toán 10 phương trình bậc 2',
        ]);
        $aiRes->assertStatus(403);

        $ocrRes = $this->actingAs($this->admin, 'api')->postJson('/api/ocr/import-quiz', [
            'title' => 'OCR Quiz',
            'questions' => [],
        ]);
        $ocrRes->assertStatus(403);
    }

    // =========================================================================
    // VI. ADMIN ALLOWED ACTIONS & USER REGRESSION
    // =========================================================================

    /**
     * 19. Admin ĐƯỢC PHÉP: Xem danh sách review requests, Duyệt / Từ chối hàng loạt
     */
    public function test_admin_allowed_quiz_review_actions()
    {
        $quiz1 = $this->createSampleQuiz(['title' => 'Quiz 1']);
        $quiz2 = $this->createSampleQuiz(['title' => 'Quiz 2']);

        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz1->id}/request-review");
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz2->id}/request-review");

        $req1 = QuizReviewRequest::where('quiz_id', $quiz1->id)->latest('id')->first();
        $req2 = QuizReviewRequest::where('quiz_id', $quiz2->id)->latest('id')->first();

        // 1. Admin xem danh sách -> 200
        $listRes = $this->actingAs($this->admin, 'api')->getJson('/api/admin/quiz-review-requests');
        $listRes->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, $listRes->json('data.total'));

        // 2. Admin Bulk Approve req1 -> 200
        $bulkApprove = $this->actingAs($this->admin, 'api')->postJson('/api/admin/quiz-review-requests/bulk-approve', [
            'ids' => [$req1->id],
        ]);
        $bulkApprove->assertStatus(200);
        $this->assertEquals('approved', $req1->fresh()->status);
        $this->assertTrue((bool) $quiz1->fresh()->is_public);

        // 3. Admin Bulk Reject req2 -> 200
        $bulkReject = $this->actingAs($this->admin, 'api')->postJson('/api/admin/quiz-review-requests/bulk-reject', [
            'ids' => [$req2->id],
            'reason' => 'Đề thi không đạt chuẩn chất lượng',
        ]);
        $bulkReject->assertStatus(200);
        $this->assertEquals('rejected', $req2->fresh()->status);
        $this->assertFalse((bool) $quiz2->fresh()->is_public);
    }

    /**
     * 20. User Regression: Người dùng bình thường tạo và sửa Quiz, Question thành công
     */
    public function test_user_regression_create_and_edit_quiz_and_question()
    {
        // 1. User tạo Question
        $createQRes = $this->actingAs($this->author, 'api')->postJson('/api/questions', [
            'content' => 'Câu hỏi mới của tác giả',
            'answers' => [
                ['content' => 'Đáp án 1', 'is_correct' => true],
                ['content' => 'Đáp án 2', 'is_correct' => false],
            ],
        ]);
        $createQRes->assertStatus(201);
        $qId = $createQRes->json('data.id');

        // 2. User sửa Question
        $editQRes = $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$qId}", [
            'content' => 'Câu hỏi mới của tác giả (đã sửa)',
            'answers' => [
                ['content' => 'Đáp án 1 mới', 'is_correct' => true],
                ['content' => 'Đáp án 2 mới', 'is_correct' => false],
            ],
        ]);
        $editQRes->assertStatus(200);

        // 3. User tạo Quiz
        $createQuizRes = $this->actingAs($this->author, 'api')->postJson('/api/quizzes', [
            'title' => 'Quiz Mới Tạo Bởi User',
            'questions' => [
                [
                    'id' => $qId,
                    'content' => 'Câu hỏi mới của tác giả (đã sửa)',
                    'answers' => [
                        ['content' => 'Đáp án 1 mới', 'is_correct' => true],
                        ['content' => 'Đáp án 2 mới', 'is_correct' => false],
                    ],
                ]
            ],
        ]);
        $createQuizRes->assertStatus(201);
        $quizId = $createQuizRes->json('data.id');

        // 4. User sửa Quiz
        $editQuizRes = $this->actingAs($this->author, 'api')->putJson("/api/quizzes/{$quizId}", [
            'title' => 'Quiz Mới Tạo Bởi User (Đã Đổi Tên)',
        ]);
        $editQuizRes->assertStatus(200);
        $this->assertEquals('Quiz Mới Tạo Bởi User (Đã Đổi Tên)', Quiz::find($quizId)->title);
    }

    // =========================================================================
    // VII. PHASE 4 DIFF COMPARISON SPECIFIC TEST CASES
    // =========================================================================

    /**
     * Case 1: Old = New -> has_differences is false
     */
    public function test_diff_when_old_equals_new_has_no_differences()
    {
        $quiz = $this->createSampleQuiz();

        // Submit Rev 1
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev1 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/quiz-review-requests/{$rev1->id}/reject", ['reason' => 'Từ chối lần 1']);

        // Submit Rev 2 with NO changes
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev2 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();

        $res = $this->actingAs($this->admin, 'api')->getJson("/api/admin/quiz-review-requests/{$rev2->id}");
        $res->assertStatus(200);
        $res->assertJsonPath('data.diff.has_previous', true);
        $res->assertJsonPath('data.diff.has_differences', false);
        $res->assertJsonPath('data.diff.metadata_changed', false);
        $this->assertEmpty($res->json('data.diff.question_diffs'));
    }

    /**
     * Case 2 & 3: Only metadata changed (Title / Description)
     */
    public function test_diff_when_only_metadata_changed()
    {
        $quiz = $this->createSampleQuiz(['title' => 'Toán 10', 'description' => 'Mô tả cũ']);

        // Rev 1
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev1 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/quiz-review-requests/{$rev1->id}/reject", ['reason' => 'Sửa tiêu đề']);

        // Edit title & description
        $this->actingAs($this->author, 'api')->putJson("/api/quizzes/{$quiz->id}", [
            'title' => 'Toán 10 Nâng Cao',
            'description' => 'Mô tả mới hoàn toàn',
        ]);

        // Rev 2
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev2 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();

        $res = $this->actingAs($this->admin, 'api')->getJson("/api/admin/quiz-review-requests/{$rev2->id}");
        $res->assertStatus(200);
        $res->assertJsonPath('data.diff.has_previous', true);
        $res->assertJsonPath('data.diff.has_differences', true);
        $res->assertJsonPath('data.diff.metadata_changed', true);
        $res->assertJsonPath('data.diff.changes.title.old', 'Toán 10');
        $res->assertJsonPath('data.diff.changes.title.new', 'Toán 10 Nâng Cao');
        $res->assertJsonPath('data.diff.changes.description.old', 'Mô tả cũ');
        $res->assertJsonPath('data.diff.changes.description.new', 'Mô tả mới hoàn toàn');
        $this->assertEmpty($res->json('data.diff.question_diffs'));
    }

    /**
     * Case 4, 5, 6, 7: Questions added, removed, and modified with answer changes
     */
    public function test_diff_question_added_removed_and_modified_with_answers()
    {
        $quiz = $this->createSampleQuiz([], 2);
        $q1 = $quiz->questions[0];
        $q2 = $quiz->questions[1];

        // Rev 1
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev1 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/quiz-review-requests/{$rev1->id}/reject", ['reason' => 'Từ chối câu hỏi']);

        // Sửa câu 1 (thay đổi nội dung & đáp án)
        $q1->update(['content' => 'Câu hỏi số 1 đã đổi nội dung']);
        $ans1A = $q1->answers()->first();
        $ans1A->update(['content' => 'Đáp án 1A đã đổi nội dung']);

        // Xóa câu 2 khỏi quiz, thêm câu 3 mới
        $q3 = Question::create([
            'user_id' => $this->author->id,
            'content' => 'Câu hỏi số 3 mới tinh',
            'type' => 'single_choice',
            'is_public' => false,
        ]);
        Answer::create(['question_id' => $q3->id, 'content' => 'A3', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $q3->id, 'content' => 'B3', 'is_correct' => false, 'order' => 1]);

        $quiz->questions()->sync([
            $q1->id => ['order' => 0, 'points' => 10],
            $q3->id => ['order' => 1, 'points' => 10],
        ]);

        // Rev 2
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev2 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();

        $res = $this->actingAs($this->admin, 'api')->getJson("/api/admin/quiz-review-requests/{$rev2->id}");
        $res->assertStatus(200);
        $res->assertJsonPath('data.diff.has_previous', true);
        $res->assertJsonPath('data.diff.has_differences', true);
        $res->assertJsonPath('data.diff.questions_summary.added_count', 1);
        $res->assertJsonPath('data.diff.questions_summary.removed_count', 1);
        $res->assertJsonPath('data.diff.questions_summary.modified_count', 1);

        $diffs = collect($res->json('data.diff.question_diffs'));
        $this->assertTrue($diffs->contains('status', 'added'));
        $this->assertTrue($diffs->contains('status', 'removed'));
        $this->assertTrue($diffs->contains('status', 'modified'));
    }

    /**
     * Case 13: Question Matching by Stable ID (Q1, Q2, Q3 -> Q1, Q3, Q4)
     * Q2 must be removed, Q4 must be added, Q1 & Q3 must be unchanged!
     */
    public function test_diff_question_matching_by_stable_id()
    {
        $quiz = $this->createSampleQuiz([], 3);
        $q1 = $quiz->questions[0];
        $q2 = $quiz->questions[1];
        $q3 = $quiz->questions[2];

        // Rev 1 (Q1, Q2, Q3)
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev1 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/quiz-review-requests/{$rev1->id}/reject", ['reason' => 'Bỏ Q2, thêm Q4']);

        // Create Q4
        $q4 = Question::create([
            'user_id' => $this->author->id,
            'content' => 'Câu hỏi số 4 hoàn toàn mới',
            'type' => 'single_choice',
            'is_public' => false,
        ]);
        Answer::create(['question_id' => $q4->id, 'content' => 'A4', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $q4->id, 'content' => 'B4', 'is_correct' => false, 'order' => 1]);

        // Sync Q1, Q3, Q4
        $quiz->questions()->sync([
            $q1->id => ['order' => 0, 'points' => 10],
            $q3->id => ['order' => 1, 'points' => 10],
            $q4->id => ['order' => 2, 'points' => 10],
        ]);

        // Rev 2 (Q1, Q3, Q4)
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev2 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();

        $res = $this->actingAs($this->admin, 'api')->getJson("/api/admin/quiz-review-requests/{$rev2->id}");
        $res->assertStatus(200);
        $res->assertJsonPath('data.diff.questions_summary.added_count', 1);
        $res->assertJsonPath('data.diff.questions_summary.removed_count', 1);
        $res->assertJsonPath('data.diff.questions_summary.modified_count', 0);
        $res->assertJsonPath('data.diff.questions_summary.unchanged_count', 2);

        $diffs = collect($res->json('data.diff.question_diffs'));
        $removed = $diffs->firstWhere('status', 'removed');
        $added = $diffs->firstWhere('status', 'added');

        $this->assertEquals($q2->id, $removed['question_id']);
        $this->assertEquals($q4->id, $added['question_id']);
    }

    /**
     * Case 11: Answer only changed (Question content is untouched)
     */
    public function test_diff_answer_only_changed()
    {
        $quiz = $this->createSampleQuiz([], 1);
        $q1 = $quiz->questions[0];

        // Rev 1
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev1 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/quiz-review-requests/{$rev1->id}/reject", ['reason' => 'Sửa đáp án']);

        // Sửa nội dung đáp án 1A (không sửa câu hỏi q1)
        $ans1A = $q1->answers()->first();
        $ans1A->update(['content' => 'Đáp án 1A nội dung mới hoàn toàn']);

        // Rev 2
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev2 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();

        $res = $this->actingAs($this->admin, 'api')->getJson("/api/admin/quiz-review-requests/{$rev2->id}");
        $res->assertStatus(200);
        $res->assertJsonPath('data.diff.questions_summary.modified_count', 1);

        $diffItem = $res->json('data.diff.question_diffs.0');
        $this->assertEquals('modified', $diffItem['status']);
        // field_changes.content phải KHÔNG tồn tại vì nội dung câu hỏi không đổi
        $this->assertArrayNotHasKey('content', $diffItem['field_changes'] ?? []);
        // answer_changes phải chứa đáp án thay đổi
        $this->assertNotEmpty($diffItem['answer_changes']);
        $this->assertEquals('Đáp án 1A nội dung mới hoàn toàn', $diffItem['answer_changes'][0]['new_content']);
    }

    /**
     * Case 12: Multiple changes in 1 Question (Content + Points + Answers)
     */
    public function test_diff_multiple_changes_in_single_question()
    {
        $quiz = $this->createSampleQuiz([], 1);
        $q1 = $quiz->questions[0];

        // Rev 1
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev1 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/quiz-review-requests/{$rev1->id}/reject", ['reason' => 'Sửa toàn diện']);

        // Sửa content, points, và answers
        $q1->update(['content' => 'Nội dung câu 1 đã sửa mới', 'points' => 20]);
        $quiz->questions()->updateExistingPivot($q1->id, ['points' => 20]);
        $ans1A = $q1->answers()->first();
        $ans1A->update(['content' => 'Đáp án 1A mới', 'is_correct' => false]);
        $ans1B = $q1->answers()->skip(1)->first();
        $ans1B->update(['is_correct' => true]);

        // Rev 2
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");
        $rev2 = QuizReviewRequest::where('quiz_id', $quiz->id)->latest('id')->first();

        $res = $this->actingAs($this->admin, 'api')->getJson("/api/admin/quiz-review-requests/{$rev2->id}");
        $res->assertStatus(200);

        $diffs = $res->json('data.diff.question_diffs');
        $this->assertCount(1, $diffs, 'Chỉ được có đúng 1 Question section');

        $diffItem = $diffs[0];
        $this->assertEquals('Nội dung câu 1 đã sửa mới', $diffItem['field_changes']['content']['new']);
        $this->assertEquals(20, $diffItem['field_changes']['points']['new']);
        $this->assertNotEmpty($diffItem['answer_changes']);
    }

    /**
     * User Authorization: Non-owner cannot edit quiz -> 403
     */
    public function test_user_cannot_edit_other_user_quiz()
    {
        $quiz = $this->createSampleQuiz();

        $res = $this->actingAs($this->otherUser, 'api')->putJson("/api/quizzes/{$quiz->id}", [
            'title' => 'User khác cố tình sửa Quiz',
        ]);
        $res->assertStatus(403);
    }

    /**
     * Notification: Save Quiz sends 0 notifications, Submit Review sends exactly 1
     */
    public function test_save_quiz_sends_no_notification_but_submit_review_does()
    {
        $quiz = $this->createSampleQuiz();

        Notification::fake();

        // 1. User Save Quiz
        $this->actingAs($this->author, 'api')->putJson("/api/quizzes/{$quiz->id}", [
            'title' => 'Chỉ lưu Quiz không gửi duyệt',
        ]);

        Notification::assertNothingSent();

        // 2. User Submit Review
        $this->actingAs($this->author, 'api')->postJson("/api/quizzes/{$quiz->id}/request-review");

        Notification::assertSentTo(
            User::whereIn('role', ['admin', 'ADMIN'])->get(),
            QuizReviewRequested::class,
            function ($notification) use ($quiz) {
                return $notification->quiz->id === $quiz->id;
            }
        );
    }
}
