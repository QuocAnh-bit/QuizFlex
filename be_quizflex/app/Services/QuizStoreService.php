<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizStoreService
{
    public function createQuizWithQuestions(array $data, User $user): Quiz
    {
        $this->validateNormalizedData($data);

        return DB::transaction(function () use ($data, $user) {
            $quiz = Quiz::create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'subject' => $data['subject'] ?? null,
                'grade' => $data['grade'] ?? null,
                'time_limit_seconds' => ($data['duration'] ?? 45) * 60,
                'status' => $data['status'] ?? 'draft',
            ]);

            foreach ($data['questions'] as $questionIndex => $questionData) {
                $question = $quiz->questions()->create([
                    'content' => $questionData['content'],
                    'image_url' => $questionData['image_url'] ?? null,
                    'type' => $questionData['type'] ?? 'single_choice',
                    'order' => $questionIndex + 1,
                    'points' => $questionData['points'] ?? 10,
                ]);

                foreach ($questionData['answers'] as $answerIndex => $answerData) {
                    $question->answers()->create([
                        'content' => $answerData['content'],
                        'is_correct' => (bool) ($answerData['is_correct'] ?? false),
                        'order' => $answerIndex,
                    ]);
                }
            }

            return $quiz->fresh([
                'questions' => fn($query) => $query->orderBy('order'),
                'questions.answers' => fn($query) => $query->orderBy('order'),
            ]);
        });
    }

    public function normalizeOcrPayload(array $payload): array
    {
        $quiz = $payload['quiz'] ?? [];

        return [
            'title' => $quiz['title'] ?? 'Bộ đề OCR',
            'description' => $quiz['description'] ?? null,
            'subject' => $quiz['subject'] ?? null,
            'grade' => $quiz['grade'] ?? null,
            'duration' => $quiz['duration'] ?? 45,
            'status' => $quiz['status'] ?? 'draft',
            'questions' => collect($payload['questions'] ?? [])
                ->map(fn($item) => $this->normalizeOcrQuestion($item))
                ->filter(fn($item) => !empty($item['content']))
                ->values()
                ->toArray(),
        ];
    }

    public function normalizeOcrQuestion(array $item): array
    {
        $type = $item['type'] ?? 'single_choice';

        if (!in_array($type, ['single_choice', 'multi_choice', 'fill_blank'])) {
            $type = 'single_choice';
        }

        $correct = $item['correct_answer'] ?? null;
        $correctList = is_array($correct) ? $correct : [$correct];

        $answers = [];

        if ($type === 'fill_blank') {
            foreach ($correctList as $answer) {
                if ($answer === null || trim((string) $answer) === '') {
                    continue;
                }

                $answers[] = [
                    'content' => trim((string) $answer),
                    'is_correct' => true,
                ];
            }
        } else {
            $options = $item['options'] ?? [];

            foreach ($options as $key => $content) {
                $answers[] = [
                    'content' => trim((string) $content),
                    'is_correct' => in_array($key, $correctList),
                ];
            }
        }

        return [
            'content' => trim((string) ($item['question'] ?? $item['content'] ?? '')),
            'type' => $type,
            'points' => $item['points'] ?? 10,
            'image_url' => $item['images'][0] ?? $item['image_url'] ?? null,
            'answers' => $answers,
        ];
    }

    private function validateNormalizedData(array $data): void
    {
        if (empty($data['title'])) {
            throw ValidationException::withMessages([
                'title' => 'Tiêu đề quiz không được để trống.',
            ]);
        }

        if (empty($data['questions']) || !is_array($data['questions'])) {
            throw ValidationException::withMessages([
                'questions' => 'Quiz phải có ít nhất 1 câu hỏi.',
            ]);
        }

        foreach ($data['questions'] as $index => $question) {
            if (empty($question['content'])) {
                throw ValidationException::withMessages([
                    "questions.$index.content" => 'Nội dung câu hỏi không được để trống.',
                ]);
            }

            if (!in_array($question['type'], ['single_choice', 'multi_choice', 'fill_blank'])) {
                throw ValidationException::withMessages([
                    "questions.$index.type" => 'Loại câu hỏi không hợp lệ.',
                ]);
            }

            if (empty($question['answers'])) {
                throw ValidationException::withMessages([
                    "questions.$index.answers" => 'Câu hỏi phải có đáp án.',
                ]);
            }

            $correctCount = collect($question['answers'])
                ->where('is_correct', true)
                ->count();

            if ($question['type'] === 'single_choice' && $correctCount !== 1) {
                throw ValidationException::withMessages([
                    "questions.$index.correct_answer" => 'Câu chọn 1 đáp án phải có đúng 1 đáp án đúng.',
                ]);
            }

            if ($question['type'] === 'multi_choice' && $correctCount < 1) {
                throw ValidationException::withMessages([
                    "questions.$index.correct_answer" => 'Câu nhiều đáp án phải có ít nhất 1 đáp án đúng.',
                ]);
            }

            if ($question['type'] === 'fill_blank' && $correctCount < 1) {
                throw ValidationException::withMessages([
                    "questions.$index.correct_answer" => 'Câu điền đáp án phải có ít nhất 1 đáp án đúng.',
                ]);
            }
        }
    }
}
