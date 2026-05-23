<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomMember;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $rooms = Room::query()
            ->where(function ($query) use ($user) {
                $query->where('host_id', $user->id)
                    ->orWhereHas('members', function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                            ->where('status', 'active');
                    });
            })
            ->withCount([
                'members as members_count' => fn ($query) => $query
                    ->where('status', 'active')
                    ->where('role', '!=', 'owner'),
                'assignments',
            ])
            ->latest()
            ->get()
            ->map(fn($room) => $this->formatRoom($room));

        return response()->json([
            'success' => true,
            'message' => 'Danh sách room của user hiện tại',
            'data' => $rooms,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (!$this->canCreateRoom($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ VIP hoặc Admin mới được tạo room.',
            ], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'in:homework,live'],
            'max_players' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $room = Room::create([
            'host_id' => $user->id,
            'quiz_id' => null,
            'code' => $this->generateUniqueRoomCode(),
            'status' => 'waiting',
            'max_players' => $data['max_players'] ?? 50,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'homework',
            'is_active' => true,
        ]);

        RoomMember::create([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $room->loadCount([
            'members as members_count' => fn ($query) => $query
                ->where('status', 'active')
                ->where('role', '!=', 'owner'),
            'assignments',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo room thành công',
            'data' => $this->formatRoom($room),
        ], 201);
    }

    public function show(Request $request, Room $room)
    {
        $user = $request->user();

        if (!$this->isRoomMemberOrHost($room, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem room này.',
            ], 403);
        }

        $room->load([
            'host:id,name,email',
            'members.user:id,name,email,role',
        ])->loadCount([
            'members as members_count' => fn ($query) => $query
                ->where('status', 'active')
                ->where('role', '!=', 'owner'),
            'assignments',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chi tiết room',
            'data' => [
                ...$this->formatRoom($room),
                'host' => $room->host,
                'members' => $room->members->map(fn($member) => [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'name' => $member->user?->name,
                    'email' => $member->user?->email,
                    'role' => $member->role,
                    'status' => $member->status,
                    'joined_at' => optional($member->joined_at)->toISOString(),
                ])->values(),
            ],
        ]);
    }

    public function updateCode(Request $request, Room $room)
    {
        $user = $request->user();

        if (!$this->canManageRoom($room, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ room hoặc admin mới được đổi mã room.',
            ], 403);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'min:4', 'max:12', 'regex:/^[A-Za-z0-9]+$/'],
        ]);

        $code = strtoupper(trim($data['code']));

        $exists = Room::where('code', $code)
            ->where('id', '!=', $room->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Mã room này đã được sử dụng.',
            ], 422);
        }

        $room->update([
            'code' => $code,
        ]);

        $room->loadCount([
            'members as members_count' => fn ($query) => $query
                ->where('status', 'active')
                ->where('role', '!=', 'owner'),
            'assignments',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đổi mã room thành công.',
            'data' => $this->formatRoom($room),
        ]);
    }

    public function join(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $code = strtoupper(trim($data['code']));

        $room = Room::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Mã room không tồn tại hoặc room đã bị khóa.',
            ], 404);
        }

        if ($room->host_id === $user->id) {
            return response()->json([
                'success' => true,
                'message' => 'Bạn là chủ room này.',
                'data' => $this->formatRoom($room->loadCount([
                    'members as members_count' => fn ($query) => $query
                        ->where('status', 'active')
                        ->where('role', '!=', 'owner'),
                    'assignments',
                ])),
            ]);
        }

        $activeMembersCount = RoomMember::where('room_id', $room->id)
            ->where('status', 'active')
            ->count();

        if ($room->max_players && $activeMembersCount >= $room->max_players) {
            return response()->json([
                'success' => false,
                'message' => 'Room đã đủ số lượng thành viên.',
            ], 422);
        }

        $member = RoomMember::updateOrCreate(
            [
                'room_id' => $room->id,
                'user_id' => $user->id,
            ],
            [
                'role' => 'member',
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        $room->loadCount([
            'members as members_count' => fn ($query) => $query
                ->where('status', 'active')
                ->where('role', '!=', 'owner'),
            'assignments',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tham gia room thành công',
            'data' => [
                'room' => $this->formatRoom($room),
                'member' => [
                    'id' => $member->id,
                    'room_id' => $member->room_id,
                    'user_id' => $member->user_id,
                    'role' => $member->role,
                    'status' => $member->status,
                ],
            ],
        ]);
    }

    public function members(Request $request, Room $room)
    {
        $user = $request->user();

        if (!$this->isRoomMemberOrHost($room, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xem thành viên room này.',
            ], 403);
        }

        $members = RoomMember::query()
            ->where('room_id', $room->id)
            ->with('user:id,name,email,role')
            ->latest('joined_at')
            ->get()
            ->map(fn($member) => [
                'id' => $member->id,
                'user_id' => $member->user_id,
                'name' => $member->user?->name,
                'email' => $member->user?->email,
                'user_role' => $member->user?->role,
                'room_role' => $member->role,
                'status' => $member->status,
                'joined_at' => optional($member->joined_at)->toISOString(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Danh sách thành viên room',
            'data' => $members,
        ]);
    }

    private function canCreateRoom($user): bool
    {
        $role = strtoupper((string) $user->role);

        return in_array($role, ['ADMIN', 'VIP']);
    }

    private function isRoomMemberOrHost(Room $room, int $userId): bool
    {
        if ((int) $room->host_id === (int) $userId) {
            return true;
        }

        return RoomMember::where('room_id', $room->id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    private function canManageRoom(Room $room, $user): bool
    {
        if (!$user) {
            return false;
        }

        if (strtoupper((string) $user->role) === 'ADMIN') {
            return true;
        }

        return (int) $room->host_id === (int) $user->id;
    }

    private function generateUniqueRoomCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Room::where('code', $code)->exists());

        return $code;
    }

    private function formatRoom(Room $room): array
    {
        return [
            'id' => $room->id,
            'host_id' => $room->host_id,
            'name' => $room->name,
            'description' => $room->description,
            'type' => $room->type,
            'code' => $room->code,
            'status' => $room->status,
            'max_players' => $room->max_players,
            'is_active' => (bool) $room->is_active,
            'members_count' => $room->members_count ?? null,
            'assignments_count' => $room->assignments_count ?? null,
            'created_at' => optional($room->created_at)->toISOString(),
        ];
    }
}
