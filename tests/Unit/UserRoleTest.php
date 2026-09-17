<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    public function test_delivery_role_is_detected(): void
    {
        $user = new User(['role' => 'delivery']);

        $this->assertTrue($user->isDelivery());
        $this->assertFalse($user->isAdmin());
    }

    public function test_admin_role_is_detected(): void
    {
        $user = new User(['role' => 'admin']);

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isDelivery());
    }

    public function test_superuser_flag_is_treated_as_admin(): void
    {
        $user = new User(['role' => 'customer']);
        $user->setAttribute('superuser', 1);

        $this->assertTrue($user->isAdmin());
    }
}