<?php

namespace App\Events;

use App\Models\LiveRoom;
use App\Services\LiveRoomPayloadService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveRoomStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly LiveRoom $liveRoom,
        private readonly int $totalQuestions,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('live-room.'.$this->liveRoom->id);
    }

    public function broadcastAs(): string
    {
        return 'live.room.started';
    }

    public function broadcastWith(): array
    {
        $payloadService = app(LiveRoomPayloadService::class);
        $playersProgress = $payloadService->playersProgress($this->liveRoom);

        return [
            'type' => 'room_started',
            'live_room_id' => $this->liveRoom->id,
            'status' => $this->liveRoom->status,
            'started_at' => optional($this->liveRoom->started_at)->toIso8601String(),
            'current_question_index' => 0,
            'total_questions' => $this->totalQuestions,
            'current_question' => $payloadService->currentQuestionForIndex($this->liveRoom, 0),
            'players' => $playersProgress,
            'players_progress' => $playersProgress,
            'leaderboard' => $payloadService->leaderboard($this->liveRoom),
        ];
    }
}
