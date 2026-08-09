<?php

namespace App\Http\Controllers;

use App\Events\LiveLeaderboardUpdated;
use App\Events\LiveRoomFinished;
use App\Models\LiveRoom;
use App\Models\LiveRoomAnswer;
use App\Models\LiveRoomPlayer;
use App\Models\QuizAttempt;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\RoomMember;
use App\Models\User;
use App\Notifications\RoomModerated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminRoomController extends Controller
{
    public function stats()
    {
        $homeworkTotal = Room::where('type', 'homework')->count();
        $liveTotal = LiveRoom::count();
        $totalRooms = $homeworkTotal + $liveTotal;

        $homeworkActive = Room::where('type', 'homework')->where('status', 'active')->count();
        $liveActive = LiveRoom::whereIn('status', ['waiting', 'playing'])->count();
        $activeTotal = $homeworkActive + $liveActive;

        $homeworkTrash = Room::onlyTrashed()->where('type', 'homework')->count();
        $liveTrash = LiveRoom::onlyTrashed()->count();
        $trashTotal = $homeworkTrash + $liveTrash;

        $homeworkPercent = $totalRooms > 0 ? round(($homeworkTotal / $totalRooms) * 100, 1) : 0;
        $livePercent = $totalRooms > 0 ? round(($liveTotal / $totalRooms) * 100, 1) : 0;
        $activePercent = $totalRooms > 0 ? round(($activeTotal / $totalRooms) * 100, 1) : 0;
        $trashGrandTotal = $totalRooms + $trashTotal;
        $trashPercent = $trashGrandTotal > 0 ? round(($trashTotal / $trashGrandTotal) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'message' => 'Admin rooms statistics',
            'data' => [
                'total_rooms' => $totalRooms,
                'homework_total' => $homeworkTotal,
                'homework_percent' => $homeworkPercent,
                'live_total' => $liveTotal,
                'live_percent' => $livePercent,
                'active_total' => $activeTotal,
                'active_percent' => $activePercent,
                'trash_total' => $trashTotal,
                'trash_percent' => $trashPercent,
            ],
        ]);
    }

    public function homeworkIndex(Request $request)
    {
        $perPage = $this->perPage($request);
        $query = Room::query()
            ->where('type', 'homework')
            ->with(['host:id,name,email'])
            ->withCount([
                'members as member_count' => fn (Builder $memberQuery) => $memberQuery
                    ->where('status', 'active')
                    ->whereColumn('room_members.user_id', '!=', 'rooms.host_id'),
                'assignments as assignment_count',
            ])
            ->latest();

        $this->applyRoomFilters($query, $request, 'host');

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

    public function homeworkTrash(Request $request)
    {
        $perPage = $this->perPage($request);
        $query = Room::onlyTrashed()
            ->where('type', 'homework')
            ->with(['host:id,name,email'])
            ->withCount([
                'members as member_count' => fn (Builder $memberQuery) => $memberQuery
                    ->where('status', 'active')
                    ->whereColumn('room_members.user_id', '!=', 'rooms.host_id'),
                'assignments as assignment_count',
            ])
            ->latest();

        $this->applyRoomFilters($query, $request, 'host');

        $rooms = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Admin homework rooms trash',
            'data' => [
                'items' => $rooms->getCollection()
                    ->map(fn (Room $room) => $this->formatHomeworkRoomSummary($room))
                    ->values(),
                'meta' => $this->paginationMeta($rooms),
            ],
        ]);
    }

    public function restoreHomework($id)
    {
        $room = Room::onlyTrashed()->findOrFail($id);
        abort_if($room->type !== 'homework', 404);
        $room->restore();

        return response()->json([
            'success' => true,
            'message' => 'Homework room restored.',
            'data' => $this->formatHomeworkRoomSummary($room->fresh(['host'])),
        ]);
    }

    public function homeworkShow(Room $room)
    {
        abort_if($room->type !== 'homework', 404);

        $room->load([
            'host:id,name,email',
            'members.user:id,name,email,role',
            'assignments' => fn ($query) => $query
                ->with(['quiz:id,title,time_limit_seconds', 'assigner:id,name,email'])
                ->withCount('attempts')
                ->latest(),
        ])->loadCount([
            'members as member_count' => fn (Builder $query) => $query
                ->where('status', 'active')
                ->where('user_id', '!=', $room->host_id),
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



    public function softDeleteHomework(Room $room)
    {
        abort_if($room->type !== 'homework', 404);

        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Homework room soft deleted.',
        ]);
    }



    public function forceDeleteHomework($id)
    {
        $room = Room::onlyTrashed()->findOrFail($id);
        abort_if($room->type !== 'homework', 404);

        DB::transaction(function () use ($room) {
            // Xóa quiz attempts trước vì không có cascade từ rooms → quiz_attempts
            // (room_submission_evaluations sẽ cascade khi quiz_attempts bị xóa)
            QuizAttempt::where('room_id', $room->id)->delete();

            // Force delete phòng – cascade tự động xóa:
            // room_members, room_assignments, room_allowed_members,
            // room_member_evaluations, room_submission_evaluations
            $room->forceDelete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Homework room đã được xóa vĩnh viễn.',
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

    public function liveTrash(Request $request)
    {
        $perPage = $this->perPage($request);
        $query = LiveRoom::onlyTrashed()
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
            'message' => 'Admin live rooms trash',
            'data' => [
                'items' => $rooms->getCollection()
                    ->map(fn (LiveRoom $liveRoom) => $this->formatLiveRoomSummary($liveRoom))
                    ->values(),
                'meta' => $this->paginationMeta($rooms),
            ],
        ]);
    }

    public function restoreLive($id)
    {
        $liveRoom = LiveRoom::onlyTrashed()->findOrFail($id);
        $liveRoom->restore();

        return response()->json([
            'success' => true,
            'message' => 'Live room restored.',
            'data' => $this->formatLiveRoomSummary($liveRoom->fresh(['host', 'quiz'])),
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



    public function softDeleteLive(LiveRoom $liveRoom)
    {
        $liveRoom->delete();

        return response()->json([
            'success' => true,
            'message' => 'Live room soft deleted.',
        ]);
    }

    public function forceDeleteLive($id)
    {
        $liveRoom = LiveRoom::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($liveRoom) {
            $liveRoom->forceDelete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Live room đã được xóa vĩnh viễn.',
        ]);
    }

    private function applyRoomFilters(Builder $query, Request $request, string $hostRelation): void
    {
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $roomQuery) use ($search, $hostRelation) {
                $roomQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas($hostRelation, function (Builder $hostQuery) use ($search) {
                        $hostQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status === 'removed') {
                $query->onlyTrashed();
            } else {
                $query->where('status', $status === 'open' ? 'active' : $status);
            }
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
            $status = $request->query('status');
            if ($status === 'removed') {
                $query->onlyTrashed();
            } else {
                $query->where('status', $status);
            }
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
            'status' => $room->trashed() ? 'removed' : ($room->status === 'active' ? 'open' : $room->status),
            'created_at' => $room->created_at,
            'updated_at' => $room->updated_at,
            'deleted_at' => $room->deleted_at ? $room->deleted_at->toIso8601String() : null,
            'host' => $room->host ? [
                'id' => $room->host->id,
                'name' => $room->host->name,
                'email' => $room->host->email,
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
        $host = $room->host ? [[
            'id' => null,
            'user_id' => $room->host->id,
            'name' => $room->host->name,
            'email' => $room->host->email,
            'role' => 'host',
            'status' => 'active',
            'joined_at' => $room->created_at,
            'user' => $room->host,
        ]] : [];

        $members = $room->members
            ->filter(fn (RoomMember $member) => (int) $member->user_id !== (int) $room->host_id)
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

        return collect(array_merge($host, $members))->values();
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
            'status' => $liveRoom->trashed() ? 'removed' : $liveRoom->status,
            'created_at' => $liveRoom->created_at,
            'started_at' => $liveRoom->started_at,
            'finished_at' => $liveRoom->ended_at,
            'ended_at' => $liveRoom->ended_at,
            'deleted_at' => $liveRoom->deleted_at ? $liveRoom->deleted_at->toIso8601String() : null,
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

    public function banHomework(Room $room)
    {
        $room->forceFill(['status' => 'banned'])->save();

        $host = User::find($room->host_id);
        if ($host) {
            $host->notify(new RoomModerated($room->id, $room->name, 'homework', 'ban'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã khóa phòng học.',
            'data' => $room->fresh(['host:id,name,email']),
        ]);
    }

    public function unbanHomework(Room $room)
    {
        $room->forceFill(['status' => 'active'])->save();

        $host = User::find($room->host_id);
        if ($host) {
            $host->notify(new RoomModerated($room->id, $room->name, 'homework', 'unban'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã mở khóa phòng học.',
            'data' => $room->fresh(['host:id,name,email']),
        ]);
    }

    public function banLive(LiveRoom $liveRoom)
    {
        if ($liveRoom->status === 'finished' || !is_null($liveRoom->ended_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể khóa phòng thi đấu đã kết thúc.',
            ], 422);
        }

        $liveRoom = DB::transaction(function () use ($liveRoom) {
            $oldStatus = $liveRoom->status;
            $liveRoom->forceFill([
                'status' => 'banned',
                'ended_at' => $liveRoom->ended_at ?: ($oldStatus === 'playing' ? now() : null),
            ])->save();

            return $liveRoom->fresh(['host:id,name,email', 'quiz:id,title']);
        });

        if ($liveRoom->ended_at) {
            LiveRoomFinished::dispatch($liveRoom, 'admin_banned');
            LiveLeaderboardUpdated::dispatch($liveRoom);
        }

        $host = User::find($liveRoom->host_id);
        if ($host) {
            $host->notify(new RoomModerated($liveRoom->id, $liveRoom->title, 'live', 'ban'));
        }

        $liveRoom->loadCount([
            'players as player_count' => fn (Builder $query) => $query
                ->where('status', 'joined')
                ->where('user_id', '!=', $liveRoom->host_id),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã khóa trận đấu trực tuyến.',
            'data' => $this->formatLiveRoomSummary($liveRoom, true),
        ]);
    }

    public function unbanLive(LiveRoom $liveRoom)
    {
        $liveRoom = DB::transaction(function () use ($liveRoom) {
            $newStatus = $liveRoom->started_at ? 'finished' : 'waiting';
            $liveRoom->forceFill([
                'status' => $newStatus,
            ])->save();

            return $liveRoom->fresh(['host:id,name,email', 'quiz:id,title']);
        });

        $host = User::find($liveRoom->host_id);
        if ($host) {
            $host->notify(new RoomModerated($liveRoom->id, $liveRoom->title, 'live', 'unban'));
        }

        $liveRoom->loadCount([
            'players as player_count' => fn (Builder $query) => $query
                ->where('status', 'joined')
                ->where('user_id', '!=', $liveRoom->host_id),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã mở khóa phòng trực tuyến.',
            'data' => $this->formatLiveRoomSummary($liveRoom, true),
        ]);
    }
}
