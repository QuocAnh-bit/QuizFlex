<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Quiz;
use App\Models\QuestionReviewRequest;
use App\Models\QuizReviewRequest;
use App\Services\QuestionReviewService;
use App\Services\QuizReviewService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AdminSearchByIdTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $teacher;
    protected QuestionReviewService $questionReviewService;
    protected QuizReviewService $quizReviewService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_search_id@quizflex.com'],
            ['name' => 'Admin Search Tester', 'password' => bcrypt('password'), 'role' => 'admin']
        );
        $this->admin->role = 'admin';
        $this->admin->save();

        $this->teacher = User::firstOrCreate(
            ['email' => 'teacher_search_id@quizflex.com'],
            ['name' => 'Teacher Search Tester', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->questionReviewService = app(QuestionReviewService::class);
        $this->quizReviewService = app(QuizReviewService::class);
    }

    private function createQuestion(string $content = 'Câu hỏi kiểm tra tìm kiếm'): Question
    {
        $q = Question::create([
            'user_id' => $this->teacher->id,
            'content' => $content,
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'points' => 10,
            'is_public' => false,
            'bank_submission_status' => 'none',
        ]);
        Answer::create(['question_id' => $q->id, 'content' => 'Đáp án 1 đúng', 'is_correct' => true, 'order' => 1]);
        Answer::create(['question_id' => $q->id, 'content' => 'Đáp án 2 sai', 'is_correct' => false, 'order' => 2]);
        return $q;
    }

    private function createQuiz(string $title = 'Quiz kiểm tra tìm kiếm'): Quiz
    {
        $quiz = Quiz::create([
            'user_id' => $this->teacher->id,
            'title' => $title,
            'description' => 'Mô tả bài quiz test',
            'is_public' => false,
            'status' => 'draft',
            'review_status' => 'draft',
        ]);

        $q1 = $this->createQuestion('Câu 1 của quiz');
        $q2 = $this->createQuestion('Câu 2 của quiz');
        $quiz->questions()->attach($q1->id, ['order' => 1, 'points' => 10]);
        $quiz->questions()->attach($q2->id, ['order' => 2, 'points' => 10]);

        return $quiz;
    }

    /**
     * Test 1: Admin searches Question Bank by Question ID or #ID
     */
    public function test_admin_search_question_bank_by_id_and_hash_id()
    {
        $originQ = $this->createQuestion('Câu hỏi Test Search By ID');
        $this->questionReviewService->submitToBank($originQ, $this->teacher);
        $this->questionReviewService->approveQuestion($originQ, $this->admin);

        $bankSnapshot = Question::where('origin_question_id', $originQ->id)->first();
        $this->assertNotNull($bankSnapshot);

        // 1. Search by exact snapshot ID
        $res1 = $this->actingAs($this->admin, 'api')->getJson("/api/admin/questions-management?search={$bankSnapshot->id}");
        $res1->assertStatus(200);
        $items1 = $res1->json('data.items');
        $this->assertNotEmpty($items1);
        $this->assertEquals($bankSnapshot->id, $items1[0]['id']);

        // 2. Search by hash ID (#123)
        $res2 = $this->actingAs($this->admin, 'api')->getJson("/api/admin/questions-management?search=%23{$bankSnapshot->id}");
        $res2->assertStatus(200);
        $items2 = $res2->json('data.items');
        $this->assertNotEmpty($items2);
        $this->assertEquals($bankSnapshot->id, $items2[0]['id']);

        // 3. Search by origin question ID
        $res3 = $this->actingAs($this->admin, 'api')->getJson("/api/admin/questions-management?search={$originQ->id}");
        $res3->assertStatus(200);
        $items3 = $res3->json('data.items');
        $this->assertNotEmpty($items3);
        $this->assertEquals($bankSnapshot->id, $items3[0]['id']);
    }

    /**
     * Test 2: Admin searches Question Review Requests by Question ID, Hash ID, or Request ID
     */
    public function test_admin_search_question_review_requests_by_id()
    {
        $question = $this->createQuestion('Câu hỏi Test Search Pending Request');
        $req = $this->questionReviewService->submitToBank($question, $this->teacher);

        // 1. Search by Question ID
        $res1 = $this->actingAs($this->admin, 'api')->getJson("/api/admin/question-bank-requests?status=pending&search={$question->id}");
        $res1->assertStatus(200);
        $items1 = $res1->json('data.items');
        $this->assertNotEmpty($items1);
        $this->assertEquals($question->id, $items1[0]['id']);

        // 2. Search by Hash ID
        $res2 = $this->actingAs($this->admin, 'api')->getJson("/api/admin/question-bank-requests?status=pending&search=%23{$question->id}");
        $res2->assertStatus(200);
        $items2 = $res2->json('data.items');
        $this->assertNotEmpty($items2);
        $this->assertEquals($question->id, $items2[0]['id']);
    }

    /**
     * Test 3: Admin searches Quiz by Quiz ID and Hash ID
     */
    public function test_admin_search_quiz_by_id_and_hash_id()
    {
        $quiz = $this->createQuiz('Quiz Thử Nghiệm Tìm Kiếm ID');

        // 1. Search by Quiz ID
        $res1 = $this->actingAs($this->admin, 'api')->getJson("/api/admin/quizzes?search={$quiz->id}");
        $res1->assertStatus(200);
        $data1 = $res1->json('data');
        $items1 = $data1['items'] ?? $data1['data'] ?? $data1;
        $this->assertNotEmpty($items1);
        $this->assertEquals($quiz->id, $items1[0]['id']);

        // 2. Search by Hash ID (#45)
        $res2 = $this->actingAs($this->admin, 'api')->getJson("/api/admin/quizzes?search=%23{$quiz->id}");
        $res2->assertStatus(200);
        $data2 = $res2->json('data');
        $items2 = $data2['items'] ?? $data2['data'] ?? $data2;
        $this->assertNotEmpty($items2);
        $this->assertEquals($quiz->id, $items2[0]['id']);
    }

    /**
     * Test 4: Admin searches Quiz Review Requests by Quiz ID and Hash ID
     */
    public function test_admin_search_quiz_review_requests_by_id()
    {
        $quiz = $this->createQuiz('Quiz Pending Review Search');
        $req = $this->quizReviewService->requestReview($quiz, $this->teacher);

        // 1. Search by Quiz ID
        $res1 = $this->actingAs($this->admin, 'api')->getJson("/api/admin/quiz-review-requests?status=pending&search={$quiz->id}");
        $res1->assertStatus(200);
        $items1 = $res1->json('data.items');
        $this->assertNotEmpty($items1);
        $this->assertEquals($quiz->id, $items1[0]['quiz_id']);

        // 2. Search by Hash ID
        $res2 = $this->actingAs($this->admin, 'api')->getJson("/api/admin/quiz-review-requests?status=pending&search=%23{$quiz->id}");
        $res2->assertStatus(200);
        $items2 = $res2->json('data.items');
        $this->assertNotEmpty($items2);
        $this->assertEquals($quiz->id, $items2[0]['quiz_id']);
    }

    /**
     * Test 5: Admin searches Question Trash by ID
     */
    public function test_admin_search_question_trash_by_id()
    {
        $originQ = $this->createQuestion('Câu hỏi Trashed Search');
        $this->questionReviewService->submitToBank($originQ, $this->teacher);
        $this->questionReviewService->approveQuestion($originQ, $this->admin);

        $bankSnapshot = Question::where('origin_question_id', $originQ->id)->first();
        $this->assertNotNull($bankSnapshot);
        $bankSnapshot->delete(); // Soft delete

        $res = $this->actingAs($this->admin, 'api')->getJson("/api/admin/questions-trash?search={$bankSnapshot->id}");
        $res->assertStatus(200);
        $items = $res->json('data.items');
        $this->assertNotEmpty($items);
        $this->assertEquals($bankSnapshot->id, $items[0]['id']);
    }
}
