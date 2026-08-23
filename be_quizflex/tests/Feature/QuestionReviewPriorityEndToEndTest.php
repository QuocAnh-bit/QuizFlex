<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionReviewRequest;
use App\Models\ReportTicket;
use App\Models\User;
use App\Notifications\QuestionModerated;
use App\Notifications\QuestionReviewRequested;
use App\Notifications\ReportResolved;
use App\Services\QuestionReviewService;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuestionReviewPriorityEndToEndTest extends TestCase
{
    protected User $author;
    protected User $reporter1;
    protected User $reporter2;
    protected User $admin;
    protected QuestionReviewService $reviewService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reviewService = app(QuestionReviewService::class);

        $this->author = User::firstOrCreate(
            ['email' => 'p3_author@quizflex.local'],
            [
                'name' => 'Phase3 Author',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        $this->reporter1 = User::firstOrCreate(
            ['email' => 'p3_reporter1@quizflex.local'],
            [
                'name' => 'Phase3 Reporter 1',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        $this->reporter2 = User::firstOrCreate(
            ['email' => 'p3_reporter2@quizflex.local'],
            [
                'name' => 'Phase3 Reporter 2',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        $this->admin = User::firstOrCreate(
            ['email' => 'p3_admin@quizflex.local'],
            [
                'name' => 'Phase3 Admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        ReportTicket::whereIn('user_id', [$this->author->id, $this->reporter1->id, $this->reporter2->id, $this->admin->id])->delete();
        QuestionReviewRequest::whereIn('user_id', [$this->author->id, $this->reporter1->id, $this->reporter2->id, $this->admin->id])->delete();
        Question::withTrashed()
            ->whereIn('user_id', [$this->author->id, $this->reporter1->id, $this->reporter2->id, $this->admin->id])
            ->forceDelete();
    }

    protected function createQuestion(array $overrides = []): Question
    {
        $question = Question::create(array_merge([
            'user_id' => $this->author->id,
            'content' => 'Câu hỏi kiểm tra ưu tiên review ' . uniqid(),
            'type' => 'single_choice',
            'difficulty' => 'medium',
            'points' => 10,
            'is_public' => false,
            'bank_submission_status' => 'none',
        ], $overrides));

        Answer::create(['question_id' => $question->id, 'content' => 'Đáp án A (Đúng)', 'is_correct' => true, 'order' => 0]);
        Answer::create(['question_id' => $question->id, 'content' => 'Đáp án B (Sai)', 'is_correct' => false, 'order' => 1]);
        Answer::create(['question_id' => $question->id, 'content' => 'Đáp án C (Sai)', 'is_correct' => false, 'order' => 2]);
        Answer::create(['question_id' => $question->id, 'content' => 'Đáp án D (Sai)', 'is_correct' => false, 'order' => 3]);

        return $question->fresh('answers');
    }

    /**
     * Case A: Public Snapshot -> User report -> Owner notification
     */
    public function test_case_a_public_snapshot_reported_triggers_owner_notification()
    {
        Notification::fake();

        $original = $this->createQuestion(['is_public' => false, 'bank_submission_status' => 'approved']);
        $snapshot = $this->createQuestion([
            'origin_question_id' => $original->id,
            'is_public' => true,
            'bank_submission_status' => 'approved',
            'content' => $original->content,
        ]);

        $token = auth('api')->login($this->reporter1);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/report-tickets', [
                'question_id' => $snapshot->id,
                'reason' => 'Sai đáp án',
                'description' => 'Đáp án A bị sai dữ liệu lịch sử.',
            ]);

        $response->assertStatus(201);

        // Owner nhận được notification QuestionModerated
        Notification::assertSentTo(
            $this->author,
            QuestionModerated::class,
            function (QuestionModerated $notif) use ($original) {
                return $notif->action === 'reported'
                    && $notif->reason === 'Sai đáp án'
                    && $notif->description === 'Đáp án A bị sai dữ liệu lịch sử.'
                    && $notif->question->id === $original->id;
            }
        );
    }

    /**
     * Case B: Notification payload chứa đầy đủ thông tin để Owner mở trực tiếp câu hỏi
     */
    public function test_case_b_notification_payload_contains_direct_link_and_details()
    {
        $original = $this->createQuestion(['is_public' => false]);
        $notification = new QuestionModerated($original, 'reported', 'Nội dung không phù hợp', 'Chi tiết phản ánh từ người dùng');

        $data = $notification->toArray($this->author);

        $this->assertEquals('question_moderated', $data['type']);
        $this->assertEquals("/dashboard/my-questions?question_id={$original->id}", $data['action_link']);
        $this->assertStringContainsString('Nội dung không phù hợp', $data['message']);
        $this->assertStringContainsString('Chi tiết phản ánh từ người dùng', $data['message']);
        $this->assertEquals($original->id, $data['metadata']['question_id']);
        $this->assertEquals('Chi tiết phản ánh từ người dùng', $data['metadata']['report_description']);
    }

    /**
     * Case C & D & E: Owner sửa -> Submit Review -> System đánh dấu PRIORITY -> Xuất hiện trong Admin
     */
    public function test_case_c_d_e_owner_edits_and_submits_triggers_priority_review()
    {
        Notification::fake();

        $original = $this->createQuestion(['is_public' => false, 'bank_submission_status' => 'approved']);
        $snapshot = $this->createQuestion([
            'origin_question_id' => $original->id,
            'is_public' => true,
            'bank_submission_status' => 'approved',
        ]);

        // 1. Reporter gửi báo cáo
        $tokenReporter = auth('api')->login($this->reporter1);
        $this->withHeader('Authorization', "Bearer {$tokenReporter}")
            ->postJson('/api/report-tickets', [
                'question_id' => $snapshot->id,
                'reason' => 'Sai đáp án phép tính',
                'description' => '1 + 1 phải bằng 2 chứ không phải 3.',
            ])->assertStatus(201);

        // 2. Owner sửa nội dung và đáp án
        $tokenAuthor = auth('api')->login($this->author);
        $this->withHeader('Authorization', "Bearer {$tokenAuthor}")
            ->putJson("/api/user/my-questions/{$original->id}", [
                'content' => 'Nội dung câu hỏi đã được đính chính hoàn toàn',
                'answers' => [
                    ['content' => 'Đáp án A chuẩn 2', 'is_correct' => true, 'order' => 0],
                    ['content' => 'Đáp án B sai 4', 'is_correct' => false, 'order' => 1],
                ],
            ])->assertStatus(200);

        // 3. Owner nộp duyệt lại vào Ngân hàng
        $resSubmit = $this->withHeader('Authorization', "Bearer {$tokenAuthor}")
            ->postJson("/api/user/my-questions/{$original->id}/submit-to-bank", [
                'request_note' => 'Đã sửa lại đáp án phép tính theo phản ánh của người học',
            ]);

        $resSubmit->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Kiểm tra bản ghi QuestionReviewRequest được đánh dấu PRIORITY
        $reviewReq = QuestionReviewRequest::where('question_id', $original->id)->latest()->first();
        $this->assertNotNull($reviewReq);
        $this->assertTrue((bool)$reviewReq->is_priority);
        $this->assertEquals('high', $reviewReq->review_priority);
        $this->assertEquals('Sai đáp án phép tính', $reviewReq->snapshot_metadata['report_reason']);

        // Admin nhận notification QuestionReviewRequested với cờ is_priority
        Notification::assertSentTo(
            $this->admin,
            QuestionReviewRequested::class,
            function (QuestionReviewRequested $notif) use ($original) {
                return $notif->isPriority === true
                    && $notif->question->id === $original->id;
            }
        );

        // 4. Admin xem danh sách duyệt: Câu hỏi PRIORITY xuất hiện và được đánh dấu
        $tokenAdmin = auth('api')->login($this->admin);
        $resAdmin = $this->withHeader('Authorization', "Bearer {$tokenAdmin}")
            ->getJson('/api/admin/question-bank-requests');

        $resAdmin->assertStatus(200);
        $items = $resAdmin->json('data.items');
        $found = collect($items)->firstWhere('id', $original->id);
        $this->assertNotNull($found);
        $this->assertTrue((bool)$found['is_priority']);
        $this->assertEquals('high', $found['review_priority']);
        $this->assertEquals('Sai đáp án phép tính', $found['report_reason']);
    }

    /**
     * Case F: Admin approve -> snapshot cập nhật, report tự động resolved, người báo cáo nhận thông báo
     */
    public function test_case_f_admin_approve_resolves_reports_and_creates_snapshot()
    {
        Notification::fake();

        $original = $this->createQuestion(['is_public' => false]);
        
        // Tạo pending report
        $report = ReportTicket::create([
            'user_id' => $this->reporter1->id,
            'question_id' => $original->id,
            'reason' => 'Lỗi chính tả',
            'status' => 'pending',
        ]);

        // Submit review
        $tokenAuthor = auth('api')->login($this->author);
        $this->withHeader('Authorization', "Bearer {$tokenAuthor}")
            ->postJson("/api/user/my-questions/{$original->id}/submit-to-bank")
            ->assertStatus(200);

        // Admin Phê duyệt
        $tokenAdmin = auth('api')->login($this->admin);
        $resApprove = $this->withHeader('Authorization', "Bearer {$tokenAdmin}")
            ->postJson("/api/admin/question-bank-requests/{$original->id}/approve");

        $resApprove->assertStatus(200);

        // 1. Trạng thái câu gốc cập nhật
        $original->refresh();
        $this->assertEquals('approved', $original->bank_submission_status);

        // 2. Report ticket được tự động chuyển sang resolved
        $report->refresh();
        $this->assertEquals('resolved', $report->status);

        // 3. Reporter nhận được notification ReportResolved
        Notification::assertSentTo($this->reporter1, ReportResolved::class);

        // 4. Snapshot công khai trong Ngân hàng được tạo
        $this->assertDatabaseHas('questions', [
            'origin_question_id' => $original->id,
            'is_public' => true,
            'bank_submission_status' => 'approved',
        ]);
    }

    /**
     * Case G: Admin reject -> trạng thái rejected, owner nhận notification, câu hỏi KHÔNG bị xóa
     */
    public function test_case_g_admin_reject_flow()
    {
        Notification::fake();

        $original = $this->createQuestion(['is_public' => false]);
        
        ReportTicket::create([
            'user_id' => $this->reporter1->id,
            'question_id' => $original->id,
            'reason' => 'Sai đáp án',
            'status' => 'pending',
        ]);

        // Submit review
        $tokenAuthor = auth('api')->login($this->author);
        $this->withHeader('Authorization', "Bearer {$tokenAuthor}")
            ->postJson("/api/user/my-questions/{$original->id}/submit-to-bank")
            ->assertStatus(200);

        // Admin Từ chối
        $tokenAdmin = auth('api')->login($this->admin);
        $resReject = $this->withHeader('Authorization', "Bearer {$tokenAdmin}")
            ->postJson("/api/admin/question-bank-requests/{$original->id}/reject", [
                'reason' => 'Đính chính chưa đúng công thức chuẩn',
            ]);

        $resReject->assertStatus(200);

        // 1. Trạng thái câu gốc
        $original->refresh();
        $this->assertEquals('rejected', $original->bank_submission_status);
        $this->assertEquals('Đính chính chưa đúng công thức chuẩn', $original->bank_submission_note);
        $this->assertNull($original->deleted_at); // Câu hỏi KHÔNG bị xóa

        // 2. Owner nhận notification
        Notification::assertSentTo($this->author, QuestionModerated::class, function ($n) {
            return $n->action === 'rejected';
        });
    }

    /**
     * Case H: Report history vẫn tồn tại trong DB sau khi xử lý (không bị xóa)
     */
    public function test_case_h_report_history_preserved_in_database()
    {
        $original = $this->createQuestion(['is_public' => false]);
        
        $report = ReportTicket::create([
            'user_id' => $this->reporter1->id,
            'question_id' => $original->id,
            'reason' => 'Lỗi định dạng',
            'status' => 'pending',
        ]);

        $tokenAuthor = auth('api')->login($this->author);
        $this->withHeader('Authorization', "Bearer {$tokenAuthor}")
            ->postJson("/api/user/my-questions/{$original->id}/submit-to-bank")
            ->assertStatus(200);

        $tokenAdmin = auth('api')->login($this->admin);
        $this->withHeader('Authorization', "Bearer {$tokenAdmin}")
            ->postJson("/api/admin/question-bank-requests/{$original->id}/approve")
            ->assertStatus(200);

        // Bản ghi ReportTicket vẫn tồn tại vẹn nguyên với status resolved
        $this->assertDatabaseHas('report_tickets', [
            'id' => $report->id,
            'question_id' => $original->id,
            'user_id' => $this->reporter1->id,
            'status' => 'resolved',
        ]);
    }

    /**
     * Case I: Một Question có nhiều user report -> không duplicate report của cùng 1 user, nhận biết tổng số report
     */
    public function test_case_i_multiple_reports_from_different_users_handled_correctly()
    {
        $original = $this->createQuestion(['is_public' => true]);

        // User 1 report lần 1: thành công
        $this->actingAs($this->reporter1, 'api')
            ->postJson('/api/report-tickets', ['question_id' => $original->id, 'reason' => 'Lý do 1'])
            ->assertStatus(201);

        // User 1 report lần 2: bị từ chối duplicate
        $this->actingAs($this->reporter1, 'api')
            ->postJson('/api/report-tickets', ['question_id' => $original->id, 'reason' => 'Lý do 1 lặp lại'])
            ->assertStatus(409);

        // User 2 report lần 1: thành công
        $this->actingAs($this->reporter2, 'api')
            ->postJson('/api/report-tickets', ['question_id' => $original->id, 'reason' => 'Lý do 2'])
            ->assertStatus(201);

        // Tổng cộng có đúng 2 reports
        $this->assertEquals(2, ReportTicket::where('question_id', $original->id)->count());

        // Khi nộp duyệt, hệ thống nhận biết cả 2 reports
        $resSubmit = $this->actingAs($this->author, 'api')
            ->postJson("/api/user/my-questions/{$original->id}/submit-to-bank");

        $resSubmit->assertStatus(200);

        $req = QuestionReviewRequest::where('question_id', $original->id)->latest()->first();
        $this->assertTrue((bool)$req->is_priority);
        $this->assertEquals(2, $req->snapshot_metadata['reports_count']);
    }


    /**
     * Case J: Question không bị report -> review bình thường, không gắn PRIORITY
     */
    public function test_case_j_unreported_question_review_is_normal_priority()
    {
        $normalQuestion = $this->createQuestion(['is_public' => false]);

        $tokenAuthor = auth('api')->login($this->author);
        $this->withHeader('Authorization', "Bearer {$tokenAuthor}")
            ->postJson("/api/user/my-questions/{$normalQuestion->id}/submit-to-bank")
            ->assertStatus(200);

        $req = QuestionReviewRequest::where('question_id', $normalQuestion->id)->latest()->first();
        $this->assertNotNull($req);
        $this->assertFalse((bool)$req->is_priority);
        $this->assertEquals('normal', $req->review_priority);
    }
}
