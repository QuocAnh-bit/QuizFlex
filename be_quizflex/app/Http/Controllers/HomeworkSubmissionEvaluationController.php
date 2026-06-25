<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\QuizAttempt;
use App\Models\RoomSubmissionEvaluation;
use Illuminate\Http\Request;

class HomeworkSubmissionEvaluationController extends Controller
{
    /**
     * Display the submission-level evaluation.
     */
    public function show(Request $request, Room $room, QuizAttempt $submission)
    {
        $currentUser = $request->user();

        // 1. Authorization: Admin, Room Owner, or the student themselves
        $isAdmin = strtolower((string) ($currentUser->role ?? 'user')) === 'admin';
        $isOwner = (int) $room->owner_id === (int) $currentUser->id;
        $isSelf = (int) $submission->user_id === (int) $currentUser->id;

        if (!$isAdmin && !$isOwner && !$isSelf) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem nhận xét này.',
            ], 403);
        }

        // 2. Validate relationship
        if ((int) $submission->room_id !== (int) $room->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bài nộp không thuộc phòng học này.',
            ], 422);
        }

        // 3. Get evaluation
        $evaluation = RoomSubmissionEvaluation::where('submission_id', $submission->id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Thông tin nhận xét bài nộp.',
            'data' => $evaluation,
        ]);
    }

    /**
     * Create or update the submission-level evaluation.
     */
    public function store(Request $request, Room $room, QuizAttempt $submission)
    {
        $currentUser = $request->user();

        // 1. Authorization: Only Owner or Admin
        $isAdmin = strtolower((string) ($currentUser->role ?? 'user')) === 'admin';
        $isOwner = (int) $room->owner_id === (int) $currentUser->id;

        if (!$isAdmin && !$isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ phòng mới có quyền lưu nhận xét bài nộp.',
            ], 403);
        }

        // 2. Validate relationship
        if ((int) $submission->room_id !== (int) $room->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bài nộp không thuộc phòng học này.',
            ], 422);
        }

        // 3. Validation
        $data = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // 4. Save or Update using unique submission_id
        $evaluation = RoomSubmissionEvaluation::updateOrCreate(
            ['submission_id' => $submission->id],
            [
                'room_id' => $room->id,
                'user_id' => $submission->user_id,
                'evaluator_id' => $currentUser->id,
                'comment' => $data['comment'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Lưu nhận xét bài nộp thành công.',
            'data' => $evaluation,
        ]);
    }
}
