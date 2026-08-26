<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private readonly User $user,
        private readonly string $status, // 'locked' | 'unlocked'
        private readonly ?string $reason = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.' . $this->user->id);
    }

    public function broadcastAs(): string
    {
        return 'account.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'status'    => $this->status,
            'reason'    => $this->reason,
            'is_locked' => (bool) $this->user->is_locked,
        ];
    }
}
