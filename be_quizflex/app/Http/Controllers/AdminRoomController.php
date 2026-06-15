<?php

namespace App\Http\Controllers;

use App\Models\LiveRoom;
use App\Models\LiveRoomAnswer;
use App\Models\LiveRoomPlayer;
use App\Models\QuizAttempt;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\RoomMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminRoomController extends Controller
{
    public function homeworkIndex(Request $request)
    {
        $perPage = $this->perPage($request);
        $query = Room::query()
            ->where('type', 'homework')
            ->with(['owner:id,name,email'])
            ->withCount([
                'members as member_count' => fn (Builder $memberQuery) => $memberQuery
                    ->where('status', 'active')
                    ->whereColumn('room_members.user_id', '!=', 'rooms.owner_id'),
                'assignments as assignment_count',
            ])
            ->latest();

        $this->applyRoomFilters($query, $request, 'owner');

        $rooms = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Admin homework rooms',
            'data' => [
                'items' => $rooms->getCollection()
                    ->map(fn (Room $room) => $this->formatHomeworkRoomSummary($room))
                    ->values(),
                'meta' => $this->paginationMeta($rooms),
            ],
        ]);
    }

    public function homeworkShow(Room $room)
    {
        abort_if($room->type !== 'homework', 404);

        $room->load([
            'owner:id,name,email',
            'members.user:id,name,email,role',
            'assignments' => fn ($query) => $query
                ->with(['quiz:id,title,time_limit_seconds', 'assigner:id,name,email'])
                ->withCount('attempts')
                ->latest(),
        ])->loadCount([
            'members as member_count' => fn (Builder $query) => $query
                ->where('status', 'active')
                ->where('user_id', '!=', $room->owner_id),
            'assignments as assignment_count',
        ]);

        $submissions = QuizAttempt::query()
            ->with([
                'user:id,name,email',
                'assignment:id,title,room_id,quiz_id',
                'quiz:id,title',
            ])
            ->where('room_id', $room->id)
            ->whereNotNull('assignment_id')
            ->where('mode', 'homework')
            ->latest('started_at')
            ->get()
            ->map(fn (QuizAttempt $attempt) => $this->formatHomeworkSubmission($attempt))
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Admin homework room detail',
            'data' => [
                'room' => $this->formatHomeworkRoomSummary($room, true),
                'members' => $this->formatHomeworkMembers($room),
                'assignments' => $room->assignments
                    ->map(fn (RoomAssignment $assignment) => $this->formatHomeworkAssignment($assignment))
                    ->values(),
                'submissions' => $submissions,
            ],
        ]);
    }

    public function liveIndex(Request $request)
    {
        $perPage = $this->perPage($request);
        $query = LiveRoom::query()
            ->with(['host:id,name,email', 'quiz:id,title'])
            ->withCount([
                'players as player_count' => fn (Builder $playerQuery) => $playerQuery
                    ->where('status', 'joined')
                    ->whereColumn('live_room_players.user_id', '!=', 'live_rooms.host_id'),
            ])
            ->latest();

        $this->applyLiveRoomFilters($query, $request);

        $rooms = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Admin live rooms',
            'data' => [
                'items' => $rooms->getCollection()
                    ->map(fn (LiveRoom $liveRoom) => $this->formatLiveRoomSummary($liveRoom))
                    ->values(),
                'meta' => $this->paginationMeta($rooms),
            ],
        ]);
    }

    public function liveShow(LiveRoom $liveRoom)
    {
        $liveRoom->load([
            'host:id,name,email',
            'quiz:id,title,time_limit_seconds',
            'players.user:id,name,email',
            'answers.user:id,name,email',
            'answers.question:id,content',
            'answers.answer:id,content',
        ])->loadCount([
            'players as player_count' => fn (Builder $query) => $query
                ->where('status', 'joined')
                ->where('user_id', '!=', $liveRoom->host_id),
        ]);

        $players = $liveRoom->players
            ->filter(fn (LiveRoomPlayer $player) => (int) $player->user_id !== (int) $liveRoom->host_id)
            ->map(fn (LiveRoomPlayer $player) => $this->formatLivePlayer($player, $liveRoom))
            ->values();

        $leaderboard = $players
            ->sortBy([
                ['score', 'desc'],
                ['correct_count', 'desc'],
                ['finished_at', 'asc'],
                ['joined_at', 'asc'],
            ])
            ->values()
            ->map(function (array $player, int $index) {
                return $player + ['rank' => $index + 1];
            });

        $answers = $liveRoom->answers
            ->map(fn (LiveRoomAnswer $answer) => $this->formatLiveAnswer($answer))
            ->values();

        $finishedCount = $players->filter(fn (array $player) => $player['is_finished'])->count();
        $playerCount = $players->count();

        return response()->json([
            'success' => true,
            'message' => 'Admin live room detail',
            'data' => [
                'room' => $this->formatLiveRoomSummary($liveRoom, true),
                'players' => $players,
                'leaderboard' => $leaderboard,
                'answers' => $answers,
                'realtime_status' => [
                    'room_status' => $liveRoom->status,
                    'finished_players' => $finishedCount,
                    'unfinished_players' => max(0, $playerCount - $finishedCount),
                    'last_updated_at' => $liveRoom->updated_at,
                    'note' => $this->liveRoomRealtimeNote($liveRoom, $playerCount, $finishedCount),
                ],
            ],
        ]);
    }

    private function applyRoomFilters(Builder $query, Request $request, string $ownerRelation): void
    {
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $roomQuery) use ($search, $ownerRelation) {
                $roomQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas($ownerRelation, function (Builder $ownerQuery) use ($search) {
                        $ownerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('owner_id')) {
            $query->where('owner_id', (int) $request->query('owner_id'));
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->query('created_from'));
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->query('created_to'));
        }
    }

    private function applyLiveRoomFilters(Builder $query, Request $request): void
    {
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $roomQuery) use ($search) {
                $roomQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('host', function (Builder $hostQuery) use ($search) {
                        $hostQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('host_id')) {
            $query->where('host_id', (int) $request->query('host_id'));
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->query('created_from'));
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->query('created_to'));
        }
    }

    private function formatHomeworkRoomSummary(Room $room, bool $includeDescription = false): array
    {
        $data = [
            'id' => $room->id,
            'name' => $room->name,
            'code' => $room->code,
            'status' => $room->status,
            'created_at' => $room->created_at,
            'updated_at' => $room->updated_at,
            'owner' => $room->owner ? [
                'id' => $room->owner->id,
                'name' => $room->owner->name,
                'email' => $room->owner->email,
            ] : null,
            'member_count' => (int) ($room->member_count ?? 0),
            'assignment_count' => (int) ($room->assignment_count ?? 0),
        ];

        if ($includeDescription) {
            $data['description'] = $room->description;
            $data['max_players'] = $room->max_players;
            $data['join_policy'] = $room->join_policy ?: 'open';
        }

        return $data;
    }

    private function formatHomeworkMembers(Room $room)
    {
        $owner = $room->owner ? [[
            'id' => null,
            'user_id' => $room->owner->id,
            'name' => $room->owner->name,
            'email' => $room->owner->email,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => $room->created_at,
            'user' => $room->owner,
        ]] : [];

        $members = $room->members
            ->filter(fn (RoomMember $member) => (int) $member->user_id !== (int) $room->owner_id)
            ->map(fn (RoomMember $member) => [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'name' => $member->user?->name,
                'email' => $member->user?->email,
                'role' => $member->role,
                'status' => $member->status,
                'joined_at' => $member->joined_at,
                'user' => $member->user,
            ])
            ->values()
            ->all();

        return collect(array_merge($owner, $members))->values();
    }

    private function formatHomeworkAssignment(RoomAssignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'quiz' => $assignment->quiz ? [
                'id' => $assignment->quiz->id,
                'title' => $assignment->quiz->title,
            ] : null,
            'assigner' => $assignment->assigner ? [
                'id' => $assignment->assigner->id,
                'name' => $assignment->assigner->name,
                'email' => $assignment->assigner->email,
            ] : null,
            'deadline_at' => $assignment->deadline_at,
            'duration_minutes' => $assignment->duration_minutes,
            'max_attempts' => $assignment->max_attempts,
            'submission_count' => (int) ($assignment->attempts_count ?? 0),
            'status' => $assignment->status,
            'created_at' => $assignment->created_at,
        ];
    }

    private function formatHomeworkSubmission(QuizAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'student' => $attempt->user ? [
                'id' => $attempt->user->id,
                'name' => $attempt->user->name,
                'email' => $attempt->user->email,
            ] : null,
            'assignment' => $attempt->assignment ? [
                'id' => $attempt->assignment->id,
                'title' => $attempt->assignment->title,
            ] : null,
            'quiz' => $attempt->quiz ? [
                'id' => $attempt->quiz->id,
                'title' => $attempt->quiz->title,
            ] : null,
            'score' => $attempt->score,
            'total_points' => $attempt->total_points,
            'status' => $attempt->status,
            'started_at' => $attempt->started_at,
            'submitted_at' => $attempt->submitted_at,
            'finished_at' => $attempt->finished_at,
        ];
    }

    private function formatLiveRoomSummary(LiveRoom $liveRoom, bool $includeQuestionOrder = false): array
    {
        $data = [
            'id' => $liveRoom->id,
            'title' => $liveRoom->title,
            'name' => $liveRoom->title,
            'code' => $liveRoom->code,
            'status' => $liveRoom->status,
            'created_at' => $liveRoom->created_at,
            'started_at' => $liveRoom->started_at,
            'finished_at' => $liveRoom->ended_at,
            'ended_at' => $liveRoom->ended_at,
            'host' => $liveRoom->host ? [
                'id' => $liveRoom->host->id,
                'name' => $liveRoom->host->name,
                'email' => $liveRoom->host->email,
            ] : null,
            'quiz' => $liveRoom->quiz ? [
                'id' => $liveRoom->quiz->id,
                'title' => $liveRoom->quiz->title,
            ] : null,
            'player_count' => (int) ($liveRoom->player_count ?? 0),
        ];

        if ($includeQuestionOrder) {
            $data['current_question_index'] = (int) $liveRoom->current_question_index;
            $data['question_order'] = $this->questionOrder($liveRoom);
        }

        return $data;
    }

    private function formatLivePlayer(LiveRoomPlayer $player, LiveRoom $liveRoom): array
    {
        $totalQuestions = count($this->questionOrder($liveRoom));
        $answeredCount = LiveRoomAnswer::where('live_room_id', $liveRoom->id)
            ->where('user_id', $player->user_id)
            ->count();

        return [
            'id' => $player->id,
            'user_id' => $player->user_id,
            'name' => $player->user?->name,
            'email' => $player->user?->email,
            'score' => (int) $player->score,
            'correct_count' => (int) $player->correct_count,
            'current_question_index' => (int) $player->current_question_index,
            'answered_count' => $answeredCount,
            'total_questions' => $totalQuestions,
            'is_finished' => (bool) $player->finished_at || ($totalQuestions > 0 && (int) $player->current_question_index >= $totalQuestions),
            'finished_at' => $player->finished_at,
            'last_answered_at' => $player->last_answered_at,
            'joined_at' => $player->joined_at,
            'status' => $player->status,
            'user' => $player->user,
        ];
    }

    private function formatLiveAnswer(LiveRoomAnswer $answer): array
    {
        return [
            'id' => $answer->id,
            'player' => $answer->user ? [
                'id' => $answer->user->id,
                'name' => $answer->user->name,
                'email' => $answer->user->email,
            ] : null,
            'question' => $answer->question ? [
                'id' => $answer->question->id,
                'content' => $answer->question->content,
            ] : null,
            'answer' => $answer->answer ? [
                'id' => $answer->answer->id,
                'content' => $answer->answer->content,
            ] : null,
            'is_correct' => (bool) $answer->is_correct,
            'score_awarded' => (int) $answer->score_awarded,
            'answered_at' => $answer->answered_at,
            'response_time_ms' => $answer->response_time_ms,
        ];
    }

    private function liveRoomRealtimeNote(LiveRoom $liveRoom, int $playerCount, int $finishedCount): ?string
    {
        if ($liveRoom->status === 'playing' && $playerCount > 0 && $finishedCount < $playerCount && $liveRoom->updated_at?->lt(now()->subHours(2))) {
            return 'Room dang dien ra nhung da lau chua cap nhat.';
        }

        return null;
    }

    private function questionOrder(LiveRoom $liveRoom): array
    {
        return collect($liveRoom->question_order ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    private function perPage(Request $request): int
    {
        return min(100, max(5, (int) $request->query('per_page', 10)));
    }

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
