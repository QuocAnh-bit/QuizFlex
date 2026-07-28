<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;
use App\Models\RoomMember;

class RoomPolicy
{
   
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
