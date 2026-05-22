<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\RoomMember;
use Illuminate\Http\Request;

class RoomAssignmentController extends Controller
{
    public function index(Request $request, Room $room)
    {
        $user = $request->user();

        if (!$this->isRoomMemberOrHost($room, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem bài tập trong room này.',
            ], 403);
        }

        $assignments = RoomAssignment::query()
            ->where('room_id', $room->id)
            ->with(['quiz:id,title,category,difficulty,time_limit_seconds,is_public', 'assigner:id,name'])
            ->withCount('submissions')
            ->latest()
            ->get()
            ->map(fn($assignment) => $this->formatAssignment($assignment));

        return response()->json([
            'success' => true,
            'message' => 'Danh sách bài tập trong room',
            'data' => $assignments,
        ]);
    }

    public function store(Request $request, Room $room)
    {
        $user = $request->user();

        if (!$this->canManageRoom($room, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ room mới được giao bài.',
            ], 403);
        }

        $data = $request->validate([
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
            'show_result_mode' => 'nullable|in:immediately,after_submit,after_deadline,manual',
            'status' => ['nullable', 'in:draft,published,closed'],
        ]);

        $quiz = Quiz::withCount('questions')->findOrFail($data['quiz_id']);

        if ($quiz->questions_count <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz này chưa có câu hỏi, không thể giao bài.',
            ], 422);
        }

        $assignment = RoomAssignment::create([
            'room_id' => $room->id,
            'quiz_id' => $quiz->id,
            'assigned_by' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'deadline_at' => $data['deadline_at'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'max_attempts' => $data['max_attempts'] ?? 1,
            'show_result_mode' => $data['show_result_mode'] ?? 'after_submit',
            'status' => $data['status'] ?? 'published',
        ]);

        $assignment->load(['quiz:id,title,category,difficulty,time_limit_seconds,is_public', 'assigner:id,name'])
            ->loadCount('submissions');

        return response()->json([
            'success' => true,
            'message' => 'Giao bài thành công',
            'data' => $this->formatAssignment($assignment),
        ], 201);
    }

    public function show(Request $request, Room $room, RoomAssignment $assignment)
    {
        $user = $request->user();

        if ((int) $assignment->room_id !== (int) $room->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bài tập không thuộc room này.',
            ], 404);
        }

        if (!$this->isRoomMemberOrHost($room, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem bài tập này.',
            ], 403);
        }

        $assignment->load([
            'quiz:id,title,description,category,difficulty,time_limit_seconds,is_public',
            'quiz.questions:id,quiz_id',
            'assigner:id,name'
        ])->loadCount('submissions');

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết bài tập',
            'data' => [
                ...$this->formatAssignment($assignment),
                'quiz' => [
                    'id' => $assignment->quiz->id,
                    'title' => $assignment->quiz->title,
                    'description' => $assignment->quiz->description,
                    'category' => $assignment->quiz->category,
                    'difficulty' => $assignment->quiz->difficulty,
                    'time_limit_seconds' => $assignment->quiz->time_limit_seconds,
                    'questions_count' => $assignment->quiz->questions->count(),
                ],
            ],
        ]);
    }

    private function canManageRoom(Room $room, int $userId): bool
    {
        return (int) $room->host_id === (int) $userId;
    }

    private function isRoomMemberOrHost(Room $room, int $userId): bool
    {
        if ((int) $room->host_id === (int) $userId) {
            return true;
        }

        return RoomMember::where('room_id', $room->id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    private function formatAssignment(RoomAssignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'room_id' => $assignment->room_id,
            'quiz_id' => $assignment->quiz_id,
            'quiz_title' => $assignment->quiz?->title,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'assigned_by' => $assignment->assigned_by,
            'assigned_by_name' => $assignment->assigner?->name,
            'starts_at' => optional($assignment->starts_at)->toISOString(),
            'deadline_at' => optional($assignment->deadline_at)->toISOString(),
            'duration_minutes' => $assignment->duration_minutes,
            'max_attempts' => $assignment->max_attempts,
            'show_result_mode' => $assignment->show_result_mode,
            'status' => $assignment->status,
            'submissions_count' => $assignment->submissions_count ?? null,
            'created_at' => optional($assignment->created_at)->toISOString(),
        ];
    }
}
