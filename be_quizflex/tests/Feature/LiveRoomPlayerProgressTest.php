<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\LiveRoom;
use App\Models\LiveRoomPlayer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveRoomPlayerProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required for the in-memory database feature test.');
        }

        parent::setUp();
    }

    public function test_live_room_uses_player_specific_progress(): void
    {
        $host = User::factory()->create(['role' => 'VIP']);
        $playerA = User::factory()->create(['role' => 'USER']);
        $playerB = User::factory()->create(['role' => 'USER']);
        $quiz = $this->createQuizWithQuestions($host);

        $createResponse = $this->actingAs($host, 'api')
            ->postJson('/api/live-rooms', ['quiz_id' => $quiz->id])
            ->assertCreated();

        $liveRoomId = $createResponse->json('data.id');
        $liveRoom = LiveRoom::findOrFail($liveRoomId);

        $this->assertDatabaseMissing('live_room_players', [
            'live_room_id' => $liveRoom->id,
            'user_id' => $host->id,
        ]);

        $this->actingAs($host, 'api')
            ->postJson('/api/live-rooms/join', ['code' => $liveRoom->code])
            ->assertForbidden();

        $this->actingAs($playerA, 'api')
            ->postJson('/api/live-rooms/join', ['code' => $liveRoom->code])
            ->assertOk();

        $this->actingAs($playerB, 'api')
            ->postJson('/api/live-rooms/join', ['code' => $liveRoom->code])
            ->assertOk();

        $this->actingAs($host, 'api')
            ->postJson("/api/live-rooms/{$liveRoom->id}/start")
            ->assertOk()
            ->assertJsonPath('data.monitor.total_players', 2);

        $liveRoom->refresh();
        $this->assertNotEmpty($liveRoom->question_order);

        $firstQuestionId = $liveRoom->question_order[0];
        $correctAnswer = Answer::where('question_id', $firstQuestionId)
            ->where('is_correct', true)
            ->firstOrFail();

        $this->actingAs($playerA, 'api')
            ->postJson("/api/live-rooms/{$liveRoom->id}/answer", ['answer_id' => $correctAnswer->id])
            ->assertOk()
            ->assertJsonPath('data.current_score', 100)
            ->assertJsonPath('data.next_question_index', 1);

        $this->assertSame(1, LiveRoomPlayer::where('live_room_id', $liveRoom->id)->where('user_id', $playerA->id)->value('current_question_index'));
        $this->assertSame(0, LiveRoomPlayer::where('live_room_id', $liveRoom->id)->where('user_id', $playerB->id)->value('current_question_index'));

        $this->actingAs($playerB, 'api')
            ->getJson("/api/live-rooms/{$liveRoom->id}/current-question")
            ->assertOk()
            ->assertJsonPath('data.question.id', $firstQuestionId)
            ->assertJsonPath('data.player_current_question_index', 0);

        $this->actingAs($host, 'api')
            ->postJson("/api/live-rooms/{$liveRoom->id}/next-question")
            ->assertOk()
            ->assertJsonPath('message', 'Live room hien tai dung tien do rieng tung player, host khong can chuyen cau chung.');

        $this->assertSame(0, LiveRoomPlayer::where('live_room_id', $liveRoom->id)->where('user_id', $playerB->id)->value('current_question_index'));

        $this->actingAs($host, 'api')
            ->postJson("/api/live-rooms/{$liveRoom->id}/finish")
            ->assertOk()
            ->assertJsonPath('data.live_room.status', 'finished');

        $this->actingAs($playerB, 'api')
            ->postJson("/api/live-rooms/{$liveRoom->id}/answer", ['answer_id' => $correctAnswer->id])
            ->assertUnprocessable();

        $leaderboard = $this->actingAs($host, 'api')
            ->getJson("/api/live-rooms/{$liveRoom->id}/leaderboard")
            ->assertOk()
            ->json('data');

        $this->assertCount(2, $leaderboard);
        $this->assertNotContains($host->id, collect($leaderboard)->pluck('user_id')->all());
        $this->assertSame($playerA->id, $leaderboard[0]['user_id']);
    }

    private function createQuizWithQuestions(User $host): Quiz
    {
        $quiz = Quiz::create([
            'user_id' => $host->id,
            'title' => 'Live quiz',
            'description' => 'Quiz for live room tests',
            'category' => 'General',
            'difficulty' => 'easy',
            'status' => 'published',
            'is_public' => true,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $question = Question::create([
                'quiz_id' => $quiz->id,
                'content' => 'Question ' . ($i + 1),
                'type' => 'single_choice',
                'order' => $i,
                'points' => 10,
            ]);

            Answer::create([
                'question_id' => $question->id,
                'content' => 'Correct',
                'is_correct' => true,
                'order' => 0,
            ]);

            Answer::create([
                'question_id' => $question->id,
                'content' => 'Wrong',
                'is_correct' => false,
                'order' => 1,
            ]);
        }

        return $quiz;
    }
}
