<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\Room;
use App\Models\RoomAllowedMember;
use App\Models\RoomMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeworkRoomAllowedMembersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required for the in-memory database feature test.');
        }

        parent::setUp();
    }

    public function test_open_homework_room_keeps_code_join_behavior(): void
    {
        $owner = User::factory()->create(['role' => 'VIP']);
        $member = User::factory()->create(['role' => 'USER']);
        $room = $this->createHomeworkRoom($owner, ['join_policy' => 'open']);

        $this->actingAs($member, 'api')
            ->postJson('/api/rooms/join', ['code' => strtolower($room->code)])
            ->assertOk()
            ->assertJsonPath('data.room.id', $room->id);

        $this->assertDatabaseHas('room_members', [
            'room_id' => $room->id,
            'user_id' => $member->id,
            'status' => 'active',
        ]);
    }

    public function test_email_whitelist_allows_only_active_allowed_email_and_does_not_duplicate_members(): void
    {
        $owner = User::factory()->create(['role' => 'VIP']);
        $allowedUser = User::factory()->create(['role' => 'USER', 'email' => 'student@example.com']);
        $blockedUser = User::factory()->create(['role' => 'USER', 'email' => 'blocked@example.com']);
        $room = $this->createHomeworkRoom($owner, ['join_policy' => 'email_whitelist']);

        RoomAllowedMember::create([
            'room_id' => $room->id,
            'email' => 'student@example.com',
            'invited_by' => $owner->id,
            'status' => 'active',
        ]);

        $this->actingAs($allowedUser, 'api')
            ->postJson('/api/rooms/join', ['code' => $room->code])
            ->assertOk();

        $this->actingAs($allowedUser, 'api')
            ->postJson('/api/rooms/join', ['code' => $room->code])
            ->assertOk();

        $this->assertSame(1, RoomMember::where('room_id', $room->id)->where('user_id', $allowedUser->id)->count());
        $this->assertDatabaseHas('room_allowed_members', [
            'room_id' => $room->id,
            'email' => 'student@example.com',
            'user_id' => $allowedUser->id,
        ]);

        $this->actingAs($blockedUser, 'api')
            ->postJson('/api/rooms/join', ['code' => $room->code])
            ->assertForbidden()
            ->assertJsonPath('message', 'Email của bạn không nằm trong danh sách được phép tham gia phòng này.');

        $this->assertDatabaseMissing('room_members', [
            'room_id' => $room->id,
            'user_id' => $blockedUser->id,
        ]);
    }

    public function test_owner_can_add_normalized_allowed_emails_without_duplicates(): void
    {
        $owner = User::factory()->create(['role' => 'VIP']);
        $room = $this->createHomeworkRoom($owner, ['join_policy' => 'email_whitelist']);

        $this->actingAs($owner, 'api')
            ->postJson("/api/homework-rooms/{$room->id}/allowed-members", [
                'emails' => [' Student@Example.com ', 'student@example.com', 'OTHER@example.com'],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseHas('room_allowed_members', [
            'room_id' => $room->id,
            'email' => 'student@example.com',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('room_allowed_members', [
            'room_id' => $room->id,
            'email' => 'other@example.com',
            'status' => 'active',
        ]);
        $this->assertSame(1, RoomAllowedMember::where('room_id', $room->id)->where('email', 'student@example.com')->count());
    }

    private function createHomeworkRoom(User $owner, array $attributes = []): Room
    {
        $quiz = Quiz::create([
            'user_id' => $owner->id,
            'title' => 'Homework quiz',
            'description' => 'Quiz for homework room tests',
            'category' => 'General',
            'difficulty' => 'easy',
            'status' => 'published',
            'is_public' => true,
        ]);

        return Room::create(array_merge([
            'host_id' => $owner->id,
            'quiz_id' => $quiz->id,
            'name' => 'Homework room',
            'description' => null,
            'type' => 'homework',
            'code' => strtoupper(fake()->bothify('??????')),
            'status' => 'active',
            'max_players' => 50,
            'join_policy' => 'open',
        ], $attributes));
    }
}
