<?php

namespace App\Events;

use App\Models\LiveParticipant;
use App\Models\LiveSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveParticipantJoined implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public LiveSession $session,
        public LiveParticipant $participant
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('live-session.' . $this->session->id);
    }

    public function broadcastAs(): string
    {
        return 'participant.joined';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
            'participant' => [
                'id' => $this->participant->id,
                'user_id' => $this->participant->user_id,
                'display_name' => $this->participant->display_name,
                'score' => $this->participant->score,
                'correct_count' => $this->participant->correct_count,
                'wrong_count' => $this->participant->wrong_count,
                'status' => $this->participant->status,
            ],
        ];
    }
}