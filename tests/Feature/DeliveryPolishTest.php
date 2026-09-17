<?php

namespace Tests\Feature;

use App\Models\DeliveryAssignment;
use App\Models\User;
use App\Support\ShopOrders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesShopOrderTables;
use Tests\TestCase;

class DeliveryPolishTest extends TestCase
{
    use CreatesShopOrderTables;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createShopOrderTables();
    }

    protected function makeOrder(int $id, int $statusdelivery = ShopOrders::STATUS_UNFINISHED, ?float $lat = null, ?float $lng = null): int
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
            'type' => 'delivery',
            'firstname' => 'Ali',
            'lastname' => 'Ahmadi',
            'address1' => 'Kabul Street 1',
            'city' => 'Kabul',
            'mobile' => '0700000000',
            'latitude' => $lat,
            'longitude' => $lng,
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

    public function test_admin_shell_ships_back_forward_and_unsaved_guard(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin/delivery/orders')
            ->assertOk()
            ->assertSee('data-history-back', false)
            ->assertSee('data-history-forward', false)
            ->assertSee('os-desktop', false)
            ->assertSee('beforeunload', false);
    }

    public function test_delivery_shell_ships_back_forward_and_unsaved_guard(): void
    {
        $this->actingAs($this->makeStaff())
            ->get('/delivery')
            ->assertOk()
            ->assertSee('data-history-back', false)
            ->assertSee('data-history-forward', false)
            ->assertSee('beforeunload', false);
    }

    public function test_login_shell_ships_the_history_controls(): void
    {
        $this->get('/delivery/login')
            ->assertOk()
            ->assertSee('data-history-back', false)
            ->assertSee('os-desktop', false);
    }

    public function test_disabled_staff_error_is_shown_beside_the_order_row(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff(false);
        $this->makeOrder(300);
        $this->makeOrder(301);

        $this->actingAs($admin)
            ->from('/admin/delivery/orders')
            ->followingRedirects()
            ->post(route('delivery.admin.orders.assign', 300), [
                '_order' => 300,
                'delivery_user_id' => $staff->id,
            ])
            ->assertSee('disabled and cannot be assigned');
    }

    public function test_missing_delivery_person_error_is_shown_beside_the_order_row(): void
    {
        $admin = $this->makeAdmin();
        $this->makeStaff();
        $this->makeOrder(302);

        $this->actingAs($admin)
            ->from('/admin/delivery/orders')
            ->followingRedirects()
            ->post(route('delivery.admin.orders.assign', 302), [
                '_order' => 302,
                'delivery_user_id' => '',
            ])
            ->assertSee('Choose a delivery person.');
    }

    public function test_pages_mark_unsaved_input_only_after_a_failed_save(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff(false);
        $this->makeOrder(303);

        $this->actingAs($admin)
            ->get('/admin/delivery/orders')
            ->assertOk()
            ->assertDontSee('data-unsaved="1"', false);

        $this->actingAs($admin)
            ->from('/admin/delivery/orders')
            ->followingRedirects()
            ->post(route('delivery.admin.orders.assign', 303), [
                '_order' => 303,
                'delivery_user_id' => $staff->id,
            ])
            ->assertSee('data-unsaved="1"', false);
    }

    public function test_order_page_ships_a_live_tracking_map(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();
        $this->makeOrder(304);

        $assignment = DeliveryAssignment::create([
            'order_id' => 304,
            'delivery_user_id' => $staff->id,
            'assigned_by' => $admin->id,
            'status' => DeliveryAssignment::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('delivery.orders.show', $assignment))
            ->assertOk()
            ->assertSee('delivery-map', false)
            ->assertSee('leaflet/leaflet.css', false)
            ->assertSee('leaflet/leaflet.js', false)
            ->assertSee('Delivery destination', false);
    }

    public function test_order_page_shows_the_destination_marker_when_coordinates_exist(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();
        $this->makeOrder(305, ShopOrders::STATUS_UNFINISHED, 34.5, 69.1);

        $assignment = DeliveryAssignment::create([
            'order_id' => 305,
            'delivery_user_id' => $staff->id,
            'assigned_by' => $admin->id,
            'status' => DeliveryAssignment::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('delivery.orders.show', $assignment))
            ->assertOk()
            ->assertSee('destLat = 34.5', false)
            ->assertSee('destLng = 69.1', false);
    }
}
