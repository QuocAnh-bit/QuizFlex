<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;
use App\Models\RoomMember;
use App\Models\RoomAssignment;
use App\Models\QuizAttempt;
use Illuminate\Auth\Access\Response;

class RoomPolicy
{
    public function viewAnyAdminScope(User $user): bool
    {
        return strtolower((string) ($user->role ?? 'user')) === 'admin';
    }

    public function create(User $user)
    {
        if (strtolower((string) ($user->role ?? 'user')) === 'admin') {
            return Response::deny('Tính năng tạo phòng yêu cầu tài khoản VIP.');
        }

        if (!method_exists($user, 'getSubscriptionTier')) {
            return Response::deny('Tính năng tạo phòng yêu cầu tài khoản VIP.');
        }

        $tier = strtolower((string) $user->getSubscriptionTier());
        if (!in_array($tier, ['plus', 'pro', 'ultra'], true)) {
            return Response::deny('Tính năng tạo phòng yêu cầu tài khoản VIP.');
        }

        return Response::allow();
    }

    public function join(User $user, Room $room)
    {
        if (strtolower((string) ($user->role ?? 'user')) === 'admin') {
            return Response::deny('Tài khoản quản trị viên không thể tham gia phòng học với tư cách học sinh.');
        }

        if ((int) $room->host_id === (int) $user->id) {
            return Response::deny('Chủ room không cần tham gia room của chính mình.');
        }

        return Response::allow();
    }

    public function leave(User $user, Room $room)
    {
        if ((int) $room->host_id === (int) $user->id) {
            return Response::deny('Host không thể rời phòng của chính mình.');
        }

        return Response::allow();
    }

    public function takeAssignment(User $user, Room $room)
    {
        if ((int) $room->host_id === (int) $user->id) {
            return Response::deny('Host không thể làm bài trong phòng của mình.');
        }

        $isActive = RoomMember::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->where('role', 'member')
            ->where('status', 'active')
            ->exists();

        if (!$isActive) {
            return Response::deny('Bạn chưa là thành viên của phòng này.');
        }

        return Response::allow();
    }

    public function viewAssignmentAttempts(User $user, Room $room): bool
    {
        return (int) $room->host_id === (int) $user->id
            || strtolower((string) ($user->role ?? 'user')) === 'admin';
    }

    public function useHomeworkAttempt(User $user, Room $room, QuizAttempt $attempt)
    {
        if ((int) $room->host_id === (int) $user->id) {
            return Response::deny('Host room không thể làm bài trong room của mình.');
        }

        if ((int) $attempt->user_id !== (int) $user->id) {
            return Response::deny('Ban khong co quyen thao tac luot lam bai nay.');
        }

        $isActive = RoomMember::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->where('role', 'member')
            ->where('status', 'active')
            ->exists();

        if (!$isActive) {
            return Response::deny('Ban chua la thanh vien cua phong nay.');
        }

        return Response::allow();
    }

    public function viewAssignmentAttemptResult(User $user, Room $room, RoomAssignment $assignment, QuizAttempt $attempt): bool
    {
        if ((int) $room->host_id === (int) $user->id || strtolower((string) ($user->role ?? 'user')) === 'admin') {
            return true;
        }

        if ((int) $attempt->user_id !== (int) $user->id) {
            return false;
        }

        if (!in_array($attempt->status, ['completed', 'expired'], true)) {
            return false;
        }

        return match ($assignment->show_result_mode) {
            'immediately' => true,
            'after_deadline' => $assignment->deadline_at && now()->gte($assignment->deadline_at),
            default => false,
        };
    }

    public function viewGradebook(User $user, Room $room): bool
    {
        return (int) $room->host_id === (int) $user->id
            || strtolower((string) ($user->role ?? 'user')) === 'admin';
    }

    public function viewMemberEvaluation(User $user, Room $room, User $targetUser)
    {
        if (strtolower((string) ($user->role ?? 'user')) === 'admin'
            || (int) $room->host_id === (int) $user->id
            || (int) $targetUser->id === (int) $user->id) {
            return Response::allow();
        }

        return Response::deny('Bạn không có quyền xem đánh giá này.');
    }

    public function viewSubmissionEvaluation(User $user, Room $room, QuizAttempt $submission)
    {
        if (strtolower((string) ($user->role ?? 'user')) === 'admin'
            || (int) $room->host_id === (int) $user->id
            || (int) $submission->user_id === (int) $user->id) {
            return Response::allow();
        }

        return Response::deny('Bạn không có quyền xem nhận xét này.');
    }


    public function view(User $user, Room $room): bool
    {
        // Admin chỉ được xem thông tin room (không được làm bài tập, v.v., chỉ view detail)
        if (strtolower((string)($user->role ?? '')) === 'admin') {
            return true;
        }

        // Host được view
        if ((int)$room->host_id === (int)$user->id) {
            return true;
        }

        // Thành viên phòng có status là active được view
        return RoomMember::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

   
    public function update(User $user, Room $room): bool
    {
     
        return (int)$room->host_id === (int)$user->id;
    }

    
    public function delete(User $user, Room $room): bool
    {
     
        return (int)$room->host_id === (int)$user->id;
    }

    
    public function manageMembers(User $user, Room $room): bool
    {
    
        return (int)$room->host_id === (int)$user->id;
    }

    
    public function manageWhitelist(User $user, Room $room): bool
    {
        
        return (int)$room->host_id === (int)$user->id;
    }

    
    public function assignHomework(User $user, Room $room): bool
    {
        
        return (int)$room->host_id === (int)$user->id;
    }

    
    public function resetAttempt(User $user, Room $room): bool
    {
        
        return (int)$room->host_id === (int)$user->id;
    }

    public function dissolve(User $user, Room $room): bool
    {
        // Chỉ Host mới được giải tán phòng của mình.
        // Admin không dùng API này – Admin có API riêng trong AdminRoomController.
        return (int)$room->host_id === (int)$user->id;
    }
}
