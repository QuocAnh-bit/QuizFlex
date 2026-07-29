<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\QuizAttempt;
use App\Models\RoomSubmissionEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HomeworkSubmissionEvaluationController extends Controller
{
    /**
     * Display the submission-level evaluation.
     */
    public function show(Request $request, Room $room, QuizAttempt $submission)
    {
        $currentUser = $request->user();

        // 1. Authorization: Admin, Room Host, or the student themselves
        Gate::forUser($currentUser)->authorize('view', $room);
        $viewAuthorization = Gate::forUser($currentUser)->inspect('viewSubmissionEvaluation', [$room, $submission]);
        if ($viewAuthorization->denied()) {
            return response()->json([
                'success' => false,
                'message' => $viewAuthorization->message(),
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
        if ($room->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        $currentUser = $request->user();

        // 1. Authorization: Only Host
        Gate::forUser($currentUser)->authorize('manageMembers', $room);

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
