<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // ─── Danh sách ───────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = User::query()->withCount(['quizzes', 'attempts'])->latest();

        if ($request->filled('search')) {
            $kw = trim((string) $request->query('search'));
            $query->where(fn($q) => $q->where('name', 'like', "%{$kw}%")->orWhere('email', 'like', "%{$kw}%"));
        }

        if ($request->filled('role') && $request->query('role') !== 'all') {
            $query->where('role', strtolower((string) $request->query('role')));
        }

        if ($request->filled('plan') && $request->query('plan') !== 'all') {
            $query->where('plan', strtolower((string) $request->query('plan')));
        }

        $perPage = min(max((int) $request->query('per_page', 50), 1), 100);
        $users   = $query->paginate($perPage)->through(fn(User $u) => $this->formatUser($u));

        return response()->json(['success' => true, 'message' => 'Danh sách người dùng', 'data' => $users]);
    }

    // ─── Tạo mới ─────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $actor = auth('api')->user();

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'        => ['required', 'string', 'min:6', 'max:255'],
            'role'            => ['nullable', Rule::in(['admin', 'user'])],
            'plan'            => ['nullable', Rule::in(['free', 'plus', 'pro', 'ultra'])],
            'avatar'          => ['nullable', 'string', 'max:255'],
            'plan_expires_at' => ['nullable', 'date'],
            'plan_started_at' => ['nullable', 'date'],
            'is_main_admin'   => ['nullable', 'boolean'],
        ]);

        $role = strtolower($data['role'] ?? 'user');
        $plan = strtolower($data['plan'] ?? 'free');
        $isMainAdmin = (bool) ($data['is_main_admin'] ?? false);

        // Chỉ admin chính mới được tạo admin
        if ($role === 'admin') {
            if (!$actor || !$this->isMainAdminActor($actor)) {
                return response()->json(['success' => false, 'message' => 'Chỉ admin chính mới được tạo tài khoản admin.'], 403);
            }
        }

        $planExpiresAt = null;
        $planStartedAt = null;
        if ($plan !== 'free') {
            $planExpiresAt = isset($data['plan_expires_at']) ? Carbon::parse($data['plan_expires_at']) : now()->addMonth();
            $planStartedAt = isset($data['plan_started_at']) ? Carbon::parse($data['plan_started_at']) : now();
        }

        $user = User::create([
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => Hash::make($data['password']),
            'role'               => $role,
            'plan'               => $plan,
            'plan_started_at'    => $planStartedAt,
            'plan_expires_at'    => $planExpiresAt,
            'avatar'             => $data['avatar'] ?? null,
            'ai_quota_remaining' => $this->defaultQuotaForPlan($plan),
            'is_main_admin'      => $role === 'admin' ? $isMainAdmin : false,
        ]);

        return response()->json(['success' => true, 'message' => 'Tạo người dùng thành công', 'data' => $this->formatUser($user)], 201);
    }

    // ─── Chi tiết ────────────────────────────────────────────────────────────

    public function show(User $user)
    {
        $user->loadCount(['quizzes', 'attempts']);
        return response()->json(['success' => true, 'message' => 'Chi tiết người dùng', 'data' => $this->formatUserDetail($user)]);
    }

    // ─── Cập nhật ────────────────────────────────────────────────────────────

    public function update(Request $request, User $user)
    {
        $actor = auth('api')->user();

        $actorIsMainAdmin = $this->isMainAdminActor($actor);
        $targetIsMainAdmin = (bool) $user->isMainAdmin();
        $targetIsAdmin = (bool) $user->isAdmin();

        $data = $request->validate([
            'name'            => ['sometimes', 'string', 'max:255'],
            'email'           => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password'        => ['nullable', 'string', 'min:6', 'max:255'],
            'role'            => ['sometimes', Rule::in(['admin', 'user'])],
            'plan'            => ['sometimes', Rule::in(['free', 'plus', 'pro', 'ultra'])],
            'avatar'          => ['nullable', 'string', 'max:255'],
            'plan_expires_at' => ['nullable', 'date'],
            'plan_started_at' => ['nullable', 'date'],
            'is_main_admin'   => ['sometimes', 'boolean'],
        ]);

        $payload = collect($data)->except('password')->all();

        // Admin phụ chỉ được sửa user thông thường, không sửa tài khoản admin nào.
        if ($actor && !$actorIsMainAdmin) {
            if ($targetIsAdmin) {
                return response()->json(['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa tài khoản admin.'], 403);
            }

            $wantsAdminRole = array_key_exists('role', $payload) && strtolower($payload['role']) === 'admin';
            $wantsAdminStateChange = array_key_exists('is_main_admin', $payload);
            if ($wantsAdminRole || $wantsAdminStateChange) {
                return response()->json(['success' => false, 'message' => 'Chỉ admin chính mới được thay đổi quyền admin.'], 403);
            }
        }

        // Admin chính không được sửa admin chính khác, và không được tự hạ quyền chính mình.
        if ($actor && $actorIsMainAdmin && $targetIsMainAdmin && (int) ($actor?->id ?? 0) !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Không thể chỉnh sửa admin chính khác.'], 403);
        }

        if ($actor && $actorIsMainAdmin && (int) ($actor?->id ?? 0) === (int) $user->id) {
            if (array_key_exists('role', $payload) && strtolower($payload['role']) === 'user') {
                return response()->json(['success' => false, 'message' => 'Không thể tự hạ quyền chính mình.'], 403);
            }

            if (array_key_exists('is_main_admin', $payload) && !$payload['is_main_admin']) {
                return response()->json(['success' => false, 'message' => 'Không thể bỏ quyền admin chính khỏi chính mình.'], 403);
            }
        }

        // Chuẩn hóa role
        if (isset($payload['role'])) {
            $payload['role'] = strtolower($payload['role']);
        }

        if (array_key_exists('is_main_admin', $payload)) {
            if (!$actorIsMainAdmin) {
                return response()->json(['success' => false, 'message' => 'Chỉ admin chính mới được thay đổi trạng thái admin chính.'], 403);
            }

            if ((int) ($actor?->id ?? 0) === (int) $user->id && !$payload['is_main_admin']) {
                return response()->json(['success' => false, 'message' => 'Không thể bỏ quyền admin chính khỏi chính mình.'], 403);
            }

            if ($targetIsMainAdmin && (int) ($actor?->id ?? 0) !== (int) $user->id) {
                return response()->json(['success' => false, 'message' => 'Không thể sửa trạng thái admin chính của người khác.'], 403);
            }
        }

        // Xử lý plan — quota luôn được set tự động theo plan
        if (isset($payload['plan'])) {
            $payload['plan'] = strtolower($payload['plan']);

            if ($payload['plan'] === 'free') {
                $payload['plan_started_at'] = null;
                $payload['plan_expires_at'] = null;
            } else {
                if (!isset($payload['plan_started_at'])) {
                    $payload['plan_started_at'] = $user->plan_started_at ?? now();
                }
                if (!isset($payload['plan_expires_at']) && !$user->plan_expires_at) {
                    $payload['plan_expires_at'] = now()->addMonth();
                }
            }

            $payload['ai_quota_remaining'] = $this->defaultQuotaForPlan($payload['plan']);
        }

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $user->loadCount(['quizzes', 'attempts']);

        return response()->json(['success' => true, 'message' => 'Cập nhật người dùng thành công', 'data' => $this->formatUser($user)]);
    }

    public function lock(Request $request, User $user)
    {
        $actor = auth('api')->user();
        $permissionError = $this->authorizeLockAction($actor, $user);
        if ($permissionError) {
            return $permissionError;
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->forceFill([
            'is_locked'     => true,
            'locked_at'     => now(),
            'locked_reason' => trim((string) ($data['reason'] ?? '')) ?: null,
            'locked_by'     => $actor?->id,
        ])->save();

        $user->load(['lockedBy']);
        $user->loadCount(['quizzes', 'attempts']);

        return response()->json(['success' => true, 'message' => 'Tài khoản đã được khóa.', 'data' => $this->formatUser($user)]);
    }

    public function unlock(User $user)
    {
        $actor = auth('api')->user();
        $permissionError = $this->authorizeLockAction($actor, $user);
        if ($permissionError) {
            return $permissionError;
        }

        $user->forceFill([
            'is_locked'     => false,
            'locked_at'     => null,
            'locked_reason' => null,
            'locked_by'     => null,
        ])->save();

        $user->load(['lockedBy']);
        $user->loadCount(['quizzes', 'attempts']);

        return response()->json(['success' => true, 'message' => 'Tài khoản đã được mở khóa.', 'data' => $this->formatUser($user)]);
    }

    // ─── Xóa mềm ─────────────────────────────────────────────────────────────

    public function destroy(User $user)
    {
        $actor = auth('api')->user();
        $actorIsMainAdmin = $this->isMainAdminActor($actor);
        $targetIsMainAdmin = (bool) $user->isMainAdmin();
        $targetIsAdmin = (bool) $user->isAdmin();

        if ($targetIsAdmin) {
            if (!$actor || !$actorIsMainAdmin) {
                return response()->json(['success' => false, 'message' => 'Chỉ admin chính mới được xóa tài khoản admin.'], 403);
            }

            if ($targetIsMainAdmin) {
                if ((int) ($actor?->id ?? 0) === (int) $user->id) {
                    return response()->json(['success' => false, 'message' => 'Không thể xóa chính mình.'], 403);
                }

                return response()->json(['success' => false, 'message' => 'Không thể xóa admin chính.'], 403);
            }
        }

        if ($actor && (int) ($actor?->id ?? 0) === (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Không thể xóa chính mình.'], 403);
        }

        $user->delete();
        return response()->json(['success' => true, 'message' => 'Đã xóa người dùng']);
    }

    // ─── Thùng rác ───────────────────────────────────────────────────────────

    public function trashed(Request $request)
    {
        $query = User::onlyTrashed()->withCount(['quizzes', 'attempts'])->latest('deleted_at');

        if ($request->filled('search')) {
            $kw = trim((string) $request->query('search'));
            $query->where(fn($q) => $q->where('name', 'like', "%{$kw}%")->orWhere('email', 'like', "%{$kw}%"));
        }

        if ($request->filled('role') && $request->query('role') !== 'all') {
            $query->where('role', strtolower((string) $request->query('role')));
        }

        $perPage = min(max((int) $request->query('per_page', 50), 1), 100);
        $users   = $query->paginate($perPage)->through(fn(User $u) => $this->formatUser($u));

        return response()->json(['success' => true, 'message' => 'Danh sách người dùng đã xóa', 'data' => $users]);
    }

    public function restore(int $id)
    {
        $user  = User::onlyTrashed()->findOrFail($id);
        $actor = auth('api')->user();

        if ($user->isAdmin() && (!$actor || !$this->isMainAdminActor($actor))) {
            return response()->json(['success' => false, 'message' => 'Chỉ admin chính mới được khôi phục tài khoản admin.'], 403);
        }

        $user->restore();
        $user->loadCount(['quizzes', 'attempts']);

        return response()->json(['success' => true, 'message' => 'Đã khôi phục người dùng', 'data' => $this->formatUser($user)]);
    }

    public function forceDelete(int $id)
    {
        $user  = User::onlyTrashed()->findOrFail($id);
        $actor = auth('api')->user();

        if ($user->isAdmin()) {
            if (!$actor || !$this->isMainAdminActor($actor)) {
                return response()->json(['success' => false, 'message' => 'Chỉ admin chính mới được xóa vĩnh viễn tài khoản admin.'], 403);
            }
            if ($user->isMainAdmin()) {
                return response()->json(['success' => false, 'message' => 'Không thể xóa vĩnh viễn admin chính.'], 403);
            }
        }

        $user->forceDelete();
        return response()->json(['success' => true, 'message' => 'Đã xóa vĩnh viễn người dùng']);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function defaultQuotaForPlan(string $plan): int
    {
        return match (strtolower($plan)) {
            'ultra' => 1500,
            'pro'   => 350,
            'plus'  => 100,
            default => 5,
        };
    }

    private function isMainAdminActor(?object $actor): bool
    {
        if (!$actor) {
            return false;
        }

        if (method_exists($actor, 'isMainAdmin')) {
            return (bool) $actor->isMainAdmin();
        }

        return strtolower((string) ($actor->role ?? 'user')) === 'admin' && (bool) ($actor->is_main_admin ?? false);
    }

    private function ocrLabelForPlan(string $plan): string
    {
        return match ($plan) {
            'ultra' => '∞',
            'pro'   => '50',
            'plus'  => '10',
            default => '0',
        };
    }

    private function authorizeLockAction(?object $actor, User $target): ?\Illuminate\Http\JsonResponse
    {
        if (!$actor) {
            return response()->json(['success' => false, 'message' => 'Không có quyền thực hiện thao tác này.'], 403);
        }

        if (!$actor->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.'], 403);
        }

        if ((int) ($actor?->id ?? 0) === (int) $target->id) {
            return response()->json(['success' => false, 'message' => 'Không thể thực hiện trên chính mình.'], 403);
        }

        $actorIsMainAdmin = $this->isMainAdminActor($actor);
        $targetIsMainAdmin = (bool) $target->isMainAdmin();

        if ($targetIsMainAdmin) {
            return response()->json(['success' => false, 'message' => 'Không thể khóa hoặc mở khóa admin chính.'], 403);
        }

        if (!$actorIsMainAdmin && $target->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác với tài khoản admin phụ.'], 403);
        }

        return null;
    }

    private function formatUser(User $user): array
    {
        $activePlan = $user->getActivePlan();

        return [
            'id'                 => $user->id,
            'name'               => $user->name,
            'email'              => $user->email,
            'role'               => $user->getRole(),
            'role_label'         => $user->isAdmin() ? 'Admin' : 'User',
            'is_main_admin'      => $user->isMainAdmin(),
            'is_locked'          => (bool) $user->is_locked,
            'locked_at'          => $user->locked_at?->toDateTimeString(),
            'locked_reason'      => $user->locked_reason,
            'locked_by'          => $user->lockedBy ? [
                'id'    => $user->lockedBy->id,
                'name'  => $user->lockedBy->name,
                'email' => $user->lockedBy->email,
            ] : null,
            'plan'               => $activePlan,
            'plan_label'         => ucfirst($activePlan),
            'plan_started_at'    => $user->plan_started_at?->toDateTimeString(),
            'plan_expires_at'    => $user->plan_expires_at?->toDateTimeString(),
            'avatar'             => $this->resolveAvatar($user->avatar),
            'ai_quota_remaining' => $user->ai_quota_remaining,
            'ocr_label'          => $this->ocrLabelForPlan($activePlan),
            'quizzes_count'      => $user->quizzes_count ?? 0,
            'attempts_count'     => $user->attempts_count ?? 0,
            'status'             => $user->is_locked ? 'locked' : 'active',
            'joined_at'          => $user->created_at,
            'created_at'         => $user->created_at,
            'updated_at'         => $user->updated_at,
            'deleted_at'         => $user->deleted_at?->toDateTimeString(),
            // Tương thích ngược với frontend cũ
            'vip_expires_at'     => $user->plan_expires_at?->toDateTimeString(),
        ];
    }

    private function formatUserDetail(User $user): array
    {
        $user->load(['payments', 'lockedBy']);
        $userData = $this->formatUser($user);

        $payments        = Payment::where('user_id', $user->id)->orderByDesc('created_at')->get();
        $successPayments = $payments->where('status', 'success');

        $paidMonths = $successPayments
            ->filter(fn($p) => $p->paid_at)
            ->map(fn($p) => Carbon::parse($p->paid_at)->format('Y-m'))
            ->unique()->values()->all();

        $paymentMonths = $payments
            ->filter(fn($p) => $p->paid_at)
            ->map(fn($p) => Carbon::parse($p->paid_at)->format('Y-m'))
            ->unique()->values()->all();

        $quizIds    = Quiz::where('user_id', $user->id)->pluck('id')->all();
        $quizScores = QuizAttempt::query()
            ->select('quiz_id', DB::raw('AVG(CASE WHEN total_points > 0 THEN score * 100 / total_points ELSE 0 END) as avg_score'))
            ->where('status', 'completed')
            ->whereIn('quiz_id', $quizIds)
            ->groupBy('quiz_id')
            ->pluck('avg_score', 'quiz_id')->all();

        $quizzes = Quiz::where('user_id', $user->id)
            ->withCount(['questions', 'attempts'])
            ->orderByDesc('created_at')->get()
            ->map(fn(Quiz $q) => [
                'id'             => $q->id,
                'title'          => $q->title,
                'status'         => $q->status,
                'questions_count'=> $q->questions_count,
                'attempts_count' => $q->attempts_count,
                'avg_score'      => round((float) ($quizScores[$q->id] ?? 0), 2),
                'created_at'     => $q->created_at?->toDateTimeString(),
            ])->all();

        $attempts = QuizAttempt::with('quiz:id,title')
            ->where('user_id', $user->id)
            ->orderByDesc('finished_at')->take(20)->get()
            ->map(fn(QuizAttempt $a) => [
                'id'           => $a->id,
                'quiz_id'      => $a->quiz_id,
                'quiz_title'   => $a->quiz?->title,
                'score'        => $a->score,
                'total_points' => $a->total_points,
                'percent'      => $a->total_points > 0 ? round($a->score * 100 / $a->total_points, 2) : 0,
                'status'       => $a->status,
                'started_at'   => $a->started_at?->toDateTimeString(),
                'finished_at'  => $a->finished_at?->toDateTimeString(),
            ])->all();

        $paymentHistory = $payments->map(fn(Payment $p) => [
            'id'             => $p->id,
            'order_code'     => $p->order_code,
            'provider'       => $p->provider,
            'status'         => $p->status,
            'amount'         => (float) $p->amount,
            'transaction_id' => $p->transaction_id,
            'paid_at'        => $p->paid_at?->toDateTimeString(),
            'created_at'     => $p->created_at?->toDateTimeString(),
        ])->all();

        $activePlan = $user->getActivePlan();

        $userData['plan_status']          = $activePlan !== 'free' ? "Đang dùng " . ucfirst($activePlan) : 'Free';
        $userData['vip_status']           = $activePlan !== 'free' ? 'Đang VIP' : 'Không VIP'; // tương thích ngược
        $userData['vip_months_purchased'] = $paidMonths;
        $userData['payment_months']       = $paymentMonths;
        $userData['total_paid']           = (float) $successPayments->sum('amount');
        $userData['quizzes']              = $quizzes;
        $userData['attempt_history']      = $attempts;
        $userData['payment_history']      = $paymentHistory;

        return $userData;
    }

    private function resolveAvatar(?string $avatar): ?string
    {
        if (!$avatar) return null;
        return str_starts_with($avatar, '/storage/') ? url($avatar) : $avatar;
    }
}
