<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\QuizModerated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->resolveOptionalApiUser();

        $query = Quiz::query()
            ->with(['user:id,name', 'educationLevel', 'grade', 'subject'])
            ->withCount(['questions', 'attempts'])
            ->withAvg(['attempts as avg_score' => fn($q) => $q->where('status', 'completed')], 'score')
            ->latest();

        if ($request->filled('search')) {
            $keyword = trim((string) $request->query('search'));
            $cleanKeyword = ltrim($keyword, '#');
            $numericId = is_numeric($cleanKeyword) ? (int) $cleanKeyword : null;

            $query->where(function ($q) use ($keyword, $numericId) {
                if ($numericId !== null) {
                    $q->where('id', $numericId)
                        ->orWhere('title', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('category', 'like', "%{$keyword}%")
                        ->orWhere('tag', 'like', "%{$keyword}%")
                        ->orWhere('topic_name', 'like', "%{$keyword}%")
                        ->orWhere('room_code', 'like', "%{$keyword}%")
                        ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%"));
                } else {
                    $q->where('title', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('category', 'like', "%{$keyword}%")
                        ->orWhere('tag', 'like', "%{$keyword}%")
                        ->orWhere('topic_name', 'like', "%{$keyword}%")
                        ->orWhere('room_code', 'like', "%{$keyword}%")
                        ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%"));
                }
            });
        }

        if ($request->filled('category')) {
            $cat = trim((string) $request->query('category'));
            $cleanCat = preg_replace('/\s*(học|hoc)$/ui', '', $cat);

            $query->where(function ($q) use ($cat, $cleanCat) {
                $q->where(function ($subQ) use ($cat, $cleanCat) {
                    // 1. Khớp theo Môn học (Subject relationship)
                    $subQ->whereHas('subject', function ($sq) use ($cat, $cleanCat) {
                        $sq->where('name', $cat)
                            ->orWhere('name', $cleanCat)
                            ->orWhere('name', 'like', "%{$cat}%");
                    })
                    // 2. Khớp theo Category / Tag / Topic_name
                    ->orWhere('category', $cat)
                    ->orWhere('category', $cleanCat)
                    ->orWhere('tag', $cat)
                    ->orWhere('tag', $cleanCat)
                    ->orWhere('topic_name', $cat)
                    ->orWhere('topic_name', $cleanCat)
                    // 3. Khớp theo Tiêu đề Quiz
                    ->orWhereRaw("BINARY title LIKE ?", ["%{$cat}%"]);
                })
                // 4. Nếu Quiz chưa gán subject_id thì mới fallback tìm theo câu hỏi
                ->orWhere(function ($fallbackQ) use ($cat, $cleanCat) {
                    $fallbackQ->whereNull('subject_id')
                        ->whereHas('questions.subject', function ($sq) use ($cat, $cleanCat) {
                            $sq->where('name', $cat)
                                ->orWhere('name', $cleanCat);
                        });
                });
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
            $query->where('topic_name', 'like', "%" . trim((string)$request->query('topic_name')) . "%");
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $this->normalizeDifficulty($request->query('difficulty')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('creation_mode')) {
            $query->where('creation_mode', $request->query('creation_mode'));
        }

        if ($request->filled('review_status')) {
            $query->where('review_status', $request->query('review_status'));
        }

        $isAdmin = $user && strtolower($user->role ?? '') === 'admin';
        $visibility = $request->query('visibility');
        $owner = strtolower((string) $request->query('owner', ''));
        $mineOnly = $request->boolean('mine') || in_array($owner, ['me', 'mine', 'self'], true);

        if ($mineOnly) {
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần đăng nhập để xem kho quiz của mình.',
                ], 401);
            }

            $query->where('user_id', $user->id);
        }

        if ($visibility === 'public') {
            $query->where('is_public', true)->where('status', 'published');
        } elseif ($visibility === 'private') {
            $query->where('is_public', false)->whereNull('room_code');
            if (!$isAdmin) {
                $query->where('user_id', $user?->id ?? -1);
            }
        } elseif ($visibility === 'group') {
            $query->whereNotNull('room_code');
            if (!$isAdmin) {
                $query->where(function ($q) use ($user) {
                    $q->where('is_public', true)
                        ->orWhere('user_id', $user?->id ?? -1);
                });
            }
        } else {
            // Default or visibility = 'all':
            // - ADMIN: sees all quizzes.
            // - FREE/PLUS/PRO/ULTRA/GUEST: sees public published quizzes OR their own quizzes.
            // - When mine/owner=me is requested, keep the owner-only filter above.
            if (!$mineOnly && !$isAdmin) {
                $query->where(function ($q) use ($user) {
                    $q->where(function ($sub) {
                        $sub->where('is_public', true)->where('status', 'published');
                    });
                    if ($user) {
                        $q->orWhere('user_id', $user->id);
                    }
                });
            }
        }

        $perPage = min(max((int) $request->query('per_page', 12), 1), 100);
        $quizzes = $query->paginate($perPage)->through(fn(Quiz $quiz) => $this->formatQuiz($quiz));

        return response()->json([
            'success' => true,
            'message' => 'Danh sách quiz',
            'data' => $quizzes,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (Gate::forUser($user)->denies('create', Quiz::class)) {
            return response()->json([
                'success' => false,
                'message' => 'Admin không được tạo Quiz trực tiếp.',
            ], 403);
        }

        $data = $this->prepareQuizData($request, $this->validateQuizPayload($request));

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

    public function show($id)
    {
        $quiz = Quiz::withTrashed()->find($id);

        if (!$quiz) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài quiz này hoặc quiz đã bị xóa vĩnh viễn.',
            ], 404);
        }

        if ($quiz->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Bài quiz này đã bị đưa vào thùng rác.',
                'data' => [
                    'id' => $quiz->id,
                    'is_trashed' => true,
                    'title' => $quiz->title,
                ]
            ], 404);
        }

        try {
            $user = $this->resolveOptionalApiUser();
        } catch (TokenExpiredException) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Gate::forUser($user)->authorize('view', $quiz);

        $quiz->load(['user:id,name', 'educationLevel', 'grade', 'subject', 'questions.answers'])
            ->loadCount(['questions', 'attempts'])
            ->loadAvg(['attempts as avg_score' => fn($q) => $q->where('status', 'completed')], 'score');

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết quiz',
            'data' => $this->formatQuiz($quiz, true),
        ]);
    }

    public function editData(Quiz $quiz)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (Gate::forUser($user)->denies('update', $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền chỉnh sửa bài Quiz này.',
            ], 403);
        }

        $quiz->load(['user:id,name', 'educationLevel', 'grade', 'subject', 'questions.answers'])
            ->loadCount(['questions', 'attempts'])
            ->loadAvg(['attempts as avg_score' => fn($q) => $q->where('status', 'completed')], 'score');

        return response()->json([
            'success' => true,
            'message' => 'Quiz edit data',
            'data' => $this->formatQuiz($quiz, true),
        ]);
    }

    public function update(Request $request, Quiz $quiz)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (Gate::forUser($user)->denies('update', $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền chỉnh sửa bài Quiz này.',
            ], 403);
        }

        $data = $this->prepareQuizData($request, $this->validateQuizPayload($request, true), $quiz);

        $quiz = DB::transaction(function () use ($quiz, $data) {
            $quiz->update($this->quizAttributes($data, $quiz->user_id, $quiz));

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
        $currentUser = auth('api')->user();

        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để thực hiện thao tác này.'
            ], 401);
        }

        if (Gate::forUser($currentUser)->denies('delete', $quiz)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa quiz này.'
            ], 403);
        }

        if ($quiz->trashed()) {
            return response()->json([
                'success' => true,
                'message' => 'Quiz đã được xóa mềm trước đó.',
            ]);
        }

        $quiz->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa mềm quiz',
        ]);
    }
    public function toggleVisibility($id)
    {
        $user = auth('api')->user();

        // Chỉ admin mới được ẩn/hiện quiz
        if (!$user || strtolower($user->role) !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền thực hiện thao tác này.'
            ], 403);
        }

        $quiz = Quiz::findOrFail($id);

        // Đảo trạng thái public/private
        $quiz->is_public = !$quiz->is_public;
        $quiz->save();

        // Gửi thông báo cho người tạo quiz
        $owner = User::find($quiz->user_id);

        if ($owner) {
            $owner->notify(new QuizModerated(
                $quiz,
                $quiz->is_public ? 'shown' : 'hidden'
            ));
        }

        return response()->json([
            'success' => true,
            'message' => $quiz->is_public
                ? 'Quiz đã được hiển thị lại'
                : 'Quiz đã được ẩn do vi phạm',
            'data' => [
                'id' => $quiz->id,
                'is_public' => $quiz->is_public,
            ]
        ]);
    }
    private function validateQuizPayload(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'title' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'education_level_id' => ['nullable', 'integer', 'exists:education_levels,id'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'topic_name' => ['nullable', 'string', 'max:150'],
            'curriculum_unit_ids' => ['nullable', 'array'],
            'curriculum_unit_ids.*' => ['integer', 'distinct', 'exists:curriculum_units,id'],
            'tag' => ['nullable', 'string', 'max:100'],
            'difficulty' => ['nullable', 'string', Rule::in(['easy', 'medium', 'hard', 'Dễ', 'Vừa', 'Khó'])],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'visibility' => ['nullable', Rule::in(['public', 'private', 'group'])],
            'is_public' => ['nullable', 'boolean'],
            'room_code' => ['nullable', 'string', 'max:32'],
            'roomCode' => ['nullable', 'string', 'max:32'],
            'time_limit_seconds' => ['nullable', 'integer', 'min:30', 'max:86400'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'duration' => ['nullable', 'string', 'max:50'],
            'cover' => ['nullable', 'string', 'max:2048'],
            'cover_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'remove_cover' => ['nullable', 'boolean'],
            'icon' => ['nullable', 'string', 'max:32'],
            'badge' => ['nullable', 'string', 'max:32'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'questions' => ['nullable', 'array'],
            'questions.*.id' => ['nullable', 'integer', 'exists:questions,id'],
            'questions.*.content' => ['nullable', 'string'],
            'questions.*.text' => ['nullable', 'string'],
            'questions.*.image_url' => ['nullable'],
            'questions.*.images' => ['nullable'],
            'questions.*.type' => ['nullable', Rule::in(['single_choice', 'multi_choice', 'multiple_choice', 'true_false', 'fill_blank'])],
            'questions.*.points' => ['nullable', 'numeric', 'min:0.01', 'max:1000'],
            'questions.*.order' => ['nullable', 'integer', 'min:0'],
            'questions.*.origin_question_id' => ['nullable', 'integer', 'exists:questions,id'],
            'questions.*.correct' => ['nullable'],
            'questions.*.answers' => ['required_with:questions', 'array', 'min:2'],
            'questions.*.answers.*.id' => ['nullable', 'integer', 'exists:answers,id'],
            'questions.*.answers.*.content' => ['nullable', 'string'],
            'questions.*.answers.*.text' => ['nullable', 'string'],
            'questions.*.answers.*.key' => ['nullable', 'string', 'max:4'],
            'questions.*.answers.*.is_correct' => ['nullable', 'boolean'],
            'questions.*.answers.*.order' => ['nullable', 'integer', 'min:0'],
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề bài Quiz.',
            'subject_id.exists' => 'Bộ môn được chọn không tồn tại.',
            'questions.*.answers.required_with' => 'Mỗi câu hỏi phải có danh sách đáp án.',
            'questions.*.answers.min' => 'Mỗi câu hỏi trắc nghiệm phải có ít nhất 2 đáp án.',
            'questions.*.answers.*.content.required' => 'Vui lòng điền nội dung cho tất cả các lựa chọn đáp án.',
            'questions.*.answers.*.text.required' => 'Vui lòng điền nội dung cho tất cả các lựa chọn đáp án.',
        ]);
    }

    private function prepareQuizData(Request $request, array $data, ?Quiz $quiz = null): array
    {
        if ($request->hasFile('cover_file')) {
            $this->deleteStoredCover($quiz?->cover);
            $path = $request->file('cover_file')->store('quiz-covers', 'public');
            $data['cover'] = $this->storedCoverPublicUrl($path);
        } elseif ($request->boolean('remove_cover')) {
            $this->deleteStoredCover($quiz?->cover);
            $data['cover'] = null;
        }

        return $data;
    }

    private function storedCoverPublicUrl(string $path): string
    {
        return url(Storage::url($path));
    }

    private function resolveCoverForResponse(?string $cover): ?string
    {
        if (!$cover) {
            return null;
        }

        if (str_starts_with($cover, '/storage/')) {
            return url($cover);
        }

        return $cover;
    }

    private function deleteStoredCover(?string $cover): void
    {
        if (!$cover) {
            return;
        }

        $path = parse_url($cover, PHP_URL_PATH) ?: $cover;

        if (!str_starts_with($path, '/storage/quiz-covers/')) {
            return;
        }

        Storage::disk('public')->delete(str_replace('/storage/', '', $path));
    }

    private function quizAttributes(array $data, int $userId, ?Quiz $currentQuiz = null): array
    {
        $user = auth('api')->user();
        $isAdmin = $user && strtolower($user->role ?? '') === 'admin';

        $visibility = $data['visibility'] ?? null;
        $roomCode = $data['room_code'] ?? $data['roomCode'] ?? $currentQuiz?->room_code;
        
        $creationMode = $currentQuiz?->creation_mode ?? ($data['creation_mode'] ?? 'manual');

        if ($creationMode === 'manual') {
            // Quiz thủ công: User thường không được tự ý công khai (phải qua kiểm duyệt)
            if ($isAdmin) {
                $isPublic = array_key_exists('is_public', $data)
                    ? (bool) $data['is_public']
                    : ($visibility === null && $currentQuiz ? (bool) $currentQuiz->is_public : $visibility === 'public');
                $reviewStatus = $isPublic ? 'approved' : ($data['review_status'] ?? $currentQuiz?->review_status ?? 'draft');
                $status = $data['status'] ?? $currentQuiz?->status ?? ($isPublic ? 'published' : 'draft');
            } else {
                // User thường: Luôn ở chế độ private trừ khi đã được Admin duyệt
                $isPublic = false;
                $reviewStatus = $currentQuiz?->review_status ?? 'draft';
                $status = $currentQuiz?->status ?? 'draft';
            }
        } else {
            // Quiz tự động (auto): 100% câu hỏi từ Ngân hàng đã duyệt
            $isPublic = array_key_exists('is_public', $data)
                ? (bool) $data['is_public']
                : ($visibility === null && $currentQuiz ? (bool) $currentQuiz->is_public : $visibility === 'public');
            $reviewStatus = $isPublic ? 'approved' : 'draft';
            $status = $data['status'] ?? $currentQuiz?->status ?? ($isPublic ? 'published' : 'draft');
        }

        if ($visibility === 'group') {
            $isPublic = false;
        }

        $rawCategory = $data['category'] ?? $currentQuiz?->category;
        $category = (!empty($data['topic_name']) && (empty($rawCategory) || $rawCategory === 'General' || $rawCategory === 'Khoa học'))
            ? $data['topic_name']
            : ($rawCategory ?: 'General');

        return [
            'user_id' => $userId,
            'title' => $data['title'] ?? $currentQuiz?->title ?? 'Untitled quiz',
            'description' => array_key_exists('description', $data) ? $data['description'] : $currentQuiz?->description,
            'category' => $category,
            'education_level_id' => array_key_exists('education_level_id', $data) ? $data['education_level_id'] : $currentQuiz?->education_level_id,
            'grade_id' => array_key_exists('grade_id', $data) ? $data['grade_id'] : $currentQuiz?->grade_id,
            'subject_id' => array_key_exists('subject_id', $data) ? $data['subject_id'] : $currentQuiz?->subject_id,
            'topic_name' => array_key_exists('topic_name', $data) ? $data['topic_name'] : $currentQuiz?->topic_name,
            'curriculum_unit_ids' => array_key_exists('curriculum_unit_ids', $data) ? array_values(array_unique(array_map('intval', $data['curriculum_unit_ids'] ?? []))) : ($currentQuiz?->curriculum_unit_ids ?? []),
            'tag' => array_key_exists('tag', $data) ? $data['tag'] : $currentQuiz?->tag,
            'difficulty' => $this->normalizeDifficulty($data['difficulty'] ?? $currentQuiz?->difficulty ?? 'medium'),
            'creation_mode' => $creationMode,
            'review_status' => $reviewStatus,
            'rejection_reason' => $currentQuiz?->rejection_reason,
            'reviewed_by' => $currentQuiz?->reviewed_by,
            'reviewed_at' => $currentQuiz?->reviewed_at,
            'status' => $status,
            'is_public' => $isPublic,
            'room_code' => $roomCode,
            'time_limit_seconds' => $this->resolveTimeLimitSeconds($data, $currentQuiz),
            'cover' => array_key_exists('cover', $data) ? ($data['cover'] ?: null) : $currentQuiz?->cover,
            'icon' => array_key_exists('icon', $data) ? ($data['icon'] ?: null) : $currentQuiz?->icon,
            'badge' => array_key_exists('badge', $data) ? ($data['badge'] ?: mb_strtoupper(mb_substr($category, 0, 4, 'UTF-8'), 'UTF-8')) : $currentQuiz?->badge,
        ];
    }

    private function syncQuestions(Quiz $quiz, array $questions): void
    {
        $syncData = [];
        $fingerprints = [];
        $snapshotService = app(\App\Services\QuestionSnapshotService::class);

        // Validate the complete incoming set before writing anything. This keeps
        // one Quiz free of duplicate question snapshots and returns a useful
        // validation error instead of allowing a later database conflict.
        foreach ($questions as $index => $questionData) {
            $content = trim((string) ($questionData['content'] ?? $questionData['text'] ?? ''));
            if ($content === '') {
                continue;
            }

            $fingerprint = $snapshotService->computeFingerprintFromSnapshot(
                $content,
                $questionData['type'] ?? 'single_choice',
                $questionData['answers'] ?? []
            );
            if (isset($fingerprints[$fingerprint])) {
                throw ValidationException::withMessages([
                    "questions.{$index}" => 'Câu ' . ($index + 1) . ' trùng với câu ' . ($fingerprints[$fingerprint] + 1) . ' trong Quiz.',
                ]);
            }
            $fingerprints[$fingerprint] = $index;
        }

        foreach ($questions as $index => $questionData) {
            $questionContent = trim((string) ($questionData['content'] ?? $questionData['text'] ?? ''));
            if ($questionContent === '') {
                continue;
            }

            $existingQuestion = !empty($questionData['id'])
                ? Question::with('answers')->find($questionData['id'])
                : null;

            // A Bank question is stored as an immutable quiz snapshot. Even if a
            // caller bypasses the V2 UI, its content, type, image and answers
            // cannot be altered while it keeps the approved Bank provenance.
            if ($existingQuestion?->origin_question_id) {
                $incomingFingerprint = $snapshotService->computeFingerprintFromSnapshot(
                        $questionContent,
                        $questionData['type'] ?? 'single_choice',
                        $questionData['answers'] ?? []
                    );
                $currentFingerprint = $snapshotService->computeFingerprint($existingQuestion);

                if ($incomingFingerprint !== $currentFingerprint || ($questionData['image_url'] ?? null) !== $existingQuestion->image_url) {
                    throw ValidationException::withMessages([
                        "questions.{$index}" => 'Câu ' . ($index + 1) . ': Câu hỏi từ Ngân hàng đã kiểm duyệt không thể chỉnh sửa. Hãy nhân bản câu hỏi để tạo bản sao riêng.',
                    ]);
                }
            }

            $questionValues = [
                    'user_id' => $questionData['user_id'] ?? $quiz->user_id,
                    'education_level_id' => $questionData['education_level_id'] ?? $quiz->education_level_id,
                    'grade_id' => $questionData['grade_id'] ?? $quiz->grade_id,
                    'subject_id' => $questionData['subject_id'] ?? $quiz->subject_id,
                    'topic_name' => $questionData['topic_name'] ?? $quiz->topic_name,
                    'content' => $questionContent,
                    'type' => $questionData['type'] ?? 'single_choice',
                    'order' => $questionData['order'] ?? $index,
                    'points' => max(0.01, (float) ($questionData['points'] ?? 1.0)),
                    'image_url' => is_string($questionData['image_url'] ?? null)
                        ? $questionData['image_url']
                        : (is_array($questionData['image_url'] ?? null)
                            ? ($questionData['image_url']['preview'] ?? $questionData['image_url']['url'] ?? null)
                            : (is_array($questionData['images'][0] ?? null)
                                ? ($questionData['images'][0]['preview'] ?? $questionData['images'][0]['url'] ?? null)
                                : (is_string($questionData['images'][0] ?? null) ? $questionData['images'][0] : null))),
            ];

            if (empty($questionData['id'])) {
                // A question created from the quiz editor is an independent
                // quiz snapshot, not a new public/personal-bank entry.
                $questionValues['quiz_id'] = $quiz->id;
                $questionValues['is_public'] = false;

                if (!empty($questionData['origin_question_id'])) {
                    $bankQuestion = Question::query()
                        ->whereKey($questionData['origin_question_id'])
                        ->where('is_public', true)
                        // Keep this definition aligned with QuestionController::bank().
                        // Older approved Bank snapshots may not have
                        // bank_submission_status populated, but are public with
                        // status=approved and are valid selectable Bank records.
                        ->where('status', 'approved')
                        ->first();

                    if (!$bankQuestion) {
                        throw ValidationException::withMessages([
                            "questions.{$index}.origin_question_id" => 'Câu ' . ($index + 1) . ': Câu hỏi nguồn không còn là câu hỏi Ngân hàng đã được duyệt.',
                        ]);
                    }

                    $questionValues['origin_question_id'] = $bankQuestion->id;
                }
            }

            $question = Question::updateOrCreate(
                ['id' => $questionData['id'] ?? null],
                $questionValues
            );

            $this->syncAnswers($question, $questionData['answers'] ?? [], $questionData['correct'] ?? null);

            $syncData[$question->id] = [
                'order' => $questionData['order'] ?? $index,
                'points' => max(0.01, (float) ($questionData['points'] ?? 1.0)),
            ];
        }

        $quiz->questions()->sync($syncData);
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

        $snapshotService = app(\App\Services\QuestionSnapshotService::class);
        $question->updateQuietly([
            'fingerprint' => $snapshotService->computeFingerprint($question->fresh('answers'))
        ]);
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

    private function resolveTimeLimitSeconds(array $data, ?Quiz $currentQuiz = null): ?int
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

        return $currentQuiz?->time_limit_seconds ?? 600;
    }

    private function resolveOptionalApiUser(): ?User
    {
        try {
            return auth('api')->user();
        } catch (\Throwable $exception) {
            return null;
        }
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
        $visibility = $quiz->room_code ? 'group' : ($quiz->is_public ? 'public' : 'private');

        $data = [
            'id' => $quiz->id,
            'user_id' => $quiz->user_id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'category' => (!empty($quiz->category) && $quiz->category !== 'General' && $quiz->category !== 'Khoa học') 
                ? $quiz->category 
                : ($quiz->topic_name ?: ($quiz->subject?->name ?: ($quiz->category ?: 'General'))),
            'education_level_id' => $quiz->education_level_id,
            'education_level_name' => $quiz->educationLevel?->name,
            'grade_id' => $quiz->grade_id,
            'grade_name' => $quiz->grade?->name,
            'subject_id' => $quiz->subject_id,
            'subject_name' => $quiz->subject?->name,
            'subject_icon' => $quiz->subject?->icon,
            'topic_name' => $quiz->topic_name,
            'curriculum_unit_ids' => $quiz->curriculum_unit_ids ?? [],
            'tag' => $quiz->tag ?? $quiz->category,
            'difficulty' => $quiz->difficulty,
            'difficulty_label' => $this->difficultyLabel($quiz->difficulty),
            'creation_mode' => $quiz->creation_mode ?? 'manual',
            'review_status' => $quiz->review_status ?? ($quiz->is_public ? 'approved' : 'draft'),
            'rejection_reason' => $quiz->rejection_reason,
            'reviewed_at' => $quiz->reviewed_at ? $quiz->reviewed_at->toIso8601String() : null,
            'pending_review' => $quiz->review_status === 'pending_review',
            'status' => $quiz->status,
            'is_public' => (bool) $quiz->is_public,
            'visibility' => $visibility,
            'room_code' => $quiz->room_code,
            'time_limit_seconds' => $timeLimit,
            'duration_minutes' => (int) ceil($timeLimit / 60),
            'questions_count' => $quiz->questions_count ?? $quiz->questions()->count(),
            'attempts_count' => $quiz->attempts_count ?? $quiz->attempts()->count(),
            'avg_score' => round((float) ($quiz->avg_score ?? 0), 2),
            'author' => $quiz->user?->name ?? 'QuizFlex',
            'cover' => $this->resolveCoverForResponse($quiz->cover) ?? 'linear-gradient(135deg, #0f172a, #7c3aed)',
            'icon' => $quiz->icon ?? 'QZ',
            'badge' => $quiz->badge ?? mb_strtoupper(mb_substr($quiz->category ?? 'Quiz', 0, 4, 'UTF-8'), 'UTF-8'),
            'created_at' => $quiz->created_at,
            'updated_at' => $quiz->updated_at,
        ];

        if ($includeQuestions) {
            $data['questions'] = $quiz->questions->map(fn(Question $question) => $this->formatQuestion($question))->values();
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
            'difficulty' => $question->difficulty ?? 'medium',
            'education_level_id' => $question->education_level_id,
            'grade_id' => $question->grade_id,
            'subject_id' => $question->subject_id,
            'topic_name' => $question->topic_name,
            'order' => $question->pivot?->order ?? $question->order,
            'points' => $question->pivot?->points ?? $question->points,
            'origin_question_id' => $question->origin_question_id,
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

    private function difficultyLabel(string $difficulty): string
    {
        return match ($difficulty) {
            'easy' => 'Dễ',
            'hard' => 'Khó',
            default => 'Vừa',
        };
    }

    // =========================
    // 3.1 QUẢN LÝ QUIZ - ADMIN
    // =========================

   
   // Danh sách quiz
public function adminIndex(Request $request)
{
    $query = Quiz::query()
        ->with('user:id,name')
        ->withCount(['questions', 'attempts'])
        ->withAvg(['attempts as avg_score' => fn($q) => $q->where('status', 'completed')], 'score')
        ->latest();

    // Tìm kiếm quiz
    if ($request->filled('search')) {
        $keyword = trim((string) $request->search);
        $cleanKeyword = ltrim($keyword, '#');
        $numericId = is_numeric($cleanKeyword) ? (int) $cleanKeyword : null;

        $query->where(function ($q) use ($keyword, $numericId) {
            if ($numericId !== null) {
                $q->where('id', $numericId)
                    ->orWhere('title', 'like', "%{$keyword}%")
                    ->orWhere('category', 'like', "%{$keyword}%")
                    ->orWhere('tag', 'like', "%{$keyword}%")
                    ->orWhere('topic_name', 'like', "%{$keyword}%")
                    ->orWhere('room_code', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"));
            } else {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('category', 'like', "%{$keyword}%")
                    ->orWhere('tag', 'like', "%{$keyword}%")
                    ->orWhere('topic_name', 'like', "%{$keyword}%")
                    ->orWhere('room_code', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%"));
            }
        });
    }

    // Tìm kiếm người tạo
    if ($request->filled('creator')) {
        $query->whereHas('user', function ($q) use ($request) {
            $q->where(
                'name',
                'like',
                '%' . $request->creator . '%'
            );
        });
    }

    // Lọc độ khó
    if ($request->filled('difficulty')) {
        $query->where(
            'difficulty',
            $request->difficulty
        );
    }

    // Lọc môn học
    if ($request->filled('subject_id')) {
        $query->where('subject_id', $request->subject_id);
    }

    // Lọc khối lớp
    if ($request->filled('grade_id')) {
        $query->where('grade_id', $request->grade_id);
    }

    // Lọc public/private
    if ($request->filled('visibility')) {
        if ($request->visibility === 'public') {
            $query->where('is_public', true);
        }
        if ($request->visibility === 'private') {
            $query->where('is_public', false);
        }
    }

    // Lọc trạng thái duyệt
    if ($request->filled('review_status')) {
        $query->where('review_status', $request->review_status);
    }

    // Lọc chế độ tạo
    if ($request->filled('creation_mode')) {
        $query->where('creation_mode', $request->creation_mode);
    }

    // Lọc quiz sinh bởi AI
    if ($request->filled('ai_generated')) {
        $query->where(
            'is_ai_generated',
            (bool)$request->ai_generated
        );
    }

    $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

    $quizzes = $query
        ->paginate($perPage)
        ->through(function ($quiz) {
            return [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'category' => $quiz->category,
                'difficulty' => $quiz->difficulty,
                'difficulty_label' => $this->difficultyLabel($quiz->difficulty),
                'questions_count' => $quiz->questions_count,
                'attempts_count' => $quiz->attempts_count,
                'avg_score' => round((float) ($quiz->avg_score ?? 0), 1),
                'creation_mode' => $quiz->creation_mode ?? 'manual',
                'review_status' => $quiz->review_status ?? ($quiz->is_public ? 'approved' : 'draft'),
                'rejection_reason' => $quiz->rejection_reason,
                'is_public' => (bool)$quiz->is_public,
                'author' => $quiz->user?->name ?? 'Chưa có',
                'created_at' => $quiz->created_at,
            ];
        });

    // ✅ THÊM MỚI: tính thống kê tổng toàn hệ thống (không phụ thuộc filter/trang hiện tại)
    $stats = [
        'total' => Quiz::count(),
        'public' => Quiz::where('is_public', true)->count(),
        'private' => Quiz::where('is_public', false)->count(),
    ];

    return response()->json([
        'success' => true,
        'message' => 'Danh sách quiz',
        // ✅ Gộp 'stats' vào cùng cấp với total/last_page/data bên trong object phân trang
        'data' => array_merge($quizzes->toArray(), [
            'stats' => $stats,
        ]),
    ]);
}
    // Chi tiết quiz
    public function adminShow($id)
    {
        $quiz = Quiz::withTrashed()
            ->with([
                'user:id,name,email,avatar',
                'educationLevel',
                'grade',
                'subject',
                'questions.answers',
                'questions.user:id,name,email',
                'attempts.user:id,name'
            ])
            ->withCount([
                'questions',
                'attempts'
            ])
            ->findOrFail($id);

        $averageScore = round(
            $quiz->attempts()->avg('score') ?? 0,
            2
        );

        $reviewService = app(\App\Services\QuizReviewService::class);
        $diffData = $reviewService->getReviewDetailsWithDiff($quiz);

        return response()->json([
            'success' => true,
            'data' => [
                'quiz' => $quiz,
                'average_score' => $averageScore,
                'is_ai_generated' => (bool)$quiz->is_ai_generated,
                'current_revision' => $diffData['current_revision'] ?? null,
                'previous_revision' => $diffData['previous_revision'] ?? null,
                'previous_rejection_reason' => $diffData['previous_rejection_reason'] ?? null,
                'diff' => $diffData['diff'] ?? null,
                'history' => $diffData['history'] ?? [],
            ]
        ]);
    }

    // Thùng rác
    public function trash()
    {
        $user = Auth::user();


        $quizzes = Quiz::onlyTrashed()
            ->where('user_id', $user->id)
            ->with('user')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $quizzes
        ]);
    }
    // Khôi phục quiz
    public function restore($id)
    {
        $admin = Auth::user();

        $quiz = Quiz::onlyTrashed()
            ->where('id', $id)
            ->where('user_id', $admin->id)
            ->firstOrFail();

        $quiz->restore();

        return response()->json([
            'message' => 'Khôi phục thành công',
            'data' => $quiz
        ]);
    }
    // Xóa vĩnh viễn
    public function forceDelete($id)
    {
        $admin = Auth::user();

        $quiz = Quiz::onlyTrashed()
            ->where('id', $id)
            ->where('user_id', $admin->id)
            ->firstOrFail();

        $quiz->forceDelete();

        return response()->json([
            'message' => 'Đã xóa vĩnh viễn'
        ]);
    }
    public function adminTrash()
    {
        $admin = Auth::user();

        $quizzes = Quiz::onlyTrashed()
            ->where('user_id', $admin->id)
            ->with('user')
            ->get();

        return response()->json([
            'data' => $quizzes
        ]);
    }
}
