<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\LiveRoom;
use App\Models\LiveRoomPlayer;

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('test-user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('live-room.{liveRoomId}', function ($user, $liveRoomId) {
    $liveRoom = LiveRoom::find($liveRoomId);
    if (!$liveRoom) {
        return false;
    }

    $isHost = (int) $liveRoom->host_id === (int) $user->id;
    $isPlayer = false;

    if (!$isHost) {
        $isPlayer = LiveRoomPlayer::where('live_room_id', $liveRoom->id)
            ->where('user_id', $user->id)
            ->where('status', 'joined')
            ->exists();
    }

    if (!$isHost && !$isPlayer) {
        return false;
    }

    $tabId = request()->header('X-Tab-Id')
        ?: request()->input('tab_id')
        ?: request()->query('tab_id')
        ?: (string) \Illuminate\Support\Str::uuid();

    return [
        'id' => $tabId,
        'tab_id' => $tabId,
        'user_id' => (int) $user->id,
        'name' => $user->name,
        'joined_at' => (int) (microtime(true) * 1000),
    ];
});

