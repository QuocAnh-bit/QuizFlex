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
use Illuminate\Support\Facades\Gate;

class LiveRoomController extends Controller
{
    public function __construct(private readonly QuestionOrderService $questionOrderService) {}

    public function index(Request $request)
    {
        $user = auth('api')->user() ?? $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        $rooms = LiveRoom::query()
            ->with(['quiz:id,title', 'host:id,name'])
            ->withCount('players')
            ->where(function ($q) use ($user) {
                $q->where('host_id', $user->id)
                    ->orWhereHas('players', function ($pq) use ($user) {
                        $pq->where('user_id', $user->id);
                    });
            })
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(function ($room) use ($user) {
                $isHost = (int) $room->host_id === (int) $user->id;
                return [
                    'id' => $room->id,
                    'name' => $room->title ?: ($room->quiz?->title ? 'Thi đấu: ' . $room->quiz->title : 'Phòng thi đấu #' . $room->id),
                    'pin' => $room->pin,
                    'code' => $room->pin,
                    'description' => $room->quiz?->title ? 'Bộ quiz: ' . $room->quiz->title : 'Phòng thi đấu thời gian thực',
                    'quiz_title' => $room->quiz?->title,
                    'host_name' => $room->host?->name,
                    'host_id' => $room->host_id,
                    'is_host' => $isHost,
                    'role' => $isHost ? 'Chủ phòng' : 'Người chơi',
                    'members_count' => $room->players_count,
                    'status' => $room->status,
                    'created_at' => $room->created_at?->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $rooms,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $createAuthorization = Gate::forUser($user)->inspect('create', LiveRoom::class);
        if ($createAuthorization->denied()) {
            return response()->json([
                'success' => false,
                'message' => $createAuthorization->message(),
            ], 403);
        }

        $data = $request->validate([
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $quiz = Quiz::withCount('questions')->findOrFail($data['quiz_id']);
        $quizAuthorization = Gate::forUser($user)->inspect('createFromQuiz', [LiveRoom::class, $quiz]);
        if ($quizAuthorization->denied()) {
            return response()->json([
                'success' => false,
                'message' => $quizAuthorization->message(),
            ], 403);
        }

        if ((int) $quiz->questions_count < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Bộ Quiz cần có ít nhất 1 câu hỏi để tạo phòng thi đấu.',
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
            'message' => 'Tạo phòng thi đấu trực tiếp thành công!',
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
                'message' => 'Không tìm thấy phòng thi đấu hoặc mã phòng không hợp lệ.',
            ], 404);
        }

        if ($liveRoom->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng trực tuyến này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        $joinAuthorization = Gate::forUser($user)->inspect('join', $liveRoom);
        if ($joinAuthorization->denied()) {
            return response()->json([
                'success' => false,
                'message' => $joinAuthorization->message(),
            ], 403);
        }

        if (!in_array($liveRoom->status, ['waiting', 'playing'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Phòng thi đấu này hiện không nhận thêm người tham gia.',
            ], 422);
        }

        if ($liveRoom->status === 'playing' || $liveRoom->started_at !== null) {
            $hasJoined = LiveRoomPlayer::where('live_room_id', $liveRoom->id)
                ->where('user_id', $user->id)
                ->where('status', 'joined')
                ->exists();

            if (!$hasJoined) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trận đấu đã bắt đầu, hiện không thể tham gia.',
                ], 403);
            }
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
            'message' => 'Tham gia phòng thi đấu thành công!',
            'data' => [
                'live_room' => $this->formatLiveRoom($liveRoom),
                'player' => $this->formatPlayer($player->fresh('user:id,name,email'), $liveRoom),
            ],
        ]);
    }

    public function show(Request $request, LiveRoom $liveRoom)
    {
        Gate::forUser($request->user())->authorize('view', $liveRoom);

        $liveRoom->load([
            'host:id,name,email',
            'quiz:id,title,description,time_limit_seconds',
            'players.user:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thông tin chi tiết phòng thi đấu',
            'data' => $this->formatLiveRoom($liveRoom, true),
        ]);
    }

    public function start(Request $request, LiveRoom $liveRoom)
    {
        if ($liveRoom->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng trực tuyến này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        Gate::forUser($request->user())->authorize('start', $liveRoom);

        if ($liveRoom->status !== 'waiting') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng thi đấu chỉ có thể bắt đầu khi đang ở trạng thái chờ.',
            ], 422);
        }

        $liveRoom->load('quiz.questions.answers');
        if ($liveRoom->quiz->questions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Bộ Quiz chưa có câu hỏi nào để bắt đầu.',
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
            'message' => 'Trận đấu trực tiếp đã chính thức bắt đầu!',
            'data' => [
                'live_room' => $this->formatLiveRoom($liveRoom),
                'monitor' => $this->monitorData($liveRoom),
            ],
        ]);
    }

    public function currentQuestion(Request $request, LiveRoom $liveRoom)
    {
        $user = $request->user();
        Gate::forUser($user)->authorize('view', $liveRoom);

        if (Gate::forUser($user)->allows('viewMonitor', $liveRoom)) {
            return response()->json([
                'success' => true,
                'message' => 'Dữ liệu giám sát phòng trực tiếp',
                'data' => $this->monitorData($liveRoom),
            ]);
        }

        $player = $this->activePlayer($liveRoom, $user->id);
        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa tham gia vào phòng thi đấu này.',
            ], 403);
        }

        if ($liveRoom->status === 'finished') {
            return response()->json([
                'success' => true,
                'message' => 'Phòng thi đấu đã kết thúc.',
                'data' => $this->playerProgressData($liveRoom, $player) + [
                    'question' => null,
                    'leaderboard' => $this->leaderboardData($liveRoom),
                ],
            ]);
        }

        if ($liveRoom->status !== 'playing') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng thi đấu chưa bắt đầu. Vui lòng chờ chủ phòng khởi động trận đấu nhé!',
            ], 422);
        }

        $questionPayload = $this->formatCurrentQuestionForPlayer($liveRoom, $player);
        if (!$questionPayload['question']) {
            return response()->json([
                'success' => true,
                'message' => 'Bạn đã hoàn thành tất cả các câu hỏi trong trận đấu!',
                'data' => $questionPayload,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Câu hỏi hiện tại',
            'data' => $questionPayload,
        ]);
    }

    public function answer(Request $request, LiveRoom $liveRoom)
    {
        if ($liveRoom->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng trực tuyến này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        $data = $request->validate([
            'answer_id' => ['required', 'integer', 'exists:answers,id'],
            'response_time_ms' => ['nullable', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $answerAuthorization = Gate::forUser($user)->inspect('answer', $liveRoom);
        if ($answerAuthorization->denied()) {
            return response()->json([
                'success' => false,
                'message' => $answerAuthorization->message(),
            ], 403);
        }

        $player = $this->activePlayer($liveRoom, $user->id);
        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa tham gia vào phòng thi đấu này.',
            ], 403);
        }

        if ($liveRoom->status !== 'playing') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng thi đấu hiện không ở trạng thái đang diễn ra.',
            ], 422);
        }

        $question = $this->currentQuestionModelForPlayer($liveRoom, $player);
        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã hoàn thành tất cả các câu hỏi trong trận đấu!',
            ], 422);
        }

        $answerId = (int) $data['answer_id'];
        if (!$question->answers->contains('id', $answerId)) {
            return response()->json([
                'success' => false,
                'message' => 'Đáp án đã chọn không hợp lệ hoặc không thuộc câu hỏi hiện tại.',
            ], 422);
        }

        $result = DB::transaction(function () use ($liveRoom, $user, $player, $question, $answerId, $data) {
            $lockedPlayer = LiveRoomPlayer::whereKey($player->id)->lockForUpdate()->firstOrFail();
            $question = $this->currentQuestionModelForPlayer($liveRoom->fresh(), $lockedPlayer);

            if (!$question) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Bạn đã hoàn thành tất cả các câu hỏi trong trận đấu!',
                ], 422));
            }

            if (!$question->answers->contains('id', $answerId)) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Đáp án đã chọn không hợp lệ hoặc không thuộc câu hỏi hiện tại.',
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

        $liveRoomAfterAnswer = $liveRoom->fresh(['host:id,name,email', 'quiz:id,title']);
        $payloadService = app(LiveRoomPayloadService::class);
        $leaderboard = $payloadService->leaderboard($liveRoomAfterAnswer);

        if (!$result['already_answered']) {
            LiveAnswerSubmitted::dispatch($result['player']);
            if (!$this->maybeFinishLiveRoom($liveRoom->fresh())) {
                LiveLeaderboardUpdated::dispatch($liveRoom->fresh(), $leaderboard);
            }
        }

        $nextQuestion = $result['has_next_question']
            ? $payloadService->currentQuestionForIndex($liveRoomAfterAnswer, (int) $result['player']->current_question_index)
            : null;

        return response()->json([
            'success' => true,
            'message' => $result['already_answered'] ? 'Câu hỏi này đã được trả lời trước đó.' : 'Đã ghi nhận câu trả lời của bạn.',
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
                'leaderboard' => $leaderboard,
            ],
        ]);
    }

    public function nextQuestion(Request $request, LiveRoom $liveRoom)
    {
        if ($liveRoom->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng trực tuyến này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        Gate::forUser($request->user())->authorize('nextQuestion', $liveRoom);

        return response()->json([
            'success' => true,
            'message' => 'Phòng thi đấu sử dụng tiến độ riêng cho từng người chơi.',
            'data' => [
                'live_room' => $this->formatLiveRoom($liveRoom),
            ],
        ]);
    }

    public function finish(Request $request, LiveRoom $liveRoom)
    {
        if ($liveRoom->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng trực tuyến này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        Gate::forUser($request->user())->authorize('finish', $liveRoom);

        if (!in_array($liveRoom->status, ['waiting', 'playing'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể kết thúc phòng thi đấu ở trạng thái hiện tại.',
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
            'message' => 'Phòng thi đấu đã kết thúc thành công.',
            'data' => [
                'live_room' => $this->formatLiveRoom($liveRoom),
                'leaderboard' => $this->leaderboardData($liveRoom),
            ],
        ]);
    }

    public function leaderboard(Request $request, LiveRoom $liveRoom)
    {
        Gate::forUser($request->user())->authorize('view', $liveRoom);

        return response()->json([
            'success' => true,
            'message' => 'Bảng xếp hạng phòng thi đấu',
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

        if ($players->contains(fn(LiveRoomPlayer $player) => is_null($player->finished_at))) {
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
            ->map(fn($id) => (int) $id)
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
                'image_url' => $question->image_url,
                'type' => $question->type,
                'points' => $question->points,
                'answers' => $question->answers->map(fn(Answer $answer, int $index) => [
                    'id' => $answer->id,
                    'content' => $answer->content,
                    'text' => $answer->content,
                    'answer_key' => chr(65 + $index),
                    'key' => chr(65 + $index),
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
            ->map(fn(LiveRoomPlayer $player, int $index) => [
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
            ->map(fn(LiveRoomPlayer $player) => [
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
                ->filter(fn(LiveRoomPlayer $player) => (int) $player->user_id !== (int) $liveRoom->host_id)
                ->map(fn(LiveRoomPlayer $player) => $this->formatPlayer($player, $liveRoom))
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
