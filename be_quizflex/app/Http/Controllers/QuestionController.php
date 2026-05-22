<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function index(Quiz $quiz)
    {
        $quiz->load('questions.answers');

        return response()->json([
            'success' => true,
            'message' => 'Danh sách câu hỏi',
            'data' => $quiz->questions->map(fn (Question $question) => $this->formatQuestion($question))->values(),
        ]);
    }

    public function store(Request $request, Quiz $quiz)
    {
        Gate::forUser(auth('api')->user())->authorize('update', $quiz);

        $data = $this->validateQuestionPayload($request);

        $question = DB::transaction(function () use ($quiz, $data) {
            $question = $quiz->questions()->create([
                'content' => $data['content'] ?? $data['text'],
                'image_url' => $data['image_url'] ?? null,
                'type' => $data['type'] ?? 'single_choice',
                'order' => $data['order'] ?? ($quiz->questions()->max('order') + 1),
                'points' => $data['points'] ?? 10,
            ]);

            $this->syncAnswers($question, $data['answers'] ?? [], $data['correct'] ?? null);

            return $question->fresh('answers');
        });

        return response()->json([
            'success' => true,
            'message' => 'Tạo câu hỏi thành công',
            'data' => $this->formatQuestion($question),
        ], 201);
    }

    public function show(Question $question)
    {
        $question->load('answers');

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết câu hỏi',
            'data' => $this->formatQuestion($question),
        ]);
    }

    public function update(Request $request, Question $question)
    {
        $question->loadMissing('quiz');
        Gate::forUser(auth('api')->user())->authorize('update', $question->quiz);

        $data = $this->validateQuestionPayload($request, true);

        $question = DB::transaction(function () use ($question, $data) {
            $question->update([
                'content' => $data['content'] ?? $data['text'] ?? $question->content,
                'image_url' => $data['image_url'] ?? $question->image_url,
                'type' => $data['type'] ?? $question->type,
                'order' => $data['order'] ?? $question->order,
                'points' => $data['points'] ?? $question->points,
            ]);

            if (array_key_exists('answers', $data)) {
                $this->syncAnswers($question, $data['answers'], $data['correct'] ?? null);
            }

            return $question->fresh('answers');
        });

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật câu hỏi thành công',
            'data' => $this->formatQuestion($question),
        ]);
    }

    public function destroy(Question $question)
    {
        $question->loadMissing('quiz');
        Gate::forUser(auth('api')->user())->authorize('update', $question->quiz);

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa câu hỏi',
        ]);
    }

    private function validateQuestionPayload(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'content' => [$isUpdate ? 'nullable' : 'required_without:text', 'string'],
            'text' => [$isUpdate ? 'nullable' : 'required_without:content', 'string'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['single_choice', 'multiple_choice', 'true_false'])],
            'order' => ['nullable', 'integer', 'min:0'],
            'points' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'correct' => ['nullable'],
            'answers' => [$isUpdate ? 'nullable' : 'required', 'array', 'min:2'],
            'answers.*.id' => ['nullable', 'integer', 'exists:answers,id'],
            'answers.*.content' => ['nullable', 'string'],
            'answers.*.text' => ['nullable', 'string'],
            'answers.*.key' => ['nullable', 'string', 'max:4'],
            'answers.*.is_correct' => ['nullable', 'boolean'],
            'answers.*.order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function syncAnswers(Question $question, array $answers, mixed $correct): void
    {
        $correctKeys = collect(is_array($correct) ? $correct : [$correct])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => strtoupper((string) $value))
            ->values()
            ->all();

        $keptAnswerIds = [];

        foreach ($answers as $index => $answerData) {
            $content = trim((string) ($answerData['content'] ?? $answerData['text'] ?? ''));
            if ($content === '') {
                continue;
            }

            $key = strtoupper((string) ($answerData['key'] ?? chr(65 + $index)));
            $isCorrect = array_key_exists('is_correct', $answerData)
                ? (bool) $answerData['is_correct']
                : in_array($key, $correctKeys, true);

            $answer = Answer::updateOrCreate(
                [
                    'id' => $answerData['id'] ?? null,
                    'question_id' => $question->id,
                ],
                [
                    'content' => $content,
                    'is_correct' => $isCorrect,
                    'order' => $answerData['order'] ?? $index,
                ]
            );

            $keptAnswerIds[] = $answer->id;
        }

        if (!empty($keptAnswerIds)) {
            $question->answers()->whereNotIn('id', $keptAnswerIds)->delete();
        }
    }

    private function formatQuestion(Question $question): array
    {
        return [
            'id' => $question->id,
            'quiz_id' => $question->quiz_id,
            'content' => $question->content,
            'text' => $question->content,
            'image_url' => $question->image_url,
            'type' => $question->type,
            'order' => $question->order,
            'points' => $question->points,
            'answers' => $question->answers->map(fn (Answer $answer, int $index) => [
                'id' => $answer->id,
                'question_id' => $answer->question_id,
                'content' => $answer->content,
                'text' => $answer->content,
                'answer_key' => chr(65 + ($answer->order ?? $index)),
                'key' => chr(65 + ($answer->order ?? $index)),
                'is_correct' => (bool) $answer->is_correct,
                'order' => $answer->order,
            ])->values(),
        ];
    }
}
