<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserRoleNormalizationTest extends TestCase
{
    public function test_user_role_is_treated_as_free_for_access_control(): void
    {
        $user = new User(['role' => 'user']);

        $this->assertSame('free', $user->getRole());
    }

    public function test_admin_role_is_still_preserved(): void
    {
        $user = new User(['role' => 'ADMIN']);

        $this->assertSame('admin', $user->getRole());
    }
}
