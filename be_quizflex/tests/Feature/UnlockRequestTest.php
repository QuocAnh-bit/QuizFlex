<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnlockRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_unlock_request(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'unlock@example.com',
            'role' => 'free',
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/unlock-requests', [
            'message' => 'Tôi nghĩ tài khoản của tôi bị khóa nhầm vì tôi chưa vi phạm quy định nào.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('unlock_requests', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_submit_multiple_pending_requests(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User 2',
            'email' => 'unlock2@example.com',
            'role' => 'free',
        ]);

        $this->actingAs($user, 'api')->postJson('/api/unlock-requests', [
            'message' => 'Tôi nghĩ tài khoản của tôi bị khóa nhầm vì tôi chưa vi phạm quy định nào.',
        ]);

        $response = $this->actingAs($user, 'api')->postJson('/api/unlock-requests', [
            'message' => 'Tôi cần gửi thêm một kháng cáo khác vì lần đầu chưa được xử lý.',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
