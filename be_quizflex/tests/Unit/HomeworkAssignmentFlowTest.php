<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use App\Models\RoomAssignment;
use App\Models\QuizAttempt;
use App\Http\Controllers\RoomAssignmentController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;

class HomeworkAssignmentFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $host;
    protected User $member;
    protected Room $room;
    protected Quiz $quiz;
    protected RoomAssignment $assignment;
    protected RoomAssignmentController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = User::firstOrCreate(
            ['email' => 'host_homework_test@quizflex.com'],
            ['name' => 'Host Homework', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->member = User::firstOrCreate(
            ['email' => 'member_homework_test@quizflex.com'],
            ['name' => 'Member Homework', 'password' => bcrypt('password'), 'role' => 'free']
        );

        $this->room = Room::create([
            'host_id' => $this->host->id,
            'name' => 'Phòng Test Homework Assignment',
            'code' => strtoupper(substr(uniqid(), -6)),
            'join_policy' => 'open',
            'status' => 'active',
        ]);

        RoomMember::firstOrCreate([
            'room_id' => $this->room->id,
            'user_id' => $this->member->id,
        ], [
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->quiz = Quiz::create([
            'user_id' => $this->host->id,
            'title' => 'Quiz Homework Test',
            'status' => 'published',
            'is_public' => true,
        ]);

        $q1 = Question::create([
            'user_id' => $this->host->id,
            'content' => 'Câu hỏi 1 test homework',
            'type' => 'single_choice',
            'points' => 10,
        ]);
        $a1_1 = Answer::create(['question_id' => $q1->id, 'content' => 'Đáp án A đúng', 'is_correct' => true, 'order' => 0]);
        $a1_2 = Answer::create(['question_id' => $q1->id, 'content' => 'Đáp án B sai', 'is_correct' => false, 'order' => 1]);

        $q2 = Question::create([
            'user_id' => $this->host->id,
            'content' => 'Câu hỏi 2 test homework',
            'type' => 'single_choice',
            'points' => 10,
        ]);
        $a2_1 = Answer::create(['question_id' => $q2->id, 'content' => 'Đáp án C sai', 'is_correct' => false, 'order' => 0]);
        $a2_2 = Answer::create(['question_id' => $q2->id, 'content' => 'Đáp án D đúng', 'is_correct' => true, 'order' => 1]);

        $this->quiz->questions()->attach([
            $q1->id => ['order' => 1, 'points' => 10],
            $q2->id => ['order' => 2, 'points' => 10],
        ]);

        $this->assignment = RoomAssignment::create([
            'room_id' => $this->room->id,
            'quiz_id' => $this->quiz->id,
            'assigned_by' => $this->host->id,
            'title' => 'Bài tập số 1',
            'duration_minutes' => 30,
            'max_attempts' => 2,
            'show_result_mode' => 'immediately',
            'status' => 'published',
        ]);

        $this->controller = app(RoomAssignmentController::class);
    }

    public function test_01_member_starts_homework_assignment_attempt()
    {
        $request = new Request();
        $request->setUserResolver(fn() => $this->member);

        $response = $this->controller->startAttempt($request, $this->assignment);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertArrayHasKey('attempt', $data['data']);
        $this->assertArrayHasKey('assignment', $data['data']);
        $this->assertArrayHasKey('quiz', $data['data']);

        $quizData = $data['data']['quiz'];
        $this->assertNotEmpty($quizData['questions']);
        $this->assertCount(2, $quizData['questions']);

        $firstQ = $quizData['questions'][0];
        $this->assertArrayHasKey('id', $firstQ);
        $this->assertArrayHasKey('content', $firstQ);
        $this->assertArrayHasKey('answers', $firstQ);
        $this->assertCount(2, $firstQ['answers']);
    }

    public function test_02_member_submits_homework_assignment_and_receives_grade()
    {
        $startRequest = new Request();
        $startRequest->setUserResolver(fn() => $this->member);
        $startRes = $this->controller->startAttempt($startRequest, $this->assignment);
        $startData = $startRes->getData(true);
        $attemptId = $startData['data']['attempt']['id'];

        $attempt = QuizAttempt::findOrFail($attemptId);

        $qList = $this->quiz->questions()->with('answers')->get();
        $q1 = $qList[0];
        $correctAnswer1 = $q1->answers->where('is_correct', true)->first();

        $q2 = $qList[1];
        $correctAnswer2 = $q2->answers->where('is_correct', true)->first();

        $submitRequest = new Request([
            'answers' => [
                $q1->id => $correctAnswer1->id,
                $q2->id => $correctAnswer2->id,
            ],
        ]);
        $submitRequest->setUserResolver(fn() => $this->member);

        $submitRes = $this->controller->submitAttempt($submitRequest, $this->assignment, $attempt);
        $submitData = $submitRes->getData(true);

        $this->assertTrue($submitData['success']);
        $this->assertEquals(20, $submitData['data']['score']);
        $this->assertEquals(20, $submitData['data']['total_points']);
        $this->assertEquals(100, $submitData['data']['score_percent']);
        $this->assertEquals(2, $submitData['data']['correct_count']);
        $this->assertEquals(2, $submitData['data']['total_questions']);

        $attempt->refresh();
        $this->assertEquals('completed', $attempt->status);
        $this->assertNotNull($attempt->submitted_at);
    }

    public function test_03_member_answers_intermediately()
    {
        $startRequest = new Request();
        $startRequest->setUserResolver(fn() => $this->member);
        $startRes = $this->controller->startAttempt($startRequest, $this->assignment);
        $startData = $startRes->getData(true);
        $attempt = QuizAttempt::findOrFail($startData['data']['attempt']['id']);

        $q = $this->quiz->questions()->first();
        $answerReq = new Request([
            'question_id' => $q->id,
            'answer' => $q->answers()->first()->id,
        ]);
        $answerReq->setUserResolver(fn() => $this->member);

        $answerRes = $this->controller->answer($answerReq, $this->assignment, $attempt);
        $this->assertTrue($answerRes->getData(true)['success']);
    }

    public function test_04_host_can_view_attempts_and_gradebook()
    {
        $startRequest = new Request();
        $startRequest->setUserResolver(fn() => $this->member);
        $this->controller->startAttempt($startRequest, $this->assignment);

        $attemptsReq = new Request();
        $attemptsReq->setUserResolver(fn() => $this->host);

        $attemptsRes = $this->controller->attempts($attemptsReq, $this->assignment);
        $attemptsData = $attemptsRes->getData(true);

        $this->assertTrue($attemptsData['success']);
        $this->assertNotEmpty($attemptsData['data']);

        $gradebookRes = $this->controller->gradebook($attemptsReq, $this->room);
        $gradebookData = $gradebookRes->getData(true);
        $this->assertTrue($gradebookData['success']);
    }
}
