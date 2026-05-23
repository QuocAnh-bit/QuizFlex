<?php

namespace App\Events;

use App\Models\LiveSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveSessionCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public LiveSession $session
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('room.' . $this->session->room_id);
    }

    public function broadcastAs(): string
    {
        return 'live.created';
    }

    public function broadcastWith(): array
    {
        return [
            'session' => [
                'id' => $this->session->id,
                'room_id' => $this->session->room_id,
                'quiz_id' => $this->session->quiz_id,
                'host_id' => $this->session->host_id,
                'assignment_id' => $this->session->assignment_id,
                'code' => $this->session->code,
                'status' => $this->session->status,
                'current_question_id' => $this->session->current_question_id,
                'current_question_index' => $this->session->current_question_index,
                'question_duration_sec' => $this->session->question_duration_sec,
                'started_at' => optional($this->session->started_at)->toISOString(),
                'ended_at' => optional($this->session->ended_at)->toISOString(),
            ],
            'leaderboard' => [],
            'participants' => [],
        ];
    }
}
