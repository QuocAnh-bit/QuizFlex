<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Models\RoomMember;
use App\Models\RoomMemberEvaluation;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class HomeworkRoomMemberEvaluationController extends Controller
{
    /**
     * Display the member's evaluation in the room.
     */
    public function show(Request $request, Room $room, User $user)
    {
        $currentUser = $request->user();

        // 1. Authorization check: Admin, Room Owner, or the user themselves
        $isAdmin = strtolower((string) ($currentUser->role ?? 'user')) === 'admin';
        $isOwner = (int) $room->owner_id === (int) $currentUser->id;
        $isSelf = (int) $user->id === (int) $currentUser->id;

        if (!$isAdmin && !$isOwner && !$isSelf) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem đánh giá này.',
            ], 403);
        }

        // 2. Verify target user is an active member in this room
        $isMember = RoomMember::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (!$isMember) {
            return response()->json([
                'success' => false,
                'message' => 'Thành viên này không thuộc phòng học.',
            ], 404);
        }

        // 3. Retrieve evaluation
        $evaluation = RoomMemberEvaluation::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        // 4. Retrieve and format submission evaluations
        $submissions = QuizAttempt::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->whereNotNull('assignment_id')
            ->with(['assignment', 'evaluation', 'quiz'])
            ->latest('started_at')
            ->get();

        $submissionEvaluations = $submissions->map(fn (QuizAttempt $attempt) => [
            'id' => $attempt->id,
            'assignment_name' => $attempt->assignment->title ?? $attempt->quiz->title ?? 'Bài tập',
            'score' => $attempt->total_points > 0 ? $attempt->score . '/' . $attempt->total_points : $attempt->score,
            'submitted_at' => $attempt->submitted_at ?: $attempt->finished_at ?: $attempt->created_at,
            'comment' => $attempt->evaluation->comment ?? null,
        ]);

        $data = $evaluation ? $evaluation->toArray() : [
            'id' => null,
            'room_id' => $room->id,
            'user_id' => $user->id,
            'comment' => null,
        ];
        $data['submission_evaluations'] = $submissionEvaluations;

        return response()->json([
            'success' => true,
            'message' => 'Thông tin đánh giá thành viên.',
            'data' => $data,
        ]);
    }

    /**
     * Store or update evaluation for the member.
     */
    public function store(Request $request, Room $room, User $user)
    {
        $currentUser = $request->user();

        // 1. Authorization check: Only Room Owner or Admin can write evaluation
        $isAdmin = strtolower((string) ($currentUser->role ?? 'user')) === 'admin';
        $isOwner = (int) $room->owner_id === (int) $currentUser->id;

        if (!$isAdmin && !$isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ phòng mới có quyền lưu đánh giá.',
            ], 403);
        }

        // 2. Verify target user is an active member in this room
        $isMember = RoomMember::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (!$isMember) {
            return response()->json([
                'success' => false,
                'message' => 'Thành viên này không thuộc phòng học.',
            ], 404);
        }

        // 3. Validation
        $data = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // 4. Save/Update evaluation
        $evaluation = RoomMemberEvaluation::updateOrCreate(
            [
                'room_id' => $room->id,
                'user_id' => $user->id,
            ],
            [
                'evaluator_id' => $currentUser->id,
                'comment' => $data['comment'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Lưu đánh giá thành công.',
            'data' => $evaluation,
        ]);
    }
}
