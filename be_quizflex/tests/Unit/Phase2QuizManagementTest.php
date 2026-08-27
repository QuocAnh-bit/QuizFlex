<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use App\Models\QuizReviewRequest;
use App\Models\Subject;
use App\Models\Grade;
use App\Models\EducationLevel;
use App\Services\QuizReviewService;
use App\Services\QuestionSnapshotService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;

class Phase2QuizManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $author;
    protected QuizReviewService $reviewService;
    protected QuestionSnapshotService $snapshotService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_test_quiz@quizflex.com'],
            ['name' => 'Admin Quiz Tester', 'password' => bcrypt('password'), 'role' => 'admin']
        );
        $this->admin->role = 'admin';
        $this->admin->save();

        $this->author = User::firstOrCreate(
            ['email' => 'author_test_quiz@quizflex.com'],
            ['name' => 'Author Quiz Tester', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->reviewService = app(QuizReviewService::class);
        $this->snapshotService = app(QuestionSnapshotService::class);
    }

    private function createSampleQuizWithQuestions(string $title = 'Đề thi thử nghiệm'): Quiz
    {
        $quiz = Quiz::create([
            'user_id' => $this->author->id,
            'title' => $title,
            'description' => 'Mô tả bài kiểm tra thử nghiệm',
            'is_public' => false,
            'status' => 'draft',
            'review_status' => 'draft',
        ]);

        // Tạo 2 câu hỏi cá nhân
        for ($i = 1; $i <= 2; $i++) {
            $q = Question::create([
                'user_id' => $this->author->id,
                'content' => "Câu hỏi {$i} của {$title}",
                'type' => 'single_choice',
                'difficulty' => 'medium',
                'points' => 10,
                'is_public' => false,
                'bank_submission_status' => 'none',
            ]);

            Answer::create(['question_id' => $q->id, 'key' => 'A', 'content' => 'Đáp án A đúng', 'is_correct' => true, 'order' => 1]);
            Answer::create(['question_id' => $q->id, 'key' => 'B', 'content' => 'Đáp án B sai', 'is_correct' => false, 'order' => 2]);

            $quiz->questions()->attach($q->id, ['order' => $i, 'points' => 10]);
        }

        return $quiz;
    }

    /**
     * Test A: Pending -> Approve
     */
    public function test_a_pending_approve_workflow()
    {
        $quiz = $this->createSampleQuizWithQuestions('Quiz A Test');

        // 1. Author requests review
        $reviewReq = $this->reviewService->requestReview($quiz, $this->author, 'Xin duyệt bài Quiz này');
        $quiz->refresh();

        $this->assertEquals('pending_review', $quiz->review_status);
        $this->assertEquals(1, $reviewReq->revision_number);
        $this->assertEquals('pending', $reviewReq->status);

        // 2. Admin approves
        $approvedReq = $this->reviewService->approveQuiz($quiz, $this->admin);
        $quiz->refresh();

        $this->assertEquals('approved', $quiz->review_status);
        $this->assertTrue((bool)$quiz->is_public);
        $this->assertEquals('approved', $approvedReq->status);

        // Verify questions in quiz are now public bank snapshots
        foreach ($quiz->questions as $q) {
            $this->assertTrue((bool)$q->is_public);
            $this->assertEquals('approved', $q->bank_submission_status);
        }
    }

    /**
     * Test B: Pending -> Reject
     */
    public function test_b_pending_reject_workflow()
    {
        $quiz = $this->createSampleQuizWithQuestions('Quiz B Test');

        $this->reviewService->requestReview($quiz, $this->author);
        $quiz->refresh();
        $this->assertEquals('pending_review', $quiz->review_status);

        // Admin rejects
        $reason = 'Tiêu đề chưa rõ ràng và thiếu hướng dẫn làm bài';
        $rejectedReq = $this->reviewService->rejectQuiz($quiz, $this->admin, $reason);
        $quiz->refresh();

        $this->assertEquals('rejected', $quiz->review_status);
        $this->assertFalse((bool)$quiz->is_public);
        $this->assertEquals($reason, $quiz->rejection_reason);
        $this->assertEquals('rejected', $rejectedReq->status);
        $this->assertEquals($reason, $rejectedReq->rejection_reason);
    }

    /**
     * Test C: Backend Guard: Calling approve or reject on a rejected quiz throws ValidationException
     */
    public function test_c_backend_guard_cannot_approve_rejected_quiz_without_resubmission()
    {
        $quiz = $this->createSampleQuizWithQuestions('Quiz C Test');
        $this->reviewService->requestReview($quiz, $this->author);
        $this->reviewService->rejectQuiz($quiz, $this->admin, 'Từ chối duyệt');
        $quiz->refresh();

        $this->assertEquals('rejected', $quiz->review_status);

        // Admin attempts to approve without author resubmitting
        $this->expectException(ValidationException::class);
        $this->reviewService->approveQuiz($quiz, $this->admin);
    }

    /**
     * Test D & E: Rejected -> Resubmit Revision 2 -> Diff (Revision 2 VS Revision 1)
     */
    public function test_d_and_e_rejected_resubmit_revision_2_diff()
    {
        $quiz = $this->createSampleQuizWithQuestions('Quiz Rev 1');
        $this->reviewService->requestReview($quiz, $this->author, 'Gửi duyệt lần 1');
        $this->reviewService->rejectQuiz($quiz, $this->admin, 'Nội dung chưa đầy đủ');

        // Author edits title and adds another question
        $quiz->update(['title' => 'Quiz Rev 2 đã chỉnh sửa']);

        $newQ = Question::create([
            'user_id' => $this->author->id,
            'content' => 'Câu hỏi mới thêm vào Rev 2',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'points' => 10,
            'is_public' => false,
        ]);
        Answer::create(['question_id' => $newQ->id, 'key' => 'A', 'content' => 'Đúng', 'is_correct' => true, 'order' => 1]);
        Answer::create(['question_id' => $newQ->id, 'key' => 'B', 'content' => 'Sai', 'is_correct' => false, 'order' => 2]);
        $quiz->questions()->attach($newQ->id, ['order' => 3, 'points' => 10]);

        $rev2 = $this->reviewService->requestReview($quiz, $this->author, 'Đã sửa và bổ sung câu 3');
        $quiz->refresh();

        $this->assertEquals(2, $rev2->revision_number);
        $this->assertEquals('pending_review', $quiz->review_status);

        // Admin views Diff
        $diff = $this->reviewService->getReviewDetailsWithDiff($quiz);

        $this->assertNotNull($diff['current_revision']);
        $this->assertNotNull($diff['previous_revision']);
        $this->assertEquals(2, $diff['current_revision']['revision_number']);
        $this->assertEquals('Quiz Rev 2 đã chỉnh sửa', $diff['current_revision']['title']);

        $this->assertEquals(1, $diff['previous_revision']['revision_number']);
        $this->assertEquals('Quiz Rev 1', $diff['previous_revision']['title']);
        $this->assertEquals('Nội dung chưa đầy đủ', $diff['previous_rejection_reason']);

        // Check Diff metadata & questions count
        $this->assertEquals(1, $diff['diff']['questions_summary']['added_count']);
        $this->assertArrayHasKey('title', $diff['diff']['changes']);
    }

    /**
     * Test F: Multiple Revisions Diff: Revision 3 VS Revision 2
     */
    public function test_f_multiple_revisions_diff_revision_3_vs_revision_2()
    {
        $quiz = $this->createSampleQuizWithQuestions('Quiz Base');

        // Rev 1
        $this->reviewService->requestReview($quiz, $this->author);
        $this->reviewService->rejectQuiz($quiz, $this->admin, 'Lý do 1');

        // Rev 2
        $quiz->update(['title' => 'Quiz Phiên bản 2']);
        $this->reviewService->requestReview($quiz, $this->author);
        $this->reviewService->rejectQuiz($quiz, $this->admin, 'Lý do 2');

        // Rev 3
        $quiz->update(['title' => 'Quiz Phiên bản 3 hoàn thiện']);
        $rev3 = $this->reviewService->requestReview($quiz, $this->author);
        $quiz->refresh();

        $this->assertEquals(3, $rev3->revision_number);

        // Admin views Diff for Revision 3
        $diff = $this->reviewService->getReviewDetailsWithDiff($quiz);

        $this->assertEquals(3, $diff['current_revision']['revision_number']);
        $this->assertEquals('Quiz Phiên bản 3 hoàn thiện', $diff['current_revision']['title']);

        // CRITICAL CHECK: Previous revision must be Revision 2 (NOT Revision 1)
        $this->assertEquals(2, $diff['previous_revision']['revision_number']);
        $this->assertEquals('Quiz Phiên bản 2', $diff['previous_revision']['title']);
        $this->assertEquals('Lý do 2', $diff['previous_rejection_reason']);

        // Total history count is 3
        $this->assertCount(3, $diff['history']);
    }

    /**
     * Test G: Approved Quiz -> Admin Show API
     */
    public function test_g_approved_quiz_admin_show_api()
    {
        $quiz = $this->createSampleQuizWithQuestions('Quiz Test G');
        $this->reviewService->requestReview($quiz, $this->author);
        $this->reviewService->approveQuiz($quiz, $this->admin);

        $response = $this->actingAs($this->admin, 'api')->getJson("/api/admin/quizzes/{$quiz->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'quiz' => [
                    'id',
                    'title',
                    'is_public',
                    'review_status',
                    'questions' => [
                        '*' => [
                            'id',
                            'content',
                            'answers' => [
                                '*' => ['id', 'content', 'is_correct']
                            ]
                        ]
                    ],
                    'user',
                ],
                'average_score',
                'history',
            ]
        ]);

        $data = $response->json('data');
        $this->assertEquals('approved', $data['quiz']['review_status']);
        $this->assertTrue((bool)$data['quiz']['is_public']);
        $this->assertCount(2, $data['quiz']['questions']);

        // Verify correct answer badge data
        $firstQAnswers = $data['quiz']['questions'][0]['answers'];
        $correct = array_filter($firstQAnswers, fn($a) => !empty($a['is_correct']));
        $this->assertCount(1, $correct);
    }
}
