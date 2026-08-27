<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionReviewRequest;
use App\Models\Quiz;
use App\Models\User;
use App\Models\ReportTicket;
use App\Notifications\QuestionModerated;
use App\Notifications\ReportAuthorUpdated;
use App\Services\QuestionReviewService;
use App\Services\QuestionSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuestionController extends Controller
{
    public function __construct(
        protected ?QuestionReviewService $reviewService = null,
        protected ?QuestionSnapshotService $snapshotService = null
    ) {
        $this->reviewService = $reviewService ?? app(QuestionReviewService::class);
        $this->snapshotService = $snapshotService ?? app(QuestionSnapshotService::class);
    }
    public function index(Quiz $quiz)
    {
        $quiz->load('questions.answers');

        return response()->json([
            'success' => true,
            'message' => 'Danh sách câu hỏi',
            'data' => $quiz->questions->map(fn(Question $question) => $this->formatQuestion($question, true))->values(),
        ]);
    }

    /**
     * Scope lọc chỉ lấy các câu hỏi công khai ĐÃ ĐƯỢC ADMIN DUYỆT thuộc ngân hàng chung
     */
    private function applyPublicQuestionScope($query)
    {
        return $query->where('is_public', true)
            ->where('status', 'approved')
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
            $cleanKeyword = ltrim($keyword, '#');
            $numericId = is_numeric($cleanKeyword) ? (int) $cleanKeyword : null;

            $query->where(function ($q) use ($keyword, $numericId) {
                if ($numericId !== null) {
                    $q->where('id', $numericId)
                      ->orWhere('origin_question_id', $numericId)
                      ->orWhere('content', 'like', "%{$keyword}%");
                } else {
                    $q->where('content', 'like', "%{$keyword}%");
                }
            });
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

        if ($request->filled('ids')) {
            $ids = $request->input('ids');
            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }
            $ids = array_filter(array_map('intval', (array) $ids));
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            }
        }

        $includeAnswerKey = $request->filled('ids');

        // Bốc ngẫu nhiên nếu có cờ random
        if ($request->boolean('random')) {
            $limit = min(max((int) $request->query('limit', 10), 1), 100);
            $questions = $query->inRandomOrder()->take($limit)->get()->map(fn(Question $q) => $this->formatQuestion($q, $includeAnswerKey));

            return response()->json([
                'success' => true,
                'message' => "Lấy ngẫu nhiên {$questions->count()} câu hỏi",
                'data' => $questions,
            ]);
        }

        $query->latest();
        $defaultPerPage = $request->filled('ids') ? 100 : 20;
        $perPage = min(max((int) $request->query('per_page', $defaultPerPage), 1), 500);
        $questions = $query->paginate($perPage)->through(fn(Question $q) => $this->formatQuestion($q, $includeAnswerKey));

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
            'mode' => ['nullable', 'string', Rule::in(['manual', 'auto', 'random'])],
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

        if (Gate::forUser($user)->denies('create', Quiz::class)) {
            return response()->json([
                'success' => false,
                'message' => 'Admin không được tạo Quiz trực tiếp.',
            ], 403);
        }

        $isAdmin = strtolower($user->role ?? '') === 'admin';
        $modeInput = strtolower(trim((string) ($validated['mode'] ?? '')));
        $rawQuestionIds = collect($validated['question_ids'] ?? [])->unique()->values()->all();
        $hasQuestionIds = !empty($rawQuestionIds);
        $hasMatrixCounts = (isset($validated['easy_count']) && (int)$validated['easy_count'] > 0)
            || (isset($validated['medium_count']) && (int)$validated['medium_count'] > 0)
            || (isset($validated['hard_count']) && (int)$validated['hard_count'] > 0)
            || (isset($validated['random_count']) && (int)$validated['random_count'] > 0);

        if ($modeInput === 'auto' || $modeInput === 'random' || (!$hasQuestionIds && $hasMatrixCounts)) {
            $creationMode = 'auto';
        } else {
            $creationMode = 'manual';
        }

        $questionIds = [];

        if ($creationMode === 'manual') {
            // Chế độ CHỌN THỦ CÔNG: Cho phép dùng câu hỏi từ Kho cá nhân của user hoặc Ngân hàng công khai
            if (!$hasQuestionIds) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn ít nhất 1 câu hỏi cho Quiz thủ công.',
                ], 422);
            }

            $allowedCount = Question::query()
                ->whereIn('id', $rawQuestionIds)
                ->where(function ($q) use ($user) {
                    $q->where('is_public', true)
                      ->orWhere('user_id', $user->id);
                })
                ->count();

            if ($allowedCount !== count($rawQuestionIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Một hoặc nhiều câu hỏi không tồn tại hoặc bạn không có quyền sử dụng (câu hỏi riêng tư của người khác).',
                ], 403);
            }

            $questionIds = $rawQuestionIds;
        } else {
            // Chế độ TỰ ĐỘNG: Nguồn duy nhất là Question Bank (is_public = true)
            // Nếu request auto cố tình gửi question_ids, kiểm tra 100% câu hỏi phải là Bank public
            if ($hasQuestionIds) {
                $bankCount = Question::query()
                    ->whereIn('id', $rawQuestionIds)
                    ->where('is_public', true)
                    ->count();

                if ($bankCount !== count($rawQuestionIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Chế độ tạo tự động chỉ chấp nhận câu hỏi từ Ngân hàng câu hỏi công khai đã được kiểm duyệt.',
                    ], 422);
                }

                $questionIds = $rawQuestionIds;
            }
        }

        $easyCount = (int)($validated['easy_count'] ?? 0);
        $mediumCount = (int)($validated['medium_count'] ?? 0);
        $hardCount = (int)($validated['hard_count'] ?? 0);

        // TH1: Sử dụng Cấu trúc Phân bổ Độ khó (TỰ ĐỘNG PHÂN BỔ - Auto Mode)
        if (empty($questionIds) && ($easyCount > 0 || $mediumCount > 0 || $hardCount > 0)) {
            $creationMode = 'auto';
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
        // TH2: Sử dụng bốc ngẫu nhiên tổng số N câu đơn thuần (TỰ ĐỘNG PHÂN BỔ - Auto Mode)
        elseif (empty($questionIds) && !empty($validated['random_count'])) {
            $creationMode = 'auto';
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

        // QUY TẮC NGHIỆP VỤ:
        // 1. Chế độ THỦ CÔNG (Manual): Bắt buộc PRIVATE cho user thường (chờ gửi yêu cầu duyệt Admin).
        // 2. Chế độ TỰ ĐỘNG (Auto): Do 100% câu hỏi đã từ Ngân hàng được kiểm duyệt, cho phép Public hoặc Private theo lựa chọn.
        if ($creationMode === 'manual') {
            $isPublic = $isAdmin && isset($validated['is_public']) ? (bool)$validated['is_public'] : false;
            $reviewStatus = $isPublic ? 'approved' : 'draft';
            $status = $isPublic ? 'published' : 'draft';
        } else {
            $isPublic = isset($validated['is_public']) ? (bool)$validated['is_public'] : true;
            $reviewStatus = $isPublic ? 'approved' : 'draft';
            $status = $validated['status'] ?? ($isPublic ? 'published' : 'draft');
        }

        $displayTopicName = $validated['quiz_topic_name'] ?? $validated['topic_name'] ?? null;

        $quiz = DB::transaction(function () use ($validated, $user, $questionIds, $timeLimitSeconds, $creationMode, $isPublic, $status, $reviewStatus, $displayTopicName, $coverUrl) {
            $quiz = Quiz::create([
                'user_id' => $user->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? ($creationMode === 'auto' ? 'Bộ đề thi được tạo tự động từ Ngân hàng câu hỏi' : 'Bộ đề thi tạo thủ công'),
                'education_level_id' => $validated['education_level_id'] ?? null,
                'grade_id' => $validated['grade_id'] ?? null,
                'subject_id' => $validated['subject_id'] ?? null,
                'topic_name' => $displayTopicName,
                'tag' => $validated['tag'] ?? null,
                'difficulty' => $validated['difficulty'] ?? 'medium',
                'category' => $displayTopicName ?? 'General',
                'creation_mode' => $creationMode,
                'review_status' => $reviewStatus,
                'status' => $status,
                'is_public' => $isPublic,
                'time_limit_seconds' => $timeLimitSeconds,
                'cover' => $coverUrl,
                'badge' => $validated['badge'] ?? ($creationMode === 'auto' ? 'AUTO' : 'QUIZ'),
                'icon' => $validated['icon'] ?? ($creationMode === 'auto' ? '🎯' : '📝'),
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
        $message = $creationMode === 'manual'
            ? 'Tạo Quiz thủ công thành công! (Mặc định ở chế độ Riêng tư, bạn có thể gửi yêu cầu duyệt để công khai).'
            : 'Tạo Quiz tự động từ Ngân hàng câu hỏi thành công!';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $quizController->formatQuiz($quiz, true),
        ], 201);
    }

    public function store(Request $request, Quiz $quiz)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (Gate::forUser($user)->denies('create', Question::class) || Gate::forUser($user)->denies('update', $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền tạo câu hỏi vào Quiz này.',
            ], 403);
        }

        $data = $this->validateQuestionPayload($request);
        $content = $data['content'] ?? $data['text'];
        $type = $data['type'] ?? 'single_choice';
        $rawAnswers = $data['answers'] ?? [];
        $correct = $data['correct'] ?? null;

        $correctKeys = collect(is_array($correct) ? $correct : [$correct])
            ->filter(fn($value) => $value !== null && $value !== '')
            ->map(fn($value) => strtoupper((string) $value))
            ->values()
            ->all();

        $normalizedAnswers = [];
        foreach ($rawAnswers as $index => $answerData) {
            $ansContent = trim((string) ($answerData['content'] ?? $answerData['text'] ?? ''));
            if ($ansContent === '') continue;
            $key = strtoupper((string) ($answerData['key'] ?? chr(65 + $index)));
            $isCorrect = array_key_exists('is_correct', $answerData)
                ? (bool) $answerData['is_correct']
                : in_array($key, $correctKeys, true);
            $normalizedAnswers[] = [
                'content' => $ansContent,
                'is_correct' => $isCorrect,
            ];
        }

        $fingerprint = $this->snapshotService->computeFingerprintFromSnapshot($content, $type, $normalizedAnswers);

        // Kiểm tra câu hỏi trùng trong kho cá nhân / quiz của user
        $duplicate = Question::where(function ($q) use ($user, $quiz) {
                $q->where('user_id', $user->id)
                    ->orWhere('quiz_id', $quiz->id);
            })
            ->whereNull('origin_question_id')
            ->where('fingerprint', $fingerprint)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'content' => 'Câu hỏi với nội dung và tập đáp án này đã tồn tại trong kho cá nhân của bạn.',
            ]);
        }

        $question = DB::transaction(function () use ($quiz, $data, $content, $type, $fingerprint, $user) {
            $question = $quiz->questions()->create([
                'user_id' => $user->id,
                'content' => $content,
                'image_url' => $data['image_url'] ?? null,
                'type' => $type,
                'order' => $data['order'] ?? ($quiz->questions()->max('order') + 1),
                'points' => $data['points'] ?? 10,
                'fingerprint' => $fingerprint,
            ]);

            $this->syncAnswers($question, $data['answers'] ?? [], $data['correct'] ?? null);

            return $question->fresh('answers');
        });

        return response()->json([
            'success' => true,
            'message' => 'Tạo câu hỏi thành công',
            'data' => $this->formatQuestion($question, true),
        ], 201);
    }

    public function show(Question $question)
    {
        $question->load('answers');

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết câu hỏi',
            'data' => $this->formatQuestion($question, true),
        ]);
    }

    public function update(Request $request, Question $question)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $question->loadMissing('quiz');
        if (Gate::forUser($user)->denies('update', $question)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền sửa câu hỏi này.',
            ], 403);
        }

        $data = $this->validateQuestionPayload($request, true);

        $content = $data['content'] ?? $data['text'] ?? $question->content;
        $type = $data['type'] ?? $question->type;
        $rawAnswers = array_key_exists('answers', $data) ? $data['answers'] : null;
        $correct = $data['correct'] ?? null;

        if ($rawAnswers !== null) {
            $correctKeys = collect(is_array($correct) ? $correct : [$correct])
                ->filter(fn($value) => $value !== null && $value !== '')
                ->map(fn($value) => strtoupper((string) $value))
                ->values()
                ->all();

            $normalizedAnswers = [];
            foreach ($rawAnswers as $index => $answerData) {
                $ansContent = trim((string) ($answerData['content'] ?? $answerData['text'] ?? ''));
                if ($ansContent === '') continue;
                $key = strtoupper((string) ($answerData['key'] ?? chr(65 + $index)));
                $isCorrect = array_key_exists('is_correct', $answerData)
                    ? (bool) $answerData['is_correct']
                    : in_array($key, $correctKeys, true);
                $normalizedAnswers[] = [
                    'content' => $ansContent,
                    'is_correct' => $isCorrect,
                ];
            }
        } else {
            $answers = $question->relationLoaded('answers') ? $question->answers : $question->answers()->get();
            $normalizedAnswers = $answers->all();
        }

        $fingerprint = $this->snapshotService->computeFingerprintFromSnapshot($content, $type, $normalizedAnswers);

        $duplicate = Question::where(function ($q) use ($user, $question) {
                $q->where('user_id', $user->id);
                if ($question->quiz_id) {
                    $q->orWhere('quiz_id', $question->quiz_id);
                }
            })
            ->whereNull('origin_question_id')
            ->where('fingerprint', $fingerprint)
            ->where('id', '!=', $question->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'content' => 'Câu hỏi với nội dung và tập đáp án này đã tồn tại trong kho cá nhân của bạn.',
            ]);
        }

        $question = DB::transaction(function () use ($question, $data, $content, $type, $fingerprint) {
            $question->update([
                'content' => $content,
                'image_url' => $data['image_url'] ?? $question->image_url,
                'type' => $type,
                'order' => $data['order'] ?? $question->order,
                'points' => $data['points'] ?? $question->points,
                'fingerprint' => $fingerprint,
            ]);

            if (array_key_exists('answers', $data)) {
                $this->syncAnswers($question, $data['answers'], $data['correct'] ?? null);
            }

            return $question->fresh('answers');
        });

        if ($user) {
            $this->notifyAdminsIfAuthorUpdatedContent($question, $user);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật câu hỏi thành công',
            'data' => $this->formatQuestion($question, true),
        ]);
    }

    public function destroy(Question $question)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $question->loadMissing('quiz');
        if (Gate::forUser($user)->denies('delete', $question)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa câu hỏi này.',
            ], 403);
        }

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

    private function formatQuestion(Question $question, bool $includeAnswerKey = false): array
    {
        $user = auth('api')->user();
        if (!$includeAnswerKey && $user) {
            $isOwner = ($question->user_id && $question->user_id === $user->id) ||
                       ($question->quiz && $question->quiz->user_id === $user->id) ||
                       in_array(strtolower($user->role ?? ''), ['admin', 'superadmin']);
            if ($isOwner) {
                $includeAnswerKey = true;
            }
        }

        $reportQuestionIds = array_filter(array_unique([$question->id, $question->origin_question_id]));
        $unresolvedReport = ReportTicket::whereIn('question_id', $reportQuestionIds)
            ->whereIn('status', ['pending', 'author_updated', 'admin_review_required'])
            ->latest()
            ->first();

        $latestReport = $unresolvedReport ?? ReportTicket::whereIn('question_id', $reportQuestionIds)->latest()->first();
        $hasPendingReport = $unresolvedReport !== null;
        $hasAuthorUpdated = $unresolvedReport?->status === ReportTicket::STATUS_AUTHOR_UPDATED;
        $pendingReport = $unresolvedReport;
        $isLockedByAdmin = $hasPendingReport;

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
            'origin_question_id' => $question->origin_question_id,
            'is_public' => (bool) $question->is_public,
            'bank_submission_status' => $question->bank_submission_status ?? 'none',
            'bank_submission_note' => $question->bank_submission_note,
            'bank_submission_at' => $question->bank_submission_at ? $question->bank_submission_at->toIso8601String() : null,
            'has_report' => $hasPendingReport,
            'has_author_updated' => $hasAuthorUpdated,
            'is_locked_by_admin' => $isLockedByAdmin,
            'report_reason' => $pendingReport?->description ?? $pendingReport?->reason ?? $latestReport?->description ?? $latestReport?->reason ?? null,
            'order' => $question->order,
            'points' => $question->points ?? 10,
            'author_name' => $question->user?->name ?? $question->quiz?->user?->name ?? 'Vô danh',
            'created_at' => $question->created_at ? $question->created_at->toIso8601String() : null,
            'updated_at' => $question->updated_at ? $question->updated_at->toIso8601String() : null,
            'deleted_at' => $question->deleted_at ? $question->deleted_at->toIso8601String() : null,
            'answers' => $question->answers->map(function (Answer $answer, int $index) use ($includeAnswerKey) {
                $ans = [
                    'id' => $answer->id,
                    'question_id' => $answer->question_id,
                    'content' => $answer->content,
                    'text' => $answer->content,
                    'answer_key' => chr(65 + ($answer->order ?? $index)),
                    'key' => chr(65 + ($answer->order ?? $index)),
                    'order' => $answer->order,
                ];

                if ($includeAnswerKey) {
                    $ans['is_correct'] = (bool) $answer->is_correct;
                }

                return $ans;
            })->values(),
        ];
    }

    /**
     * Admin duyệt hoặc từ chối hàng loạt câu hỏi
     */
    public function bulkModerateQuestions(Request $request)
    {
        $admin = auth('api')->user();
        if (!$admin || strtolower($admin->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền thực hiện chức năng này.'], 403);
        }

        $validated = $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:questions,id'],
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $isApprove = $validated['action'] === 'approve';
        $note = $validated['note'] ?? null;
        $questions = Question::with('user')->whereIn('id', $validated['question_ids'])->get();

        foreach ($questions as $question) {
            $question->update([
                'is_public' => $isApprove ? true : false,
                'status' => $isApprove ? 'approved' : 'rejected',
            ]);

            if ($isApprove) {
                \App\Models\ReportTicket::where('question_id', $question->id)
                    ->update(['status' => 'resolved', 'has_author_updated' => false]);
            } else {
                \App\Models\ReportTicket::updateOrCreate(
                    [
                        'question_id' => $question->id,
                        'status' => 'pending',
                    ],
                    [
                        'user_id' => $admin->id,
                        'reason' => 'Admin từ chối duyệt công khai',
                        'description' => $note ?: 'Nội dung chưa đạt yêu cầu duyệt công khai',
                        'has_author_updated' => false,
                    ]
                );
            }

            if ($question->user) {
                try {
                    $question->user->notify(new \App\Notifications\QuestionModerated($question, $validated['action'], $note));
                } catch (\Exception $e) {
                    // Ignore broadcast error
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => $isApprove 
                ? "Đã duyệt công khai {$questions->count()} câu hỏi thành công!" 
                : "Đã từ chối {$questions->count()} câu hỏi thành công!",
            'count' => $questions->count(),
        ]);
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
            ->whereNull('origin_question_id')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('quiz', fn($sq) => $sq->where('user_id', $user->id));
            });

        if ($request->filled('question_id')) {
            $query->where('id', $request->query('question_id'));
        }

        if ($request->filled('search')) {
            $keyword = trim((string) $request->query('search'));
            $cleanKeyword = ltrim($keyword, '#');
            $numericId = is_numeric($cleanKeyword) ? (int) $cleanKeyword : null;

            $query->where(function ($q) use ($keyword, $numericId) {
                if ($numericId !== null) {
                    $q->where('id', $numericId)
                      ->orWhere('content', 'like', "%{$keyword}%");
                } else {
                    $q->where('content', 'like', "%{$keyword}%");
                }
            });
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

        if ($request->filled('ids')) {
            $ids = $request->input('ids');
            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }
            $ids = array_filter(array_map('intval', (array) $ids));
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            }
        }

        $query->latest();
        $defaultPerPage = $request->filled('ids') ? 100 : 20;
        $perPage = min(max((int) $request->query('per_page', $defaultPerPage), 1), 500);
        $questions = $query->paginate($perPage)->through(fn(Question $q) => $this->formatQuestion($q, true));

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
            ->map(fn(Question $q) => $this->formatQuestion($q, true));

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

        if (Gate::forUser($user)->denies('update', $question)) {
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

        $isAdmin = strtolower($user->role ?? '') === 'admin';

        $type = $question->type ?? 'single_choice';
        $fingerprint = $this->snapshotService->computeFingerprintFromSnapshot(
            $validated['content'],
            $type,
            $validated['answers']
        );

        $duplicate = Question::where('user_id', $user->id)
            ->whereNull('origin_question_id')
            ->where('fingerprint', $fingerprint)
            ->where('id', '!=', $question->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'content' => 'Câu hỏi với nội dung và tập đáp án này đã tồn tại trong kho cá nhân của bạn.',
            ]);
        }

        DB::transaction(function () use ($question, $validated, $isAdmin, $fingerprint) {
            $updateData = [
                'content' => trim($validated['content']),
                'difficulty' => $validated['difficulty'] ?? $question->difficulty ?? 'medium',
                'points' => $validated['points'] ?? $question->points ?? 10,
                'education_level_id' => array_key_exists('education_level_id', $validated) ? $validated['education_level_id'] : $question->education_level_id,
                'grade_id' => array_key_exists('grade_id', $validated) ? $validated['grade_id'] : $question->grade_id,
                'subject_id' => array_key_exists('subject_id', $validated) ? $validated['subject_id'] : $question->subject_id,
                'topic_name' => array_key_exists('topic_name', $validated) ? $validated['topic_name'] : $question->topic_name,
                'fingerprint' => $fingerprint,
            ];

            if ($isAdmin && array_key_exists('is_public', $validated)) {
                $updateData['is_public'] = (bool)$validated['is_public'];
                if ($updateData['is_public']) {
                    $updateData['bank_submission_status'] = 'approved';
                }
            } else {
                // Người dùng chỉnh sửa câu hỏi:
                $updateData['is_public'] = false;
                // Nếu câu hỏi trước đó là approved và user sửa nội dung cá nhân, reset về none
                if ($question->bank_submission_status === 'approved') {
                    $updateData['bank_submission_status'] = 'none';
                    $updateData['bank_submission_note'] = null;
                }
                // Nếu đang là 'rejected', 'none', 'pending': GIỮ NGUYÊN trạng thái hiện tại.
                // Không tự động chuyển sang pending, không xóa lý do bị từ chối trước đó,
                // chỉ khi người dùng chủ động bấm 'Gửi duyệt' thì mới tạo revision mới.
            }

            $question->update($updateData);

            // Đánh dấu cho tất cả các vé báo cáo vi phạm liên quan là tác giả đã đính chính
            \App\Models\ReportTicket::where('question_id', $question->id)
                ->where('status', 'pending')
                ->update(['has_author_updated' => true]);

            if (isset($validated['answers'])) {
                $this->syncAnswers($question, $validated['answers'], null);
            }
        });

        $this->notifyAdminsIfAuthorUpdatedContent($question, $user);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật câu hỏi thành công!',
            'data' => $this->formatQuestion($question->fresh(['answers', 'educationLevel', 'grade', 'subject']), true),
        ]);
    }

    private function notifyAdminsIfAuthorUpdatedContent(Question $question, User $user): void
    {
        if (strtolower($user->role ?? '') === 'admin') {
            return;
        }

        $targetQuestionIds = array_filter(array_unique([
            $question->id,
            $question->origin_question_id,
        ]));
        $snapshotIds = Question::where('origin_question_id', $question->id)->pluck('id')->all();
        $allRelatedIds = array_values(array_unique(array_merge($targetQuestionIds, $snapshotIds)));

        // Cập nhật các ReportTicket pending / admin_review_required sang author_updated
        ReportTicket::whereIn('question_id', $allRelatedIds)
            ->whereIn('status', [ReportTicket::STATUS_PENDING, ReportTicket::STATUS_ADMIN_REVIEW_REQUIRED])
            ->update(['status' => ReportTicket::STATUS_AUTHOR_UPDATED]);

        $hasAuthorUpdatedReport = ReportTicket::whereIn('question_id', $allRelatedIds)
            ->where('status', ReportTicket::STATUS_AUTHOR_UPDATED)
            ->exists();

        if ($hasAuthorUpdatedReport) {
            $admins = User::whereIn('role', ['admin', 'ADMIN'])->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new ReportAuthorUpdated($question, 'question', $user));
            }
        }
    }

    /**
     * Tạo câu hỏi mới (Mặc định Riêng tư, muốn vào Ngân hàng phải gửi duyệt)
     */
    public function storeQuestion(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (Gate::forUser($user)->denies('create', Question::class)) {
            return response()->json([
                'success' => false,
                'message' => 'Admin không được tạo câu hỏi trực tiếp.',
            ], 403);
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

        $isAdmin = strtolower($user->role ?? '') === 'admin';
        $isPublic = $isAdmin && isset($validated['is_public']) ? (bool)$validated['is_public'] : false;
        $bankSubmissionStatus = $isPublic ? 'approved' : 'none';
        $type = $validated['type'] ?? 'single_choice';

        $fingerprint = $this->snapshotService->computeFingerprintFromSnapshot(
            $validated['content'],
            $type,
            $validated['answers']
        );

        $exists = Question::where('user_id', $user->id)
            ->whereNull('origin_question_id')
            ->where('fingerprint', $fingerprint)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'content' => 'Câu hỏi với nội dung và tập đáp án này đã tồn tại trong kho cá nhân của bạn.',
            ]);
        }

        $question = DB::transaction(function () use ($user, $validated, $isPublic, $bankSubmissionStatus, $type, $fingerprint) {
            $q = Question::create([
                'user_id' => $user->id,
                'content' => trim($validated['content']),
                'type' => $type,
                'difficulty' => $validated['difficulty'] ?? 'medium',
                'points' => $validated['points'] ?? 10,
                'education_level_id' => $validated['education_level_id'] ?? null,
                'grade_id' => $validated['grade_id'] ?? null,
                'subject_id' => $validated['subject_id'] ?? null,
                'topic_name' => $validated['topic_name'] ?? null,
                'is_public' => $isPublic,
                'bank_submission_status' => $bankSubmissionStatus,
                'fingerprint' => $fingerprint,
            ]);

            if (isset($validated['answers'])) {
                $this->syncAnswers($q, $validated['answers'], null);
            }

            return $q;
        });

        $msg = $status === 'pending'
            ? 'Tạo câu hỏi mới thành công! Đã gửi thông báo chờ Admin duyệt công khai lên Ngân hàng.'
            : 'Tạo câu hỏi mới thành công!';

        return response()->json([
            'success' => true,
            'message' => 'Tạo câu hỏi mới thành công!',
            'data' => $this->formatQuestion($question->fresh(['answers', 'educationLevel', 'grade', 'subject']), true),
        ], 201);
    }

    /**
     * Gửi yêu cầu duyệt câu hỏi cá nhân vào Ngân hàng câu hỏi
     */
    public function submitToBank(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $question = Question::with(['answers', 'educationLevel', 'grade', 'subject'])->find($id);
        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy câu hỏi.'], 404);
        }

        if ($question->user_id !== $user->id && strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền gửi duyệt câu hỏi này.'], 403);
        }

        $note = $request->input('note') ?? $request->input('request_note');
        $reviewRequest = $this->reviewService->submitToBank($question, $user, $note);

        $isAutoApproved = $reviewRequest->status === 'approved';
        $message = $isAutoApproved
            ? 'Câu hỏi đã được hệ thống tự động kiểm duyệt và phê duyệt thành công!'
            : 'Đã gửi yêu cầu kiểm duyệt câu hỏi vào Ngân hàng thành công!';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'question' => $this->formatQuestion($question->fresh(['answers', 'educationLevel', 'grade', 'subject']), true),
                'review_request' => $this->reviewService->formatRevision($reviewRequest),
            ],
        ]);
    }

    /**
     * Gửi yêu cầu duyệt câu hỏi HÀNG LOẠT vào Ngân hàng
     */
    public function bulkSubmitToBank(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:questions,id',
        ]);

        $ids = $request->input('ids');
        $questions = Question::with(['answers', 'educationLevel', 'grade', 'subject'])
            ->whereIn('id', $ids)
            ->where('user_id', $user->id)
            ->get();

        $successCount = 0;
        $errors = [];

        foreach ($questions as $question) {
            try {
                $this->reviewService->submitToBank($question, $user);
                $successCount++;
            } catch (\Throwable $e) {
                $errors[] = "Câu hỏi #{$question->id}: " . $e->getMessage();
            }
        }

        if ($successCount === 0 && !empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi duyệt các câu hỏi đã chọn: ' . implode('; ', $errors),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Đã gửi yêu cầu kiểm duyệt {$successCount} câu hỏi vào Ngân hàng thành công!" . (!empty($errors) ? " (Bỏ qua " . count($errors) . " câu hỏi)" : ""),
        ]);
    }

    /**
     * Admin: Danh sách yêu cầu duyệt câu hỏi vào Ngân hàng
     */
    public function adminBankRequests(Request $request)
    {
        $query = Question::query()
            ->with([
                'answers',
                'user:id,name,email,avatar',
                'quiz.user:id,name,email,avatar',
                'educationLevel',
                'grade',
                'subject',
                'latestReviewRequest.reviewer:id,name,email,avatar',
                'latestReviewRequest.user:id,name,email,avatar',
            ]);

        $status = $request->query('status', 'pending');
        if ($status !== 'all') {
            $query->where('bank_submission_status', $status);
        } else {
            $query->whereIn('bank_submission_status', ['pending', 'approved', 'rejected']);
        }

        if ($request->filled('search')) {
            $keyword = trim((string) $request->query('search'));
            $cleanKeyword = ltrim($keyword, '#');
            $numericId = is_numeric($cleanKeyword) ? (int) $cleanKeyword : null;

            $query->where(function ($q) use ($keyword, $numericId) {
                if ($numericId !== null) {
                    $q->where('id', $numericId)
                      ->orWhere('origin_question_id', $numericId)
                      ->orWhereHas('reviewRequests', fn($rq) => $rq->where('id', $numericId))
                      ->orWhere('content', 'like', "%{$keyword}%")
                      ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"))
                      ->orWhereHas('quiz', fn($qz) => $qz->where('title', 'like', "%{$keyword}%"));
                } else {
                    $q->where('content', 'like', "%{$keyword}%")
                      ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"))
                      ->orWhereHas('quiz', fn($qz) => $qz->where('title', 'like', "%{$keyword}%"));
                }
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

        if ($request->query('priority') === 'high' || $request->query('priority') === 'reported') {
            $query->where(function ($q) {
                $q->whereHas('reviewRequests', function ($rq) {
                    $rq->where('review_priority', 'high')->orWhere('is_priority', true);
                })->orWhereHas('reports');
            });
        }

        $pendingCount = Question::where('bank_submission_status', 'pending')->count();
        $approvedCount = Question::where('bank_submission_status', 'approved')->count();
        $rejectedCount = Question::where('bank_submission_status', 'rejected')->count();
        $priorityCount = Question::where('bank_submission_status', 'pending')
            ->where(function ($q) {
                $q->whereHas('reviewRequests', fn($rq) => $rq->where('is_priority', true)->orWhere('review_priority', 'high'))
                  ->orWhereHas('reports');
            })->count();

        // Ưu tiên các câu hỏi có cờ PRIORITY hoặc có Báo cáo vi phạm lên đầu danh sách
        $query->orderByRaw("(
            SELECT CASE WHEN is_priority = 1 OR review_priority = 'high' THEN 1 ELSE 0 END 
            FROM question_review_requests 
            WHERE question_review_requests.question_id = questions.id 
            ORDER BY id DESC LIMIT 1
        ) DESC")
        ->latest('bank_submission_at')
        ->latest('updated_at');

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $paginated = $query->paginate($perPage);

        $items = collect($paginated->items())->map(function (Question $q) {
            $formatted = $this->formatQuestion($q, true);
            $formatted['author_name'] = $q->user?->name ?? $q->quiz?->user?->name ?? 'Vô danh';
            $formatted['author_email'] = $q->user?->email ?? $q->quiz?->user?->email;
            $formatted['author_avatar'] = $q->user?->avatar ?? $q->quiz?->user?->avatar;

            $latestReq = $q->latestReviewRequest;
            $hasReportTickets = \App\Models\ReportTicket::where('question_id', $q->id)->exists();
            $isPriority = ($latestReq && ($latestReq->is_priority || $latestReq->review_priority === 'high')) || $hasReportTickets;

            $formatted['is_priority'] = $isPriority;
            $formatted['review_priority'] = $isPriority ? 'high' : 'normal';
            $formatted['reports_count'] = \App\Models\ReportTicket::where('question_id', $q->id)->count();
            $formatted['report_reason'] = $latestReq?->snapshot_metadata['report_reason'] ?? $formatted['report_reason'] ?? null;
            $formatted['report_description'] = $latestReq?->snapshot_metadata['report_description'] ?? null;

            $formatted['revision_number'] = $latestReq?->revision_number ?? 1;
            $formatted['review_request_id'] = $latestReq?->id;
            $formatted['rejection_reason'] = $latestReq?->rejection_reason ?? $q->bank_submission_note;
            $formatted['reviewer_name'] = $latestReq?->reviewer?->name;
            $formatted['reviewed_at'] = $latestReq?->reviewed_at ? $latestReq->reviewed_at->toIso8601String() : null;

            return $formatted;
        });

        return response()->json([
            'success' => true,
            'message' => 'Danh sách yêu cầu duyệt câu hỏi vào Ngân hàng',
            'data' => [
                'items' => $items,
                'total' => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'stats' => [
                    'pending' => $pendingCount,
                    'approved' => $approvedCount,
                    'rejected' => $rejectedCount,
                    'priority' => $priorityCount,
                    'total' => $pendingCount + $approvedCount + $rejectedCount,
                ],
            ]
        ]);
    }

    /**
     * Admin: Xem chi tiết yêu cầu duyệt câu hỏi kèm Diff (Previous vs Current)
     */
    public function adminShowBankRequest($id)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $question = Question::with(['answers', 'user', 'educationLevel', 'grade', 'subject'])->findOrFail($id);
        $diffData = $this->reviewService->getReviewDetailsWithDiff($question);

        return response()->json([
            'success' => true,
            'data' => [
                'question' => $this->formatQuestion($question, true),
                'current_revision' => $diffData['current_revision'],
                'previous_revision' => $diffData['previous_revision'],
                'history' => $diffData['history'],
                'reports' => $diffData['reports'] ?? [],
                'is_priority' => $diffData['is_priority'] ?? false,
                'review_priority' => $diffData['review_priority'] ?? 'normal',
            ],
        ]);
    }


    /**
     * User / Admin: Xem lịch sử các lần gửi duyệt của 1 câu hỏi
     */
    public function questionReviewHistory($id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $question = Question::findOrFail($id);
        $isAdmin = strtolower($user->role ?? '') === 'admin';

        if (!$isAdmin && $question->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xem thông tin này.'], 403);
        }

        $historyData = $this->reviewService->getReviewDetailsWithDiff($question);

        return response()->json([
            'success' => true,
            'data' => $historyData['history'],
        ]);
    }

    /**
     * Admin: Phê duyệt 1 câu hỏi vào Ngân hàng
     */
    public function adminApproveBankRequest($id)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $question = Question::with(['user', 'quiz.user', 'answers', 'educationLevel', 'grade', 'subject'])->findOrFail($id);
        $reviewRequest = $this->reviewService->approveQuestion($question, $user);

        return response()->json([
            'success' => true,
            'message' => "Đã duyệt câu hỏi #{$question->id} vào Ngân hàng câu hỏi thành công!",
            'data' => [
                'question' => $this->formatQuestion($question->fresh(['answers', 'educationLevel', 'grade', 'subject']), true),
                'review_request' => $this->reviewService->formatRevision($reviewRequest),
            ],
        ]);
    }

    /**
     * Admin: Từ chối 1 câu hỏi vào Ngân hàng
     */
    public function adminRejectBankRequest(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'note' => 'required_without:reason|nullable|string|max:1000',
            'reason' => 'required_without:note|nullable|string|max:1000',
        ], [
            'note.required_without' => 'Vui lòng nhập lý do từ chối kiểm duyệt.',
            'reason.required_without' => 'Vui lòng nhập lý do từ chối kiểm duyệt.',
        ]);

        $question = Question::with(['user', 'quiz.user', 'answers', 'educationLevel', 'grade', 'subject'])->findOrFail($id);
        $note = trim((string)($request->input('note') ?? $request->input('reason')));
        $reviewRequest = $this->reviewService->rejectQuestion($question, $user, $note);


        return response()->json([
            'success' => true,
            'message' => "Đã từ chối duyệt câu hỏi #{$question->id}.",
            'data' => [
                'question' => $this->formatQuestion($question->fresh(['answers', 'educationLevel', 'grade', 'subject']), true),
                'review_request' => $this->reviewService->formatRevision($reviewRequest),
            ],
        ]);
    }

    /**
     * Admin: Phê duyệt HÀNG LOẠT câu hỏi vào Ngân hàng
     */
    public function adminBulkApproveBankRequests(Request $request)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:questions,id',
        ]);

        $ids = $request->input('ids');
        $questions = Question::with(['user', 'quiz.user', 'answers', 'educationLevel', 'grade', 'subject'])
            ->whereIn('id', $ids)
            ->where('bank_submission_status', 'pending')
            ->get();

        $processedCount = 0;
        foreach ($questions as $question) {
            $this->reviewService->approveQuestion($question, $user);
            $processedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Đã phê duyệt {$processedCount} câu hỏi vào Ngân hàng thành công!",
        ]);
    }

    /**
     * Admin: Từ chối HÀNG LOẠT câu hỏi vào Ngân hàng
     */
    public function adminBulkRejectBankRequests(Request $request)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:questions,id',
            'note' => 'required|string|max:1000',
        ], [
            'note.required' => 'Vui lòng nhập lý do từ chối kiểm duyệt.',
        ]);

        $ids = $request->input('ids');
        $note = trim($request->input('note'));
        $questions = Question::with(['user', 'quiz.user', 'answers', 'educationLevel', 'grade', 'subject'])
            ->whereIn('id', $ids)
            ->where('bank_submission_status', 'pending')
            ->get();

        $processedCount = 0;
        foreach ($questions as $question) {
            $this->reviewService->rejectQuestion($question, $user, $note);
            $processedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Đã từ chối kiểm duyệt {$processedCount} câu hỏi.",
        ]);
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

        if (Gate::forUser($user)->denies('delete', $question)) {
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

        if (Gate::forUser($user)->denies('restore', $question)) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền khôi phục câu hỏi này.'], 403);
        }

        $question->restore();

        return response()->json([
            'success' => true,
            'message' => 'Khôi phục câu hỏi thành công!',
            'data' => $this->formatQuestion($question->fresh(['answers', 'educationLevel', 'grade', 'subject']), true),
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

        if (strtolower($user->role ?? '') === 'admin' || (int) $question->user_id !== (int) $user->id) {
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
     * Chỉ trả về các câu hỏi thuộc Question Bank (bản ghi snapshot đã approved)
     */
    public function adminIndex(Request $request)
    {
        $query = Question::query()
            ->whereNotNull('origin_question_id')
            ->where('bank_submission_status', 'approved')
            ->with(['answers', 'quiz.user:id,name,email,avatar', 'user:id,name,email,avatar', 'educationLevel', 'grade', 'subject']);

        if ($request->filled('search')) {
            $keyword = trim((string) $request->query('search'));
            $cleanKeyword = ltrim($keyword, '#');
            $numericId = is_numeric($cleanKeyword) ? (int) $cleanKeyword : null;

            $query->where(function ($q) use ($keyword, $numericId) {
                if ($numericId !== null) {
                    $q->where('id', $numericId)
                      ->orWhere('origin_question_id', $numericId)
                      ->orWhere('content', 'like', "%{$keyword}%")
                      ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"))
                      ->orWhereHas('quiz', fn($qz) => $qz->where('title', 'like', "%{$keyword}%"));
                } else {
                    $q->where('content', 'like', "%{$keyword}%")
                      ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"))
                      ->orWhereHas('quiz', fn($qz) => $qz->where('title', 'like', "%{$keyword}%"));
                }
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

        // Stats tổng quan Ngân hàng câu hỏi
        $bankBase = Question::whereNotNull('origin_question_id')->where('bank_submission_status', 'approved');
        $totalQuestions = (clone $bankBase)->count();
        $publicQuestions = (clone $bankBase)->where('is_public', true)->count();
        $privateQuestions = (clone $bankBase)->where('is_public', false)->count();
        
        // Thống kê số lượng ticket báo cáo vi phạm liên quan (ReportTicket)
        $reportedCount = \App\Models\ReportTicket::where('status', 'pending')->count();

        $query->latest();
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $paginated = $query->paginate($perPage);

        $items = collect($paginated->items())->map(function (Question $q) {
            $formatted = $this->formatQuestion($q, true);
            $formatted['author_name'] = $q->user?->name ?? $q->quiz?->user?->name ?? 'Vô danh';
            $formatted['author_email'] = $q->user?->email ?? $q->quiz?->user?->email;
            $formatted['author_avatar'] = $q->user?->avatar ?? $q->quiz?->user?->avatar;
            return $formatted;
        });

        return response()->json([
            'success' => true,
            'message' => 'Danh sách câu hỏi ngân hàng câu hỏi',
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

        $targetQuestionIds = array_filter(array_unique([
            $question->id,
            $question->origin_question_id,
        ]));
        $snapshotIds = Question::where('origin_question_id', $question->id)->pluck('id')->all();
        $allRelatedIds = array_values(array_unique(array_merge($targetQuestionIds, $snapshotIds)));

        if ($question->is_public) {
            \App\Models\ReportTicket::whereIn('question_id', $allRelatedIds)
                ->where('status', 'pending')
                ->update(['status' => 'resolved']);
        }

        $question->save();

        // Gửi thông báo cho tác giả của câu hỏi kèm lý do vi phạm (nếu có)
        $author = $question->user ?? $question->quiz?->user;
        if ($author) {
            $action = $question->is_public ? 'shown' : 'hidden';
            $latestReport = \App\Models\ReportTicket::whereIn('question_id', $allRelatedIds)->latest()->first();
            $reason = $latestReport?->reason ?? $latestReport?->description;
            $author->notify(new QuestionModerated($question, $action, $reason));
        }

        $formatted = $this->formatQuestion($question, true);
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

        $originIds = Question::whereIn('id', $ids)->whereNotNull('origin_question_id')->pluck('origin_question_id')->all();
        $snapshotIds = Question::whereIn('origin_question_id', $ids)->pluck('id')->all();
        $allTargetIds = array_values(array_unique(array_merge($ids, $originIds, $snapshotIds)));

        if ($isPublic) {
            \App\Models\ReportTicket::whereIn('question_id', $allTargetIds)
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
     * Admin xóa 1 câu hỏi thuộc Ngân hàng câu hỏi (Soft Delete)
     */
    public function adminDelete(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $question = Question::find($id);
        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy câu hỏi.'], 404);
        }

        if (Gate::forUser($user)->denies('delete', $question)) {
            return response()->json([
                'success' => false,
                'message' => 'Admin chỉ được xóa câu hỏi snapshot đã được duyệt vào Ngân hàng câu hỏi.',
            ], 403);
        }

        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã chuyển câu hỏi ngân hàng vào thùng rác thành công.',
        ]);
    }

    /**
     * Admin xóa hàng loạt câu hỏi (Bulk Delete)
     */
    public function adminBulkDelete(Request $request)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer|exists:questions,id',
        ]);

        $ids = $request->input('question_ids');
        $questions = Question::whereIn('id', $ids)->get();

        $deletedCount = 0;
        $unauthorizedCount = 0;

        foreach ($questions as $question) {
            if (Gate::forUser($user)->allows('delete', $question)) {
                $question->delete();
                $deletedCount++;
            } else {
                $unauthorizedCount++;
            }
        }

        if ($deletedCount === 0 && $unauthorizedCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa các câu hỏi đã chọn do không thỏa mãn điều kiện là câu hỏi snapshot đã duyệt trong Ngân hàng.',
            ], 403);
        }

        $message = "Đã chuyển {$deletedCount} câu hỏi vào thùng rác.";
        if ($unauthorizedCount > 0) {
            $message .= " (Bỏ qua {$unauthorizedCount} câu hỏi không hợp lệ hoặc không có quyền xóa)";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'deleted_count' => $deletedCount,
                'skipped_count' => $unauthorizedCount,
            ]
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

        $formatted = $this->formatQuestion($question, true);

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
        $targetQuestionIds = array_filter(array_unique([
            $question->id,
            $question->origin_question_id,
        ]));
        $snapshotIds = Question::where('origin_question_id', $question->id)->pluck('id')->all();
        $allRelatedIds = array_values(array_unique(array_merge($targetQuestionIds, $snapshotIds)));

        $reports = \App\Models\ReportTicket::with('user:id,name,email')
            ->whereIn('question_id', $allRelatedIds)
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

        // Review diff & history details (lấy từ câu hỏi gốc hoặc chính câu hỏi)
        $originQuestion = $question->origin_question_id 
            ? (Question::find($question->origin_question_id) ?? $question) 
            : $question;

        $diffData = $this->reviewService->getReviewDetailsWithDiff($originQuestion);
        $formatted['review_details'] = $diffData;
        $formatted['current_revision'] = $diffData['current_revision'] ?? null;
        $formatted['previous_revision'] = $diffData['previous_revision'] ?? null;
        $formatted['history'] = $diffData['history'] ?? [];
        $formatted['is_priority'] = (bool)($diffData['is_priority'] ?? false);
        $formatted['review_priority'] = $diffData['review_priority'] ?? 'normal';

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
        return response()->json([
            'success' => false,
            'message' => 'Admin không được sửa trực tiếp nội dung câu hỏi trong Ngân hàng câu hỏi. Nội dung câu hỏi phải do tác giả chỉnh sửa và gửi duyệt lại.',
        ], 403);
    }

    /**
     * Lấy danh sách câu hỏi trong Thùng rác cho Admin
     */
    public function adminTrash(Request $request)
    {
        $query = Question::onlyTrashed()
            ->whereNotNull('origin_question_id')
            ->where('bank_submission_status', 'approved')
            ->with(['answers', 'quiz.user:id,name,email,avatar', 'user:id,name,email,avatar', 'educationLevel', 'grade', 'subject']);

        if ($request->filled('search')) {
            $keyword = trim((string) $request->query('search'));
            $cleanKeyword = ltrim($keyword, '#');
            $numericId = is_numeric($cleanKeyword) ? (int) $cleanKeyword : null;

            $query->where(function ($q) use ($keyword, $numericId) {
                if ($numericId !== null) {
                    $q->where('id', $numericId)
                      ->orWhere('origin_question_id', $numericId)
                      ->orWhere('content', 'like', "%{$keyword}%")
                      ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"));
                } else {
                    $q->where('content', 'like', "%{$keyword}%")
                      ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"));
                }
            });
        }

        $query->latest('deleted_at');
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->through(fn(Question $q) => $this->formatQuestion($q, true));

        $trashCount = (clone $query)->count();

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
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $question = Question::onlyTrashed()->findOrFail($id);
        if (Gate::forUser($user)->denies('restore', $question)) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền khôi phục câu hỏi này.'], 403);
        }

        $question->restore();

        return response()->json([
            'success' => true,
            'message' => 'Khôi phục câu hỏi thành công!',
            'data' => $this->formatQuestion($question->fresh(['answers', 'educationLevel', 'grade', 'subject']), true),
        ]);
    }

    /**
     * Admin xóa vĩnh viễn 1 câu hỏi
     */
    public function adminForceDelete($id)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $question = Question::onlyTrashed()->findOrFail($id);
        if (Gate::forUser($user)->denies('forceDelete', $question)) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền xóa vĩnh viễn câu hỏi này.'], 403);
        }

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
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer',
        ]);

        $ids = $request->input('question_ids');
        $questions = Question::onlyTrashed()->whereIn('id', $ids)->get();

        $restoredCount = 0;
        foreach ($questions as $q) {
            if (Gate::forUser($user)->allows('restore', $q)) {
                $q->restore();
                $restoredCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã khôi phục ' . $restoredCount . ' câu hỏi thành công.',
        ]);
    }

    /**
     * Admin xóa vĩnh viễn hàng loạt từ Thùng rác
     */
    public function adminBulkForceDelete(Request $request)
    {
        $user = auth('api')->user();
        if (!$user || strtolower($user->role ?? '') !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer',
        ]);

        $ids = $request->input('question_ids');
        $questions = Question::onlyTrashed()->whereIn('id', $ids)->get();

        $deletedCount = 0;
        foreach ($questions as $q) {
            if (Gate::forUser($user)->allows('forceDelete', $q)) {
                $q->answers()->delete();
                $q->forceDelete();
                $deletedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa vĩnh viễn ' . $deletedCount . ' câu hỏi.',
        ]);
    }
}



