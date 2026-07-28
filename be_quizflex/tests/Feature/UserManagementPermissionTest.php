<?php

namespace Tests\Feature;

use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class UserManagementPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_admin_can_downgrade_secondary_admin_to_user(): void
    {
        $mainAdmin = User::create([
            'name' => 'Main Admin',
            'email' => 'main@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_main_admin' => true,
        ]);

        $secondaryAdmin = User::create([
            'name' => 'Secondary Admin',
            'email' => 'secondary@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_main_admin' => false,
        ]);

        $this->actingAs($mainAdmin, 'api');

        $response = $this->app->make(UserController::class)->update(new Request(['role' => 'user']), $secondaryAdmin);

        $this->assertSame(200, $response->getStatusCode());
        $secondaryAdmin->refresh();
        $this->assertSame('user', $secondaryAdmin->getRole());
    }

    public function test_main_admin_can_delete_secondary_admin(): void
    {
        $mainAdmin = User::create([
            'name' => 'Main Admin',
            'email' => 'main-delete@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_main_admin' => true,
        ]);

        $secondaryAdmin = User::create([
            'name' => 'Secondary Admin',
            'email' => 'secondary-delete@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_main_admin' => false,
        ]);

        $this->actingAs($mainAdmin, 'api');

        $response = $this->app->make(UserController::class)->destroy($secondaryAdmin);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSoftDeleted('users', ['id' => $secondaryAdmin->id]);
    }
}
