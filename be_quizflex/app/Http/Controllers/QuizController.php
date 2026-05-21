<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $query = Quiz::query()
            ->with('user:id,name')
            ->withCount(['questions', 'attempts'])
            ->withAvg(['attempts as avg_score' => fn ($q) => $q->where('status', 'completed')], 'score')
            ->latest();

        if ($request->filled('search')) {
            $keyword = trim((string) $request->query('search'));
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%")
                    ->orWhere('category', 'like', "%{$keyword}%")
                    ->orWhere('tag', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $this->normalizeDifficulty($request->query('difficulty')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('visibility')) {
            $visibility = $request->query('visibility');
            if ($visibility === 'public') {
                $query->where('is_public', true)->where('status', 'published');
            } elseif ($visibility === 'private') {
                $query->where('is_public', false);
            }
        }

        $perPage = min(max((int) $request->query('per_page', 50), 1), 100);
        $quizzes = $query->paginate($perPage)->through(fn (Quiz $quiz) => $this->formatQuiz($quiz));

        return response()->json([
            'success' => true,
            'message' => 'Danh sách quiz',
            'data' => $quizzes,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateQuizPayload($request);
        $user = $this->resolveUser($request);

        $quiz = DB::transaction(function () use ($data, $user) {
            $quiz = Quiz::create($this->quizAttributes($data, $user->id));
            $this->syncQuestions($quiz, $data['questions'] ?? []);

            return $quiz->fresh(['user:id,name', 'questions.answers']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Tạo quiz thành công',
            'data' => $this->formatQuiz($quiz, true),
        ], 201);
    }

    public function show(Quiz $quiz)
    {
        $quiz->load(['user:id,name', 'questions.answers'])
            ->loadCount(['questions', 'attempts'])
            ->loadAvg(['attempts as avg_score' => fn ($q) => $q->where('status', 'completed')], 'score');

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết quiz',
            'data' => $this->formatQuiz($quiz, true),
        ]);
    }

    public function update(Request $request, Quiz $quiz)
    {
        $data = $this->validateQuizPayload($request, true);

        $quiz = DB::transaction(function () use ($quiz, $data) {
            $quiz->update($this->quizAttributes($data, $quiz->user_id));

            if (array_key_exists('questions', $data)) {
                $this->syncQuestions($quiz, $data['questions']);
            }

            return $quiz->fresh(['user:id,name', 'questions.answers']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật quiz thành công',
            'data' => $this->formatQuiz($quiz, true),
        ]);
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa mềm quiz',
        ]);
    }

    private function validateQuizPayload(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'title' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'tag' => ['nullable', 'string', 'max:100'],
            'difficulty' => ['nullable', 'string', Rule::in(['easy', 'medium', 'hard', 'Dễ', 'Vừa', 'Khó'])],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'visibility' => ['nullable', Rule::in(['public', 'private'])],
            'is_public' => ['nullable', 'boolean'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:30', 'max:86400'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'duration' => ['nullable', 'string', 'max:50'],
            'cover' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:32'],
            'badge' => ['nullable', 'string', 'max:32'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'questions' => ['nullable', 'array'],
            'questions.*.id' => ['nullable', 'integer', 'exists:questions,id'],
            'questions.*.content' => ['nullable', 'string'],
            'questions.*.text' => ['nullable', 'string'],
            'questions.*.type' => ['nullable', Rule::in(['single_choice', 'multiple_choice', 'true_false'])],
            'questions.*.points' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'questions.*.order' => ['nullable', 'integer', 'min:0'],
            'questions.*.correct' => ['nullable'],
            'questions.*.answers' => ['required_with:questions', 'array', 'min:2'],
            'questions.*.answers.*.id' => ['nullable', 'integer', 'exists:answers,id'],
            'questions.*.answers.*.content' => ['nullable', 'string'],
            'questions.*.answers.*.text' => ['nullable', 'string'],
            'questions.*.answers.*.key' => ['nullable', 'string', 'max:4'],
            'questions.*.answers.*.is_correct' => ['nullable', 'boolean'],
            'questions.*.answers.*.order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function quizAttributes(array $data, int $userId): array
    {
        $visibility = $data['visibility'] ?? null;
        $isPublic = array_key_exists('is_public', $data) ? (bool) $data['is_public'] : ($visibility === 'public');

        if ($visibility === 'private') {
            $isPublic = false;
        }

        return [
            'user_id' => $userId,
            'title' => $data['title'] ?? 'Untitled quiz',
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? 'General',
            'tag' => $data['tag'] ?? null,
            'difficulty' => $this->normalizeDifficulty($data['difficulty'] ?? 'medium'),
            'status' => $data['status'] ?? ($isPublic ? 'published' : 'draft'),
            'is_public' => $isPublic,
            'time_limit_seconds' => $this->resolveTimeLimitSeconds($data),
            'cover' => $data['cover'] ?? null,
            'icon' => $data['icon'] ?? null,
            'badge' => $data['badge'] ?? null,
        ];
    }

    private function syncQuestions(Quiz $quiz, array $questions): void
    {
        $keptQuestionIds = [];

        foreach ($questions as $index => $questionData) {
            $questionContent = trim((string) ($questionData['content'] ?? $questionData['text'] ?? ''));
            if ($questionContent === '') {
                continue;
            }

            $question = Question::updateOrCreate(
                [
                    'id' => $questionData['id'] ?? null,
                    'quiz_id' => $quiz->id,
                ],
                [
                    'content' => $questionContent,
                    'type' => $questionData['type'] ?? 'single_choice',
                    'order' => $questionData['order'] ?? $index,
                    'points' => $questionData['points'] ?? 10,
                    'image_url' => $questionData['image_url'] ?? null,
                ]
            );

            $keptQuestionIds[] = $question->id;
            $this->syncAnswers($question, $questionData['answers'] ?? [], $questionData['correct'] ?? null);
        }

        if (!empty($keptQuestionIds)) {
            $quiz->questions()->whereNotIn('id', $keptQuestionIds)->delete();
        }
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
            $answerContent = trim((string) ($answerData['content'] ?? $answerData['text'] ?? ''));
            if ($answerContent === '') {
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
                    'content' => $answerContent,
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

    private function resolveUser(Request $request): User
    {
        if ($request->user()) {
            return $request->user();
        }

        if ($request->filled('user_id')) {
            $user = User::find((int) $request->input('user_id'));
            if ($user) {
                return $user;
            }
        }

        return User::firstOrCreate(
            ['email' => 'guest@quizflex.local'],
            ['name' => 'Guest User', 'password' => bcrypt('password')]
        );
    }

    private function resolveTimeLimitSeconds(array $data): ?int
    {
        if (isset($data['time_limit_seconds'])) {
            return (int) $data['time_limit_seconds'];
        }

        if (isset($data['duration_minutes'])) {
            return (int) $data['duration_minutes'] * 60;
        }

        if (!empty($data['duration']) && preg_match('/\d+/', (string) $data['duration'], $matches)) {
            return (int) $matches[0] * 60;
        }

        return 600;
    }

    private function normalizeDifficulty(?string $difficulty): string
    {
        return match ($difficulty) {
            'Dễ', 'easy' => 'easy',
            'Khó', 'hard' => 'hard',
            default => 'medium',
        };
    }

    public function formatQuiz(Quiz $quiz, bool $includeQuestions = false): array
    {
        $timeLimit = $quiz->time_limit_seconds ?? 600;
        $visibility = $quiz->is_public ? 'public' : 'private';

        $data = [
            'id' => $quiz->id,
            'user_id' => $quiz->user_id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'category' => $quiz->category,
            'tag' => $quiz->tag ?? $quiz->category,
            'difficulty' => $quiz->difficulty,
            'difficulty_label' => $this->difficultyLabel($quiz->difficulty),
            'status' => $quiz->status,
            'is_public' => (bool) $quiz->is_public,
            'visibility' => $visibility,
            'time_limit_seconds' => $timeLimit,
            'duration_minutes' => (int) ceil($timeLimit / 60),
            'questions_count' => $quiz->questions_count ?? $quiz->questions()->count(),
            'attempts_count' => $quiz->attempts_count ?? $quiz->attempts()->count(),
            'avg_score' => round((float) ($quiz->avg_score ?? 0), 2),
            'author' => $quiz->user?->name ?? 'QuizFlex',
            'cover' => $quiz->cover ?? 'linear-gradient(135deg, #0f172a, #7c3aed)',
            'icon' => $quiz->icon ?? 'QZ',
            'badge' => $quiz->badge ?? strtoupper(substr($quiz->category ?? 'Quiz', 0, 4)),
            'created_at' => $quiz->created_at,
            'updated_at' => $quiz->updated_at,
        ];

        if ($includeQuestions) {
            $data['questions'] = $quiz->questions->map(fn (Question $question) => $this->formatQuestion($question))->values();
        }

        return $data;
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

    private function difficultyLabel(string $difficulty): string
    {
        return match ($difficulty) {
            'easy' => 'Dễ',
            'hard' => 'Khó',
            default => 'Vừa',
        };
    }
}
