<?php

namespace App\Events;

use App\Models\LiveRoom;
use App\Services\LiveRoomPayloadService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveLeaderboardUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(private readonly LiveRoom $liveRoom)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('live-room.'.$this->liveRoom->id);
    }

    public function broadcastAs(): string
    {
        return 'live.leaderboard.updated';
    }

    public function broadcastWith(): array
    {
        $payloadService = app(LiveRoomPayloadService::class);

        return [
            'type' => 'leaderboard_updated',
            'live_room_id' => $this->liveRoom->id,
            'leaderboard' => $payloadService->leaderboard($this->liveRoom),
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
