<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionReviewRequest;
use App\Models\User;
use App\Services\QuestionSnapshotService;
use Tests\TestCase;

class QuestionDuplicateDetectionTest extends TestCase
{
    protected User $userA;
    protected User $userB;
    protected User $admin;
    protected QuestionSnapshotService $snapshotService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->snapshotService = app(QuestionSnapshotService::class);

        $this->userA = User::firstOrCreate(
            ['email' => 'user_a_dup_test@quizflex.local'],
            [
                'name' => 'User A Dup Test',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        $this->userB = User::firstOrCreate(
            ['email' => 'user_b_dup_test@quizflex.local'],
            [
                'name' => 'User B Dup Test',
                'password' => bcrypt('password'),
                'role' => 'user',
            ]
        );

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_dup_test@quizflex.local'],
            [
                'name' => 'Admin Dup Test',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        // Dọn dẹp dữ liệu câu hỏi của các test users trước mỗi bài test
        Question::withTrashed()
            ->whereIn('user_id', [$this->userA->id, $this->userB->id, $this->admin->id])
            ->forceDelete();
    }

    /**
     * TEST A:
     * User tạo Question A.
     * User tạo Question A lần 2.
     * Kết quả:
     * - Question thứ 2 bị từ chối (422 validation error).
     * - Database chỉ có 1 Question của User này.
     */
    public function test_a_user_cannot_create_duplicate_question_in_personal_bank()
    {
        $payload = [
            'content' => 'Thủ đô của nước Pháp là thành phố nào?',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'answers' => [
                ['content' => 'Paris', 'is_correct' => true, 'key' => 'A'],
                ['content' => 'Lyon', 'is_correct' => false, 'key' => 'B'],
                ['content' => 'Marseille', 'is_correct' => false, 'key' => 'C'],
                ['content' => 'Nice', 'is_correct' => false, 'key' => 'D'],
            ],
        ];

        // Lần 1: Tạo thành công
        $res1 = $this->actingAs($this->userA, 'api')->postJson('/api/questions', $payload);
        $res1->assertStatus(201);
        $res1->assertJsonPath('success', true);

        $this->assertEquals(1, Question::where('user_id', $this->userA->id)->whereNull('origin_question_id')->count());

        // Lần 2: Tạo lại đúng câu hỏi đó (kể cả có thêm khoảng trắng dư hoặc đảo thứ tự đáp án)
        $payloadDup = [
            'content' => '  Thủ đô của nước Pháp là thành phố nào?  ',
            'type' => 'single_choice',
            'difficulty' => 'medium',
            'answers' => [
                ['content' => 'Lyon', 'is_correct' => false, 'key' => 'A'],
                ['content' => 'Paris', 'is_correct' => true, 'key' => 'B'],
                ['content' => 'Nice', 'is_correct' => false, 'key' => 'C'],
                ['content' => 'Marseille', 'is_correct' => false, 'key' => 'D'],
            ],
        ];

        $res2 = $this->actingAs($this->userA, 'api')->postJson('/api/questions', $payloadDup);
        $res2->assertStatus(422);
        $res2->assertJsonValidationErrors(['content']);

        // Database vẫn chỉ có đúng 1 câu hỏi của User A
        $this->assertEquals(1, Question::where('user_id', $this->userA->id)->whereNull('origin_question_id')->count());
    }

    /**
     * TEST B:
     * User A tạo Question X.
     * User B tạo Question X.
     * Kết quả:
     * - cả hai User đều tạo thành công trong kho cá nhân của mình.
     */
    public function test_b_different_users_can_create_same_question_in_personal_bank()
    {
        $payload = [
            'content' => 'Mặt trời mọc ở hướng nào?',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'answers' => [
                ['content' => 'Hướng Đông', 'is_correct' => true, 'key' => 'A'],
                ['content' => 'Hướng Tây', 'is_correct' => false, 'key' => 'B'],
            ],
        ];

        // User A tạo
        $resA = $this->actingAs($this->userA, 'api')->postJson('/api/questions', $payload);
        $resA->assertStatus(201);

        // User B tạo câu hỏi giống hệt
        $resB = $this->actingAs($this->userB, 'api')->postJson('/api/questions', $payload);
        $resB->assertStatus(201);

        // Cả 2 User đều có 1 câu hỏi cá nhân riêng
        $this->assertEquals(1, Question::where('user_id', $this->userA->id)->whereNull('origin_question_id')->count());
        $this->assertEquals(1, Question::where('user_id', $this->userB->id)->whereNull('origin_question_id')->count());
    }

    /**
     * TEST C:
     * Bank đã có Snapshot X.
     * User gửi Question X lên review.
     * Admin approve.
     * Kết quả:
     * - không tạo Snapshot thứ 2 trong Bank.
     * - Bank vẫn chỉ có 1 Question X.
     */
    public function test_c_approving_duplicate_question_does_not_create_second_snapshot_in_bank()
    {
        // 1. User A tạo Question X và gửi duyệt -> Admin approve -> Bank có 1 Snapshot X
        $resA = $this->actingAs($this->userA, 'api')->postJson('/api/questions', [
            'content' => 'Nước sôi ở bao nhiêu độ C ở áp suất tiêu chuẩn?',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'answers' => [
                ['content' => '100 độ C', 'is_correct' => true, 'key' => 'A'],
                ['content' => '90 độ C', 'is_correct' => false, 'key' => 'B'],
            ],
        ]);
        $qAId = $resA->json('data.id');

        $this->actingAs($this->userA, 'api')->postJson("/api/user/my-questions/{$qAId}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$qAId}/approve");

        $fingerprint = Question::find($qAId)->fingerprint;
        $this->assertNotEmpty($fingerprint);

        // Kiểm tra Bank đang có đúng 1 câu hỏi public với fingerprint này
        $bankQuestionsBefore = Question::where('fingerprint', $fingerprint)->where('is_public', true)->get();
        $this->assertCount(1, $bankQuestionsBefore);
        $bankSnapshot1 = $bankQuestionsBefore->first();

        // 2. User B cũng tạo Question X giống hệt và gửi duyệt
        $resB = $this->actingAs($this->userB, 'api')->postJson('/api/questions', [
            'content' => '  Nước sôi ở bao nhiêu độ C ở áp suất tiêu chuẩn?  ',
            'type' => 'single_choice',
            'difficulty' => 'medium',
            'answers' => [
                ['content' => '90 độ C', 'is_correct' => false, 'key' => 'A'],
                ['content' => '100 độ C', 'is_correct' => true, 'key' => 'B'],
            ],
        ]);
        $qBId = $resB->json('data.id');

        $submitB = $this->actingAs($this->userB, 'api')->postJson("/api/user/my-questions/{$qBId}/submit-to-bank");
        $submitB->assertStatus(200);

        // 3. Admin approve câu hỏi của User B
        $approveB = $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$qBId}/approve");
        $approveB->assertStatus(200);

        // Kiểm tra câu hỏi gốc của User B được cập nhật approved
        $qB = Question::find($qBId);
        $this->assertEquals('approved', $qB->bank_submission_status);
        $this->assertFalse((bool)$qB->is_public);

        // QUAN TRỌNG: Bank VẪN CHỈ CÓ ĐÚNG 1 Snapshot X (không tạo thêm snapshot thứ 2)
        $bankQuestionsAfter = Question::where('fingerprint', $fingerprint)->where('is_public', true)->get();
        $this->assertCount(1, $bankQuestionsAfter);
        $this->assertEquals($bankSnapshot1->id, $bankQuestionsAfter->first()->id);
    }

    /**
     * TEST D:
     * Bank chưa có X.
     * User submit X.
     * Admin approve.
     * Kết quả:
     * - tạo đúng 1 Snapshot trong Bank.
     */
    public function test_d_approving_new_question_creates_exactly_one_bank_snapshot()
    {
        $res = $this->actingAs($this->userA, 'api')->postJson('/api/questions', [
            'content' => 'Hành tinh nào gần Mặt trời nhất?',
            'type' => 'single_choice',
            'difficulty' => 'medium',
            'answers' => [
                ['content' => 'Sao Thủy', 'is_correct' => true, 'key' => 'A'],
                ['content' => 'Sao Kim', 'is_correct' => false, 'key' => 'B'],
                ['content' => 'Sao Hỏa', 'is_correct' => false, 'key' => 'C'],
            ],
        ]);
        $qId = $res->json('data.id');

        $this->actingAs($this->userA, 'api')->postJson("/api/user/my-questions/{$qId}/submit-to-bank");
        $approveRes = $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$qId}/approve");
        $approveRes->assertStatus(200);

        $fingerprint = Question::find($qId)->fingerprint;
        $bankSnapshots = Question::where('fingerprint', $fingerprint)->where('is_public', true)->get();

        $this->assertCount(1, $bankSnapshots);
        $snapshot = $bankSnapshots->first();
        $this->assertEquals($qId, $snapshot->origin_question_id);
        $this->assertTrue((bool) $snapshot->is_public);
        $this->assertEquals('approved', $snapshot->bank_submission_status);
        $this->assertCount(3, $snapshot->answers);
    }

    /**
     * TEST E:
     * Admin approve cùng một request 2 lần.
     * Kết quả:
     * - không tạo duplicate snapshot.
     */
    public function test_e_admin_approving_same_request_twice_is_idempotent()
    {
        $res = $this->actingAs($this->userA, 'api')->postJson('/api/questions', [
            'content' => 'Tam giác đều có 3 góc bằng bao nhiêu độ?',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'answers' => [
                ['content' => '60 độ', 'is_correct' => true, 'key' => 'A'],
                ['content' => '90 độ', 'is_correct' => false, 'key' => 'B'],
            ],
        ]);
        $qId = $res->json('data.id');

        $this->actingAs($this->userA, 'api')->postJson("/api/user/my-questions/{$qId}/submit-to-bank");

        // Lần 1: Approve thành công
        $app1 = $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$qId}/approve");
        $app1->assertStatus(200);

        $fingerprint = Question::find($qId)->fingerprint;
        $this->assertEquals(1, Question::where('fingerprint', $fingerprint)->where('is_public', true)->count());

        // Lần 2: Approve lại request đã approved
        $app2 = $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$qId}/approve");
        $app2->assertStatus(422); // Bị từ chối vì không còn ở trạng thái pending

        // Không tạo thêm snapshot nào
        $this->assertEquals(1, Question::where('fingerprint', $fingerprint)->where('is_public', true)->count());
    }

    /**
     * TEST F:
     * Concurrency / Race Condition simulation:
     * 2 review requests khác nhau có cùng fingerprint được xử lý đồng thời / liên tiếp.
     * Kết quả:
     * - Không tạo duplicate snapshot trong Bank.
     */
    public function test_f_concurrent_or_back_to_back_approvals_do_not_duplicate_snapshots()
    {
        $content = 'Số nguyên tố chẵn duy nhất là số nào? ' . uniqid();
        $answers = [
            ['content' => 'Số 2', 'is_correct' => true, 'key' => 'A'],
            ['content' => 'Số 4', 'is_correct' => false, 'key' => 'B'],
        ];

        // User A tạo & submit
        $resA = $this->actingAs($this->userA, 'api')->postJson('/api/questions', [
            'content' => $content,
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'answers' => $answers,
        ]);
        $qAId = $resA->json('data.id');
        $this->actingAs($this->userA, 'api')->postJson("/api/user/my-questions/{$qAId}/submit-to-bank");

        // User B tạo & submit cùng nội dung
        $resB = $this->actingAs($this->userB, 'api')->postJson('/api/questions', [
            'content' => $content,
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'answers' => $answers,
        ]);
        $qBId = $resB->json('data.id');
        $this->actingAs($this->userB, 'api')->postJson("/api/user/my-questions/{$qBId}/submit-to-bank");

        $reqA = QuestionReviewRequest::where('question_id', $qAId)->where('status', 'pending')->first();
        $reqB = QuestionReviewRequest::where('question_id', $qBId)->where('status', 'pending')->first();

        $this->assertNotNull($reqA);
        $this->assertNotNull($reqB);

        // Gọi snapshot service trực tiếp mô phỏng 2 tiến trình xử lý
        $snap1 = $this->snapshotService->createSnapshotFromReviewRequest($reqA, $this->admin->id);
        $snap2 = $this->snapshotService->createSnapshotFromReviewRequest($reqB, $this->admin->id);

        // Cả 2 lần đều trả về cùng 1 Question Snapshot ID
        $this->assertEquals($snap1->id, $snap2->id);

        $fingerprint = $this->snapshotService->computeFingerprint(Question::find($qAId));
        $this->assertEquals(1, Question::where('fingerprint', $fingerprint)->where('is_public', true)->count());
    }

    /**
     * TEST G:
     * Question gốc có Answer A/B/C/D.
     * Snapshot có Answer riêng.
     * Duplicate detection không được làm Answer snapshot dùng chung Answer gốc.
     */
    public function test_g_snapshot_has_independent_answers_and_does_not_share_original_answers()
    {
        $res = $this->actingAs($this->userA, 'api')->postJson('/api/questions', [
            'content' => 'Công thức hóa học của nước là gì?',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'answers' => [
                ['content' => 'H2O', 'is_correct' => true, 'key' => 'A'],
                ['content' => 'CO2', 'is_correct' => false, 'key' => 'B'],
                ['content' => 'NaCl', 'is_correct' => false, 'key' => 'C'],
                ['content' => 'O2', 'is_correct' => false, 'key' => 'D'],
            ],
        ]);
        $qId = $res->json('data.id');
        $originalQuestion = Question::with('answers')->find($qId);
        $originalAnswerIds = $originalQuestion->answers->pluck('id')->toArray();

        $this->actingAs($this->userA, 'api')->postJson("/api/user/my-questions/{$qId}/submit-to-bank");
        $this->actingAs($this->admin, 'api')->postJson("/api/admin/question-bank-requests/{$qId}/approve");

        $bankSnapshot = Question::with('answers')
            ->where('origin_question_id', $qId)
            ->where('is_public', true)
            ->first();

        $this->assertNotNull($bankSnapshot);
        $this->assertNotEquals($originalQuestion->id, $bankSnapshot->id);

        $snapshotAnswerIds = $bankSnapshot->answers->pluck('id')->toArray();
        $this->assertCount(4, $snapshotAnswerIds);

        // Đảm bảo không có bất kỳ ID đáp án nào trùng nhau giữa câu hỏi gốc và Snapshot
        $intersection = array_intersect($originalAnswerIds, $snapshotAnswerIds);
        $this->assertEmpty($intersection, 'Snapshot answers must not share IDs with original question answers.');

        // Kiểm tra từng Answer thuộc đúng question_id
        foreach ($bankSnapshot->answers as $ans) {
            $this->assertEquals($bankSnapshot->id, $ans->question_id);
        }
        foreach ($originalQuestion->answers as $ans) {
            $this->assertEquals($originalQuestion->id, $ans->question_id);
        }
    }

    /**
     * TEST H:
     * Kiểm tra Normalization:
     * Khoảng trắng thừa, xuống dòng, tab, thẻ HTML, hoa/thường, thứ tự đáp án
     * đều cho ra cùng một Fingerprint.
     */
    public function test_h_normalization_rules_generate_consistent_fingerprint()
    {
        $fp1 = $this->snapshotService->computeFingerprintFromSnapshot(
            'Thủ đô của Việt Nam là gì?',
            'single_choice',
            [
                ['content' => 'Hà Nội', 'is_correct' => true],
                ['content' => 'TP Hồ Chí Minh', 'is_correct' => false],
            ]
        );

        $fp2 = $this->snapshotService->computeFingerprintFromSnapshot(
            "   <p>Thủ đô  của \n Việt Nam \t là gì? </p>  ",
            'SINGLE_CHOICE',
            [
                ['content' => ' TP Hồ Chí Minh ', 'is_correct' => false],
                ['content' => '<strong>Hà Nội</strong>', 'is_correct' => true],
            ]
        );

        $this->assertEquals($fp1, $fp2, 'Fingerprint must match across whitespace, HTML tags, casing, and answer order.');

        // Kiểm tra dấu tiếng Việt không bị mất: "Hà Nội" khác "Ha Noi"
        $fpDiff = $this->snapshotService->computeFingerprintFromSnapshot(
            'Thu do cua Viet Nam la gi?',
            'single_choice',
            [
                ['content' => 'Ha Noi', 'is_correct' => true],
                ['content' => 'TP Ho Chi Minh', 'is_correct' => false],
            ]
        );

        $this->assertNotEquals($fp1, $fpDiff, 'Vietnamese accents must be preserved and produce different fingerprints.');
    }

    /**
     * TEST I:
     * Kiểm tra Database-Level Protection cho Question Bank (Unique Constraint).
     * Ngay cả khi bỏ qua application layer và insert trực tiếp vào DB,
     * MySQL Unique Index `uq_questions_bank_fingerprint` vẫn chặn duplicate public questions.
     */
    public function test_i_database_level_protection_blocks_duplicate_bank_snapshot()
    {
        $fp = 'bank_db_protection_test_' . uniqid();

        // Bản ghi 1: Public Question trong Bank
        \Illuminate\Support\Facades\DB::table('questions')->insert([
            'user_id' => $this->admin->id,
            'content' => 'Câu hỏi Bank DB Level 1',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => 1,
            'fingerprint' => $fp,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Bản ghi 2: Cố tình insert trực tiếp cùng fingerprint vào Bank
        $this->expectException(\Illuminate\Database\QueryException::class);

        \Illuminate\Support\Facades\DB::table('questions')->insert([
            'user_id' => $this->userA->id,
            'content' => 'Câu hỏi Bank DB Level 2 (Duplicate)',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => 1,
            'fingerprint' => $fp,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * TEST J:
     * Kiểm tra Database-Level Protection cho Kho cá nhân User (Unique Constraint).
     * - Cùng User cố insert 2 câu hỏi cùng fingerprint -> MySQL chặn.
     * - Hai User khác nhau insert cùng fingerprint -> Thành công.
     */
    public function test_j_database_level_protection_blocks_duplicate_personal_questions_for_same_user_but_allows_different_users()
    {
        $fp = 'user_db_protection_test_' . uniqid();

        // User A tạo câu hỏi cá nhân 1
        $qA1 = \Illuminate\Support\Facades\DB::table('questions')->insertGetId([
            'user_id' => $this->userA->id,
            'content' => 'Câu hỏi User A cá nhân',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => 0,
            'origin_question_id' => null,
            'fingerprint' => $fp,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertGreaterThan(0, $qA1);

        // User B tạo cùng câu hỏi đó trong kho cá nhân của mình -> Thành công!
        $qB1 = \Illuminate\Support\Facades\DB::table('questions')->insertGetId([
            'user_id' => $this->userB->id,
            'content' => 'Câu hỏi User B cá nhân (cùng nội dung)',
            'type' => 'single_choice',
            'difficulty' => 'easy',
            'is_public' => 0,
            'origin_question_id' => null,
            'fingerprint' => $fp,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertGreaterThan(0, $qB1);

        // User A cố tình insert thêm 1 bản ghi nữa có cùng fingerprint vào kho cá nhân -> MySQL chặn!
        $blocked = false;
        try {
            \Illuminate\Support\Facades\DB::table('questions')->insert([
                'user_id' => $this->userA->id,
                'content' => 'Câu hỏi User A cá nhân lần 2 (Duplicate)',
                'type' => 'single_choice',
                'difficulty' => 'easy',
                'is_public' => 0,
                'origin_question_id' => null,
                'fingerprint' => $fp,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $blocked = true;
        }

        $this->assertTrue($blocked, 'Database unique constraint must block duplicate personal questions for the same user.');
    }
}
