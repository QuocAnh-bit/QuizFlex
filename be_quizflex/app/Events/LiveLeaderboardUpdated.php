<?php

namespace App\Events;

use App\Models\LiveParticipant;
use App\Models\LiveSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveLeaderboardUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public LiveSession $session
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('live-session.' . $this->session->id);
    }

    public function broadcastAs(): string
    {
        return 'leaderboard.updated';
    }

    public function broadcastWith(): array
    {
        $leaderboard = LiveParticipant::where('session_id', $this->session->id)
            ->orderByDesc('score')
            ->orderByDesc('correct_count')
            ->orderByRaw('finished_at is null')
            ->orderBy('finished_at')
            ->orderBy('last_seen_at')
            ->get()
            ->values()
            ->map(fn ($participant, $index) => [
                'rank' => $index + 1,
                'participant_id' => $participant->id,
                'user_id' => $participant->user_id,
                'display_name' => $participant->display_name,
                'score' => $participant->score,
                'correct_count' => $participant->correct_count,
                'wrong_count' => $participant->wrong_count,
                'current_question_index' => $participant->current_question_index,
                'is_finished' => (bool) $participant->is_finished,
                'finished_at' => optional($participant->finished_at)->toISOString(),
                'status' => $participant->status,
            ]);

        return [
            'session_id' => $this->session->id,
            'leaderboard' => $leaderboard,
        ];
    }
}
