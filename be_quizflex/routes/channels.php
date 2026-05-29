<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\LiveRoom;
use App\Models\LiveRoomPlayer;

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

    if ((int) $liveRoom->host_id === (int) $user->id) {
        return true;
    }

    return LiveRoomPlayer::where('live_room_id', $liveRoom->id)
        ->where('user_id', $user->id)
        ->where('status', 'joined')
        ->exists();
});
