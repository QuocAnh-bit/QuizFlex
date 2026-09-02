<?php

namespace App\Http\Controllers;

use App\Services\AI\QuestionReviewService;
use Illuminate\Http\Request;

final class QuestionAiReviewController extends Controller
{
    public function review(Request $request, QuestionReviewService $service)
    {
        $validated = $request->validate([
            'quiz_context' => ['required', 'array'],
            'quiz_context.grade' => ['nullable', 'string', 'max:150'],
            'quiz_context.subject' => ['nullable', 'string', 'max:150'],
            'quiz_context.topic' => ['nullable', 'string', 'max:150'],
            'quiz_context.difficulty' => ['nullable', 'string', 'max:30'],
            'question' => ['required', 'array'],
            'question.id' => ['required'],
            'question.type' => ['required', 'string'],
            'question.content' => ['required', 'string'],
            'question.difficulty' => ['nullable', 'string'],
            'question.explanation' => ['nullable', 'string'],
            'question.answers' => ['nullable', 'array'],
            'question.answers.*.content' => ['required_with:question.answers', 'string'],
            'question.answers.*.is_correct' => ['required_with:question.answers', 'boolean'],
            'question.accepted_answers' => ['nullable', 'array'],
            'question.accepted_answers.*.content' => ['required_with:question.accepted_answers', 'string'],
        ]);

        return response()->json($service->review($validated, auth('api')->id()));
    }
}
