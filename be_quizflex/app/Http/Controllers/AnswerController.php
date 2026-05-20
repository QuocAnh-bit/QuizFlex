<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\Request;

class AnswerController extends Controller
{
    public function store(Request $request, Question $question)
    {
        $data = $request->validate([
            'content' => ['required_without:text', 'nullable', 'string'],
            'text' => ['required_without:content', 'nullable', 'string'],
            'is_correct' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $answer = $question->answers()->create([
            'content' => $data['content'] ?? $data['text'],
            'is_correct' => $data['is_correct'] ?? false,
            'order' => $data['order'] ?? ($question->answers()->max('order') + 1),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo đáp án thành công',
            'data' => $answer,
        ], 201);
    }

    public function update(Request $request, Answer $answer)
    {
        $data = $request->validate([
            'content' => ['nullable', 'string'],
            'text' => ['nullable', 'string'],
            'is_correct' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $answer->update([
            'content' => $data['content'] ?? $data['text'] ?? $answer->content,
            'is_correct' => $data['is_correct'] ?? $answer->is_correct,
            'order' => $data['order'] ?? $answer->order,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật đáp án thành công',
            'data' => $answer,
        ]);
    }

    public function destroy(Answer $answer)
    {
        $answer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa đáp án',
        ]);
    }
}
