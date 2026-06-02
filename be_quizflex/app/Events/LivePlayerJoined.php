<?php

namespace App\Events;

use App\Models\LiveRoomPlayer;
use App\Services\LiveRoomPayloadService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LivePlayerJoined implements ShouldBroadcastNow
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
        return 'live.player.joined';
    }

    public function broadcastWith(): array
    {
        $player = $this->player->loadMissing(['liveRoom', 'user:id,name']);
        $payloadService = app(LiveRoomPayloadService::class);
        $summary = $payloadService->playerSummary($player, $player->liveRoom);
        $playersProgress = $payloadService->playersProgress($player->liveRoom);

        return [
            'type' => 'player_joined',
            'live_room_id' => $player->live_room_id,
            'player_id' => $player->id,
            'user_id' => $player->user_id,
            'player_name' => $player->user?->name,
            'joined_at' => $summary['joined_at'],
            'player' => $summary,
            'player_count' => count($playersProgress),
            'players' => $playersProgress,
            'players_progress' => $playersProgress,
            'leaderboard' => $payloadService->leaderboard($player->liveRoom),
        ];
    }
}
