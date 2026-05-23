<?php

namespace App\Http\Controllers;

use App\Events\LiveLeaderboardUpdated;
use App\Events\LiveParticipantJoined;
use App\Events\LiveQuestionChanged;
use App\Events\LiveSessionCreated;
use App\Events\LiveSessionEnded;
use App\Events\LiveSessionStarted;
use App\Models\Answer;
use App\Models\LiveAnswer;
use App\Models\LiveParticipant;
use App\Models\LiveSession;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Room;
use App\Models\RoomMember;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class LiveSessionController extends Controller
{
    public function currentForRoom(Request $request, Room $room)
    {
        $user = $request->user();

        if (!$this->canAccessRoom($room, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban khong co quyen xem live session cua room nay.',
            ], 403);
        }

        $session = LiveSession::query()
            ->where('room_id', $room->id)
            ->whereIn('status', ['waiting', 'playing'])
            ->latest()
            ->first();

        if (!$session) {
            return response()->json([
                'success' => true,
                'message' => 'Room chua co live session dang mo.',
                'data' => null,
            ]);
        }

        $payload = [
            'session' => $this->formatSession($session),
            'leaderboard' => $this->getLeaderboardData($session),
            'participants' => LiveParticipant::where('session_id', $session->id)
                ->orderBy('joined_at')
                ->get()
                ->values(),
        ];

        if ($session->current_question_id) {
            $question = Question::with('answers')->find($session->current_question_id);
            if ($question) {
                $payload['question'] = $this->formatQuestion($question);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Live session hien tai cua room.',
            'data' => $payload,
        ]);
    }

    public function create(Request $request, Room $room)
    {
        $user = $request->user();

        if (!$this->isRoomOwner($room, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ room mới được tạo live session.',
            ], 403);
        }

        $data = $request->validate([
            'quiz_id' => ['required', 'exists:quizzes,id'],
            'question_duration_sec' => ['nullable', 'integer', 'min:5', 'max:300'],
        ]);

        $quiz = Quiz::withCount('questions')->findOrFail($data['quiz_id']);

        if ($quiz->questions_count <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz này chưa có câu hỏi.',
            ], 422);
        }

        $session = LiveSession::create([
            'room_id' => $room->id,
            'quiz_id' => $quiz->id,
            'host_id' => $user->id,
            'assignment_id' => null,
            'code' => $this->generateUniqueLiveCode(),
            'status' => 'waiting',
            'current_question_id' => null,
            'current_question_index' => 0,
            'question_duration_sec' => $data['question_duration_sec'] ?? 20,
        ]);

        broadcast(new LiveSessionCreated($session));

        return response()->json([
            'success' => true,
            'message' => 'Tạo live session thành công.',
            'data' => $this->formatSession($session),
        ], 201);
    }

    public function join(Request $request, LiveSession $session)
    {
        $user = $request->user();

        if ($session->status === 'ended' || $session->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Live session đã kết thúc.',
            ], 422);
        }

        if (!$this->isRoomMember($session->room_id, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa phải thành viên của room này.',
            ], 403);
        }

        $participant = LiveParticipant::firstOrNew([
            'session_id' => $session->id,
            'user_id' => $user->id,
        ]);

        if (!$participant->exists) {
            $participant->joined_at = now();
            $participant->current_question_index = 0;
            $participant->is_finished = false;
        }

        $participant->display_name = $user->name;
        $participant->status = $participant->is_finished
            ? 'submitted'
            : ($session->status === 'playing' ? 'playing' : 'joined');
        $participant->last_seen_at = now();
        $participant->save();

        broadcast(new LiveParticipantJoined($session, $participant));
        broadcast(new LiveLeaderboardUpdated($session));

        return response()->json([
            'success' => true,
            'message' => 'Tham gia live thành công.',
            'data' => [
                'session' => $this->formatSession($session),
                'participant' => $participant,
            ],
        ]);
    }

    public function start(Request $request, LiveSession $session)
    {
        $user = $request->user();

        if ((int) $session->host_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ host mới được bắt đầu live.',
            ], 403);
        }

        if ($session->status !== 'waiting') {
            return response()->json([
                'success' => false,
                'message' => 'Live session không ở trạng thái chờ.',
            ], 422);
        }

        $firstQuestion = Question::where('quiz_id', $session->quiz_id)
            ->orderBy('order')
            ->first();

        if (!$firstQuestion) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz chưa có câu hỏi.',
            ], 422);
        }

        $session->update([
            'status' => 'playing',
            'current_question_id' => $firstQuestion->id,
            'current_question_index' => 0,
            'started_at' => now(),
        ]);

        LiveParticipant::where('session_id', $session->id)
            ->where('status', 'joined')
            ->update([
                'status' => 'playing',
                'last_seen_at' => now(),
            ]);

        broadcast(new LiveSessionStarted($session->fresh(), $firstQuestion));

        return response()->json([
            'success' => true,
            'message' => 'Live session đã bắt đầu.',
            'data' => [
                'session' => $this->formatSession($session->fresh()),
                'question' => $this->formatQuestion($firstQuestion),
            ],
        ]);
    }

    public function currentQuestion(Request $request, LiveSession $session)
    {
        $user = $request->user();

        if (!$this->isRoomMember($session->room_id, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem live session này.',
            ], 403);
        }

        if ($session->status !== 'playing') {
            return response()->json([
                'success' => true,
                'message' => 'Live session chua bat dau.',
                'data' => [
                    'session' => $this->formatSession($session),
                ],
            ]);
        }

        if ((int) $session->host_id === (int) $user->id) {
            $question = $session->current_question_id
                ? Question::with('answers')->find($session->current_question_id)
                : Question::with('answers')->where('quiz_id', $session->quiz_id)->orderBy('order')->first();

            return response()->json([
                'success' => true,
                'message' => 'Cau hoi hien tai cua host.',
                'data' => [
                    'session' => $this->formatSession($session),
                    'question' => $question ? $this->formatQuestion($question) : null,
                    'is_finished' => false,
                ],
            ]);
        }

        $participant = LiveParticipant::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Ban chua tham gia live session nay.',
            ], 403);
        }

        $questions = Question::with('answers')
            ->where('quiz_id', $session->quiz_id)
            ->orderBy('order')
            ->get()
            ->values();

        if ($participant->is_finished || $participant->current_question_index >= $questions->count()) {
            return response()->json([
                'success' => true,
                'message' => 'Ban da hoan thanh live quiz.',
                'data' => [
                    'session' => $this->formatSession($session),
                    'participant' => $participant,
                    'question' => null,
                    'is_finished' => true,
                    'leaderboard' => $this->getLeaderboardData($session),
                ],
            ]);
        }

        $question = $questions[$participant->current_question_index];

        return response()->json([
            'success' => true,
            'message' => 'Cau hoi hien tai.',
            'data' => [
                'session' => $this->formatSession($session),
                'participant' => $participant,
                'question' => $this->formatQuestion($question),
                'current_question_index' => $participant->current_question_index,
                'total_questions' => $questions->count(),
                'is_finished' => false,
            ],
        ]);

        if (!$session->current_question_id) {
            return response()->json([
                'success' => true,
                'message' => 'Live chưa có câu hỏi hiện tại.',
                'data' => null,
            ]);
        }

        $question = Question::with('answers')
            ->where('id', $session->current_question_id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Câu hỏi hiện tại.',
            'data' => [
                'session' => $this->formatSession($session),
                'question' => $this->formatQuestion($question),
            ],
        ]);
    }

    public function answer(Request $request, LiveSession $session)
    {
        $user = $request->user();

        if ($session->status !== 'playing') {
            return response()->json([
                'success' => false,
                'message' => 'Live session chưa bắt đầu hoặc đã kết thúc.',
            ], 422);
        }

        $data = $request->validate([
            'answer_id' => ['nullable', 'exists:answers,id'],
            'selected_answer_ids' => ['nullable', 'array'],
            'selected_answer_ids.*' => ['integer', 'exists:answers,id'],
            'response_time_ms' => ['nullable', 'integer', 'min:0'],
        ]);

        $participant = LiveParticipant::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa tham gia live session này.',
            ], 403);
        }

        if ($participant->is_finished) {
            return response()->json([
                'success' => false,
                'message' => 'Ban da hoan thanh live quiz nay.',
            ], 422);
        }

        $questions = Question::with('answers')
            ->where('quiz_id', $session->quiz_id)
            ->orderBy('order')
            ->get()
            ->values();

        if ($questions->isEmpty() || $participant->current_question_index >= $questions->count()) {
            $participant->update([
                'is_finished' => true,
                'finished_at' => $participant->finished_at ?: now(),
                'status' => 'submitted',
                'last_seen_at' => now(),
            ]);

            $this->checkAndEndSessionIfAllFinished($session, $questions->count());

            return response()->json([
                'success' => true,
                'message' => 'Ban da hoan thanh live quiz.',
                'data' => [
                    'participant' => $participant->fresh(),
                    'is_finished' => true,
                    'next_question' => null,
                ],
            ]);
        }

        $question = $questions[$participant->current_question_index];
        $selectedIds = $data['selected_answer_ids'] ?? [];

        if (empty($selectedIds) && !empty($data['answer_id'])) {
            $selectedIds = [(int) $data['answer_id']];
        }

        if (empty($selectedIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban chua chon dap an.',
            ], 422);
        }

        $userSelectedIds = collect($selectedIds)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $validAnswerCount = Answer::where('question_id', $question->id)
            ->whereIn('id', $userSelectedIds)
            ->count();

        if ($validAnswerCount !== count($userSelectedIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Co dap an khong thuoc cau hoi hien tai.',
            ], 422);
        }

        $alreadyAnswered = LiveAnswer::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->exists();

        if ($alreadyAnswered) {
            return response()->json([
                'success' => false,
                'message' => 'Ban da tra loi cau hoi nay roi.',
            ], 422);
        }

        $correctIds = $question->answers
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $isCorrect = $correctIds === $userSelectedIds;
        $score = $isCorrect ? (int) $question->points : 0;

        try {
            $liveAnswer = LiveAnswer::create([
                'session_id' => $session->id,
                'participant_id' => $participant->id,
                'user_id' => $user->id,
                'question_id' => $question->id,
                'answer_id' => $userSelectedIds[0] ?? null,
                'selected_answer_ids' => $userSelectedIds,
                'is_correct' => $isCorrect,
                'score' => $score,
                'response_time_ms' => $data['response_time_ms'] ?? null,
                'answered_at' => now(),
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ban da tra loi cau hoi nay roi.',
            ], 422);
        }

        $participant->increment('score', $score);

        if ($isCorrect) {
            $participant->increment('correct_count');
        } else {
            $participant->increment('wrong_count');
        }

        $nextIndex = $participant->current_question_index + 1;
        $isFinished = $nextIndex >= $questions->count();

        $participant->update([
            'current_question_index' => $nextIndex,
            'is_finished' => $isFinished,
            'finished_at' => $isFinished ? now() : null,
            'status' => $isFinished ? 'submitted' : 'playing',
            'last_seen_at' => now(),
        ]);

        $nextQuestion = $isFinished ? null : $questions[$nextIndex];

        broadcast(new LiveLeaderboardUpdated($session));
        $this->checkAndEndSessionIfAllFinished($session, $questions->count());

        return response()->json([
            'success' => true,
            'message' => 'Tra loi thanh cong.',
            'data' => [
                'answer' => $liveAnswer,
                'participant' => $participant->fresh(),
                'is_correct' => $isCorrect,
                'correct_ids' => $correctIds,
                'score_earned' => $score,
                'is_finished' => $isFinished,
                'next_question' => $nextQuestion ? $this->formatQuestion($nextQuestion) : null,
            ],
        ]);

        if (!$session->current_question_id) {
            return response()->json([
                'success' => false,
                'message' => 'Live chua co cau hoi hien tai.',
            ], 422);
        }

        $question = Question::with('answers')
            ->findOrFail($session->current_question_id);

        $alreadyAnswered = LiveAnswer::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->exists();

        if ($alreadyAnswered) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã trả lời câu hỏi này rồi.',
            ], 422);
        }

        $selectedIds = $data['selected_answer_ids'] ?? [];

        if (empty($selectedIds) && !empty($data['answer_id'])) {
            $selectedIds = [(int) $data['answer_id']];
        }

        if (empty($selectedIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa chọn đáp án.',
            ], 422);
        }

        $userSelectedIds = collect($selectedIds)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $validAnswerCount = Answer::where('question_id', $question->id)
            ->whereIn('id', $userSelectedIds)
            ->count();

        if ($validAnswerCount !== count($userSelectedIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Co dap an khong thuoc cau hoi hien tai.',
            ], 422);
        }

        $correctIds = $question->answers
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $isCorrect = $correctIds === $userSelectedIds;
        $score = $isCorrect ? (int) $question->points : 0;

        try {
            $liveAnswer = LiveAnswer::create([
                'session_id' => $session->id,
                'participant_id' => $participant->id,
                'user_id' => $user->id,
                'question_id' => $question->id,
                'answer_id' => $userSelectedIds[0] ?? null,
                'selected_answer_ids' => $userSelectedIds,
                'is_correct' => $isCorrect,
                'score' => $score,
                'response_time_ms' => $data['response_time_ms'] ?? null,
                'answered_at' => now(),
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã trả lời câu hỏi này rồi.',
            ], 422);
        }

        $participant->increment('score', $score);

        if ($isCorrect) {
            $participant->increment('correct_count');
        } else {
            $participant->increment('wrong_count');
        }

        $participant->update([
            'last_seen_at' => now(),
        ]);

        broadcast(new LiveLeaderboardUpdated($session));

        $totalActive = LiveParticipant::where('session_id', $session->id)
            ->whereIn('status', ['joined', 'playing'])
            ->count();

        $totalAnswered = LiveAnswer::where('session_id', $session->id)
            ->where('question_id', $session->current_question_id)
            ->count();

        if ($totalActive > 0 && $totalAnswered >= $totalActive) {
            $this->autoAdvanceQuestion($session);
        }

        return response()->json([
            'success' => true,
            'message' => 'Trả lời thành công.',
            'data' => [
                'answer' => $liveAnswer,
                'participant' => $participant->fresh(),
            ],
        ]);
    }

    public function nextQuestion(Request $request, LiveSession $session)
    {
        $user = $request->user();

        if ((int) $session->host_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ host mới được chuyển câu hỏi.',
            ], 403);
        }

        $questions = Question::where('quiz_id', $session->quiz_id)
            ->orderBy('order')
            ->get();

        $nextIndex = $session->current_question_index + 1;

        if ($nextIndex >= $questions->count()) {
            $session->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);

            $freshSession = $session->fresh();

            broadcast(new LiveSessionEnded($freshSession));

            return response()->json([
                'success' => true,
                'message' => 'Live session đã kết thúc.',
                'data' => [
                    'session' => $this->formatSession($freshSession),
                    'leaderboard' => $this->getLeaderboardData($freshSession),
                ],
            ]);
        }

        $nextQuestion = $questions[$nextIndex];

        $session->update([
            'current_question_id' => $nextQuestion->id,
            'current_question_index' => $nextIndex,
        ]);

        $freshSession = $session->fresh();

        broadcast(new LiveQuestionChanged($freshSession, $nextQuestion));
        broadcast(new LiveLeaderboardUpdated($freshSession));

        return response()->json([
            'success' => true,
            'message' => 'Đã chuyển sang câu hỏi tiếp theo.',
            'data' => [
                'session' => $this->formatSession($freshSession),
                'question' => $this->formatQuestion($nextQuestion),
            ],
        ]);
    }

    public function leaderboard(Request $request, LiveSession $session)
    {
        $user = $request->user();

        if (!$this->isRoomMember($session->room_id, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem bảng xếp hạng.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bảng xếp hạng live.',
            'data' => $this->getLeaderboardData($session),
        ]);
    }

    private function checkAndEndSessionIfAllFinished(LiveSession $session, int $totalQuestions): void
    {
        if ($totalQuestions <= 0 || $session->fresh()->status !== 'playing') {
            return;
        }

        $activeParticipants = LiveParticipant::where('session_id', $session->id)
            ->whereIn('status', ['joined', 'playing', 'submitted'])
            ->get();

        if ($activeParticipants->isEmpty()) {
            return;
        }

        $unfinishedCount = $activeParticipants
            ->filter(fn($participant) => !$participant->is_finished && $participant->current_question_index < $totalQuestions)
            ->count();

        if ($unfinishedCount > 0) {
            return;
        }

        $session->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        broadcast(new LiveSessionEnded($session->fresh()));
    }

    private function autoAdvanceQuestion(LiveSession $session): void
    {
        $questions = Question::where('quiz_id', $session->quiz_id)
            ->orderBy('order')
            ->get();

        $nextIndex = $session->current_question_index + 1;

        if ($nextIndex >= $questions->count()) {
            $session->update([
                'status' => 'ended',
                'ended_at' => now(),
            ]);

            broadcast(new LiveSessionEnded($session->fresh()));

            return;
        }

        $nextQuestion = $questions[$nextIndex];

        $session->update([
            'current_question_id' => $nextQuestion->id,
            'current_question_index' => $nextIndex,
        ]);

        $freshSession = $session->fresh();

        broadcast(new LiveQuestionChanged($freshSession, $nextQuestion));
        broadcast(new LiveLeaderboardUpdated($freshSession));
    }

    private function isRoomOwner(Room $room, int $userId): bool
    {
        return RoomMember::where('room_id', $room->id)
            ->where('user_id', $userId)
            ->where('role', 'owner')
            ->where('status', 'active')
            ->exists();
    }

    private function canAccessRoom(Room $room, $user): bool
    {
        if (!$user) {
            return false;
        }

        if (strtoupper((string) $user->role) === 'ADMIN') {
            return true;
        }

        if ((int) $room->host_id === (int) $user->id) {
            return true;
        }

        return $this->isRoomMember($room->id, $user->id);
    }

    private function isRoomMember(int $roomId, int $userId): bool
    {
        return RoomMember::where('room_id', $roomId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    private function generateUniqueLiveCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (LiveSession::where('code', $code)->exists());

        return $code;
    }

    private function formatSession(LiveSession $session): array
    {
        $totalQuestions = Question::where('quiz_id', $session->quiz_id)->count();

        return [
            'id' => $session->id,
            'room_id' => $session->room_id,
            'quiz_id' => $session->quiz_id,
            'host_id' => $session->host_id,
            'assignment_id' => $session->assignment_id,
            'code' => $session->code,
            'status' => $session->status,
            'current_question_id' => $session->current_question_id,
            'current_question_index' => $session->current_question_index,
            'question_duration_sec' => $session->question_duration_sec,
            'total_questions' => $totalQuestions,
            'started_at' => optional($session->started_at)->toISOString(),
            'ended_at' => optional($session->ended_at)->toISOString(),
        ];
    }

    private function formatQuestion(Question $question): array
    {
        $question->loadMissing('answers');

        return [
            'id' => $question->id,
            'quiz_id' => $question->quiz_id,
            'content' => $question->content,
            'image_url' => $question->image_url,
            'type' => $question->type,
            'order' => $question->order,
            'points' => $question->points,
            'answers' => $question->answers->map(fn($answer) => [
                'id' => $answer->id,
                'content' => $answer->content,
                'order' => $answer->order,
            ])->values(),
        ];
    }

    private function getLeaderboardData(LiveSession $session)
    {
        return LiveParticipant::where('session_id', $session->id)
            ->orderByDesc('score')
            ->orderByDesc('correct_count')
            ->orderByRaw('finished_at is null')
            ->orderBy('finished_at')
            ->orderBy('last_seen_at')
            ->get()
            ->values()
            ->map(fn($participant, $index) => [
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
    }
}
