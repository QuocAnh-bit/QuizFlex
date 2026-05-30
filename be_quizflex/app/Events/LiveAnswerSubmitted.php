<?php

namespace App\Events;

use App\Models\LiveRoomPlayer;
use App\Services\LiveRoomPayloadService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveAnswerSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(private readonly LiveRoomPlayer $player)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('live-room.'.$this->player->live_room_id);
    }

    public function broadcastAs(): string
    {
        return 'live.answer.submitted';
    }

    public function broadcastWith(): array
    {
        $player = $this->player->loadMissing(['liveRoom', 'user:id,name']);
        $payloadService = app(LiveRoomPayloadService::class);
        $summary = $payloadService->playerSummary($player, $player->liveRoom);

        return [
            'type' => 'answer_submitted',
            'live_room_id' => $this->player->live_room_id,
            'user_id' => $this->player->user_id,
            'score' => $summary['score'],
            'correct_count' => $summary['correct_count'],
            'current_question_index' => $summary['current_question_index'],
            'answered_count' => $summary['answered_count'],
            'player_finished' => $summary['player_finished'],
            'player' => $summary,
            'leaderboard' => $payloadService->leaderboard($player->liveRoom),
        ];
    }
}
