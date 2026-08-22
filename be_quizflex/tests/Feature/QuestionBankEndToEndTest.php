<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionReviewRequest;
use App\Models\ReportTicket;
use App\Models\User;
use App\Notifications\QuestionModerated;
use App\Notifications\QuestionReviewRequested;
use App\Notifications\ReportAuthorUpdated;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuestionBankEndToEndTest extends TestCase
{
    protected User $author;
    protected User $admin;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::firstOrCreate(
            ['email' => 'e2e_author@quizflex.local'],
            [
                'name' => 'E2E Author',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        $this->admin = User::firstOrCreate(
            ['email' => 'e2e_admin@quizflex.local'],
            [
                'name' => 'E2E Admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        $this->otherUser = User::firstOrCreate(
            ['email' => 'e2e_other@quizflex.local'],
            [
                'name' => 'E2E Other User',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );
    }

    protected function createQuestion(array $overrides = [], array $answers = []): Question
    {
        $question = Question::create(array_merge([
            'user_id' => $this->author->id,
            'content' => 'Nội dung ban đầu của câu hỏi',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => false,
            'bank_submission_status' => 'none',
        ], $overrides));

        if (empty($answers)) {
            $answers = [
                ['content' => 'Đáp án A ban đầu', 'is_correct' => true, 'order' => 0],
                ['content' => 'Đáp án B ban đầu', 'is_correct' => false, 'order' => 1],
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
     * TEST CASE 1: Submit lần đầu
     */
    public function test_case_1_submit_first_time()
    {
        Notification::fake();

        $question = $this->createQuestion();

        $response = $this->actingAs($this->author, 'api')
            ->postJson("/api/user/my-questions/{$question->id}/submit-to-bank", [
                'note' => 'Gửi duyệt lần 1',
            ]);

        $response->assertStatus(200);

        // Question state
        $question->refresh();
        $this->assertEquals('pending', $question->bank_submission_status);
        $this->assertFalse($question->is_public);

        // Revision #1 snapshot
        $rev1 = QuestionReviewRequest::where('question_id', $question->id)->first();
        $this->assertNotNull($rev1);
        $this->assertEquals(1, $rev1->revision_number);
        $this->assertEquals('pending', $rev1->status);
        $this->assertEquals('Nội dung ban đầu của câu hỏi', $rev1->snapshot_content);
        $this->assertCount(2, $rev1->snapshot_answers);
        $this->assertEquals('Đáp án A ban đầu', $rev1->snapshot_answers[0]['content']);
        $this->assertTrue($rev1->snapshot_answers[0]['is_correct']);

        // Admin notification
        Notification::assertSentTo($this->admin, QuestionReviewRequested::class);
    }

    /**
     * TEST CASE 2: Admin Reject Lần 1
     */
    public function test_case_2_admin_reject_first_time()
    {
        Notification::fake();

        $question = $this->createQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/question-bank-requests/{$question->id}/reject", [
                'note' => 'Lý do từ chối lần 1: Thiếu giải thích chi tiết',
            ]);

        $response->assertStatus(200);

        $rev1 = QuestionReviewRequest::where('question_id', $question->id)->first();
        $this->assertEquals('rejected', $rev1->status);
        $this->assertEquals('Lý do từ chối lần 1: Thiếu giải thích chi tiết', $rev1->rejection_reason);
        $this->assertEquals($this->admin->id, $rev1->reviewed_by);
        $this->assertNotNull($rev1->reviewed_at);

        // Snapshot bất biến
        $this->assertEquals('Nội dung ban đầu của câu hỏi', $rev1->snapshot_content);

        // Question note
        $question->refresh();
        $this->assertEquals('rejected', $question->bank_submission_status);
        $this->assertEquals('Lý do từ chối lần 1: Thiếu giải thích chi tiết', $question->bank_submission_note);
    }

    /**
     * TEST CASE 3: User Edit sau Reject (Không tự tạo revision, không đổi status sang pending, không gửi notification)
     */
    public function test_case_3_user_edit_after_reject()
    {
        Notification::fake();

        $question = $this->createQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Lý do lần 1']);

        Notification::fake(); // reset

        $response = $this->actingAs($this->author, 'api')
            ->putJson("/api/user/my-questions/{$question->id}", [
                'content' => 'Nội dung sau khi sửa lần 1',
                'difficulty' => 'medium',
                'answers' => [
                    ['content' => 'Đáp án A mới', 'is_correct' => false, 'key' => 'A'],
                    ['content' => 'Đáp án B mới (đúng)', 'is_correct' => true, 'key' => 'B'],
                ],
            ]);

        $response->assertStatus(200);

        // Question thay đổi
        $question->refresh();
        $this->assertEquals('Nội dung sau khi sửa lần 1', $question->content);
        $this->assertEquals('rejected', $question->bank_submission_status); // Vẫn là rejected!

        // Không tạo revision #2
        $this->assertEquals(1, QuestionReviewRequest::where('question_id', $question->id)->count());

        // Revision #1 KHÔNG bị thay đổi
        $rev1 = QuestionReviewRequest::where('question_id', $question->id)->first();
        $this->assertEquals('Nội dung ban đầu của câu hỏi', $rev1->snapshot_content);
        $this->assertEquals('Đáp án A ban đầu', $rev1->snapshot_answers[0]['content']);

        // Không gửi notification cho Admin
        Notification::assertNothingSent();
    }

    /**
     * TEST CASE 4: User Submit Lần 2 (Tạo Revision #2, giữ nguyên Revision #1)
     */
    public function test_case_4_user_submit_second_time()
    {
        Notification::fake();

        $question = $this->createQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Lý do lần 1']);

        // User sửa nội dung
        $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$question->id}", [
            'content' => 'Nội dung lần 2 đã sửa',
            'answers' => [
                ['content' => 'A2', 'is_correct' => false],
                ['content' => 'B2', 'is_correct' => true],
            ],
        ]);

        // User bấm Gửi duyệt lần 2
        $response = $this->actingAs($this->author, 'api')
            ->postJson("/api/user/my-questions/{$question->id}/submit-to-bank", [
                'note' => 'Em đã sửa lại theo góp ý',
            ]);

        $response->assertStatus(200);

        // Có đúng 2 revisions
        $revisions = QuestionReviewRequest::where('question_id', $question->id)->orderBy('revision_number')->get();
        $this->assertCount(2, $revisions);

        // Revision #1: status rejected, content cũ, answers cũ
        $this->assertEquals(1, $revisions[0]->revision_number);
        $this->assertEquals('rejected', $revisions[0]->status);
        $this->assertEquals('Nội dung ban đầu của câu hỏi', $revisions[0]->snapshot_content);
        $this->assertEquals('Đáp án A ban đầu', $revisions[0]->snapshot_answers[0]['content']);

        // Revision #2: status pending, content mới, answers mới
        $this->assertEquals(2, $revisions[1]->revision_number);
        $this->assertEquals('pending', $revisions[1]->status);
        $this->assertEquals('Nội dung lần 2 đã sửa', $revisions[1]->snapshot_content);
        $this->assertEquals('A2', $revisions[1]->snapshot_answers[0]['content']);
        $this->assertEquals('Em đã sửa lại theo góp ý', $revisions[1]->request_note);

        // Admin nhận notification
        Notification::assertSentTo($this->admin, QuestionReviewRequested::class);
    }

    /**
     * TEST CASE 5: Admin Comparison (Gọi API Detail trả về đúng Current Rev #2 và Previous Rev #1)
     */
    public function test_case_5_admin_comparison_shows_rev1_and_rev2()
    {
        $question = $this->createQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Lý do reject rev 1']);

        $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$question->id}", [
            'content' => 'Nội dung câu hỏi mới rev 2',
            'answers' => [
                ['content' => 'A rev 2', 'is_correct' => false],
                ['content' => 'B rev 2', 'is_correct' => true],
            ],
        ]);
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/question-bank-requests/{$question->id}");

        $response->assertStatus(200);

        // Current Revision: Rev 2
        $response->assertJsonPath('data.current_revision.revision_number', 2);
        $response->assertJsonPath('data.current_revision.content', 'Nội dung câu hỏi mới rev 2');

        // Previous Revision: Rev 1
        $response->assertJsonPath('data.previous_revision.revision_number', 1);
        $response->assertJsonPath('data.previous_revision.content', 'Nội dung ban đầu của câu hỏi');
        $response->assertJsonPath('data.previous_revision.rejection_reason', 'Lý do reject rev 1');
    }

    /**
     * TEST CASE 6: Admin Reject Lần 2 (Cả 2 revision đều là rejected, không record nào bị overwrite)
     */
    public function test_case_6_admin_reject_second_time()
    {
        $question = $this->createQuestion();
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Reject 1']);

        $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$question->id}", [
            'content' => 'Content 2',
            'answers' => [['content' => 'A2', 'is_correct' => true], ['content' => 'B2', 'is_correct' => false]],
        ]);
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");

        // Reject lần 2
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Reject 2']);

        $revisions = QuestionReviewRequest::where('question_id', $question->id)->orderBy('revision_number')->get();
        $this->assertCount(2, $revisions);

        $this->assertEquals('rejected', $revisions[0]->status);
        $this->assertEquals('Reject 1', $revisions[0]->rejection_reason);

        $this->assertEquals('rejected', $revisions[1]->status);
        $this->assertEquals('Reject 2', $revisions[1]->rejection_reason);
    }

    /**
     * TEST CASES 7 & 8 & 9: Vòng 3 (Sửa lần 3 -> Submit lần 3 -> Diff chỉ tới Rev #2 -> Admin Approve -> Giữ nguyên cả 3 revisions)
     */
    public function test_case_7_8_9_three_rounds_and_approval()
    {
        $question = $this->createQuestion(['content' => 'Rev 1 content']);
        // Rev 1
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Reason 1']);

        // Rev 2
        $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$question->id}", [
            'content' => 'Rev 2 content',
            'answers' => [['content' => 'A2', 'is_correct' => true], ['content' => 'B2', 'is_correct' => false]],
        ]);
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/reject", ['note' => 'Reason 2']);

        // User sửa lần 3 (Test case 7: chưa submit)
        $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$question->id}", [
            'content' => 'Rev 3 content hoàn hảo',
            'answers' => [['content' => 'A3', 'is_correct' => true], ['content' => 'B3', 'is_correct' => false]],
        ]);
        $this->assertEquals(2, QuestionReviewRequest::where('question_id', $question->id)->count());

        // User submit lần 3 (Test case 8)
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$question->id}/submit-to-bank");

        // Kiểm tra diff của Rev 3: Previous Revision PHẢI LÀ Rev 2 (không được là Rev 1)
        $diffRes = $this->actingAs($this->admin, 'api')->getJson("/api/admin/question-bank-requests/{$question->id}");
        $diffRes->assertJsonPath('data.current_revision.revision_number', 3);
        $diffRes->assertJsonPath('data.previous_revision.revision_number', 2);
        $diffRes->assertJsonPath('data.previous_revision.rejection_reason', 'Reason 2');
        $diffRes->assertJsonPath('data.previous_revision.content', 'Rev 2 content');

        // Admin Approve lần 3 (Test case 9)
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$question->id}/approve");

        $question->refresh();
        $this->assertEquals('approved', $question->bank_submission_status);
        $this->assertTrue($question->is_public);

        // Database giữ toàn bộ 3 revisions
        $allRevs = QuestionReviewRequest::where('question_id', $question->id)->orderBy('revision_number')->get();
        $this->assertCount(3, $allRevs);
        $this->assertEquals('rejected', $allRevs[0]->status);
        $this->assertEquals('rejected', $allRevs[1]->status);
        $this->assertEquals('approved', $allRevs[2]->status);
    }

    /**
     * TEST CASE 10: Report Flow Isolation (Hai flow Question Bank và Report Ticket hoàn toàn độc lập)
     */
    public function test_case_10_report_ticket_flow_isolation()
    {
        Notification::fake();

        // 1. Câu hỏi bình thường khi update -> KHÔNG gửi ReportAuthorUpdated
        $normalQuestion = $this->createQuestion();
        $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$normalQuestion->id}", [
            'content' => 'Nội dung cập nhật bình thường',
            'answers' => [['content' => 'A', 'is_correct' => true], ['content' => 'B', 'is_correct' => false]],
        ]);
        Notification::assertNothingSent();

        // 2. Câu hỏi CÓ ReportTicket pending khi update -> Gửi ReportAuthorUpdated
        $reportedQuestion = $this->createQuestion();
        ReportTicket::create([
            'user_id' => $this->otherUser->id,
            'question_id' => $reportedQuestion->id,
            'reason' => 'Nội dung sai',
            'status' => 'pending',
        ]);

        $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$reportedQuestion->id}", [
            'content' => 'Nội dung tác giả sửa sau khi bị report',
            'answers' => [['content' => 'A', 'is_correct' => true], ['content' => 'B', 'is_correct' => false]],
        ]);

        // Gửi thông báo cho Admin về report ticket
        Notification::assertSentTo($this->admin, ReportAuthorUpdated::class);
    }

    /**
     * TEST CASE 11: Bulk Submit and Review Flow
     */
    public function test_case_11_bulk_flow()
    {
        $q1 = $this->createQuestion(['content' => 'Câu 1']);
        $q2 = $this->createQuestion(['content' => 'Câu 2']);

        $res = $this->actingAs($this->author, 'api')->postJson('/api/user/my-questions/bulk-submit-to-bank', [
            'ids' => [$q1->id, $q2->id],
        ]);
        $res->assertStatus(200);

        $this->assertEquals(1, QuestionReviewRequest::where('question_id', $q1->id)->count());
        $this->assertEquals(1, QuestionReviewRequest::where('question_id', $q2->id)->count());
    }

    /**
     * TEST CASE 12: Data Integrity & Immutable Snapshots Check
     */
    public function test_case_12_immutable_snapshots_integrity()
    {
        $q = $this->createQuestion(['content' => 'Snapshot Content Rev 1']);
        $this->actingAs($this->author, 'api')->postJson("/api/user/my-questions/{$q->id}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$q->id}/reject", ['note' => 'Bad']);

        // User liên tục sửa và xóa đáp án
        $this->actingAs($this->author, 'api')->putJson("/api/user/my-questions/{$q->id}", [
            'content' => 'Content Changed Many Times',
            'answers' => [
                ['content' => 'Brand New Answer X', 'is_correct' => true],
            ],
        ]);

        // Snapshot của Rev 1 vẫn bất biến 100%
        $rev1 = QuestionReviewRequest::where('question_id', $q->id)->first();
        $this->assertEquals('Snapshot Content Rev 1', $rev1->snapshot_content);
        $this->assertEquals('Đáp án A ban đầu', $rev1->snapshot_answers[0]['content']);
        $this->assertEquals('Bad', $rev1->rejection_reason);
    }
}
