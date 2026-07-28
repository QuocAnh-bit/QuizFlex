<?php

namespace App\Policies;

use App\Models\LiveRoom;
use App\Models\User;
use App\Models\LiveRoomPlayer;

class LiveRoomPolicy
{
    /**
     * Determine whether the user can view the live room.
     */
    public function view(User $user, LiveRoom $liveRoom): bool
    {
        // Admin được view detail
        if (strtolower((string)($user->role ?? '')) === 'admin') {
            return true;
        }

        // Host được view
        if ((int)$liveRoom->host_id === (int)$user->id) {
            return true;
        }

        // Người chơi đã tham gia phòng (status = joined) được view
        return LiveRoomPlayer::where('live_room_id', $liveRoom->id)
            ->where('user_id', $user->id)
            ->where('status', 'joined')
            ->exists();
    }

    /**
     * Determine whether the user can start, finish, nextQuestion, lockJoin, or kickPlayer.
     * Chỉ Host mới có quyền điều khiển. Admin/Player không được phép.
     */
    public function start(User $user, LiveRoom $liveRoom): bool
    {
        return (int)$liveRoom->host_id === (int)$user->id;
    }

    public function finish(User $user, LiveRoom $liveRoom): bool
    {
        return (int)$liveRoom->host_id === (int)$user->id;
    }

    public function nextQuestion(User $user, LiveRoom $liveRoom): bool
    {
        return (int)$liveRoom->host_id === (int)$user->id;
    }

    public function lockJoin(User $user, LiveRoom $liveRoom): bool
    {
        return (int)$liveRoom->host_id === (int)$user->id;
    }

    public function kickPlayer(User $user, LiveRoom $liveRoom): bool
    {
        return (int)$liveRoom->host_id === (int)$user->id;
    }
}
