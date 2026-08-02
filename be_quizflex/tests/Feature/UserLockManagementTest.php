<?php

namespace Tests\Feature;

use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class UserLockManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_admin_can_lock_and_unlock_a_regular_user(): void
    {
        $mainAdmin = User::create([
            'name' => 'Main Admin',
            'email' => 'main-lock@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_main_admin' => true,
        ]);

        $user = User::create([
            'name' => 'Regular User',
            'email' => 'regular-lock@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'is_main_admin' => false,
        ]);

        $this->actingAs($mainAdmin, 'api');

        $lockResponse = $this->app->make(UserController::class)->lock(new Request(['reason' => 'Spam']), $user);
        $this->assertSame(200, $lockResponse->getStatusCode());
        $user->refresh();
        $this->assertTrue($user->is_locked);
        $this->assertNotNull($user->locked_at);

        $unlockResponse = $this->app->make(UserController::class)->unlock($user);
        $this->assertSame(200, $unlockResponse->getStatusCode());
        $user->refresh();
        $this->assertFalse($user->is_locked);
    }

    public function test_sub_admin_cannot_lock_admin_account(): void
    {
        $mainAdmin = User::create([
            'name' => 'Main Admin',
            'email' => 'main-lock-2@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_main_admin' => true,
        ]);

        $subAdmin = User::create([
            'name' => 'Sub Admin',
            'email' => 'sub-lock@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_main_admin' => false,
        ]);

        $this->actingAs($subAdmin, 'api');

        $response = $this->app->make(UserController::class)->lock(new Request(['reason' => 'Test']), $mainAdmin);

        $this->assertSame(403, $response->getStatusCode());
        $mainAdmin->refresh();
        $this->assertFalse($mainAdmin->is_locked);
    }
}
