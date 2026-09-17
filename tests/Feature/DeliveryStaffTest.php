<?php

namespace Tests\Feature;

use App\Models\DeliveryAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/delivery')->assertRedirect('/login');
    }

    public function test_customers_cannot_open_delivery_management(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)->get('/admin/delivery')->assertForbidden();
    }

    public function test_delivery_staff_cannot_open_delivery_management(): void
    {
        $user = User::factory()->create(['role' => 'delivery']);

        $this->actingAs($user)->get('/admin/delivery')->assertForbidden();
    }

    public function test_admin_can_view_staff_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/delivery')
            ->assertOk()
            ->assertSee('Delivery staff');
    }

    public function test_admin_can_create_delivery_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/delivery/staff', [
            'name' => 'Rider One',
            'email' => 'rider1@example.com',
            'phone' => '0700000000',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
        ]);

        $response->assertRedirect(route('delivery.admin.staff.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'rider1@example.com',
            'role' => 'delivery',
            'phone' => '0700000000',
        ]);
    }

    public function test_create_requires_matching_password_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from('/admin/delivery/staff/create')
            ->post('/admin/delivery/staff', [
                'name' => 'Rider Two',
                'email' => 'rider2@example.com',
                'phone' => '0700000001',
                'password' => 'secret-pass',
                'password_confirmation' => 'different',
            ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'rider2@example.com']);
    }

    public function test_admin_can_disable_and_enable_staff(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'delivery', 'active' => true]);

        $this->actingAs($admin)->post(route('delivery.admin.staff.toggle', $staff))->assertRedirect();
        $this->assertFalse($staff->fresh()->active);

        $this->actingAs($admin)->post(route('delivery.admin.staff.toggle', $staff))->assertRedirect();
        $this->assertTrue($staff->fresh()->active);
    }

    public function test_admin_can_update_staff_and_keep_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'delivery']);
        $originalPassword = $staff->password;

        $this->actingAs($admin)->put(route('delivery.admin.staff.update', $staff), [
            'name' => 'Renamed',
            'email' => $staff->email,
            'phone' => '0711111111',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('delivery.admin.staff.index'));

        $staff->refresh();
        $this->assertSame('Renamed', $staff->name);
        $this->assertSame('0711111111', $staff->phone);
        $this->assertSame($originalPassword, $staff->password);
    }

    public function test_admin_can_remove_staff_without_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'delivery']);

        $this->actingAs($admin)->delete(route('delivery.admin.staff.destroy', $staff))->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }

    public function test_admin_cannot_remove_staff_with_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'delivery']);

        DeliveryAssignment::create([
            'order_id' => 1,
            'delivery_user_id' => $staff->id,
            'assigned_by' => $admin->id,
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->actingAs($admin)->delete(route('delivery.admin.staff.destroy', $staff))->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $staff->id]);
    }
}