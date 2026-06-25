<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeworkRoomOpenCloseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is required for the in-memory database feature test.');
        }

        parent::setUp();
    }

    public function test_admin_and_owner_can_close_and_reopen_homework_room_while_member_is_denied(): void
    {
        $owner = User::factory()->create(['role' => 'VIP']);
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $member = User::factory()->create(['role' => 'USER']);

        $room = $this->createHomeworkRoom($owner);

        // 1. Member tries to close -> 403
        $this->actingAs($member, 'api')
            ->patchJson("/api/admin/rooms/homework/{$room->id}/close")
            ->assertStatus(403);

        // 2. Owner closes room -> 200
        $this->actingAs($owner, 'api')
            ->patchJson("/api/admin/rooms/homework/{$room->id}/close")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'closed');

        $this->assertEquals('closed', $room->fresh()->status);

        // 3. Member tries to reopen -> 403
        $this->actingAs($member, 'api')
            ->patchJson("/api/admin/rooms/homework/{$room->id}/open")
            ->assertStatus(403);

        // 4. Admin reopens room -> 200
        $this->actingAs($admin, 'api')
            ->patchJson("/api/admin/rooms/homework/{$room->id}/open")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'open'); // maps active to open in formatted summary

        $this->assertEquals('active', $room->fresh()->status);
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
            'owner_id' => $owner->id,
            'host_id' => $owner->id,
            'quiz_id' => $quiz->id,
            'name' => 'Homework room',
            'description' => null,
            'type' => 'homework',
            'code' => 'ROOM12',
            'status' => 'active',
            'max_players' => 50,
            'join_policy' => 'open',
        ], $attributes));
    }
}
