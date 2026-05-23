<?php

namespace App\Events;

use App\Models\LiveSession;
use App\Models\Question;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveSessionStarted implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public LiveSession $session,
        public Question $question
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('live-session.' . $this->session->id);
    }

    public function broadcastAs(): string
    {
        return 'session.started';
    }

    public function broadcastWith(): array
    {
        $this->question->loadMissing('answers');

        return [
            'session' => [
                'id' => $this->session->id,
                'room_id' => $this->session->room_id,
                'quiz_id' => $this->session->quiz_id,
                'status' => $this->session->status,
                'current_question_id' => $this->session->current_question_id,
                'current_question_index' => $this->session->current_question_index,
                'question_duration_sec' => $this->session->question_duration_sec,
            ],
            'question' => [
                'id' => $this->question->id,
                'quiz_id' => $this->question->quiz_id,
                'content' => $this->question->content,
                'image_url' => $this->question->image_url,
                'type' => $this->question->type,
                'order' => $this->question->order,
                'points' => $this->question->points,
                'answers' => $this->question->answers->map(fn ($answer) => [
                    'id' => $answer->id,
                    'content' => $answer->content,
                    'order' => $answer->order,
                ])->values(),
            ],
        ];
    }
}