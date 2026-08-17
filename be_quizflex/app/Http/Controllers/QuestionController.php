<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\QuestionModerated;
use App\Notifications\ReportAuthorUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function index(Quiz $quiz)
    {
        $quiz->load('questions.answers');

        return response()->json([
            'success' => true,
            'message' => 'Danh sách câu hỏi',
            'data' => $quiz->questions->map(fn(Question $question) => $this->formatQuestion($question))->values(),
        ]);
    }

    /**
     * Scope lọc chỉ lấy các câu hỏi công khai thuộc ngân hàng chung hoặc bài thi công khai đã xuất bản
     */
    private function applyPublicQuestionScope($query)
    {
        return $query->where('is_public', true)
            ->where(function ($q) {
                $q->whereNull('quiz_id')
                    ->orWhereHas('quiz', fn($sq) => $sq->where('is_public', true)->where('status', 'published'))
                    ->orWhereHas('quizzes', fn($sq) => $sq->where('is_public', true)->where('status', 'published'));
            });
    }

    /**
     * Tra cứu Ngân hàng câu hỏi mở rộng (Lọc & Bốc ngẫu nhiên)
     */
    public function bank(Request $request)
    {
        $query = Question::query()
            ->with(['answers', 'quiz:id,title,user_id', 'educationLevel', 'grade', 'subject']);

        $this->applyPublicQuestionScope($query);

        if ($request->filled('search')) {
            $keyword = trim((string) $request->query('search'));
            $query->where('content', 'like', "%{$keyword}%");
        }

        if ($request->filled('education_level_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('education_level_id', $request->query('education_level_id'))
                    ->orWhereHas('quiz', fn($sq) => $sq->where('education_level_id', $request->query('education_level_id')));
            });
        }

        if ($request->filled('grade_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('grade_id', $request->query('grade_id'))
                    ->orWhereHas('quiz', fn($sq) => $sq->where('grade_id', $request->query('grade_id')));
            });
        }

        if ($request->filled('subject_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('subject_id', $request->query('subject_id'))
                    ->orWhereHas('quiz', fn($sq) => $sq->where('subject_id', $request->query('subject_id')));
            });
        }

        if ($request->filled('topic_name')) {
            $query->where('topic_name', 'like', '%' . trim((string)$request->query('topic_name')) . '%');
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->query('difficulty'));
        }

        // Bốc ngẫu nhiên nếu có cờ random
        if ($request->boolean('random')) {
            $limit = min(max((int) $request->query('limit', 10), 1), 100);
            $questions = $query->inRandomOrder()->take($limit)->get()->map(fn(Question $q) => $this->formatQuestion($q));

            return response()->json([
                'success' => true,
                'message' => "Lấy ngẫu nhiên {$questions->count()} câu hỏi",
                'data' => $questions,
            ]);
        }

        $query->latest();
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);
        $questions = $query->paginate($perPage)->through(fn(Question $q) => $this->formatQuestion($q));

        return response()->json([
            'success' => true,
            'message' => 'Ngân hàng câu hỏi',
            'data' => $questions,
        ]);
    }

    /**
     * Thống kê danh sách Chuyên đề (Topics) khả dụng kèm số lượng câu hỏi
     */
    public function topics(Request $request)
    {
        $query = Question::query()
            ->whereNotNull('topic_name')
            ->where('topic_name', '!=', '');

        $this->applyPublicQuestionScope($query);

        if ($request->filled('education_level_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('education_level_id', $request->query('education_level_id'))
                    ->orWhereHas('quiz', fn($sq) => $sq->where('education_level_id', $request->query('education_level_id')));
            });
        }

        if ($request->filled('grade_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('grade_id', $request->query('grade_id'))
                    ->orWhereHas('quiz', fn($sq) => $sq->where('grade_id', $request->query('grade_id')));
            });
        }

        if ($request->filled('subject_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('subject_id', $request->query('subject_id'))
                    ->orWhereHas('quiz', fn($sq) => $sq->where('subject_id', $request->query('subject_id')));
            });
        }

        $topics = $query->select('topic_name', DB::raw('count(*) as total_questions'))
            ->groupBy('topic_name')
            ->orderByDesc('total_questions')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $topics,
        ]);
    }

    /**
     * Thống kê số lượng câu hỏi khả dụng phân theo độ khó (Dễ, Vừa, Khó) cho Phạm vi Đề thi
     */
    public function stats(Request $request)
    {
        $query = Question::query();

        $this->applyPublicQuestionScope($query);

        if ($request->filled('education_level_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('education_level_id', $request->query('education_level_id'))
                    ->orWhereHas('quiz', fn($sq) => $sq->where('education_level_id', $request->query('education_level_id')));
            });
        }

        if ($request->filled('grade_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('grade_id', $request->query('grade_id'))
                    ->orWhereHas('quiz', fn($sq) => $sq->where('grade_id', $request->query('grade_id')));
            });
        }

        if ($request->filled('subject_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('subject_id', $request->query('subject_id'))
                    ->orWhereHas('quiz', fn($sq) => $sq->where('subject_id', $request->query('subject_id')));
            });
        }

        if ($request->filled('topic_name')) {
            $query->where('topic_name', 'like', '%' . trim((string)$request->query('topic_name')) . '%');
        }

        $easyCount = (clone $query)->where('difficulty', 'easy')->count();
        $mediumCount = (clone $query)->where('difficulty', 'medium')->count();
        $hardCount = (clone $query)->where('difficulty', 'hard')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'easy' => $easyCount,
                'medium' => $mediumCount,
                'hard' => $hardCount,
                'total' => $easyCount + $mediumCount + $hardCount,
            ],
        ]);
    }

    /**
     * Tạo Quiz mới từ các câu hỏi trong Question Bank (Thủ công hoặc Ma trận Phân bổ Độ khó)
     */
    public function createQuizFromBank(Request $request)
    {
        if ($request->has('shuffle_questions')) {
            $request->merge(['shuffle_questions' => filter_var($request->input('shuffle_questions'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $request->boolean('shuffle_questions')]);
        }
        if ($request->has('is_public')) {
            $request->merge(['is_public' => filter_var($request->input('is_public'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $request->boolean('is_public')]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'education_level_id' => ['nullable', 'integer', 'exists:education_levels,id'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'topic_name' => ['nullable', 'string', 'max:150'],
            'quiz_topic_name' => ['nullable', 'string', 'max:150'],
            'difficulty' => ['nullable', 'string', Rule::in(['easy', 'medium', 'hard'])],
            'question_ids' => ['nullable', 'array'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
            'random_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'easy_count' => ['nullable', 'integer', 'min:0', 'max:50'],
            'medium_count' => ['nullable', 'integer', 'min:0', 'max:50'],
            'hard_count' => ['nullable', 'integer', 'min:0', 'max:50'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', Rule::in(['published', 'draft'])],
            'tag' => ['nullable', 'string', 'max:255'],
            'cover' => ['nullable', 'string', 'max:2048'],
            'cover_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'icon' => ['nullable', 'string', 'max:32'],
            'badge' => ['nullable', 'string', 'max:32'],
        ]);

        $coverUrl = null;
        if ($request->hasFile('cover_file')) {
            $path = $request->file('cover_file')->store('quiz-covers', 'public');
            $coverUrl = url(\Illuminate\Support\Facades\Storage::url($path));
        } elseif (!empty($validated['cover'])) {
            $coverUrl = $validated['cover'];
        }

        $user = auth('api')->user() ?? Quiz::first()?->user;
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Bạn cần đăng nhập.'], 401);
        }

        $questionIds = collect($validated['question_ids'] ?? [])->unique()->values()->all();

        $easyCount = (int)($validated['easy_count'] ?? 0);
        $mediumCount = (int)($validated['medium_count'] ?? 0);
        $hardCount = (int)($validated['hard_count'] ?? 0);

        // TH1: Sử dụng Cấu trúc Phân bổ Độ khó (Easy / Medium / Hard Breakdown)
        if (empty($questionIds) && ($easyCount > 0 || $mediumCount > 0 || $hardCount > 0)) {
            $baseQuery = function () use ($validated) {
                $q = Question::query();
                $this->applyPublicQuestionScope($q);

                if (!empty($validated['education_level_id'])) {
                    $q->where(function ($sub) use ($validated) {
                        $sub->where('education_level_id', $validated['education_level_id'])
                            ->orWhereHas('quiz', fn($sq) => $sq->where('education_level_id', $validated['education_level_id']));
                    });
                }
                if (!empty($validated['grade_id'])) {
                    $q->where(function ($sub) use ($validated) {
                        $sub->where('grade_id', $validated['grade_id'])
                            ->orWhereHas('quiz', fn($sq) => $sq->where('grade_id', $validated['grade_id']));
                    });
                }
                if (!empty($validated['subject_id'])) {
                    $q->where(function ($sub) use ($validated) {
                        $sub->where('subject_id', $validated['subject_id'])
                            ->orWhereHas('quiz', fn($sq) => $sq->where('subject_id', $validated['subject_id']));
                    });
                }
                if (!empty($validated['topic_name'])) {
                    $q->where('topic_name', 'like', '%' . $validated['topic_name'] . '%');
                }
                return $q;
            };

            $easyIds = $easyCount > 0 ? $baseQuery()->where('difficulty', 'easy')->inRandomOrder()->take($easyCount)->pluck('id')->all() : [];
            $mediumIds = $mediumCount > 0 ? $baseQuery()->where('difficulty', 'medium')->inRandomOrder()->take($mediumCount)->pluck('id')->all() : [];
            $hardIds = $hardCount > 0 ? $baseQuery()->where('difficulty', 'hard')->inRandomOrder()->take($hardCount)->pluck('id')->all() : [];

            $sampled = collect(array_merge($easyIds, $mediumIds, $hardIds))->unique()->values();
            if (!empty($validated['shuffle_questions'])) {
                $sampled = $sampled->shuffle();
            }
            $questionIds = $sampled->all();
        }
        // TH2: Sử dụng bốc ngẫu nhiên tổng số N câu đơn thuần
        elseif (empty($questionIds) && !empty($validated['random_count'])) {
            $query = Question::query();
            $this->applyPublicQuestionScope($query);

            if (!empty($validated['education_level_id'])) {
                $query->where('education_level_id', $validated['education_level_id']);
            }
            if (!empty($validated['grade_id'])) {
                $query->where('grade_id', $validated['grade_id']);
            }
            if (!empty($validated['subject_id'])) {
                $query->where('subject_id', $validated['subject_id']);
            }
            if (!empty($validated['topic_name'])) {
                $query->where('topic_name', 'like', '%' . $validated['topic_name'] . '%');
            }
            if (!empty($validated['difficulty'])) {
                $query->where('difficulty', $validated['difficulty']);
            }

            $sampled = $query->inRandomOrder()->take((int)$validated['random_count'])->pluck('id');
            if (!empty($validated['shuffle_questions'])) {
                $sampled = $sampled->shuffle();
            }
            $questionIds = $sampled->all();
        }

        if (empty($questionIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng chọn ít nhất 1 câu hỏi hoặc không tìm thấy câu hỏi thỏa điều kiện cấu trúc.',
            ], 422);
        }

        $timeLimitSeconds = !empty($validated['time_limit_minutes'])
            ? ((int)$validated['time_limit_minutes']) * 60
            : max(count($questionIds) * 60, 600);

        $isPublic = isset($validated['is_public']) ? (bool)$validated['is_public'] : true;
        $status = $validated['status'] ?? ($isPublic ? 'published' : 'draft');
        $displayTopicName = $validated['quiz_topic_name'] ?? $validated['topic_name'] ?? null;

        $quiz = DB::transaction(function () use ($validated, $user, $questionIds, $timeLimitSeconds, $isPublic, $status, $displayTopicName, $coverUrl) {
            $quiz = Quiz::create([
                'user_id' => $user->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? 'Bộ đề thi được tạo tự động từ Ngân hàng câu hỏi',
                'education_level_id' => $validated['education_level_id'] ?? null,
                'grade_id' => $validated['grade_id'] ?? null,
                'subject_id' => $validated['subject_id'] ?? null,
                'topic_name' => $displayTopicName,
                'tag' => $validated['tag'] ?? null,
                'difficulty' => $validated['difficulty'] ?? 'medium',
                'category' => $displayTopicName ?? 'General',
                'status' => $status,
                'is_public' => $isPublic,
                'time_limit_seconds' => $timeLimitSeconds,
                'cover' => $coverUrl,
                'badge' => $validated['badge'] ?? 'AUTO',
                'icon' => $validated['icon'] ?? '🎯',
            ]);

            $syncData = [];
            foreach ($questionIds as $index => $qId) {
                $syncData[$qId] = [
                    'order' => $index,
                    'points' => 10,
                ];
            }

            $quiz->questions()->sync($syncData);

            return $quiz->fresh(['user:id,name', 'educationLevel', 'grade', 'subject', 'questions.answers']);
        });

        $quizController = new QuizController();
        return response()->json([
            'success' => true,
            'message' => 'Tạo Quiz ma trận từ Ngân hàng câu hỏi thành công!',
            'data' => $quizController->formatQuiz($quiz, true),
        ], 201);
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

        $user = auth('api')->user();
        if ($user) {
            $this->notifyAdminsIfAuthorUpdatedContent($question, $user);
        }

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
            'type' => ['nullable', Rule::in(['single_choice', 'multi_choice', 'fill_blank'])],
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
            ->filter(fn($value) => $value !== null && $value !== '')
            ->map(fn($value) => strtoupper((string) $value))
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
        $pendingReport = \App\Models\ReportTicket::where('question_id', $question->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        $latestReport = $pendingReport ?? \App\Models\ReportTicket::where('question_id', $question->id)->latest()->first();
        $hasPendingReport = $pendingReport !== null;
        $isLockedByAdmin = !$question->is_public && ($hasPendingReport || ($latestReport !== null && $latestReport->status !== 'dismissed'));

        return [
            'id' => $question->id,
            'user_id' => $question->user_id,
            'quiz_id' => $question->quiz_id,
            'quiz_title' => $question->quiz?->title,
            'content' => $question->content,
            'text' => $question->content,
            'image_url' => $question->image_url,
            'type' => $question->type,
            'difficulty' => $question->difficulty ?? 'medium',
            'education_level_id' => $question->education_level_id,
            'education_level_name' => $question->educationLevel?->name,
            'grade_id' => $question->grade_id,
            'grade_name' => $question->grade?->name,
            'subject_id' => $question->subject_id,
            'subject_name' => $question->subject?->name,
            'topic_name' => $question->topic_name,
            'is_public' => (bool) $question->is_public,
            'has_report' => $hasPendingReport,
            'is_locked_by_admin' => $isLockedByAdmin,
            'report_reason' => $pendingReport?->reason ?? $pendingReport?->description ?? ($isLockedByAdmin ? ($latestReport?->reason ?? $latestReport?->description) : null),
            'order' => $question->order,
            'points' => $question->points ?? 10,
            'created_at' => $question->created_at,
            'answers' => $question->answers->map(fn(Answer $answer, int $index) => [
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

    /**
     * Danh sách Kho câu hỏi cá nhân của user
     */
    public function userBank(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $query = Question::query()
            ->with(['answers', 'quiz:id,title,user_id', 'educationLevel', 'grade', 'subject'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('quiz', fn($sq) => $sq->where('user_id', $user->id));
            });

        if ($request->filled('question_id')) {
            $query->where('id', $request->query('question_id'));
        }

        if ($request->filled('search')) {
            $keyword = trim((string) $request->query('search'));
            $query->where('content', 'like', "%{$keyword}%");
        }

        if ($request->filled('education_level_id')) {
            $query->where('education_level_id', $request->query('education_level_id'));
        }

        if ($request->filled('grade_id')) {
            $query->where('grade_id', $request->query('grade_id'));
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->query('subject_id'));
        }

        if ($request->filled('topic_name')) {
            $query->where('topic_name', 'like', '%' . trim((string)$request->query('topic_name')) . '%');
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->query('difficulty'));
        }

        $query->latest();
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);
        $questions = $query->paginate($perPage)->through(fn(Question $q) => $this->formatQuestion($q));

        return response()->json([
            'success' => true,
            'message' => 'Kho câu hỏi cá nhân',
            'data' => $questions,
        ]);
    }

    /**
     * Thùng rác câu hỏi cá nhân của user
     */
    public function userTrash(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $questions = Question::onlyTrashed()
            ->with(['answers', 'educationLevel', 'grade', 'subject'])
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn(Question $q) => $this->formatQuestion($q));

        return response()->json([
            'success' => true,
            'message' => 'Thùng rác câu hỏi cá nhân',
            'data' => $questions,
        ]);
    }

    /**
     * Cập nhật câu hỏi cá nhân
     */
    public function updateQuestion(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $question = Question::with('answers')->find($id);
        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy câu hỏi.'], 404);
        }

        if ($question->user_id !== $user->id && strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền sửa câu hỏi này.'], 403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string'],
            'difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
            'points' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'education_level_id' => ['nullable', 'integer'],
            'grade_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'topic_name' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
            'answers' => ['required', 'array', 'min:2'],
            'answers.*.content' => ['required', 'string'],
            'answers.*.key' => ['nullable', 'string'],
            'answers.*.is_correct' => ['nullable', 'boolean'],
        ], [
            'content.required' => 'Vui lòng nhập nội dung câu hỏi.',
            'answers.required' => 'Danh sách đáp án không được để trống.',
            'answers.min' => 'Câu hỏi phải có ít nhất 2 phương án đáp án.',
            'answers.*.content.required' => 'Vui lòng điền đầy đủ nội dung cho tất cả các lựa chọn đáp án.',
        ]);

        DB::transaction(function () use ($question, $validated) {
            $question->update([
                'content' => trim($validated['content']),
                'difficulty' => $validated['difficulty'] ?? $question->difficulty ?? 'medium',
                'points' => $validated['points'] ?? $question->points ?? 10,
                'education_level_id' => array_key_exists('education_level_id', $validated) ? $validated['education_level_id'] : $question->education_level_id,
                'grade_id' => array_key_exists('grade_id', $validated) ? $validated['grade_id'] : $question->grade_id,
                'subject_id' => array_key_exists('subject_id', $validated) ? $validated['subject_id'] : $question->subject_id,
                'topic_name' => array_key_exists('topic_name', $validated) ? $validated['topic_name'] : $question->topic_name,
                'is_public' => array_key_exists('is_public', $validated) ? (bool)$validated['is_public'] : (bool)$question->is_public,
            ]);

            if (isset($validated['answers'])) {
                $this->syncAnswers($question, $validated['answers'], null);
            }
        });

        $this->notifyAdminsIfAuthorUpdatedContent($question, $user);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật câu hỏi thành công!',
            'data' => $this->formatQuestion($question->fresh(['answers', 'educationLevel', 'grade', 'subject'])),
        ]);
    }

    private function notifyAdminsIfAuthorUpdatedContent(Question $question, User $user): void
    {
        if (strtolower($user->role ?? '') === 'admin') {
            return;
        }

        $hasReport = \App\Models\ReportTicket::where('question_id', $question->id)->exists();
        if ($hasReport || !$question->is_public) {
            $admins = User::whereIn('role', ['admin', 'ADMIN'])->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new ReportAuthorUpdated($question, 'question', $user));
            }
        }
    }

    /**
     * Tạo câu hỏi mới (Công khai hoặc Riêng tư)
     */
    public function storeQuestion(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'content' => ['required', 'string'],
            'type' => ['nullable', Rule::in(['single_choice', 'multi_choice', 'true_false', 'fill_blank'])],
            'difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
            'points' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'education_level_id' => ['nullable', 'integer'],
            'grade_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'topic_name' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
            'answers' => ['required', 'array', 'min:2'],
            'answers.*.content' => ['required', 'string'],
            'answers.*.key' => ['nullable', 'string'],
            'answers.*.is_correct' => ['nullable', 'boolean'],
        ]);

        $isPublic = isset($validated['is_public']) ? (bool)$validated['is_public'] : false;

        $question = DB::transaction(function () use ($user, $validated, $isPublic) {
            $q = Question::create([
                'user_id' => $user->id,
                'content' => trim($validated['content']),
                'type' => $validated['type'] ?? 'single_choice',
                'difficulty' => $validated['difficulty'] ?? 'medium',
                'points' => $validated['points'] ?? 10,
                'education_level_id' => $validated['education_level_id'] ?? null,
                'grade_id' => $validated['grade_id'] ?? null,
                'subject_id' => $validated['subject_id'] ?? null,
                'topic_name' => $validated['topic_name'] ?? null,
                'is_public' => $isPublic,
            ]);

            if (isset($validated['answers'])) {
                $this->syncAnswers($q, $validated['answers'], null);
            }

            return $q;
        });

        return response()->json([
            'success' => true,
            'message' => 'Tạo câu hỏi mới thành công!',
            'data' => $this->formatQuestion($question->fresh(['answers', 'educationLevel', 'grade', 'subject'])),
        ], 201);
    }

    /**
     * Xóa mềm câu hỏi cá nhân
     */
    public function softDeleteQuestion(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $question = Question::find($id);
        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy câu hỏi.'], 404);
        }

        if ($question->user_id !== $user->id && strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xóa câu hỏi này.'], 403);
        }

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã chuyển câu hỏi vào thùng rác thành công.',
        ]);
    }

    /**
     * Khôi phục câu hỏi từ Thùng rác
     */
    public function restoreQuestion(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $question = Question::onlyTrashed()->find($id);
        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy câu hỏi trong Thùng rác.'], 404);
        }

        if ($question->user_id !== $user->id && strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền khôi phục câu hỏi này.'], 403);
        }

        $question->restore();

        return response()->json([
            'success' => true,
            'message' => 'Khôi phục câu hỏi thành công!',
            'data' => $this->formatQuestion($question->fresh(['answers', 'educationLevel', 'grade', 'subject'])),
        ]);
    }

    /**
     * Xóa vĩnh viễn câu hỏi
     */
    public function forceDeleteQuestion(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $question = Question::onlyTrashed()->find($id);
        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy câu hỏi trong Thùng rác.'], 404);
        }

        if ($question->user_id !== $user->id && strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xóa vĩnh viễn câu hỏi này.'], 403);
        }

        $question->answers()->delete();
        $question->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa vĩnh viễn câu hỏi.',
        ]);
    }

    /**
     * Lấy toàn bộ danh sách câu hỏi cho Admin Console (kèm Thống kê KPI & Bộ lọc)
     */
    public function adminIndex(Request $request)
    {
        $query = Question::query()
            ->with(['answers', 'quiz.user:id,name,email,avatar', 'user:id,name,email,avatar', 'educationLevel', 'grade', 'subject']);

        if ($request->filled('search')) {
            $keyword = trim((string) $request->query('search'));
            $query->where(function ($q) use ($keyword) {
                $q->where('content', 'like', "%{$keyword}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%"))
                  ->orWhereHas('quiz', fn($qz) => $qz->where('title', 'like', "%{$keyword}%"));
            });
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->query('subject_id'));
        }

        if ($request->filled('grade_id')) {
            $query->where('grade_id', $request->query('grade_id'));
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->query('difficulty'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('visibility')) {
            $vis = $request->query('visibility');
            if ($vis === 'public') {
                $query->where('is_public', true);
            } elseif ($vis === 'private') {
                $query->where('is_public', false);
            }
        }

        // Stats tổng quan toàn hệ thống
        $totalQuestions = Question::count();
        $publicQuestions = Question::where('is_public', true)->count();
        $privateQuestions = Question::where('is_public', false)->count();
        
        // Thống kê số lượng ticket báo cáo vi phạm liên quan (ReportTicket)
        $reportedCount = \App\Models\ReportTicket::where('status', 'pending')->count();

        $query->latest();
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $paginated = $query->paginate($perPage);

        $items = collect($paginated->items())->map(function (Question $q) {
            $formatted = $this->formatQuestion($q);
            $formatted['author_name'] = $q->user?->name ?? $q->quiz?->user?->name ?? 'Vô danh';
            $formatted['author_email'] = $q->user?->email ?? $q->quiz?->user?->email;
            $formatted['author_avatar'] = $q->user?->avatar ?? $q->quiz?->user?->avatar;
            return $formatted;
        });

        return response()->json([
            'success' => true,
            'message' => 'Danh sách câu hỏi toàn hệ thống',
            'data' => [
                'items' => $items,
                'total' => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'stats' => [
                    'total' => $totalQuestions,
                    'public' => $publicQuestions,
                    'private' => $privateQuestions,
                    'reported' => $reportedCount,
                ],
            ]
        ]);
    }

    /**
     * Admin đổi trạng thái Ẩn / Công khai của 1 câu hỏi
     */
    public function adminToggleVisibility($id)
    {
        $question = Question::with(['answers', 'educationLevel', 'grade', 'subject', 'user', 'quiz.user'])->findOrFail($id);
        $question->is_public = !$question->is_public;
        $question->save();

        if ($question->is_public) {
            \App\Models\ReportTicket::where('question_id', $question->id)
                ->where('status', 'pending')
                ->update(['status' => 'resolved']);
        }

        // Gửi thông báo cho tác giả của câu hỏi kèm lý do vi phạm (nếu có)
        $author = $question->user ?? $question->quiz?->user;
        if ($author) {
            $action = $question->is_public ? 'shown' : 'hidden';
            $latestReport = \App\Models\ReportTicket::where('question_id', $question->id)->latest()->first();
            $reason = $latestReport?->reason ?? $latestReport?->description;
            $author->notify(new QuestionModerated($question, $action, $reason));
        }

        $formatted = $this->formatQuestion($question);
        $formatted['author_name'] = $question->user?->name ?? 'Vô danh';

        return response()->json([
            'success' => true,
            'message' => $question->is_public ? 'Đã công khai câu hỏi.' : 'Đã gỡ công khai (Set Private) câu hỏi.',
            'data' => $formatted,
        ]);
    }

    /**
     * Admin đổi trạng thái Riêng tư / Công khai HÀNG LOẠT (Bulk Action)
     */
    public function adminBulkToggleVisibility(Request $request)
    {
        $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer|exists:questions,id',
            'is_public' => 'required|boolean',
        ]);

        $ids = $request->input('question_ids');
        $isPublic = (bool) $request->input('is_public');

        $questions = Question::with(['user', 'quiz.user'])->whereIn('id', $ids)->get();
        Question::whereIn('id', $ids)->update(['is_public' => $isPublic]);

        if ($isPublic) {
            \App\Models\ReportTicket::whereIn('question_id', $ids)
                ->where('status', 'pending')
                ->update(['status' => 'resolved']);
        }

        $action = $isPublic ? 'shown' : 'hidden';
        foreach ($questions as $question) {
            $question->is_public = $isPublic;
            $author = $question->user ?? $question->quiz?->user;
            if ($author) {
                $author->notify(new QuestionModerated($question, $action));
            }
        }

        $statusText = $isPublic ? 'Công khai' : 'Riêng tư / Gỡ công khai';

        return response()->json([
            'success' => true,
            'message' => "Đã chuyển {$statusText} cho " . count($ids) . " câu hỏi thành công.",
        ]);
    }

    /**
     * Admin xóa hàng loạt câu hỏi (Bulk Delete)
     */
    public function adminBulkDelete(Request $request)
    {
        $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer|exists:questions,id',
        ]);

        $ids = $request->input('question_ids');
        Question::whereIn('id', $ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã chuyển " . count($ids) . " câu hỏi vào thùng rác.",
        ]);
    }

    /**
     * Admin xem chi tiết 1 câu hỏi
     */
    public function adminShow($id)
    {
        $question = Question::with([
            'answers',
            'user:id,name,email,avatar',
            'quiz.user:id,name,email,avatar',
            'quizzes:id,title,is_public,created_at',
            'educationLevel',
            'grade',
            'subject'
        ])->findOrFail($id);

        $formatted = $this->formatQuestion($question);

        $authorName = $question->user?->name ?? $question->quiz?->user?->name ?? 'Vô danh';
        $authorEmail = $question->user?->email ?? $question->quiz?->user?->email;
        $authorAvatar = $question->user?->avatar ?? $question->quiz?->user?->avatar;

        $formatted['author'] = [
            'id' => $question->user_id ?? $question->quiz?->user_id,
            'name' => $authorName,
            'email' => $authorEmail,
            'avatar' => $authorAvatar,
        ];

        // Quizzes using this question
        $usingQuizzes = $question->quizzes ? $question->quizzes->map(function ($q) {
            return [
                'id' => $q->id,
                'title' => $q->title,
                'is_public' => (bool)$q->is_public,
                'created_at' => $q->created_at ? $q->created_at->toIso8601String() : null,
            ];
        })->values() : collect();

        if ($question->quiz && !$usingQuizzes->contains('id', $question->quiz->id)) {
            $usingQuizzes->prepend([
                'id' => $question->quiz->id,
                'title' => $question->quiz->title,
                'is_public' => (bool)$question->quiz->is_public,
                'created_at' => $question->quiz->created_at ? $question->quiz->created_at->toIso8601String() : null,
            ]);
        }

        $formatted['using_quizzes'] = $usingQuizzes;

        // Associated report tickets if any
        $reports = \App\Models\ReportTicket::with('user:id,name,email')
            ->where('quiz_id', $question->quiz_id)
            ->latest()
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'reporter_name' => $r->user?->name ?? 'Người dùng',
                    'reason' => $r->reason,
                    'description' => $r->description,
                    'status' => $r->status,
                    'created_at' => $r->created_at ? $r->created_at->toIso8601String() : null,
                ];
            });

        $formatted['reports'] = $reports;

        return response()->json([
            'success' => true,
            'data' => $formatted,
        ]);
    }

    /**
     * Admin cập nhật câu hỏi
     */
    public function adminUpdate(Request $request, $id)
    {
        $question = Question::findOrFail($id);

        $request->validate([
            'content' => 'required|string',
            'difficulty' => 'nullable|string|in:easy,medium,hard',
            'points' => 'nullable|integer|min:1|max:100',
            'subject_id' => 'nullable|integer',
            'grade_id' => 'nullable|integer',
            'is_public' => 'nullable|boolean',
            'answers' => 'required|array|min:2',
            'answers.*.content' => 'required|string',
            'answers.*.is_correct' => 'required|boolean',
        ]);

        DB::transaction(function () use ($question, $request) {
            $question->update([
                'content' => $request->input('content'),
                'difficulty' => $request->input('difficulty', 'medium'),
                'points' => $request->input('points', 10),
                'subject_id' => $request->input('subject_id'),
                'grade_id' => $request->input('grade_id'),
                'is_public' => $request->has('is_public') ? (bool)$request->input('is_public') : (bool)$question->is_public,
            ]);

            if ($request->has('answers')) {
                $question->answers()->delete();
                $answersData = $request->input('answers');
                foreach ($answersData as $index => $ans) {
                    $key = chr(65 + $index);
                    $question->answers()->create([
                        'answer_key' => $key,
                        'content' => $ans['content'],
                        'is_correct' => (bool)$ans['is_correct'],
                    ]);
                }
            }
        });

        $question->load(['answers', 'subject', 'grade', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật câu hỏi thành công',
            'data' => $this->formatQuestion($question),
        ]);
    }

    /**
     * Lấy danh sách câu hỏi trong Thùng rác cho Admin
     */
    public function adminTrash(Request $request)
    {
        $query = Question::onlyTrashed()
            ->with(['answers', 'quiz.user:id,name,email,avatar', 'user:id,name,email,avatar', 'educationLevel', 'grade', 'subject']);

        if ($request->filled('search')) {
            $keyword = trim((string) $request->query('search'));
            $query->where('content', 'like', "%{$keyword}%");
        }

        $query->latest('deleted_at');
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->through(fn(Question $q) => $this->formatQuestion($q));

        $trashCount = Question::onlyTrashed()->count();

        return response()->json([
            'success' => true,
            'message' => 'Thùng rác câu hỏi',
            'data' => [
                'items' => $paginator->items(),
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'trash_count' => $trashCount,
            ]
        ]);
    }

    /**
     * Admin khôi phục 1 câu hỏi từ Thùng rác
     */
    public function adminRestore($id)
    {
        $question = Question::onlyTrashed()->findOrFail($id);
        $question->restore();

        return response()->json([
            'success' => true,
            'message' => 'Khôi phục câu hỏi thành công!',
            'data' => $this->formatQuestion($question->fresh(['answers', 'educationLevel', 'grade', 'subject'])),
        ]);
    }

    /**
     * Admin xóa vĩnh viễn 1 câu hỏi
     */
    public function adminForceDelete($id)
    {
        $question = Question::onlyTrashed()->findOrFail($id);
        $question->answers()->delete();
        $question->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa vĩnh viễn câu hỏi khỏi cơ sở dữ liệu.',
        ]);
    }

    /**
     * Admin khôi phục hàng loạt từ Thùng rác
     */
    public function adminBulkRestore(Request $request)
    {
        $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer',
        ]);

        $ids = $request->input('question_ids');
        Question::onlyTrashed()->whereIn('id', $ids)->restore();

        return response()->json([
            'success' => true,
            'message' => 'Đã khôi phục ' . count($ids) . ' câu hỏi thành công.',
        ]);
    }

    /**
     * Admin xóa vĩnh viễn hàng loạt từ Thùng rác
     */
    public function adminBulkForceDelete(Request $request)
    {
        $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer',
        ]);

        $ids = $request->input('question_ids');
        $questions = Question::onlyTrashed()->whereIn('id', $ids)->get();

        foreach ($questions as $q) {
            $q->answers()->delete();
            $q->forceDelete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa vĩnh viễn ' . count($ids) . ' câu hỏi.',
        ]);
    }
}



