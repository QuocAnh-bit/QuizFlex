<?php

namespace App\Events;

use App\Models\LiveRoom;
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
        return [
            'type' => 'room_started',
            'live_room_id' => $this->liveRoom->id,
            'status' => $this->liveRoom->status,
            'started_at' => optional($this->liveRoom->started_at)->toIso8601String(),
            'total_questions' => $this->totalQuestions,
        ];
    }
}
