<?php

namespace App\Events;

use App\Models\LiveRoom;
use App\Services\LiveRoomPayloadService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveRoomFinished implements ShouldBroadcastNow
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
        return 'live.room.finished';
    }

    public function broadcastWith(): array
    {
        $payloadService = app(LiveRoomPayloadService::class);

        return [
            'type' => 'room_finished',
            'live_room_id' => $this->liveRoom->id,
            'status' => $this->liveRoom->status,
            'ended_at' => optional($this->liveRoom->ended_at)->toIso8601String(),
            'leaderboard' => $payloadService->leaderboard($this->liveRoom),
        ];
    }
}
