<?php

namespace App\Services;

use App\Models\LiveRoom;
use App\Models\LiveRoomAnswer;
use App\Models\LiveRoomPlayer;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class LiveRoomPayloadService
{
    public function playerSummary(LiveRoomPlayer $player, ?LiveRoom $liveRoom = null, ?int $answeredCountOverride = null): array
    {
        $liveRoom ??= $player->liveRoom;
        $player->loadMissing('user:id,name');

        $totalQuestions = $liveRoom ? count($this->questionOrder($liveRoom)) : 0;
        $answeredCount = $answeredCountOverride ?? ($liveRoom ? $this->answeredCount($liveRoom, $player->user_id) : 0);
        $isFinished = (bool) $player->finished_at || ($totalQuestions > 0 && (int) $player->current_question_index >= $totalQuestions);

        return [
            'id' => $player->id,
            'player_id' => $player->id,
            'live_room_id' => $player->live_room_id,
            'user_id' => $player->user_id,
            'user' => $player->user ? [
                'id' => $player->user->id,
                'name' => $player->user->name,
            ] : null,
            'score' => (int) $player->score,
            'correct_count' => (int) $player->correct_count,
            'current_question_index' => (int) $player->current_question_index,
            'answered_count' => $answeredCount,
            'total_questions' => $totalQuestions,
            'status' => $player->status,
            'is_finished' => $isFinished,
            'player_finished' => $isFinished,
            'finished_at' => optional($player->finished_at)->toIso8601String(),
            'last_answered_at' => optional($player->last_answered_at)->toIso8601String(),
            'joined_at' => optional($player->joined_at)->toIso8601String(),
        ];
    }

    public function leaderboard(LiveRoom $liveRoom): array
    {
        $answeredCounts = $this->answerCounts($liveRoom);

        return $this->playerQuery($liveRoom)
            ->with('user:id,name')
            ->orderByDesc('score')
            ->orderByDesc('correct_count')
            ->orderByRaw('finished_at IS NULL asc')
            ->orderBy('finished_at')
            ->orderBy('joined_at')
            ->get()
            ->values()
            ->map(function (LiveRoomPlayer $player, int $index) use ($liveRoom, $answeredCounts) {
                return array_merge(['rank' => $index + 1], $this->playerSummary($player, $liveRoom, $answeredCounts[$player->user_id] ?? 0));
            })
            ->all();
    }

    public function playersProgress(LiveRoom $liveRoom): array
    {
        $answeredCounts = $this->answerCounts($liveRoom);

        return $this->playerQuery($liveRoom)
            ->with('user:id,name')
            ->orderBy('joined_at')
            ->get()
            ->map(fn (LiveRoomPlayer $player) => $this->playerSummary($player, $liveRoom, $answeredCounts[$player->user_id] ?? 0))
            ->values()
            ->all();
    }

    public function monitorSummary(LiveRoom $liveRoom): array
    {
        $totalQuestions = count($this->questionOrder($liveRoom));
        $players = $this->playerQuery($liveRoom);

        return [
            'live_room_id' => $liveRoom->id,
            'room_status' => $liveRoom->status,
            'total_players' => (clone $players)->count(),
            'total_finished_players' => (clone $players)->whereNotNull('finished_at')->count(),
            'total_questions' => $totalQuestions,
            'players_progress' => $this->playersProgress($liveRoom),
            'leaderboard' => $this->leaderboard($liveRoom),
        ];
    }

    public function currentQuestionForIndex(LiveRoom $liveRoom, int $index = 0): ?array
    {
        $questionOrder = $this->questionOrder($liveRoom);
        $questionId = $questionOrder[$index] ?? null;
        if (!$questionId) {
            return null;
        }

        $question = Question::with('answers')->whereKey($questionId)->first();
        if (!$question) {
            return null;
        }

        return [
            'id' => $question->id,
            'content' => $question->content,
            'text' => $question->content,
            'type' => $question->type,
            'points' => $question->points,
            'answers' => $question->answers->map(fn (Answer $answer, int $answerIndex) => [
                'id' => $answer->id,
                'content' => $answer->content,
                'text' => $answer->content,
                'answer_key' => chr(65 + $answerIndex),
                'key' => chr(65 + $answerIndex),
                'order' => $answer->order,
            ])->values()->all(),
        ];
    }

    private function playerQuery(LiveRoom $liveRoom)
    {
        return LiveRoomPlayer::query()
            ->where('live_room_id', $liveRoom->id)
            ->where('user_id', '!=', $liveRoom->host_id)
            ->where('status', 'joined');
    }

    private function questionOrder(LiveRoom $liveRoom): array
    {
        return collect($liveRoom->question_order ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    private function answeredCount(LiveRoom $liveRoom, int $userId): int
    {
        return LiveRoomAnswer::where('live_room_id', $liveRoom->id)
            ->where('user_id', $userId)
            ->count();
    }

    private function answerCounts(LiveRoom $liveRoom): array
    {
        return LiveRoomAnswer::query()
            ->select('user_id', DB::raw('count(*) as total'))
            ->where('live_room_id', $liveRoom->id)
            ->groupBy('user_id')
            ->pluck('total', 'user_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
