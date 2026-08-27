<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Grade;
use App\Models\EducationLevel;
use App\Models\QuestionReviewRequest;
use App\Models\ReportTicket;
use App\Services\QuestionReviewService;
use App\Services\QuestionSnapshotService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;

class Phase1QuestionBankTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $author;
    protected QuestionReviewService $reviewService;
    protected QuestionSnapshotService $snapshotService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_test@quizflex.com'],
            ['name' => 'Admin Tester', 'password' => bcrypt('password'), 'role' => 'admin']
        );
        $this->admin->role = 'admin';
        $this->admin->save();

        $this->author = User::firstOrCreate(
            ['email' => 'author_test@quizflex.com'],
            ['name' => 'Author Tester', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->reviewService = app(QuestionReviewService::class);
        $this->snapshotService = app(QuestionSnapshotService::class);
    }

    private function createSampleQuestion(string $content = 'Câu hỏi thử nghiệm số 1'): Question
    {
        $question = Question::create([
            'user_id' => $this->author->id,
            'content' => $content,
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'points' => 10,
            'is_public' => false,
            'bank_submission_status' => 'none',
        ]);

        Answer::create(['question_id' => $question->id, 'key' => 'A', 'content' => 'Đáp án A đúng', 'is_correct' => true, 'order' => 1]);
        Answer::create(['question_id' => $question->id, 'key' => 'B', 'content' => 'Đáp án B sai', 'is_correct' => false, 'order' => 2]);
        Answer::create(['question_id' => $question->id, 'key' => 'C', 'content' => 'Đáp án C sai', 'is_correct' => false, 'order' => 3]);
        Answer::create(['question_id' => $question->id, 'key' => 'D', 'content' => 'Đáp án D sai', 'is_correct' => false, 'order' => 4]);

        return $question;
    }

    /**
     * Test A: Pending -> Review -> Approve
     */
    public function test_a_pending_review_approve_workflow()
    {
        $question = $this->createSampleQuestion('Test A Question');

        // 1. Author submits to bank
        $revRequest = $this->reviewService->submitToBank($question, $this->author, 'Xin duyệt câu hỏi này');
        $question->refresh();

        $this->assertEquals('pending', $question->bank_submission_status);
        $this->assertEquals(1, $revRequest->revision_number);
        $this->assertEquals('pending', $revRequest->status);

        // 2. Admin approves
        $approvedRev = $this->reviewService->approveQuestion($question, $this->admin);
        $question->refresh();

        $this->assertEquals('approved', $question->bank_submission_status);
        $this->assertEquals('approved', $approvedRev->status);

        // 3. Verify public bank snapshot exists
        $bankSnapshot = Question::where('origin_question_id', $question->id)->first();
        $this->assertNotNull($bankSnapshot);
        $this->assertTrue((bool)$bankSnapshot->is_public);
        $this->assertEquals('approved', $bankSnapshot->bank_submission_status);
        $this->assertCount(4, $bankSnapshot->answers);
    }

    /**
     * Test B: Pending -> Reject
     */
    public function test_b_pending_reject_workflow()
    {
        $question = $this->createSampleQuestion('Test B Question');

        $this->reviewService->submitToBank($question, $this->author);
        $question->refresh();
        $this->assertEquals('pending', $question->bank_submission_status);

        // Admin rejects
        $reason = 'Sai chính tả và chưa chọn đúng môn học';
        $rejectedRev = $this->reviewService->rejectQuestion($question, $this->admin, $reason);
        $question->refresh();

        $this->assertEquals('rejected', $question->bank_submission_status);
        $this->assertEquals($reason, $question->bank_submission_note);
        $this->assertEquals('rejected', $rejectedRev->status);
        $this->assertEquals($reason, $rejectedRev->rejection_reason);

        // No public bank snapshot should exist
        $bankSnapshot = Question::where('origin_question_id', $question->id)->first();
        $this->assertNull($bankSnapshot);
    }

    /**
     * Test C: Reject -> Resubmit Revision 2 -> Admin views Revision 2 vs Revision 1
     */
    public function test_c_reject_and_resubmit_revision_2_diff()
    {
        $question = $this->createSampleQuestion('Nội dung Revision 1');
        $this->reviewService->submitToBank($question, $this->author, 'Gửi lần 1');
        $this->reviewService->rejectQuestion($question, $this->admin, 'Nội dung chưa chuẩn');

        // Author edits question and resubmits
        $question->update(['content' => 'Nội dung Revision 2 đã được sửa']);
        $rev2 = $this->reviewService->submitToBank($question, $this->author, 'Đã sửa theo góp ý');
        $question->refresh();

        $this->assertEquals(2, $rev2->revision_number);
        $this->assertEquals('pending', $question->bank_submission_status);

        // Admin gets Diff
        $diff = $this->reviewService->getReviewDetailsWithDiff($question);

        $this->assertNotNull($diff['current_revision']);
        $this->assertNotNull($diff['previous_revision']);
        $this->assertEquals(2, $diff['current_revision']['revision_number']);
        $this->assertEquals('Nội dung Revision 2 đã được sửa', $diff['current_revision']['content']);
        $this->assertEquals(1, $diff['previous_revision']['revision_number']);
        $this->assertEquals('Nội dung Revision 1', $diff['previous_revision']['content']);
        $this->assertEquals('Nội dung chưa chuẩn', $diff['previous_revision']['rejection_reason']);
    }

    /**
     * Test D: Revision 2 -> Reject -> Resubmit Revision 3 -> Admin views Revision 3 vs Revision 2
     */
    public function test_d_multiple_revisions_diff_comparison()
    {
        $question = $this->createSampleQuestion('Nội dung Revision 1');
        $this->reviewService->submitToBank($question, $this->author);
        $this->reviewService->rejectQuestion($question, $this->admin, 'Lý do từ chối 1');

        $question->update(['content' => 'Nội dung Revision 2']);
        $this->reviewService->submitToBank($question, $this->author);
        $this->reviewService->rejectQuestion($question, $this->admin, 'Lý do từ chối 2');

        $question->update(['content' => 'Nội dung Revision 3 hoàn thiện']);
        $rev3 = $this->reviewService->submitToBank($question, $this->author);
        $question->refresh();

        $this->assertEquals(3, $rev3->revision_number);

        // Admin views Diff on Revision 3
        $diff = $this->reviewService->getReviewDetailsWithDiff($question);

        $this->assertEquals(3, $diff['current_revision']['revision_number']);
        $this->assertEquals('Nội dung Revision 3 hoàn thiện', $diff['current_revision']['content']);

        // CRITICAL CHECK: Previous revision must be Revision 2 (NOT Revision 1 and NOT current database row)
        $this->assertEquals(2, $diff['previous_revision']['revision_number']);
        $this->assertEquals('Nội dung Revision 2', $diff['previous_revision']['content']);
        $this->assertEquals('Lý do từ chối 2', $diff['previous_revision']['rejection_reason']);

        // History count should be 3
        $this->assertCount(3, $diff['history']);
    }

    /**
     * Test E: Approved -> View Detail (Check answers with is_correct, using_quizzes, author, reports)
     */
    public function test_e_approved_question_view_detail_api()
    {
        $question = $this->createSampleQuestion('Test E Approved Question');
        $this->reviewService->submitToBank($question, $this->author);
        $this->reviewService->approveQuestion($question, $this->admin);

        $bankSnapshot = Question::where('origin_question_id', $question->id)->first();
        $this->assertNotNull($bankSnapshot);

        $response = $this->actingAs($this->admin, 'api')->getJson("/api/admin/questions/{$bankSnapshot->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'content',
                'is_public',
                'bank_submission_status',
                'answers',
                'author',
                'using_quizzes',
                'reports',
            ]
        ]);

        $data = $response->json('data');
        $this->assertEquals('approved', $data['bank_submission_status']);
        $this->assertTrue((bool)$data['is_public']);
        $this->assertCount(4, $data['answers']);

        // Ensure correct answer flag is present
        $correctAnswers = array_filter($data['answers'], fn($a) => !empty($a['is_correct']));
        $this->assertCount(1, $correctAnswers);
    }

    /**
     * Test F & G: Trash -> Restore & Force Delete
     */
    public function test_f_and_g_trash_restore_and_force_delete()
    {
        $question = $this->createSampleQuestion('Test F Question');
        $this->reviewService->submitToBank($question, $this->author);
        $this->reviewService->approveQuestion($question, $this->admin);

        $bankSnapshot = Question::where('origin_question_id', $question->id)->first();

        // 1. Soft Delete
        $deleteRes = $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/questions/{$bankSnapshot->id}");
        $deleteRes->assertStatus(200);
        $this->assertSoftDeleted('questions', ['id' => $bankSnapshot->id]);

        // 2. Restore
        $restoreRes = $this->actingAs($this->admin, 'api')->postJson("/api/admin/questions/{$bankSnapshot->id}/restore");
        $restoreRes->assertStatus(200);
        $this->assertNotSoftDeleted('questions', ['id' => $bankSnapshot->id]);

        // 3. Soft delete again and Force Delete
        $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/questions/{$bankSnapshot->id}");
        $forceRes = $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/questions/{$bankSnapshot->id}/force-delete");
        $forceRes->assertStatus(200);
        $this->assertDatabaseMissing('questions', ['id' => $bankSnapshot->id]);
    }

    /**
     * Validation Check: Calling approve on a rejected question throws ValidationException
     */
    public function test_cannot_approve_rejected_question_without_resubmission()
    {
        $question = $this->createSampleQuestion('Test Reject Approve Validation');
        $this->reviewService->submitToBank($question, $this->author);
        $this->reviewService->rejectQuestion($question, $this->admin, 'Từ chối');

        $this->expectException(ValidationException::class);
        $this->reviewService->approveQuestion($question, $this->admin);
    }

    /**
     * Test: Resubmit pending question without changing content throws ValidationException
     */
    public function test_pending_question_resubmit_without_content_change_throws_validation_exception()
    {
        $question = $this->createSampleQuestion('Nội dung chưa đổi');
        $this->reviewService->submitToBank($question, $this->author, 'Gửi lần 1');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('nội dung chưa thay đổi');

        // Gửi lại khi chưa thay đổi gì
        $this->reviewService->submitToBank($question, $this->author, 'Gửi lại mà không sửa');
    }

    /**
     * Test: Pending question edited by owner and resubmitted supersedes previous pending request
     */
    public function test_pending_question_edited_and_resubmitted_supersedes_previous_pending_request()
    {
        $question = $this->createSampleQuestion('Nội dung gốc Revision 1');
        $rev1 = $this->reviewService->submitToBank($question, $this->author, 'Gửi Revision 1');

        $this->assertEquals(1, $rev1->revision_number);
        $this->assertEquals('pending', $rev1->status);
        $this->assertEquals('pending', $question->fresh()->bank_submission_status);

        // Owner edits question content
        $question->update(['content' => 'Nội dung đính chính Revision 2']);

        // Owner resubmits
        $rev2 = $this->reviewService->submitToBank($question, $this->author, 'Đã đính chính nội dung');

        // Assert Rev 1 is superseded
        $rev1->refresh();
        $this->assertEquals('superseded', $rev1->status);

        // Assert Rev 2 is pending with revision_number = 2
        $this->assertEquals(2, $rev2->revision_number);
        $this->assertEquals('pending', $rev2->status);
        $this->assertEquals('Nội dung đính chính Revision 2', $rev2->snapshot_content);

        // Verify only 1 pending request exists for this question
        $pendingCount = QuestionReviewRequest::where('question_id', $question->id)->where('status', 'pending')->count();
        $this->assertEquals(1, $pendingCount);

        // Admin approves Revision 2
        $approvedRev = $this->reviewService->approveQuestion($question, $this->admin);
        $this->assertEquals('approved', $approvedRev->status);
        $this->assertEquals(2, $approvedRev->revision_number);

        // Verify Bank Snapshot has Revision 2 content
        $bankSnapshot = Question::where('origin_question_id', $question->id)->first();
        $this->assertNotNull($bankSnapshot);
        $this->assertEquals('Nội dung đính chính Revision 2', $bankSnapshot->content);
        $this->assertTrue((bool)$bankSnapshot->is_public);
    }

    /**
     * Test: Superseded revision appears in history and diff
     */
    public function test_superseded_request_appears_in_review_history_with_diff()
    {
        $question = $this->createSampleQuestion('Nội dung Rev 1');
        $this->reviewService->submitToBank($question, $this->author, 'Gửi Rev 1');

        // Owner edits and resubmits (supersedes Rev 1)
        $question->update(['content' => 'Nội dung Rev 2']);
        $this->reviewService->submitToBank($question, $this->author, 'Gửi Rev 2');

        // Admin rejects Rev 2
        $this->reviewService->rejectQuestion($question, $this->admin, 'Từ chối Rev 2');

        // Owner edits and submits Rev 3
        $question->update(['content' => 'Nội dung Rev 3 hoàn hảo']);
        $this->reviewService->submitToBank($question, $this->author, 'Gửi Rev 3');

        $diff = $this->reviewService->getReviewDetailsWithDiff($question);

        $this->assertEquals(3, $diff['current_revision']['revision_number']);
        $this->assertEquals('pending', $diff['current_revision']['status']);
        $this->assertEquals(2, $diff['previous_revision']['revision_number']);
        $this->assertEquals('rejected', $diff['previous_revision']['status']);

        // Check full history has 3 items
        $this->assertCount(3, $diff['history']);
        $this->assertEquals('superseded', $diff['history'][2]['status']);
        $this->assertEquals(1, $diff['history'][2]['revision_number']);
    }
}

