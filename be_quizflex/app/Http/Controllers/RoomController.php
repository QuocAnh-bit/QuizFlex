<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomAllowedMember;
use App\Models\RoomMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Room::query()
            ->with(['owner:id,name,email'])
            ->withCount([
                'members as members_count' => fn ($memberQuery) => $memberQuery
                    ->where('status', 'active')
                    ->whereColumn('room_members.user_id', '!=', 'rooms.owner_id'),
                'assignments as assignments_count',
            ])
            ->where('type', 'homework')
            ->latest();

        if (!$this->isAdmin($user)) {
            $query->where(function ($roomQuery) use ($user) {
                $roomQuery->where('owner_id', $user->id)
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
        if (!$this->canCreateRoom($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Tính năng tạo phòng yêu cầu tài khoản nâng cấp (Plus/Pro/Ultra).',
            ], 403);
        }   

        $allowedEmailsInput = $request->input('allowed_emails', []);
        $request->merge([
            'allowed_emails' => $this->normalizeEmailList(is_array($allowedEmailsInput) ? $allowedEmailsInput : []),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'max_players' => ['nullable', 'integer', 'min:1', 'max:500'],
            'join_policy' => ['nullable', 'string', Rule::in(['open', 'email_whitelist'])],
            'allowed_emails' => ['nullable', 'array'],
            'allowed_emails.*' => ['required', 'email'],
        ]);

        $room = DB::transaction(function () use ($data, $user) {
            $joinPolicy = $data['join_policy'] ?? 'open';

            $room = Room::create([
                'owner_id' => $user->id,
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

            if ($joinPolicy === 'email_whitelist') {
                foreach ($this->normalizeEmailList($data['allowed_emails'] ?? []) as $email) {
                    RoomAllowedMember::updateOrCreate(
                        ['room_id' => $room->id, 'email' => $email],
                        [
                            'invited_by' => $user->id,
                            'status' => 'active',
                        ]
                    );
                }
            }

            return $room->fresh(['owner:id,name,email']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Tạo phòng thành công',
            'data' => $this->formatRoom($room),
        ], 201);
    }

    public function show(Request $request, Room $room)
    {
        if (!$this->canViewRoom($request->user(), $room)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem phòng này.',
            ], 403);
        }

        $room->load([
            'owner:id,name,email',
            'members' => fn ($query) => $query
                ->where('user_id', '!=', $room->owner_id)
                ->where('status', 'active')
                ->with('user:id,name,email,role'),
            'assignments.quiz:id,title,description,time_limit_seconds',
        ])->loadCount([
            'members as members_count' => fn ($query) => $query
                ->where('status', 'active')
                ->where('user_id', '!=', $room->owner_id),
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
        $user = $request->user();

        if (!$this->isRoomActive($room)) {
            return response()->json([
                'success' => false,
                'message' => 'Phòng này không còn hoạt động.',
            ], 422);
        }

        if ((int) $room->owner_id === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Chủ room không cần tham gia room của chính mình.',
            ], 403);
        }

        if ($this->usesEmailWhitelist($room)) {
            $email = $this->normalizeEmail($user->email ?? '');
            $allowedMember = RoomAllowedMember::query()
                ->where('room_id', $room->id)
                ->where('email', $email)
                ->where('status', 'active')
                ->first();

            if (!$email || !$allowedMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email của bạn không nằm trong danh sách được phép tham gia phòng này.',
                ], 403);
            }

            $allowedMember->user_id = $allowedMember->user_id ?: $user->id;
            $allowedMember->joined_at = $allowedMember->joined_at ?: now();
            $allowedMember->save();
        }

        $member = RoomMember::updateOrCreate(
            ['room_id' => $room->id, 'user_id' => $user->id],
            [
                'role' => 'member',
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        $room->load('owner:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Tham gia phong thanh cong',
            'data' => [
                'room' => $this->formatRoom($room),
                'member' => $member,
            ],
        ]);
    }

    public function members(Request $request, Room $room)
    {
        if (!$this->canViewRoom($request->user(), $room)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban khong co quyen xem thanh vien phong nay.',
            ], 403);
        }

        $members = RoomMember::query()
            ->with('user:id,name,email,role')
            ->where('room_id', $room->id)
            ->where('user_id', '!=', $room->owner_id)
            ->where('status', 'active')
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

    public function allowedMembers(Request $request, Room $room)
    {
        if (!$this->canManageAllowedMembers($request->user(), $room)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban khong co quyen quan ly danh sach email phong nay.',
            ], 403);
        }

        $allowedMembers = RoomAllowedMember::query()
            ->with(['user:id,name,email', 'inviter:id,name,email'])
            ->where('room_id', $room->id)
            ->orderBy('email')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Danh sach email duoc phep tham gia',
            'data' => $allowedMembers->map(fn (RoomAllowedMember $allowedMember) => $this->formatAllowedMember($allowedMember)),
        ]);
    }

    public function storeAllowedMembers(Request $request, Room $room)
    {
        $user = $request->user();
        if (!$this->canManageAllowedMembers($user, $room)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban khong co quyen quan ly danh sach email phong nay.',
            ], 403);
        }

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
                'message' => 'Vui long nhap it nhat mot email hop le.',
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
            'message' => 'Cap nhat danh sach email thanh cong',
            'data' => collect($allowedMembers)->map(fn (RoomAllowedMember $allowedMember) => $this->formatAllowedMember($allowedMember))->values(),
        ]);
    }

    public function destroyAllowedMember(Request $request, Room $room, RoomAllowedMember $allowedMember)
    {
        if (!$this->canManageAllowedMembers($request->user(), $room)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban khong co quyen quan ly danh sach email phong nay.',
            ], 403);
        }

        if ((int) $allowedMember->room_id !== (int) $room->id) {
            return response()->json([
                'success' => false,
                'message' => 'Email nay khong thuoc phong hien tai.',
            ], 404);
        }

        $allowedMember->delete();

        return response()->json([
            'success' => true,
            'message' => 'Da xoa email khoi danh sach duoc phep tham gia',
        ]);
    }

    private function canCreateRoom($user): bool
{
    if ($this->isAdmin($user)) {
        return true;
    }

    if (!method_exists($user, 'getSubscriptionTier')) {
        return false;
    }

    $tier = strtolower((string) $user->getSubscriptionTier());

    return in_array(
        $tier,
        ['plus', 'pro', 'ultra'],
        true
    );
}

    private function canViewRoom($user, Room $room): bool
    {
        if ($this->isAdmin($user) || (int) $room->owner_id === (int) $user->id) {
            return true;
        }

        return RoomMember::where('room_id', $room->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();
    }

    private function isRoomActive(Room $room): bool
    {
        return $room->status === 'active' || $room->status === 'waiting';
    }

    private function isAdmin($user): bool
    {
        return strtolower((string) ($user->role ?? 'user')) === 'admin';
    }

    private function canManageAllowedMembers($user, Room $room): bool
    {
        return $room->type === 'homework' && (int) $room->owner_id === (int) $user->id;
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
        $membersCount = $room->members_count ?? null;
        if ($membersCount === null && $room->relationLoaded('members')) {
            $membersCount = $room->members
                ->filter(fn (RoomMember $member) => (int) $member->user_id !== (int) $room->owner_id && $member->status === 'active')
                ->count();
        }
        if ($membersCount === null) {
            $membersCount = RoomMember::where('room_id', $room->id)
                ->where('user_id', '!=', $room->owner_id)
                ->where('status', 'active')
                ->count();
        }

        $data = [
            'id' => $room->id,
            'owner_id' => $room->owner_id,
            'name' => $room->name,
            'description' => $room->description,
            'type' => $room->type,
            'code' => $room->code,
            'status' => $room->status,
            'max_players' => $room->max_players,
            'join_policy' => $room->join_policy ?: 'open',
            'owner' => $room->owner,
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
                ->filter(fn (RoomMember $member) => (int) $member->user_id !== (int) $room->owner_id && $member->status === 'active')
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
}
