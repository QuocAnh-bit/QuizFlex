<?php

namespace App\Policies;

use App\Models\LiveRoom;
use App\Models\User;
use App\Models\LiveRoomPlayer;
use App\Models\Quiz;
use Illuminate\Auth\Access\Response;

class LiveRoomPolicy
{
    /**
     * Determine whether the user can create a live room.
     */
    public function create(User $user)
    {
        if (strtolower((string)($user->role ?? '')) === 'admin') {
            return Response::deny('Quản trị viên không được phép tạo phòng trực tuyến.');
        }

        if (!method_exists($user, 'getSubscriptionTier')) {
            return Response::deny('Tính năng tạo phòng trực tuyến yêu cầu nâng cấp gói Plus trở lên.');
        }

        $tier = strtolower((string)$user->getSubscriptionTier());
        if (!in_array($tier, ['plus', 'pro', 'ultra'], true)) {
            return Response::deny('Tính năng tạo phòng trực tuyến yêu cầu nâng cấp gói Plus trở lên.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can create a live room from a specific quiz.
     */
    public function createFromQuiz(User $user, Quiz $quiz)
    {
        $canUse = (int) $quiz->user_id === (int) $user->id
            || ((bool) $quiz->is_public && $quiz->status === 'published');
        
        if (!$canUse) {
            return Response::deny('Ban khong co quyen tao live room tu quiz nay.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can join the live room as a player.
     */
    public function join(User $user, LiveRoom $liveRoom)
    {
        if (strtolower((string)($user->role ?? '')) === 'admin') {
            return Response::deny('Tài khoản quản trị viên không thể tham gia phòng trực tuyến với tư cách người chơi.');
        }

        if ((int)$liveRoom->host_id === (int)$user->id) {
            return Response::deny('Host khong the join live room cua chinh minh voi tu cach player.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view monitor/realtime data of the live room.
     */
    public function viewMonitor(User $user, LiveRoom $liveRoom): bool
    {
        return (int)$liveRoom->host_id === (int)$user->id || strtolower((string)($user->role ?? '')) === 'admin';
    }

    /**
     * Determine whether the user can answer questions in the live room.
     */
    public function answer(User $user, LiveRoom $liveRoom)
    {
        if ((int)$liveRoom->host_id === (int)$user->id) {
            return Response::deny('Host khong duoc tra loi cau hoi live room.');
        }

        return Response::allow();
    }

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

