<?php

namespace App\Http\Controllers;

use App\Events\LiveAnswerSubmitted;
use App\Events\LiveLeaderboardUpdated;
use App\Events\LivePlayerJoined;
use App\Events\LiveRoomFinished;
use App\Events\LiveRoomStarted;
use App\Models\Answer;
use App\Models\LiveRoom;
use App\Models\LiveRoomAnswer;
use App\Models\LiveRoomPlayer;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\LiveRoomPayloadService;
use App\Services\QuestionOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LiveRoomController extends Controller
{
    public function __construct(private readonly QuestionOrderService $questionOrderService)
    {
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (strtolower((string) ($user->role ?? 'user')) !== 'vip') {
            return response()->json([
                'success' => false,
                'message' => 'Chi tai khoan VIP moi duoc tao live room.',
            ], 403);
        }

        $data = $request->validate([
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $quiz = Quiz::withCount('questions')->findOrFail($data['quiz_id']);
        if (!$this->canUseQuiz($user, $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban khong co quyen tao live room tu quiz nay.',
            ], 403);
        }

        if ((int) $quiz->questions_count < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz can co it nhat mot cau hoi de tao live room.',
            ], 422);
        }

        $liveRoom = LiveRoom::create([
            'host_id' => $user->id,
            'quiz_id' => $quiz->id,
            'code' => $this->generateCode(),
            'title' => $data['title'] ?? $quiz->title,
            'status' => 'waiting',
            'current_question_index' => 0,
        ])->fresh(['host:id,name,email', 'quiz:id,title,description,time_limit_seconds']);

        return response()->json([
            'success' => true,
            'message' => 'Tao live room thanh cong',
            'data' => $this->formatLiveRoom($liveRoom),
        ], 201);
    }

    public function join(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:12'],
        ]);

        $user = $request->user();
        $liveRoom = LiveRoom::where('code', strtoupper(trim((string) $data['code'])))->first();
        if (!$liveRoom) {
            return response()->json([
                'success' => false,
                'message' => 'Khong tim thay live room.',
            ], 404);
        }

        if ($this->isHost($user, $liveRoom)) {
            return response()->json([
                'success' => false,
                'message' => 'Host khong the join live room cua chinh minh voi tu cach player.',
            ], 403);
        }

        if (!in_array($liveRoom->status, ['waiting', 'playing'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Live room nay khong cho tham gia them.',
            ], 422);
        }

        $player = LiveRoomPlayer::firstOrNew([
            'live_room_id' => $liveRoom->id,
            'user_id' => $user->id,
        ]);

        $shouldBroadcastJoin = !$player->exists || $player->status === 'left' || !$player->joined_at;

        if ($shouldBroadcastJoin) {
            $player->joined_at = now();
        }

        $player->status = 'joined';
        $player->save();

        if ($shouldBroadcastJoin) {
            LivePlayerJoined::dispatch($player->fresh('user:id,name,email'));
        }

        $liveRoom->load(['host:id,name,email', 'quiz:id,title,description,time_limit_seconds']);

        return response()->json([
            'success' => true,
            'message' => 'Tham gia live room thanh cong',
            'data' => [
                'live_room' => $this->formatLiveRoom($liveRoom),
                'player' => $this->formatPlayer($player->fresh('user:id,name,email'), $liveRoom),
            ],
        ]);
    }

    public function show(Request $request, LiveRoom $liveRoom)
    {
        if (!$this->canViewLiveRoom($request->user(), $liveRoom)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban khong co quyen xem live room nay.',
            ], 403);
        }

        $liveRoom->load([
            'host:id,name,email',
            'quiz:id,title,description,time_limit_seconds',
            'players.user:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chi tiet live room',
            'data' => $this->formatLiveRoom($liveRoom, true),
        ]);
    }

    public function start(Request $request, LiveRoom $liveRoom)
    {
        if (!$this->isHost($request->user(), $liveRoom)) {
            return response()->json([
                'success' => false,
                'message' => 'Chi host moi duoc bat dau live room.',
            ], 403);
        }

        if ($liveRoom->status !== 'waiting') {
            return response()->json([
                'success' => false,
                'message' => 'Live room chi duoc bat dau khi dang cho.',
            ], 422);
        }

        $liveRoom->load('quiz.questions.answers');
        if ($liveRoom->quiz->questions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz chua co cau hoi.',
            ], 422);
        }

        DB::transaction(function () use ($liveRoom) {
            $liveRoom->update([
                'question_order' => $this->questionOrderService->makeForQuiz($liveRoom->quiz),
                'current_question_index' => 0,
                'status' => 'playing',
                'started_at' => now(),
                'ended_at' => null,
            ]);

            LiveRoomPlayer::where('live_room_id', $liveRoom->id)
                ->where('user_id', '!=', $liveRoom->host_id)
                ->update([
                    'current_question_index' => 0,
                    'finished_at' => null,
                    'last_answered_at' => null,
                    'score' => 0,
                    'correct_count' => 0,
                ]);
        });

        $liveRoom = $liveRoom->fresh(['host:id,name,email', 'quiz:id,title']);
        $totalQuestions = count($this->normalizedQuestionOrder($liveRoom));

        LiveRoomStarted::dispatch($liveRoom, $totalQuestions);
        LiveLeaderboardUpdated::dispatch($liveRoom);

        return response()->json([
            'success' => true,
            'message' => 'Live room da bat dau',
            'data' => [
                'live_room' => $this->formatLiveRoom($liveRoom),
                'monitor' => $this->monitorData($liveRoom),
            ],
        ]);
    }

    public function currentQuestion(Request $request, LiveRoom $liveRoom)
    {
        $user = $request->user();
        if (!$this->canViewLiveRoom($user, $liveRoom)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban khong co quyen xem cau hoi live room nay.',
            ], 403);
        }

        if ($this->isHost($user, $liveRoom)) {
            return response()->json([
                'success' => true,
                'message' => 'Du lieu theo doi live room',
                'data' => $this->monitorData($liveRoom),
            ]);
        }

        $player = $this->activePlayer($liveRoom, $user->id);
        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Ban chua tham gia live room nay.',
            ], 403);
        }

        if ($liveRoom->status === 'finished') {
            return response()->json([
                'success' => true,
                'message' => 'Live room da ket thuc.',
                'data' => $this->playerProgressData($liveRoom, $player) + [
                    'question' => null,
                    'leaderboard' => $this->leaderboardData($liveRoom),
                ],
            ]);
        }

        if ($liveRoom->status !== 'playing') {
            return response()->json([
                'success' => false,
                'message' => 'Live room chua trong trang thai dang choi.',
            ], 422);
        }

        $questionPayload = $this->formatCurrentQuestionForPlayer($liveRoom, $player);
        if (!$questionPayload['question']) {
            return response()->json([
                'success' => true,
                'message' => 'Ban da hoan thanh tat ca cau hoi.',
                'data' => $questionPayload,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cau hoi hien tai',
            'data' => $questionPayload,
        ]);
    }

    public function answer(Request $request, LiveRoom $liveRoom)
    {
        $data = $request->validate([
            'answer_id' => ['required', 'integer', 'exists:answers,id'],
            'response_time_ms' => ['nullable', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        if ($this->isHost($user, $liveRoom)) {
            return response()->json([
                'success' => false,
                'message' => 'Host khong duoc tra loi cau hoi live room.',
            ], 403);
        }

        $player = $this->activePlayer($liveRoom, $user->id);
        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Ban chua tham gia live room nay.',
            ], 403);
        }

        if ($liveRoom->status !== 'playing') {
            return response()->json([
                'success' => false,
                'message' => 'Live room khong trong trang thai dang choi.',
            ], 422);
        }

        $question = $this->currentQuestionModelForPlayer($liveRoom, $player);
        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Ban da hoan thanh tat ca cau hoi.',
            ], 422);
        }

        $answerId = (int) $data['answer_id'];
        if (!$question->answers->contains('id', $answerId)) {
            return response()->json([
                'success' => false,
                'message' => 'Dap an khong thuoc cau hoi hien tai.',
            ], 422);
        }

        $result = DB::transaction(function () use ($liveRoom, $user, $player, $question, $answerId, $data) {
            $lockedPlayer = LiveRoomPlayer::whereKey($player->id)->lockForUpdate()->firstOrFail();
            $question = $this->currentQuestionModelForPlayer($liveRoom->fresh(), $lockedPlayer);

            if (!$question) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Ban da hoan thanh tat ca cau hoi.',
                ], 422));
            }

            if (!$question->answers->contains('id', $answerId)) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Dap an khong thuoc cau hoi hien tai.',
                ], 422));
            }

            $existing = LiveRoomAnswer::where('live_room_id', $liveRoom->id)
                ->where('user_id', $user->id)
                ->where('question_id', $question->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return [
                    'answer' => $existing,
                    'player' => $lockedPlayer,
                    'already_answered' => true,
                    'has_next_question' => $this->hasQuestionAtIndex($liveRoom, (int) $lockedPlayer->current_question_index),
                ];
            }

            $correctAnswer = $question->answers->firstWhere('is_correct', true);
            $isCorrect = $correctAnswer && (int) $correctAnswer->id === $answerId;
            $scoreAwarded = $isCorrect ? 100 : 0;
            $answeredAt = now();

            $answer = LiveRoomAnswer::create([
                'live_room_id' => $liveRoom->id,
                'user_id' => $user->id,
                'question_id' => $question->id,
                'answer_id' => $answerId,
                'is_correct' => $isCorrect,
                'score_awarded' => $scoreAwarded,
                'answered_at' => $answeredAt,
                'response_time_ms' => $data['response_time_ms'] ?? null,
            ]);

            $questionOrder = $this->normalizedQuestionOrder($liveRoom);
            $nextIndex = (int) $lockedPlayer->current_question_index + 1;
            $hasNextQuestion = $nextIndex < count($questionOrder);

            $lockedPlayer->score = (int) $lockedPlayer->score + $scoreAwarded;
            $lockedPlayer->correct_count = (int) $lockedPlayer->correct_count + ($isCorrect ? 1 : 0);
            $lockedPlayer->last_answered_at = $answeredAt;
            $lockedPlayer->current_question_index = $hasNextQuestion ? $nextIndex : count($questionOrder);
            $lockedPlayer->finished_at = $hasNextQuestion ? null : $answeredAt;
            $lockedPlayer->save();

            return [
                'answer' => $answer,
                'player' => $lockedPlayer->fresh(),
                'already_answered' => false,
                'has_next_question' => $hasNextQuestion,
            ];
        });

        if (!$result['already_answered']) {
            LiveAnswerSubmitted::dispatch($result['player']);
            if (!$this->maybeFinishLiveRoom($liveRoom->fresh())) {
                LiveLeaderboardUpdated::dispatch($liveRoom->fresh());
            }
        }

        $liveRoomAfterAnswer = $liveRoom->fresh(['host:id,name,email', 'quiz:id,title']);
        $payloadService = app(LiveRoomPayloadService::class);
        $nextQuestion = $result['has_next_question']
            ? $payloadService->currentQuestionForIndex($liveRoomAfterAnswer, (int) $result['player']->current_question_index)
            : null;

        return response()->json([
            'success' => true,
            'message' => $result['already_answered'] ? 'Cau hoi nay da duoc tra loi truoc do.' : 'Da ghi nhan cau tra loi.',
            'data' => [
                'is_correct' => (bool) $result['answer']->is_correct,
                'score_awarded' => (int) $result['answer']->score_awarded,
                'current_score' => (int) $result['player']->score,
                'correct_count' => (int) $result['player']->correct_count,
                'next_question_index' => (int) $result['player']->current_question_index,
                'has_next_question' => (bool) $result['has_next_question'],
                'player_finished' => (bool) $result['player']->finished_at,
                'already_answered' => $result['already_answered'],
                'room_status' => $liveRoomAfterAnswer->status,
                'next_question' => $nextQuestion,
                'leaderboard' => $payloadService->leaderboard($liveRoomAfterAnswer),
            ],
        ]);
    }

    public function nextQuestion(Request $request, LiveRoom $liveRoom)
    {
        if (!$this->isHost($request->user(), $liveRoom)) {
            return response()->json([
                'success' => false,
                'message' => 'Chi host moi duoc goi API nay.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Live room hien tai dung tien do rieng tung player, host khong can chuyen cau chung.',
            'data' => [
                'live_room' => $this->formatLiveRoom($liveRoom),
            ],
        ]);
    }

    public function finish(Request $request, LiveRoom $liveRoom)
    {
        if (!$this->isHost($request->user(), $liveRoom)) {
            return response()->json([
                'success' => false,
                'message' => 'Chi host moi duoc ket thuc live room.',
            ], 403);
        }

        if (!in_array($liveRoom->status, ['waiting', 'playing'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Live room khong the ket thuc o trang thai hien tai.',
            ], 422);
        }

        $liveRoom->update([
            'status' => 'finished',
            'ended_at' => now(),
        ]);

        $liveRoom = $liveRoom->fresh(['host:id,name,email', 'quiz:id,title']);

        LiveRoomFinished::dispatch($liveRoom);
        LiveLeaderboardUpdated::dispatch($liveRoom);

        return response()->json([
            'success' => true,
            'message' => 'Live room da ket thuc',
            'data' => [
                'live_room' => $this->formatLiveRoom($liveRoom),
                'leaderboard' => $this->leaderboardData($liveRoom),
            ],
        ]);
    }

    public function leaderboard(Request $request, LiveRoom $liveRoom)
    {
        if (!$this->canViewLiveRoom($request->user(), $liveRoom)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban khong co quyen xem bang xep hang live room nay.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bang xep hang live room',
            'data' => $this->leaderboardData($liveRoom),
        ]);
    }

    private function maybeFinishLiveRoom(LiveRoom $liveRoom): bool
    {
        $liveRoom->refresh();
        if ($liveRoom->status !== 'playing') {
            return false;
        }

        $players = $this->playerQuery($liveRoom)->get(['id', 'finished_at']);
        if ($players->isEmpty()) {
            return false;
        }

        if ($players->contains(fn (LiveRoomPlayer $player) => is_null($player->finished_at))) {
            return false;
        }

        $updated = LiveRoom::whereKey($liveRoom->id)
            ->where('status', 'playing')
            ->update([
                'status' => 'finished',
                'ended_at' => now(),
            ]);

        if (!$updated) {
            return false;
        }

        $finishedRoom = $liveRoom->fresh(['host:id,name,email', 'quiz:id,title']);
        LiveRoomFinished::dispatch($finishedRoom, 'all_players_finished');
        LiveLeaderboardUpdated::dispatch($finishedRoom);

        return true;
    }

    private function canUseQuiz($user, Quiz $quiz): bool
    {
        return (int) $quiz->user_id === (int) $user->id
            || ((bool) $quiz->is_public && $quiz->status === 'published');
    }

    private function canViewLiveRoom($user, LiveRoom $liveRoom): bool
    {
        return $this->isHost($user, $liveRoom) || (bool) $this->activePlayer($liveRoom, $user->id);
    }

    private function isHost($user, LiveRoom $liveRoom): bool
    {
        return (int) $liveRoom->host_id === (int) $user->id;
    }

    private function activePlayer(LiveRoom $liveRoom, int $userId): ?LiveRoomPlayer
    {
        if ((int) $liveRoom->host_id === $userId) {
            return null;
        }

        return LiveRoomPlayer::where('live_room_id', $liveRoom->id)
            ->where('user_id', $userId)
            ->where('status', 'joined')
            ->first();
    }

    private function currentQuestionModelForPlayer(LiveRoom $liveRoom, LiveRoomPlayer $player): ?Question
    {
        $questionOrder = $this->normalizedQuestionOrder($liveRoom);
        $questionId = $questionOrder[(int) $player->current_question_index] ?? null;
        if (!$questionId) {
            return null;
        }

        return Question::with('answers')->whereKey($questionId)->first();
    }

    private function normalizedQuestionOrder(LiveRoom $liveRoom): array
    {
        return collect($liveRoom->question_order ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    private function hasQuestionAtIndex(LiveRoom $liveRoom, int $index): bool
    {
        return array_key_exists($index, $this->normalizedQuestionOrder($liveRoom));
    }

    private function formatCurrentQuestionForPlayer(LiveRoom $liveRoom, LiveRoomPlayer $player): array
    {
        $question = $this->currentQuestionModelForPlayer($liveRoom, $player);
        $progress = $this->playerProgressData($liveRoom, $player);

        return $progress + [
            'question' => $question ? [
                'id' => $question->id,
                'content' => $question->content,
                'text' => $question->content,
                'type' => $question->type,
                'points' => $question->points,
                'answers' => $question->answers->map(fn (Answer $answer, int $index) => [
                    'id' => $answer->id,
                    'content' => $answer->content,
                    'text' => $answer->content,
                    'answer_key' => chr(65 + ($answer->order ?? $index)),
                    'key' => chr(65 + ($answer->order ?? $index)),
                    'order' => $answer->order,
                ])->values(),
            ] : null,
        ];
    }

    private function playerProgressData(LiveRoom $liveRoom, LiveRoomPlayer $player): array
    {
        $questionOrder = $this->normalizedQuestionOrder($liveRoom);
        $totalQuestions = count($questionOrder);
        $answeredCount = $this->answeredCount($liveRoom, $player->user_id);
        $isFinished = (bool) $player->finished_at || (int) $player->current_question_index >= $totalQuestions;

        return [
            'live_room_id' => $liveRoom->id,
            'room_status' => $liveRoom->status,
            'player_current_question_index' => (int) $player->current_question_index,
            'current_question_index' => (int) $player->current_question_index,
            'total_questions' => $totalQuestions,
            'answered_count' => $answeredCount,
            'player_finished' => $isFinished,
            'is_finished' => $isFinished,
            'current_score' => (int) $player->score,
            'correct_count' => (int) $player->correct_count,
            'finished_at' => $player->finished_at,
            'last_answered_at' => $player->last_answered_at,
        ];
    }

    private function monitorData(LiveRoom $liveRoom): array
    {
        $liveRoom = $liveRoom->fresh(['host:id,name,email', 'quiz:id,title']);
        $totalPlayers = $this->playerQuery($liveRoom)->count();
        $totalFinishedPlayers = $this->playerQuery($liveRoom)->whereNotNull('finished_at')->count();

        return [
            'live_room' => $this->formatLiveRoom($liveRoom),
            'room_status' => $liveRoom->status,
            'total_players' => $totalPlayers,
            'total_finished_players' => $totalFinishedPlayers,
            'total_questions' => count($this->normalizedQuestionOrder($liveRoom)),
            'leaderboard' => $this->leaderboardData($liveRoom),
            'players_progress' => $this->playersProgress($liveRoom),
        ];
    }

    private function leaderboardData(LiveRoom $liveRoom)
    {
        $totalQuestions = count($this->normalizedQuestionOrder($liveRoom));

        return $this->playerQuery($liveRoom)
            ->with('user:id,name,email')
            ->orderByDesc('score')
            ->orderByDesc('correct_count')
            ->orderByRaw('finished_at IS NULL asc')
            ->orderBy('finished_at')
            ->orderBy('joined_at')
            ->get()
            ->values()
            ->map(fn (LiveRoomPlayer $player, int $index) => [
                'rank' => $index + 1,
                'user_id' => $player->user_id,
                'user' => $player->user,
                'score' => (int) $player->score,
                'correct_count' => (int) $player->correct_count,
                'current_question_index' => (int) $player->current_question_index,
                'answered_count' => $this->answeredCount($liveRoom, $player->user_id),
                'total_questions' => $totalQuestions,
                'finished_at' => $player->finished_at,
                'is_finished' => (bool) $player->finished_at || (int) $player->current_question_index >= $totalQuestions,
                'joined_at' => $player->joined_at,
            ]);
    }

    private function playersProgress(LiveRoom $liveRoom)
    {
        $totalQuestions = count($this->normalizedQuestionOrder($liveRoom));

        return $this->playerQuery($liveRoom)
            ->with('user:id,name,email')
            ->orderBy('joined_at')
            ->get()
            ->map(fn (LiveRoomPlayer $player) => [
                'user_id' => $player->user_id,
                'user' => $player->user,
                'score' => (int) $player->score,
                'correct_count' => (int) $player->correct_count,
                'current_question_index' => (int) $player->current_question_index,
                'answered_count' => $this->answeredCount($liveRoom, $player->user_id),
                'total_questions' => $totalQuestions,
                'finished_at' => $player->finished_at,
                'is_finished' => (bool) $player->finished_at || (int) $player->current_question_index >= $totalQuestions,
                'last_answered_at' => $player->last_answered_at,
            ]);
    }

    private function playerQuery(LiveRoom $liveRoom)
    {
        return LiveRoomPlayer::query()
            ->where('live_room_id', $liveRoom->id)
            ->where('user_id', '!=', $liveRoom->host_id)
            ->where('status', 'joined');
    }

    private function answeredCount(LiveRoom $liveRoom, int $userId): int
    {
        return LiveRoomAnswer::where('live_room_id', $liveRoom->id)
            ->where('user_id', $userId)
            ->count();
    }

    private function formatLiveRoom(LiveRoom $liveRoom, bool $includePlayers = false): array
    {
        $data = [
            'id' => $liveRoom->id,
            'host_id' => $liveRoom->host_id,
            'quiz_id' => $liveRoom->quiz_id,
            'code' => $liveRoom->code,
            'title' => $liveRoom->title,
            'status' => $liveRoom->status,
            'current_question_index' => $liveRoom->current_question_index,
            'question_order' => $liveRoom->question_order ?? [],
            'started_at' => $liveRoom->started_at,
            'ended_at' => $liveRoom->ended_at,
            'host' => $liveRoom->host ?? null,
            'quiz' => $liveRoom->quiz ?? null,
            'created_at' => $liveRoom->created_at,
            'updated_at' => $liveRoom->updated_at,
        ];

        if ($includePlayers) {
            $data['players'] = $liveRoom->players
                ->filter(fn (LiveRoomPlayer $player) => (int) $player->user_id !== (int) $liveRoom->host_id)
                ->map(fn (LiveRoomPlayer $player) => $this->formatPlayer($player, $liveRoom))
                ->values();
            $data['monitor'] = $this->monitorData($liveRoom);
        }

        return $data;
    }

    private function formatPlayer(LiveRoomPlayer $player, ?LiveRoom $liveRoom = null): array
    {
        $totalQuestions = $liveRoom ? count($this->normalizedQuestionOrder($liveRoom)) : null;

        return [
            'id' => $player->id,
            'live_room_id' => $player->live_room_id,
            'user_id' => $player->user_id,
            'score' => (int) $player->score,
            'correct_count' => (int) $player->correct_count,
            'current_question_index' => (int) $player->current_question_index,
            'answered_count' => $liveRoom ? $this->answeredCount($liveRoom, $player->user_id) : null,
            'total_questions' => $totalQuestions,
            'is_finished' => $totalQuestions === null ? (bool) $player->finished_at : ((bool) $player->finished_at || (int) $player->current_question_index >= $totalQuestions),
            'finished_at' => $player->finished_at,
            'last_answered_at' => $player->last_answered_at,
            'status' => $player->status,
            'joined_at' => $player->joined_at,
            'user' => $player->user ?? null,
        ];
    }

    private function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (LiveRoom::where('code', $code)->exists());

        return $code;
    }
}
