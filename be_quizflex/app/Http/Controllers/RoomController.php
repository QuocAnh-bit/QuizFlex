<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            'message' => 'Danh sach phong',
            'data' => $query->get()->map(fn (Room $room) => $this->formatRoom($room)),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$this->canCreateRoom($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Tinh nang tao phong yeu cau tai khoan VIP.',
            ], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'max_players' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $room = DB::transaction(function () use ($data, $user) {
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
            ]);

            return $room->fresh(['owner:id,name,email']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Tao phong thanh cong',
            'data' => $this->formatRoom($room),
        ], 201);
    }

    public function show(Request $request, Room $room)
    {
        if (!$this->canViewRoom($request->user(), $room)) {
            return response()->json([
                'success' => false,
                'message' => 'Ban khong co quyen xem phong nay.',
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
            'message' => 'Chi tiet phong',
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
                'message' => 'Khong tim thay phong.',
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
                'message' => 'Phong nay khong con hoat dong.',
            ], 422);
        }

        if ((int) $room->owner_id === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Chủ room không cần tham gia room của chính mình.',
            ], 403);
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

        return response()->json([
            'success' => true,
            'message' => 'Danh sach thanh vien',
            'data' => $members->map(fn (RoomMember $member) => [
                'id' => $member->id,
                'room_id' => $member->room_id,
                'user_id' => $member->user_id,
                'role' => $member->role,
                'status' => $member->status,
                'joined_at' => $member->joined_at,
                'user' => $member->user,
            ]),
        ]);
    }

    private function canCreateRoom($user): bool
    {
        return in_array(strtolower((string) ($user->role ?? 'user')), ['admin', 'vip'], true);
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
            'owner' => $room->owner,
            'members_count' => $membersCount,
            'assignments_count' => $room->assignments_count ?? null,
            'created_at' => $room->created_at,
            'updated_at' => $room->updated_at,
        ];

        if ($includeRelations) {
            $data['members'] = $room->members
                ->filter(fn (RoomMember $member) => (int) $member->user_id !== (int) $room->owner_id && $member->status === 'active')
                ->values();
            $data['assignments'] = $room->assignments;
        }

        return $data;
    }
}
