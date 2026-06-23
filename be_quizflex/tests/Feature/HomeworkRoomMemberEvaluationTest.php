<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\RoomAssignment;
use App\Models\QuizAttempt;
use App\Models\RoomMemberEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeworkRoomMemberEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required for the feature test.');
        }

        parent::setUp();
    }

    public function test_owner_can_create_and_update_evaluation(): void
    {
        $owner = User::factory()->create(['role' => 'VIP']);
        $student = User::factory()->create(['role' => 'USER']);
        $room = $this->createHomeworkRoom($owner);

        // Join student to room
        RoomMember::create([
            'room_id' => $room->id,
            'user_id' => $student->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        // Post evaluation by owner
        $this->actingAs($owner, 'api')
            ->postJson("/api/homework-rooms/{$room->id}/members/{$student->id}/evaluation", [
                'comment' => 'Làm bài đầy đủ và nghiêm túc',
            ])
            ->assertOk()
            ->assertJsonPath('data.comment', 'Làm bài đầy đủ và nghiêm túc');

        $this->assertDatabaseHas('room_member_evaluations', [
            'room_id' => $room->id,
            'user_id' => $student->id,
            'comment' => 'Làm bài đầy đủ và nghiêm túc',
        ]);

        // Update evaluation by owner
        $this->actingAs($owner, 'api')
            ->postJson("/api/homework-rooms/{$room->id}/members/{$student->id}/evaluation", [
                'comment' => 'Khá tốt, cần phát huy',
            ])
            ->assertOk()
            ->assertJsonPath('data.comment', 'Khá tốt, cần phát huy');

        $this->assertDatabaseHas('room_member_evaluations', [
            'room_id' => $room->id,
            'user_id' => $student->id,
            'comment' => 'Khá tốt, cần phát huy',
        ]);

        // Check if database contains only 1 evaluation row for this student-room pair
        $this->assertSame(1, RoomMemberEvaluation::where('room_id', $room->id)->where('user_id', $student->id)->count());
    }

    public function test_student_can_view_own_evaluation(): void
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
            'joined_at' => now(),
        ]);

        // Create evaluation directly
        RoomMemberEvaluation::create([
            'room_id' => $room->id,
            'user_id' => $student->id,
            'evaluator_id' => $owner->id,
            'comment' => 'Làm bài rất tốt',
        ]);

        // View by student themselves
        $this->actingAs($student, 'api')
            ->getJson("/api/homework-rooms/{$room->id}/members/{$student->id}/evaluation")
            ->assertOk()
            ->assertJsonPath('data.comment', 'Làm bài rất tốt');
    }

    public function test_other_student_cannot_view_or_write_evaluation(): void
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

        // Student2 attempts to post evaluation for Student1
        $this->actingAs($student2, 'api')
            ->postJson("/api/homework-rooms/{$room->id}/members/{$student1->id}/evaluation", [
                'comment' => 'Hacker comment',
            ])
            ->assertStatus(403);

        // Student2 attempts to view Student1's evaluation
        $this->actingAs($student2, 'api')
            ->getJson("/api/homework-rooms/{$room->id}/members/{$student1->id}/evaluation")
            ->assertStatus(403);
    }

    public function test_member_stats_calculations(): void
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
            'joined_at' => now(),
        ]);

        // Create two published assignments
        $quiz = Quiz::create([
            'user_id' => $owner->id,
            'title' => 'Quiz 1',
            'description' => 'Test',
            'category' => 'Test',
            'difficulty' => 'easy',
            'status' => 'published',
        ]);

        $assignment1 = RoomAssignment::create([
            'room_id' => $room->id,
            'quiz_id' => $quiz->id,
            'assigned_by' => $owner->id,
            'title' => 'Assignment 1',
            'status' => 'published',
        ]);

        $assignment2 = RoomAssignment::create([
            'room_id' => $room->id,
            'quiz_id' => $quiz->id,
            'assigned_by' => $owner->id,
            'title' => 'Assignment 2',
            'status' => 'published',
        ]);

        // Create a draft assignment (should not be counted in assigned count)
        RoomAssignment::create([
            'room_id' => $room->id,
            'quiz_id' => $quiz->id,
            'assigned_by' => $owner->id,
            'title' => 'Draft Assignment',
            'status' => 'draft',
        ]);

        // Submit one attempt for assignment 1: score = 8, total_points = 10 (score on 10-point scale: 8)
        QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'room_id' => $room->id,
            'assignment_id' => $assignment1->id,
            'mode' => 'homework',
            'attempt_number' => 1,
            'score' => 8,
            'total_points' => 10,
            'status' => 'completed',
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        // Submit two attempts for assignment 2:
        // Attempt 1: score = 5, total_points = 10
        // Attempt 2: score = 9, total_points = 10 (best score: 9)
        QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'room_id' => $room->id,
            'assignment_id' => $assignment2->id,
            'mode' => 'homework',
            'attempt_number' => 1,
            'score' => 5,
            'total_points' => 10,
            'status' => 'completed',
            'started_at' => now(),
            'finished_at' => now(),
        ]);
        QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'room_id' => $room->id,
            'assignment_id' => $assignment2->id,
            'mode' => 'homework',
            'attempt_number' => 2,
            'score' => 9,
            'total_points' => 10,
            'status' => 'completed',
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        // Call the members API
        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/rooms/{$room->id}/members")
            ->assertOk();

        // Check computed stats:
        // assigned = 2 (published/closed assignments)
        // completed = 2 (assignments with completed attempts)
        // completion_rate = 100%
        // average_score = (8 + 9) / 2 = 8.5
        $response->assertJsonPath('data.0.assigned', 2)
            ->assertJsonPath('data.0.completed', 2)
            ->assertJsonPath('data.0.completion_rate', 100)
            ->assertJsonPath('data.0.average_score', 8.5);

        // Call the room show API
        $responseShow = $this->actingAs($owner, 'api')
            ->getJson("/api/rooms/{$room->id}")
            ->assertOk();

        $responseShow->assertJsonPath('data.members.0.assigned', 2)
            ->assertJsonPath('data.members.0.completed', 2)
            ->assertJsonPath('data.members.0.completion_rate', 100)
            ->assertJsonPath('data.members.0.average_score', 8.5);
    }

    public function test_evaluation_response_contains_submission_evaluations(): void
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
            'title' => 'Homework quiz',
            'category' => 'General',
            'status' => 'published',
        ]);

        $assignment = RoomAssignment::create([
            'room_id' => $room->id,
            'quiz_id' => $quiz->id,
            'assigned_by' => $owner->id,
            'title' => 'Quiz Assignment',
            'status' => 'published',
        ]);

        $attempt = QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'room_id' => $room->id,
            'assignment_id' => $assignment->id,
            'score' => 7,
            'total_points' => 10,
            'status' => 'completed',
        ]);

        // Create individual submission comment
        \App\Models\RoomSubmissionEvaluation::create([
            'room_id' => $room->id,
            'submission_id' => $attempt->id,
            'user_id' => $student->id,
            'evaluator_id' => $owner->id,
            'comment' => 'Nhận xét bài nộp số 1',
        ]);

        // Fetch evaluation
        $response = $this->actingAs($owner, 'api')
            ->getJson("/api/homework-rooms/{$room->id}/members/{$student->id}/evaluation")
            ->assertOk()
            ->assertJsonCount(1, 'data.submission_evaluations')
            ->assertJsonPath('data.submission_evaluations.0.assignment_name', 'Quiz Assignment')
            ->assertJsonPath('data.submission_evaluations.0.comment', 'Nhận xét bài nộp số 1');
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
