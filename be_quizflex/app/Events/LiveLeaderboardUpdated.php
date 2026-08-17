<?php

namespace App\Events;

use App\Models\LiveRoom;
use App\Services\LiveRoomPayloadService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveLeaderboardUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(private readonly LiveRoom $liveRoom, private readonly ?array $leaderboard = null)
    {
    }

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('live-room.'.$this->liveRoom->id);
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
            'leaderboard' => $this->leaderboard ?? $payloadService->leaderboard($this->liveRoom),
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
