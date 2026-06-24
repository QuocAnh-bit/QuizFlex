<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\RoomAssignment;
use App\Models\QuizAttempt;
use App\Models\RoomSubmissionEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeworkSubmissionEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required for the feature test.');
        }

        parent::setUp();
    }

    public function test_owner_can_create_and_update_submission_evaluation(): void
    {
        $owner = User::factory()->create(['role' => 'VIP']);
        $student = User::factory()->create(['role' => 'USER']);
        $room = $this->createHomeworkRoom($owner);

        // Join student
        RoomMember::create([
            'room_id' => $room->id,
            'user_id' => $student->id,
            'role' => 'member',
            'status' => 'active',
        ]);

        $quiz = Quiz::create([
            'user_id' => $owner->id,
            'title' => 'Homework quiz',
            'category' => 'General',
            'difficulty' => 'easy',
            'status' => 'published',
        ]);

        $assignment = RoomAssignment::create([
            'room_id' => $room->id,
            'quiz_id' => $quiz->id,
            'assigned_by' => $owner->id,
            'title' => 'Homework Assignment',
            'status' => 'published',
        ]);

        $attempt = QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'room_id' => $room->id,
            'assignment_id' => $assignment->id,
            'mode' => 'homework',
            'attempt_number' => 1,
            'score' => 8,
            'total_points' => 10,
            'status' => 'completed',
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        // Post submission evaluation
        $this->actingAs($owner, 'api')
            ->postJson("/api/homework-rooms/{$room->id}/submissions/{$attempt->id}/evaluation", [
                'comment' => 'Bài làm rất tốt, phân tích xuất sắc!',
            ])
            ->assertOk()
            ->assertJsonPath('data.comment', 'Bài làm rất tốt, phân tích xuất sắc!');

        $this->assertDatabaseHas('room_submission_evaluations', [
            'room_id' => $room->id,
            'submission_id' => $attempt->id,
            'user_id' => $student->id,
            'comment' => 'Bài làm rất tốt, phân tích xuất sắc!',
        ]);

        // Update submission evaluation
        $this->actingAs($owner, 'api')
            ->postJson("/api/homework-rooms/{$room->id}/submissions/{$attempt->id}/evaluation", [
                'comment' => 'Bài làm xuất sắc!',
            ])
            ->assertOk()
            ->assertJsonPath('data.comment', 'Bài làm xuất sắc!');

        $this->assertDatabaseHas('room_submission_evaluations', [
            'room_id' => $room->id,
            'submission_id' => $attempt->id,
            'comment' => 'Bài làm xuất sắc!',
        ]);

        $this->assertSame(1, RoomSubmissionEvaluation::where('submission_id', $attempt->id)->count());
    }

    public function test_student_can_view_own_submission_evaluation(): void
    {
        $owner = User::factory()->create(['role' => 'VIP']);
        $student = User::factory()->create(['role' => 'USER']);
        $room = $this->createHomeworkRoom($owner);

        // Join student
        RoomMember::create([
            'room_id' => $room->id,
            'user_id' => $student->id,
            'status' => 'active',
        ]);

        $quiz = Quiz::create([
            'user_id' => $owner->id,
            'title' => 'Quiz',
            'status' => 'published',
        ]);

        $assignment = RoomAssignment::create([
            'room_id' => $room->id,
            'quiz_id' => $quiz->id,
            'assigned_by' => $owner->id,
            'status' => 'published',
        ]);

        $attempt = QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'room_id' => $room->id,
            'assignment_id' => $assignment->id,
            'status' => 'completed',
        ]);

        RoomSubmissionEvaluation::create([
            'room_id' => $room->id,
            'submission_id' => $attempt->id,
            'user_id' => $student->id,
            'evaluator_id' => $owner->id,
            'comment' => 'Nhận xét chi tiết bài nộp',
        ]);

        // View by student
        $this->actingAs($student, 'api')
            ->getJson("/api/homework-rooms/{$room->id}/submissions/{$attempt->id}/evaluation")
            ->assertOk()
            ->assertJsonPath('data.comment', 'Nhận xét chi tiết bài nộp');
    }

    public function test_other_student_cannot_view_or_write_submission_evaluation(): void
    {
        $owner = User::factory()->create(['role' => 'VIP']);
        $student1 = User::factory()->create(['role' => 'USER']);
        $student2 = User::factory()->create(['role' => 'USER']);
        $room = $this->createHomeworkRoom($owner);

        RoomMember::create([
            'room_id' => $room->id,
            'user_id' => $student1->id,
            'status' => 'active',
        ]);
        RoomMember::create([
            'room_id' => $room->id,
            'user_id' => $student2->id,
            'status' => 'active',
        ]);

        $quiz = Quiz::create([
            'user_id' => $owner->id,
            'title' => 'Quiz',
            'status' => 'published',
        ]);

        $assignment = RoomAssignment::create([
            'room_id' => $room->id,
            'quiz_id' => $quiz->id,
            'assigned_by' => $owner->id,
            'status' => 'published',
        ]);

        $attempt = QuizAttempt::create([
            'user_id' => $student1->id,
            'quiz_id' => $quiz->id,
            'room_id' => $room->id,
            'assignment_id' => $assignment->id,
            'status' => 'completed',
        ]);

        // Student2 attempts to post evaluation for Student1's attempt
        $this->actingAs($student2, 'api')
            ->postJson("/api/homework-rooms/{$room->id}/submissions/{$attempt->id}/evaluation", [
                'comment' => 'Hacker comment',
            ])
            ->assertStatus(403);

        // Student2 attempts to view Student1's attempt evaluation
        $this->actingAs($student2, 'api')
            ->getJson("/api/homework-rooms/{$room->id}/submissions/{$attempt->id}/evaluation")
            ->assertStatus(403);
    }

    public function test_attempts_response_includes_evaluation(): void
    {
        $owner = User::factory()->create(['role' => 'VIP']);
        $student = User::factory()->create(['role' => 'USER']);
        $room = $this->createHomeworkRoom($owner);

        RoomMember::create([
            'room_id' => $room->id,
            'user_id' => $student->id,
            'status' => 'active',
        ]);

        $quiz = Quiz::create([
            'user_id' => $owner->id,
            'title' => 'Quiz',
            'status' => 'published',
        ]);

        $assignment = RoomAssignment::create([
            'room_id' => $room->id,
            'quiz_id' => $quiz->id,
            'assigned_by' => $owner->id,
            'status' => 'published',
        ]);

        $attempt = QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'room_id' => $room->id,
            'assignment_id' => $assignment->id,
            'status' => 'completed',
        ]);

        // Create evaluation
        RoomSubmissionEvaluation::create([
            'room_id' => $room->id,
            'submission_id' => $attempt->id,
            'user_id' => $student->id,
            'evaluator_id' => $owner->id,
            'comment' => 'Tuyệt vời',
        ]);

        // Fetch attempts
        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/room-assignments/{$assignment->id}/attempts")
            ->assertOk()
            ->assertJsonPath('data.0.evaluation.comment', 'Tuyệt vời');
    }

    public function test_attempt_detail_includes_evaluation_comment_for_authorized_users(): void
    {
        $owner = User::factory()->create(['role' => 'VIP']);
        $studentA = User::factory()->create(['role' => 'USER']);
        $studentB = User::factory()->create(['role' => 'USER']);
        $room = $this->createHomeworkRoom($owner);

        // Join students
        RoomMember::create(['room_id' => $room->id, 'user_id' => $studentA->id, 'status' => 'active']);
        RoomMember::create(['room_id' => $room->id, 'user_id' => $studentB->id, 'status' => 'active']);

        $quiz = Quiz::create(['user_id' => $owner->id, 'title' => 'Test Quiz', 'status' => 'published']);
        $assignment = RoomAssignment::create(['room_id' => $room->id, 'quiz_id' => $quiz->id, 'assigned_by' => $owner->id, 'status' => 'published']);

        // Student A attempt
        $attemptA = QuizAttempt::create([
            'user_id' => $studentA->id,
            'quiz_id' => $quiz->id,
            'room_id' => $room->id,
            'assignment_id' => $assignment->id,
            'status' => 'completed',
        ]);

        // Student B attempt
        $attemptB = QuizAttempt::create([
            'user_id' => $studentB->id,
            'quiz_id' => $quiz->id,
            'room_id' => $room->id,
            'assignment_id' => $assignment->id,
            'status' => 'completed',
        ]);

        // Teacher reviews A's attempt
        RoomSubmissionEvaluation::create([
            'room_id' => $room->id,
            'submission_id' => $attemptA->id,
            'user_id' => $studentA->id,
            'evaluator_id' => $owner->id,
            'comment' => 'Nhận xét bài A',
        ]);

        // 1. Student A fetches A's attempt -> receives evaluation comment
        $this->actingAs($studentA, 'api')
            ->getJson("/api/quiz-attempts/{$attemptA->id}")
            ->assertOk()
            ->assertJsonPath('data.evaluation_comment', 'Nhận xét bài A')
            ->assertJsonNotNull('data.evaluation_comment_updated_at');

        // 2. Student B fetches B's attempt -> comment is null
        $this->actingAs($studentB, 'api')
            ->getJson("/api/quiz-attempts/{$attemptB->id}")
            ->assertOk()
            ->assertJsonPath('data.evaluation_comment', null)
            ->assertJsonPath('data.evaluation_comment_updated_at', null);

        // 3. Student A tries to fetch B's attempt -> 403 Forbidden
        $this->actingAs($studentA, 'api')
            ->getJson("/api/quiz-attempts/{$attemptB->id}")
            ->assertStatus(403);
    }

    private function createHomeworkRoom(User $owner, array $attributes = []): Room
    {
        $quiz = Quiz::create([
            'user_id' => $owner->id,
            'title' => 'Homework quiz',
            'description' => 'Quiz for homework room tests',
            'category' => 'General',
            'difficulty' => 'easy',
            'status' => 'published',
            'is_public' => true,
        ]);

        return Room::create(array_merge([
            'owner_id' => $owner->id,
            'host_id' => $owner->id,
            'quiz_id' => $quiz->id,
            'name' => 'Homework room',
            'description' => null,
            'type' => 'homework',
            'code' => strtoupper(fake()->bothify('??????')),
            'status' => 'active',
            'max_players' => 50,
            'join_policy' => 'open',
        ], $attributes));
    }
}
