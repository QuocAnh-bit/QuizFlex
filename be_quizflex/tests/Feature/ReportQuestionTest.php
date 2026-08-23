<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\ReportTicket;
use App\Models\User;
use App\Notifications\QuestionModerated;
use App\Notifications\ReportCreated;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReportQuestionTest extends TestCase
{
    protected User $author;
    protected User $reporter;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::firstOrCreate(
            ['email' => 'report_author@quizflex.local'],
            [
                'name' => 'Question Author',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        $this->reporter = User::firstOrCreate(
            ['email' => 'report_reporter@quizflex.local'],
            [
                'name' => 'Report User',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        $this->admin = User::firstOrCreate(
            ['email' => 'report_admin@quizflex.local'],
            [
                'name' => 'Admin Moderator',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        // Dọn dẹp dữ liệu cũ trước mỗi test
        ReportTicket::whereIn('user_id', [$this->author->id, $this->reporter->id, $this->admin->id])->delete();
        Question::withTrashed()
            ->whereIn('user_id', [$this->author->id, $this->reporter->id, $this->admin->id])
            ->forceDelete();
    }

    protected function createQuestion(array $overrides = [], array $answers = []): Question
    {
        $defaultAnswers = [
            ['content' => 'Đáp án đúng A', 'is_correct' => true, 'order' => 0],
            ['content' => 'Đáp án sai B', 'is_correct' => false, 'order' => 1],
            ['content' => 'Đáp án sai C', 'is_correct' => false, 'order' => 2],
            ['content' => 'Đáp án sai D', 'is_correct' => false, 'order' => 3],
        ];

        $ansData = !empty($answers) ? $answers : $defaultAnswers;

        $question = Question::create(array_merge([
            'user_id' => $this->author->id,
            'content' => 'Nội dung câu hỏi kiểm tra báo cáo vi phạm ' . uniqid(),
            'type' => 'single_choice',
            'difficulty' => 'medium',
            'points' => 10,
            'is_public' => false,
            'bank_submission_status' => 'none',
        ], $overrides));

        foreach ($ansData as $ans) {
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
     * Case 1: User report public Question Snapshot -> tạo report thành công (201).
     */
    public function test_case_1_user_report_public_question_snapshot_success()
    {
        Notification::fake();

        // 1. Tạo câu hỏi gốc của tác giả
        $original = $this->createQuestion(['is_public' => false, 'bank_submission_status' => 'approved']);

        // 2. Tạo Snapshot công khai trong Ngân hàng câu hỏi
        $snapshot = $this->createQuestion([
            'user_id' => $this->author->id,
            'origin_question_id' => $original->id,
            'is_public' => true,
            'bank_submission_status' => 'approved',
            'content' => $original->content,
        ]);

        $token = auth('api')->login($this->reporter);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/report-tickets', [
                'question_id' => $snapshot->id,
                'reason' => 'Sai đáp án / Phương án đúng không chính xác',
                'description' => 'Đáp án A bị nhầm lẫn công thức toán học.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Báo cáo câu hỏi đã được gửi thành công.',
            ]);

        $this->assertDatabaseHas('report_tickets', [
            'user_id' => $this->reporter->id,
            'question_id' => $original->id,
            'reason' => 'Sai đáp án / Phương án đúng không chính xác',
            'status' => 'pending',
        ]);
    }

    /**
     * Case 2: User report private Question -> bị từ chối (422).
     */
    public function test_case_2_user_report_private_question_rejected()
    {
        $privateQuestion = $this->createQuestion([
            'is_public' => false,
            'origin_question_id' => null,
            'bank_submission_status' => 'none',
        ]);

        $token = auth('api')->login($this->reporter);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/report-tickets', [
                'question_id' => $privateQuestion->id,
                'reason' => 'Sai đề bài',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Chỉ có thể báo cáo câu hỏi công khai hoặc trong bài thi công khai.',
            ]);

        $this->assertDatabaseMissing('report_tickets', [
            'question_id' => $privateQuestion->id,
        ]);
    }

    /**
     * Case 3: User report cùng Question lần 2 khi report cũ đang pending -> không tạo duplicate (409).
     */
    public function test_case_3_user_report_duplicate_pending_rejected()
    {
        $publicQuestion = $this->createQuestion([
            'is_public' => true,
            'bank_submission_status' => 'approved',
        ]);

        $token = auth('api')->login($this->reporter);

        // Lần 1: Thành công
        $res1 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/report-tickets', [
                'question_id' => $publicQuestion->id,
                'reason' => 'Nội dung không phù hợp',
            ]);
        $res1->assertStatus(201);

        // Lần 2: Bị từ chối chống duplicate
        $res2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/report-tickets', [
                'question_id' => $publicQuestion->id,
                'reason' => 'Spam thông tin',
            ]);

        $res2->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Bạn đã báo cáo câu hỏi này và báo cáo đang được xử lý.',
            ]);

        // Đảm bảo chỉ có đúng 1 bản ghi report duy nhất
        $this->assertEquals(1, ReportTicket::where('user_id', $this->reporter->id)->where('question_id', $publicQuestion->id)->count());
    }

    /**
     * Case 4: Snapshot có origin_question_id -> xác định đúng Question gốc.
     */
    public function test_case_4_snapshot_resolves_to_origin_question_id()
    {
        $original = $this->createQuestion(['is_public' => false]);
        $snapshot = $this->createQuestion([
            'origin_question_id' => $original->id,
            'is_public' => true,
        ]);

        $token = auth('api')->login($this->reporter);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/report-tickets', [
                'question_id' => $snapshot->id,
                'reason' => 'Lỗi chính tả nghiêm trọng',
            ]);

        $response->assertStatus(201);

        // ReportTicket phải được gán vào ID của Question gốc ($original->id), không phải ID của snapshot
        $this->assertDatabaseHas('report_tickets', [
            'user_id' => $this->reporter->id,
            'question_id' => $original->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Case 5: Xác định đúng Owner của Question gốc.
     */
    public function test_case_5_correctly_identifies_owner_of_origin_question()
    {
        Notification::fake();

        $original = $this->createQuestion(['user_id' => $this->author->id, 'is_public' => false]);
        $snapshot = $this->createQuestion([
            'user_id' => $this->author->id,
            'origin_question_id' => $original->id,
            'is_public' => true,
        ]);

        $token = auth('api')->login($this->reporter);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/report-tickets', [
                'question_id' => $snapshot->id,
                'reason' => 'Sai đáp án',
            ]);

        $response->assertStatus(201);

        $ticket = ReportTicket::where('question_id', $original->id)->first();
        $this->assertNotNull($ticket);
        $this->assertEquals($this->author->id, $ticket->question->user_id);
    }

    /**
     * Case 6: Owner nhận được notification QuestionModerated ('reported').
     */
    public function test_case_6_owner_receives_question_moderated_notification()
    {
        Notification::fake();

        $original = $this->createQuestion(['user_id' => $this->author->id, 'is_public' => false]);
        $snapshot = $this->createQuestion([
            'user_id' => $this->author->id,
            'origin_question_id' => $original->id,
            'is_public' => true,
        ]);

        $token = auth('api')->login($this->reporter);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/report-tickets', [
                'question_id' => $snapshot->id,
                'reason' => 'Sai kiến thức lịch sử',
            ]);

        $response->assertStatus(201);

        // Owner (author) nhận được QuestionModerated
        Notification::assertSentTo(
            $this->author,
            QuestionModerated::class,
            function (QuestionModerated $notification) use ($original) {
                return $notification->action === 'reported'
                    && $notification->reason === 'Sai kiến thức lịch sử'
                    && $notification->question->id === $original->id;
            }
        );

        // Người báo cáo (reporter) không nhận thông báo tự báo cáo chính mình
        Notification::assertNotSentTo($this->reporter, QuestionModerated::class);
    }

    /**
     * Case 7: Report Quiz API cũ -> không còn được phép sử dụng (422).
     */
    public function test_case_7_old_report_quiz_api_payload_rejected()
    {
        $quiz = Quiz::create([
            'user_id' => $this->author->id,
            'title' => 'Test Quiz Report',
            'is_public' => true,
            'status' => 'published',
        ]);

        $token = auth('api')->login($this->reporter);

        // Gửi payload kiểu cũ chỉ có quiz_id
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/report-tickets', [
                'quiz_id' => $quiz->id,
                'reason' => 'Quiz có câu hỏi sai',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question_id']);
    }

    /**
     * Case 8: Unauthorized user -> không thể tạo report (401).
     */
    public function test_case_8_unauthorized_user_cannot_report()
    {
        $question = $this->createQuestion(['is_public' => true]);

        $response = $this->postJson('/api/report-tickets', [
            'question_id' => $question->id,
            'reason' => 'Spam',
        ]);

        $response->assertStatus(401);
    }
}
