<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomAllowedMember;
use App\Models\RoomMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Room::query()
            ->with(['host:id,name,email'])
            ->withCount([
                'members as members_count' => fn ($memberQuery) => $memberQuery
                    ->where('status', 'active')
                    ->whereColumn('room_members.user_id', '!=', 'rooms.host_id'),
                'assignments as assignments_count',
            ])
            ->where('type', 'homework')
            ->latest();

        if (Gate::forUser($user)->denies('viewAnyAdminScope', Room::class)) {
            $query->where(function ($roomQuery) use ($user) {
                $roomQuery->where('host_id', $user->id)
                    ->orWhereHas('members', function ($memberQuery) use ($user) {
                        $memberQuery->where('user_id', $user->id)
                            ->where('status', 'active');
                    });
            });
        }

        return response()->json([
            'success' => true,
            'message' => 'Danh sách phòng',
            'data' => $query->get()->map(fn (Room $room) => $this->formatRoom($room)),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $createAuthorization = Gate::forUser($user)->inspect('create', Room::class);
        if ($createAuthorization->denied()) {
            return response()->json([
                'success' => false,
                'message' => $createAuthorization->message(),
            ], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'max_players' => ['nullable', 'integer', 'min:1', 'max:500'],
            'join_policy' => ['nullable', 'string', Rule::in(['open', 'email_whitelist'])],
        ]);

        $room = DB::transaction(function () use ($data, $user) {
            $joinPolicy = $data['join_policy'] ?? 'open';

            $room = Room::create([
                'host_id' => $user->id,
                'quiz_id' => null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'type' => 'homework',
                'code' => $this->generateRoomCode(),
                'status' => 'active',
                'max_players' => $data['max_players'] ?? 50,
                'join_policy' => $joinPolicy,
            ]);

            return $room->fresh(['host:id,name,email']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Tạo phòng thành công',
            'data' => $this->formatRoom($room),
        ], 201);
    }

    public function update(Request $request, Room $room)
    {
        if ($room->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        $user = $request->user();
        Gate::forUser($user)->authorize('update', $room);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'join_policy' => ['sometimes', 'required', 'string', Rule::in(['open', 'email_whitelist'])],
            'max_players' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $room->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật cấu hình phòng thành công.',
            'data' => $this->formatRoom($room->fresh(['host:id,name,email'])),
        ]);
    }

    public function show(Request $request, Room $room)
    {
        if ($room->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học này đã bị xóa hoặc khóa bởi Ban quản trị.',
            ], 410);
        }

        Gate::forUser($request->user())->authorize('view', $room);

        $room->load([
            'host:id,name,email',
            'members' => fn ($query) => $query
                ->where('user_id', '!=', $room->host_id)
                ->where('status', 'active')
                ->with('user:id,name,email,role'),
            'assignments.quiz:id,title,description,time_limit_seconds',
        ])->loadCount([
            'members as members_count' => fn ($query) => $query
                ->where('status', 'active')
                ->where('user_id', '!=', $room->host_id),
            'assignments as assignments_count',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết phòng',
            'data' => $this->formatRoom($room, true),
        ]);
    }

    public function joinByCode(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $room = Room::where('code', strtoupper(trim((string) $data['code'])))->first();

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phòng.',
            ], 404);
        }

        return $this->joinRoom($request, $room);
    }

    public function joinRoom(Request $request, Room $room)
    {
        if ($room->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        $user = $request->user();

        $joinAuthorization = Gate::forUser($user)->inspect('join', $room);
        if ($joinAuthorization->denied()) {
            return response()->json([
                'success' => false,
                'message' => $joinAuthorization->message(),
            ], 403);
        }

        // 1. Kiểm tra nếu đã có bản ghi RoomMember của user này
        $existingMember = RoomMember::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingMember) {
            if ($existingMember->status === 'active') {
                $room->load('host:id,name,email');
                return response()->json([
                    'success' => true,
                    'message' => 'Tham gia phòng thành công',
                    'data' => [
                        'room' => $this->formatRoom($room),
                        'member' => $existingMember,
                    ],
                ]);
            }

            if ($existingMember->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'code' => 'JOIN_PENDING',
                    'message' => 'Yêu cầu tham gia của bạn đang chờ chủ phòng phê duyệt.',
                ], 403);
            }

            if ($existingMember->status === 'blocked') {
                // Học sinh bị kick -> Luôn cần duyệt lại
                $existingMember->forceFill([
                    'status' => 'pending',
                    'joined_at' => now(),
                ])->save();

                return response()->json([
                    'success' => false,
                    'code' => 'JOIN_PENDING',
                    'message' => 'Bạn đã bị xóa khỏi phòng trước đó. Yêu cầu tham gia lại đã được gửi tới chủ phòng để phê duyệt.',
                ], 403);
            }

            if ($existingMember->status === 'removed') {
                // Học sinh tự rời phòng -> Kiểm tra xem có được vào thẳng không
                if ($this->usesEmailWhitelist($room)) {
                    $email = $this->normalizeEmail($user->email ?? '');
                    $allowedMember = RoomAllowedMember::query()
                        ->where('room_id', $room->id)
                        ->where('email', $email)
                        ->where('status', 'active')
                        ->first();

                    if ($email && $allowedMember) {
                        // Vẫn có trong Whitelist -> Cho vào thẳng
                        $existingMember->forceFill([
                            'status' => 'active',
                            'joined_at' => now(),
                        ])->save();

                        $allowedMember->user_id = $allowedMember->user_id ?: $user->id;
                        $allowedMember->joined_at = $allowedMember->joined_at ?: now();
                        $allowedMember->save();

                        $room->load('host:id,name,email');
                        return response()->json([
                            'success' => true,
                            'message' => 'Tham gia phòng thành công',
                            'data' => [
                                'room' => $this->formatRoom($room),
                                'member' => $existingMember,
                            ],
                        ]);
                    }

                    // Không còn trong Whitelist -> Cần duyệt
                    $existingMember->forceFill([
                        'status' => 'pending',
                        'joined_at' => now(),
                    ])->save();

                    return response()->json([
                        'success' => false,
                        'code' => 'JOIN_PENDING',
                        'message' => 'Email của bạn không nằm trong Whitelist. Yêu cầu tham gia phòng đã được gửi và đang chờ chủ phòng phê duyệt.',
                    ], 403);
                } else {
                    // Phòng công khai -> Cho vào thẳng
                    $existingMember->forceFill([
                        'status' => 'active',
                        'joined_at' => now(),
                    ])->save();

                    $room->load('host:id,name,email');
                    return response()->json([
                        'success' => true,
                        'message' => 'Tham gia phòng thành công',
                        'data' => [
                            'room' => $this->formatRoom($room),
                            'member' => $existingMember,
                        ],
                    ]);
                }
            }
        }

        // 2. Nếu chưa từng tham gia (chưa có bản ghi RoomMember)
        // Kiểm tra giới hạn số lượng thành viên (max_players)
        $activeMembersCount = RoomMember::where('room_id', $room->id)
            ->where('status', 'active')
            ->where('user_id', '!=', $room->host_id)
            ->count();

        $maxPlayers = $room->max_players ?: 50;
        if ($activeMembersCount >= $maxPlayers) {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học đã đạt số lượng thành viên tối đa cho phép.',
            ], 422);
        }

        // Kiểm tra chính sách Whitelist
        if ($this->usesEmailWhitelist($room)) {
            $email = $this->normalizeEmail($user->email ?? '');
            $allowedMember = RoomAllowedMember::query()
                ->where('room_id', $room->id)
                ->where('email', $email)
                ->where('status', 'active')
                ->first();

            // Nếu có trong Whitelist -> Cho vào thẳng với status 'active'
            if ($email && $allowedMember) {
                $allowedMember->user_id = $allowedMember->user_id ?: $user->id;
                $allowedMember->joined_at = $allowedMember->joined_at ?: now();
                $allowedMember->save();

                $member = RoomMember::create([
                    'room_id' => $room->id,
                    'user_id' => $user->id,
                    'role' => 'member',
                    'status' => 'active',
                    'joined_at' => now(),
                ]);

                $room->load('host:id,name,email');

                return response()->json([
                    'success' => true,
                    'message' => 'Tham gia phòng thành công',
                    'data' => [
                        'room' => $this->formatRoom($room),
                        'member' => $member,
                    ],
                ]);
            }

            // Nếu KHÔNG có trong Whitelist -> Tạo yêu cầu chờ duyệt với status 'pending'
            $member = RoomMember::create([
                'room_id' => $room->id,
                'user_id' => $user->id,
                'role' => 'member',
                'status' => 'pending',
                'joined_at' => now(),
            ]);

            return response()->json([
                'success' => false,
                'code' => 'JOIN_PENDING',
                'message' => 'Email của bạn không nằm trong danh sách được phép tham gia. Yêu cầu tham gia phòng đã được gửi và đang chờ chủ phòng phê duyệt.',
                'data' => [
                    'member' => $member,
                ]
            ], 403);
        }

        // Chế độ công khai (open) -> Vào thẳng
        $member = RoomMember::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $room->load('host:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Tham gia phòng thành công',
            'data' => [
                'room' => $this->formatRoom($room),
                'member' => $member,
            ],
        ]);
    }

    public function members(Request $request, Room $room)
    {
        Gate::forUser($request->user())->authorize('view', $room);

        $status = $request->query('status', 'active');
        if (!in_array($status, ['active', 'pending'])) {
            $status = 'active';
        }

        $members = RoomMember::query()
            ->with('user:id,name,email,role')
            ->where('room_id', $room->id)
            ->where('user_id', '!=', $room->host_id)
            ->where('status', $status)
            ->latest('joined_at')
            ->get();

        $assignedCount = \App\Models\RoomAssignment::where('room_id', $room->id)
            ->whereIn('status', ['published', 'closed'])
            ->count();

        $attemptsGrouped = \App\Models\QuizAttempt::where('room_id', $room->id)
            ->whereIn('status', ['completed', 'expired'])
            ->whereNotNull('assignment_id')
            ->get()
            ->groupBy('user_id');

        return response()->json([
            'success' => true,
            'message' => 'Danh sách thành viên.',
            'data' => $members->map(function (RoomMember $member) use ($assignedCount, $attemptsGrouped) {
                $userAttempts = $attemptsGrouped->get($member->user_id, collect());
                $completed = $userAttempts->pluck('assignment_id')->unique()->count();
                
                $bestScores = [];
                foreach ($userAttempts as $attempt) {
                    $score10 = $attempt->total_points > 0 ? ($attempt->score / $attempt->total_points) * 10 : 0;
                    $assignmentId = $attempt->assignment_id;
                    if (!isset($bestScores[$assignmentId]) || $score10 > $bestScores[$assignmentId]) {
                        $bestScores[$assignmentId] = $score10;
                    }
                }
                $averageScore = count($bestScores) > 0 ? round(array_sum($bestScores) / count($bestScores), 2) : 0.0;

                return [
                    'id' => $member->id,
                    'room_id' => $member->room_id,
                    'user_id' => $member->user_id,
                    'role' => $member->role,
                    'status' => $member->status,
                    'joined_at' => $member->joined_at,
                    'user' => $member->user,
                    'assigned' => $assignedCount,
                    'completed' => $completed,
                    'completion_rate' => $assignedCount > 0 ? (int) round(($completed / $assignedCount) * 100) : 0,
                    'average_score' => $averageScore,
                ];
            }),
        ]);
    }

    public function destroyMember(Request $request, Room $room, RoomMember $member)
    {
        if ($room->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        Gate::forUser($request->user())->authorize('manageMembers', $room);

        if ((int) $member->room_id !== (int) $room->id) {
            return response()->json([
                'success' => false,
                'message' => 'Thành viên này không thuộc phòng hiện tại.',
            ], 404);
        }

        if ((int) $member->user_id === (int) $room->host_id || $member->role === 'host') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa Host.',
            ], 422);
        }

        $member->forceFill(['status' => 'blocked'])->save();

        // Đồng bộ reset joined_at và user_id trong bảng room_allowed_members
        $memberUser = $member->user;
        if ($memberUser) {
            $email = $this->normalizeEmail($memberUser->email ?? '');
            if ($email) {
                RoomAllowedMember::where('room_id', $room->id)
                    ->where('email', $email)
                    ->update([
                        'user_id' => null,
                        'joined_at' => null,
                    ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa thành viên khỏi phòng.',
            'data' => [
                'id' => $member->id,
                'room_id' => $member->room_id,
                'user_id' => $member->user_id,
                'status' => $member->status,
            ],
        ]);
    }

    public function leave(Request $request, Room $room)
    {
        $user = $request->user();
        $member = RoomMember::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không phải là thành viên hoạt động trong phòng này.',
            ], 422);
        }

        $leaveAuthorization = Gate::forUser($user)->inspect('leave', $room);
        if ($leaveAuthorization->denied()) {
            return response()->json([
                'success' => false,
                'message' => $leaveAuthorization->message(),
            ], 422);
        }

        $member->forceFill(['status' => 'removed'])->save();

        // Đồng bộ reset joined_at và user_id trong bảng room_allowed_members
        $email = $this->normalizeEmail($user->email ?? '');
        if ($email) {
            RoomAllowedMember::where('room_id', $room->id)
                ->where('email', $email)
                ->update([
                    'user_id' => null,
                    'joined_at' => null,
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Rời phòng thành công.',
        ]);
    }

    public function dissolve(Request $request, Room $room)
    {
        // Phòng bị ban thì không thể giải tán
        if ($room->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học này đã bị quản trị viên khóa và không thể giải tán.',
            ], 403);
        }

        $user = $request->user();

        // Chỉ Host mới được phép giải tán phòng – kiểm tra ở Backend
        Gate::forUser($user)->authorize('dissolve', $room);

        // Soft Delete – toàn bộ dữ liệu liên quan vẫn được giữ nguyên
        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Phòng học đã được giải tán thành công.',
        ]);
    }

    public function allowedMembers(Request $request, Room $room)
    {
        Gate::forUser($request->user())->authorize('manageWhitelist', $room);

        $allowedMembers = RoomAllowedMember::query()
            ->with(['user:id,name,email', 'inviter:id,name,email'])
            ->where('room_id', $room->id)
            ->orderBy('email')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Danh sách email được phép tham gia phòng.',
            'data' => $allowedMembers->map(fn (RoomAllowedMember $allowedMember) => $this->formatAllowedMember($allowedMember)),
        ]);
    }

    public function storeAllowedMembers(Request $request, Room $room)
    {
        if ($room->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        $user = $request->user();
        Gate::forUser($user)->authorize('manageWhitelist', $room);

        $emailsInput = $request->input('emails', []);
        $request->merge([
            'email' => $this->normalizeEmail($request->input('email', '')),
            'emails' => $this->normalizeEmailList(is_array($emailsInput) ? $emailsInput : []),
        ]);

        $data = $request->validate([
            'email' => ['nullable', 'email'],
            'emails' => ['nullable', 'array'],
            'emails.*' => ['required', 'email'],
        ]);

        $emails = $this->normalizeEmailList(array_merge(
            isset($data['email']) ? [$data['email']] : [],
            $data['emails'] ?? []
        ));

        if (empty($emails)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập ít nhất 1 email hợp lệ.',
            ], 422);
        }

        $allowedMembers = DB::transaction(function () use ($emails, $room, $user) {
            $items = [];

            foreach ($emails as $email) {
                $items[] = RoomAllowedMember::updateOrCreate(
                    ['room_id' => $room->id, 'email' => $email],
                    [
                        'invited_by' => $user->id,
                        'status' => 'active',
                    ]
                )->load(['user:id,name,email', 'inviter:id,name,email']);
            }

            return $items;
        });

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật danh sách email được phép tham gia phòng thành công.',
            'data' => collect($allowedMembers)->map(fn (RoomAllowedMember $allowedMember) => $this->formatAllowedMember($allowedMember))->values(),
        ]);
    }

    public function destroyAllowedMember(Request $request, Room $room, RoomAllowedMember $allowedMember)
    {
        if ($room->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        Gate::forUser($request->user())->authorize('manageWhitelist', $room);

        if ((int) $allowedMember->room_id !== (int) $room->id) {
            return response()->json([
                'success' => false,
                'message' => 'Email này không thuộc phòng hiện tại.',
            ], 404);
        }

        $allowedMember->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa email khỏi danh sách được phép tham gia.',
        ]);
    }

    public function destroyAllowedMembersBatch(Request $request, Room $room)
    {
        if ($room->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        Gate::forUser($request->user())->authorize('manageWhitelist', $room);

        // Xóa tất cả nếu có flag clear_all
        if ($request->input('clear_all')) {
            RoomAllowedMember::where('room_id', $room->id)->delete();
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa toàn bộ email khỏi danh sách.',
            ]);
        }

        $ids = $request->input('ids');
        if (!is_array($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Danh sách ID không hợp lệ.',
            ], 422);
        }

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Không có email nào được chọn để xóa.',
            ], 422);
        }

        RoomAllowedMember::where('room_id', $room->id)
            ->whereIn('id', $ids)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa các email được chọn khỏi danh sách.',
        ]);
    }


    private function usesEmailWhitelist(Room $room): bool
    {
        return ($room->join_policy ?: 'open') === 'email_whitelist';
    }

    private function normalizeEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    private function normalizeEmailList(array $emails): array
    {
        return collect($emails)
            ->map(fn ($email) => $this->normalizeEmail($email))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function generateRoomCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Room::where('code', $code)->exists());

        return $code;
    }

    private function formatRoom(Room $room, bool $includeRelations = false): array
    {
        $membersCount = $room->getAttribute('members_count');
        if ($membersCount === null && $room->relationLoaded('members')) {
            $membersCount = $room->members
                ->filter(fn (RoomMember $member) => (int) $member->user_id !== (int) $room->host_id && $member->status === 'active')
                ->count();
        }
        if ($membersCount === null) {
            $membersCount = 0;
        }

        $data = [
            'id' => $room->id,
            'host_id' => $room->host_id,
            'name' => $room->name,
            'description' => $room->description,
            'type' => $room->type,
            'code' => $room->code,
            'status' => $room->status === 'active' ? 'open' : $room->status,
            'max_players' => $room->max_players,
            'join_policy' => $room->join_policy ?: 'open',
            'host' => $room->host,
            'members_count' => $membersCount,
            'assignments_count' => $room->assignments_count ?? null,
            'created_at' => $room->created_at,
            'updated_at' => $room->updated_at,
        ];

        if ($includeRelations) {
            $assignedCount = \App\Models\RoomAssignment::where('room_id', $room->id)
                ->whereIn('status', ['published', 'closed'])
                ->count();

            $attemptsGrouped = \App\Models\QuizAttempt::where('room_id', $room->id)
                ->whereIn('status', ['completed', 'expired'])
                ->whereNotNull('assignment_id')
                ->get()
                ->groupBy('user_id');

            $data['members'] = $room->members
                ->filter(fn (RoomMember $member) => (int) $member->user_id !== (int) $room->host_id && $member->status === 'active')
                ->map(function (RoomMember $member) use ($assignedCount, $attemptsGrouped) {
                    $userAttempts = $attemptsGrouped->get($member->user_id, collect());
                    $completed = $userAttempts->pluck('assignment_id')->unique()->count();
                    
                    $bestScores = [];
                    foreach ($userAttempts as $attempt) {
                        $score10 = $attempt->total_points > 0 ? ($attempt->score / $attempt->total_points) * 10 : 0;
                        $assignmentId = $attempt->assignment_id;
                        if (!isset($bestScores[$assignmentId]) || $score10 > $bestScores[$assignmentId]) {
                            $bestScores[$assignmentId] = $score10;
                        }
                    }
                    $averageScore = count($bestScores) > 0 ? round(array_sum($bestScores) / count($bestScores), 2) : 0.0;

                    return [
                        'id' => $member->id,
                        'room_id' => $member->room_id,
                        'user_id' => $member->user_id,
                        'role' => $member->role,
                        'status' => $member->status,
                        'joined_at' => $member->joined_at,
                        'user' => $member->user,
                        'assigned' => $assignedCount,
                        'completed' => $completed,
                        'completion_rate' => $assignedCount > 0 ? (int) round(($completed / $assignedCount) * 100) : 0,
                        'average_score' => $averageScore,
                    ];
                })
                ->values();
            $data['assignments'] = $room->assignments;
        }

        return $data;
    }

    private function formatAllowedMember(RoomAllowedMember $allowedMember): array
    {
        return [
            'id' => $allowedMember->id,
            'room_id' => $allowedMember->room_id,
            'email' => $allowedMember->email,
            'user_id' => $allowedMember->user_id,
            'invited_by' => $allowedMember->invited_by,
            'status' => $allowedMember->status,
            'joined_at' => $allowedMember->joined_at,
            'created_at' => $allowedMember->created_at,
            'updated_at' => $allowedMember->updated_at,
            'user' => $allowedMember->user,
            'inviter' => $allowedMember->inviter,
        ];
    }

    public function approveMember(Request $request, Room $room, RoomMember $member)
    {
        if ($room->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        $user = $request->user();

        Gate::forUser($user)->authorize('manageMembers', $room);

        if ((int) $member->room_id !== (int) $room->id) {
            return response()->json([
                'success' => false,
                'message' => 'Thành viên không thuộc phòng này.',
            ], 404);
        }

        if ($member->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Thành viên này không ở trong trạng thái chờ duyệt.',
            ], 422);
        }

        $member->forceFill([
            'status' => 'active',
            'joined_at' => now(),
        ])->save();

        // Đồng bộ joined_at và user_id sang bảng room_allowed_members
        $memberUser = $member->user;
        if ($memberUser) {
            $email = $this->normalizeEmail($memberUser->email ?? '');
            if ($email) {
                RoomAllowedMember::where('room_id', $room->id)
                    ->where('email', $email)
                    ->update([
                        'user_id' => $memberUser->id,
                        'joined_at' => $member->joined_at ?: now(),
                    ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Phê duyệt thành viên thành công.',
            'data' => $member,
        ]);
    }

    public function rejectMember(Request $request, Room $room, RoomMember $member)
    {
        if ($room->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Phòng học này đã bị khóa bởi quản trị viên.',
            ], 403);
        }

        $user = $request->user();

        Gate::forUser($user)->authorize('manageMembers', $room);

        if ((int) $member->room_id !== (int) $room->id) {
            return response()->json([
                'success' => false,
                'message' => 'Thành viên không thuộc phòng này.',
            ], 404);
        }

        if ($member->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Thành viên này không ở trong trạng thái chờ duyệt.',
            ], 422);
        }

        $member->delete(); // Xóa hẳn bản ghi RoomMember để họ có thể gửi lại yêu cầu nếu muốn

        return response()->json([
            'success' => true,
            'message' => 'Từ chối thành viên thành công.',
        ]);
    }
}