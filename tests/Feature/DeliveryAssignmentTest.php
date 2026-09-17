<?php

namespace Tests\Feature;

use App\Models\DeliveryAssignment;
use App\Models\User;
use App\Support\ShopOrders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesShopOrderTables;
use Tests\TestCase;

class DeliveryAssignmentTest extends TestCase
{
    use CreatesShopOrderTables;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createShopOrderTables();
    }

    protected function makeOrder(int $id, int $statusdelivery = ShopOrders::STATUS_UNFINISHED, string $type = 'delivery'): int
    {
        DB::table('mshop_order')->insert([
            'id' => $id,
            'invoiceno' => 'INV-' . $id,
            'customerid' => 'cust-' . $id,
            'statuspayment' => 1,
            'statusdelivery' => $statusdelivery,
            'price' => 250,
            'currencyid' => 'AFN',
            'ctime' => now(),
            'mtime' => now(),
        ]);

        DB::table('mshop_order_address')->insert([
            'parentid' => $id,
            'type' => $type,
            'firstname' => 'Ali',
            'lastname' => 'Ahmadi',
            'address1' => 'Kabul Street 1',
            'city' => 'Kabul',
            'mobile' => '0700000000',
        ]);

        return $id;
    }

    protected function makeStaff(bool $active = true): User
    {
        return User::factory()->create(['role' => 'delivery', 'active' => $active]);
    }

    protected function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/delivery/orders')->assertRedirect('/login');
    }

    public function test_customers_cannot_open_the_delivery_board(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)->get('/admin/delivery/orders')->assertForbidden();
    }

    public function test_admin_sees_open_order_with_customer_details(): void
    {
        $admin = $this->makeAdmin();
        $this->makeOrder(101);

        $this->actingAs($admin)->get('/admin/delivery/orders')
            ->assertOk()
            ->assertSee('INV-101')
            ->assertSee('Ali Ahmadi')
            ->assertSee('Kabul Street 1');
    }

    public function test_finished_orders_are_not_listed(): void
    {
        $admin = $this->makeAdmin();
        $this->makeOrder(102, ShopOrders::STATUS_DELIVERED);

        $this->actingAs($admin)->get('/admin/delivery/orders')
            ->assertOk()
            ->assertDontSee('INV-102');
    }

    public function test_admin_can_assign_order_to_delivery_person(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();
        $this->makeOrder(103);

        $this->actingAs($admin)
            ->post(route('delivery.admin.orders.assign', 103), ['delivery_user_id' => $staff->id])
            ->assertRedirect();

        $this->assertDatabaseHas('delivery_assignments', [
            'order_id' => 103,
            'delivery_user_id' => $staff->id,
            'status' => 'assigned',
        ]);
    }

    public function test_assigning_again_changes_the_delivery_person(): void
    {
        $admin = $this->makeAdmin();
        $first = $this->makeStaff();
        $second = $this->makeStaff();
        $this->makeOrder(104);

        $this->actingAs($admin)->post(route('delivery.admin.orders.assign', 104), ['delivery_user_id' => $first->id]);
        $this->actingAs($admin)->post(route('delivery.admin.orders.assign', 104), ['delivery_user_id' => $second->id]);

        $this->assertSame(1, DeliveryAssignment::where('order_id', 104)
            ->whereIn('status', [DeliveryAssignment::STATUS_ASSIGNED, DeliveryAssignment::STATUS_STARTED])
            ->count());

        $this->assertDatabaseHas('delivery_assignments', [
            'order_id' => 104,
            'delivery_user_id' => $second->id,
            'status' => 'assigned',
        ]);
    }

    public function test_cannot_assign_to_inactive_staff(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff(false);
        $this->makeOrder(105);

        $this->actingAs($admin)
            ->from('/admin/delivery/orders')
            ->post(route('delivery.admin.orders.assign', 105), ['delivery_user_id' => $staff->id])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('delivery_assignments', ['order_id' => 105]);
    }

    public function test_cannot_assign_to_a_customer_account(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create(['role' => 'customer']);
        $this->makeOrder(106);

        $this->actingAs($admin)
            ->from('/admin/delivery/orders')
            ->post(route('delivery.admin.orders.assign', 106), ['delivery_user_id' => $customer->id])
            ->assertSessionHasErrors('delivery_user_id');

        $this->assertDatabaseMissing('delivery_assignments', ['order_id' => 106]);
    }

    public function test_cannot_assign_a_finished_order(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();
        $this->makeOrder(107, ShopOrders::STATUS_DELIVERED);

        $this->actingAs($admin)
            ->from('/admin/delivery/orders')
            ->post(route('delivery.admin.orders.assign', 107), ['delivery_user_id' => $staff->id])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('delivery_assignments', ['order_id' => 107]);
    }

    public function test_cannot_assign_an_order_that_does_not_exist(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();

        $this->actingAs($admin)
            ->from('/admin/delivery/orders')
            ->post(route('delivery.admin.orders.assign', 999999), ['delivery_user_id' => $staff->id])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('delivery_assignments', ['order_id' => 999999]);
    }

    public function test_admin_can_unassign_an_assigned_order(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();

        $assignment = DeliveryAssignment::create([
            'order_id' => 108,
            'delivery_user_id' => $staff->id,
            'assigned_by' => $admin->id,
            'status' => DeliveryAssignment::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('delivery.admin.orders.unassign', $assignment))
            ->assertRedirect();

        $this->assertDatabaseMissing('delivery_assignments', ['id' => $assignment->id]);
    }

    public function test_cannot_unassign_a_started_order(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();

        $assignment = DeliveryAssignment::create([
            'order_id' => 109,
            'delivery_user_id' => $staff->id,
            'assigned_by' => $admin->id,
            'status' => DeliveryAssignment::STATUS_STARTED,
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from('/admin/delivery/orders')
            ->delete(route('delivery.admin.orders.unassign', $assignment))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('delivery_assignments', ['id' => $assignment->id]);
    }
}
