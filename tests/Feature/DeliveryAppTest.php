<?php

namespace Tests\Feature;

use App\Models\DeliveryAssignment;
use App\Models\User;
use App\Support\ShopOrders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesShopOrderTables;
use Tests\TestCase;

class DeliveryAppTest extends TestCase
{
    use CreatesShopOrderTables;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createShopOrderTables();
    }

    protected function makeOrder(
        int $id,
        int $statusdelivery = ShopOrders::STATUS_UNFINISHED,
        string $firstname = 'Ali',
        string $address = 'Kabul Street 1'
    ): int {
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
            'type' => 'delivery',
            'firstname' => $firstname,
            'lastname' => 'Ahmadi',
            'address1' => $address,
            'city' => 'Kabul',
            'mobile' => '0700000000',
        ]);

        return $id;
    }

    protected function makeItem(int $orderId, string $name, float $quantity = 1, float $price = 100): void
    {
        DB::table('mshop_order_product')->insert([
            'parentid' => $orderId,
            'name' => $name,
            'quantity' => $quantity,
            'price' => $price,
            'currencyid' => 'AFN',
            'pos' => 0,
        ]);
    }

    protected function makeStaff(bool $active = true): User
    {
        return User::factory()->create(['role' => 'delivery', 'active' => $active]);
    }

    protected function makeAssignment(int $orderId, User $staff, string $status = DeliveryAssignment::STATUS_ASSIGNED): DeliveryAssignment
    {
        return DeliveryAssignment::create([
            'order_id' => $orderId,
            'delivery_user_id' => $staff->id,
            'assigned_by' => $staff->id,
            'status' => $status,
            'assigned_at' => now(),
            'started_at' => $status === DeliveryAssignment::STATUS_STARTED ? now() : null,
        ]);
    }

    public function test_guests_are_sent_to_the_delivery_login(): void
    {
        $this->get('/delivery')->assertRedirect(route('delivery.login'));
    }

    public function test_staff_can_sign_in(): void
    {
        $staff = $this->makeStaff();

        $this->post('/delivery/login', [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertRedirect(route('delivery.orders.index'));

        $this->assertAuthenticatedAs($staff);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $staff = $this->makeStaff();

        $this->from('/delivery/login')->post('/delivery/login', [
            'email' => $staff->email,
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_customer_cannot_sign_in_here(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->from('/delivery/login')->post('/delivery/login', [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_disabled_staff_cannot_sign_in(): void
    {
        $staff = $this->makeStaff(false);

        $this->from('/delivery/login')->post('/delivery/login', [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_disabled_staff_is_signed_out_from_a_live_session(): void
    {
        $staff = $this->makeStaff(false);

        $this->actingAs($staff)->get('/delivery')->assertRedirect(route('delivery.login'));
    }

    public function test_customer_is_redirected_instead_of_forbidden(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get('/delivery')
            ->assertRedirect(route('delivery.login'))
            ->assertSessionHas('info');
    }

    public function test_shop_account_can_reach_the_delivery_signin_to_switch(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/delivery/login')
            ->assertOk()
            ->assertSee('delivery person');
    }

    public function test_staff_only_sees_their_own_deliveries(): void
    {
        $staffA = $this->makeStaff();
        $staffB = $this->makeStaff();
        $this->makeAssignment($this->makeOrder(201), $staffA);
        $this->makeAssignment($this->makeOrder(202), $staffB);

        $this->actingAs($staffA)->get('/delivery')
            ->assertOk()
            ->assertSee('INV-201')
            ->assertDontSee('INV-202');
    }

    public function test_order_screen_shows_customer_and_items(): void
    {
        $staff = $this->makeStaff();
        $this->makeOrder(203);
        $this->makeItem(203, 'Non-stick frying pan', 2, 450);
        $assignment = $this->makeAssignment(203, $staff);

        $this->actingAs($staff)->get(route('delivery.orders.show', $assignment))
            ->assertOk()
            ->assertSee('Ali Ahmadi')
            ->assertSee('0700000000')
            ->assertSee('Non-stick frying pan');
    }

    public function test_staff_can_start_a_delivery(): void
    {
        $staff = $this->makeStaff();
        $assignment = $this->makeAssignment($this->makeOrder(204), $staff);

        $this->actingAs($staff)
            ->post(route('delivery.orders.start', $assignment))
            ->assertRedirect(route('delivery.orders.show', $assignment));

        $this->assertSame(DeliveryAssignment::STATUS_STARTED, $assignment->fresh()->status);
    }

    public function test_staff_can_mark_delivered(): void
    {
        $staff = $this->makeStaff();
        $assignment = $this->makeAssignment($this->makeOrder(205), $staff);

        $this->actingAs($staff)
            ->post(route('delivery.orders.deliver', $assignment))
            ->assertRedirect(route('delivery.orders.index'));

        $assignment->refresh();
        $this->assertSame(DeliveryAssignment::STATUS_DELIVERED, $assignment->status);
        $this->assertNotNull($assignment->delivered_at);
    }

    public function test_staff_can_mark_not_delivered_with_a_reason(): void
    {
        $staff = $this->makeStaff();
        $assignment = $this->makeAssignment($this->makeOrder(206), $staff);

        $this->actingAs($staff)
            ->post(route('delivery.orders.fail', $assignment), ['reason' => 'Customer was not at home'])
            ->assertRedirect(route('delivery.orders.index'));

        $assignment->refresh();
        $this->assertSame(DeliveryAssignment::STATUS_FAILED, $assignment->status);
        $this->assertSame('Customer was not at home', $assignment->note);
        $this->assertNotNull($assignment->failed_at);
    }

    public function test_not_delivered_requires_a_reason(): void
    {
        $staff = $this->makeStaff();
        $assignment = $this->makeAssignment($this->makeOrder(207), $staff);

        $this->actingAs($staff)
            ->from(route('delivery.orders.show', $assignment))
            ->post(route('delivery.orders.fail', $assignment), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(DeliveryAssignment::STATUS_ASSIGNED, $assignment->fresh()->status);
    }

    public function test_staff_cannot_open_another_persons_delivery(): void
    {
        $staffA = $this->makeStaff();
        $staffB = $this->makeStaff();
        $assignment = $this->makeAssignment($this->makeOrder(208), $staffB);

        $this->actingAs($staffA)->get(route('delivery.orders.show', $assignment))->assertNotFound();
        $this->actingAs($staffA)->post(route('delivery.orders.deliver', $assignment))->assertNotFound();

        $this->assertSame(DeliveryAssignment::STATUS_ASSIGNED, $assignment->fresh()->status);
    }

    public function test_staff_can_sign_out(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff)
            ->post('/delivery/logout')
            ->assertRedirect(route('delivery.login'));

        $this->assertGuest();
    }

    public function test_delivering_updates_the_shop_order_status(): void
    {
        $staff = $this->makeStaff();
        $assignment = $this->makeAssignment($this->makeOrder(210), $staff);

        $this->actingAs($staff)->post(route('delivery.orders.deliver', $assignment));

        $this->assertSame(
            ShopOrders::STATUS_DELIVERED,
            (int) DB::table('mshop_order')->where('id', 210)->value('statusdelivery')
        );

        $this->assertDatabaseHas('mshop_order_status', [
            'parentid' => 210,
            'type' => 'status-delivery',
            'value' => (string) ShopOrders::STATUS_DELIVERED,
        ]);
    }

    public function test_failed_delivery_updates_the_shop_order_status(): void
    {
        $staff = $this->makeStaff();
        $assignment = $this->makeAssignment($this->makeOrder(211), $staff);

        $this->actingAs($staff)
            ->post(route('delivery.orders.fail', $assignment), ['reason' => 'Nobody home']);

        $this->assertSame(
            ShopOrders::STATUS_REFUSED,
            (int) DB::table('mshop_order')->where('id', 211)->value('statusdelivery')
        );

        $this->assertDatabaseHas('mshop_order_status', [
            'parentid' => 211,
            'type' => 'status-delivery',
            'value' => (string) ShopOrders::STATUS_REFUSED,
        ]);
    }

    public function test_delivery_does_not_overwrite_a_finished_order_status(): void
    {
        $staff = $this->makeStaff();
        $assignment = $this->makeAssignment($this->makeOrder(212, ShopOrders::STATUS_DELIVERED), $staff);

        $this->actingAs($staff)
            ->post(route('delivery.orders.fail', $assignment), ['reason' => 'Nobody home']);

        $this->assertSame(
            ShopOrders::STATUS_DELIVERED,
            (int) DB::table('mshop_order')->where('id', 212)->value('statusdelivery')
        );

        $this->assertSame(0, DB::table('mshop_order_status')->where('parentid', 212)->count());
    }

    public function test_delivered_order_leaves_the_admin_board(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = $this->makeStaff();
        $assignment = $this->makeAssignment($this->makeOrder(213), $staff);

        $this->actingAs($admin)->get('/admin/delivery/orders')->assertSee('INV-213');

        $this->actingAs($staff)->post(route('delivery.orders.deliver', $assignment));

        $this->actingAs($admin)->get('/admin/delivery/orders')->assertDontSee('INV-213');
    }
}
