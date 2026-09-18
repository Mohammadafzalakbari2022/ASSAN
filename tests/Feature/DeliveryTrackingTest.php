<?php

namespace Tests\Feature;

use App\Models\DeliveryAssignment;
use App\Models\DeliveryLocation;
use App\Models\User;
use App\Support\DeliveryTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function makeStaff(bool $active = true): User
    {
        return User::factory()->create(['role' => 'delivery', 'active' => $active]);
    }

    protected function makeAssignment(User $staff, string $status = DeliveryAssignment::STATUS_ASSIGNED): DeliveryAssignment
    {
        return DeliveryAssignment::create([
            'order_id' => 501,
            'delivery_user_id' => $staff->id,
            'assigned_by' => $staff->id,
            'status' => $status,
            'assigned_at' => now(),
        ]);
    }

    protected function postLocation(User $staff, float $lat = 34.5553, float $lng = 69.2075)
    {
        return $this->actingAs($staff)->postJson(route('delivery.location'), [
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy' => 10.0,
        ]);
    }

    public function test_guests_cannot_send_a_location(): void
    {
        $this->postJson(route('delivery.location'), [
            'latitude' => 34.5,
            'longitude' => 69.2,
        ])->assertUnauthorized();
    }

    public function test_customers_cannot_send_a_location(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->postJson(route('delivery.location'), [
            'latitude' => 34.5,
            'longitude' => 69.2,
        ])->assertRedirect(route('delivery.login'));

        $this->assertDatabaseCount('delivery_locations', 0);
    }

    public function test_disabled_staff_cannot_send_a_location(): void
    {
        $staff = $this->makeStaff(false);

        $this->postLocation($staff)->assertRedirect(route('delivery.login'));

        $this->assertDatabaseCount('delivery_locations', 0);
    }

    public function test_staff_can_send_a_location(): void
    {
        $staff = $this->makeStaff();
        $assignment = $this->makeAssignment($staff);

        $this->postLocation($staff)->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('delivery_locations', [
            'delivery_user_id' => $staff->id,
            'assignment_id' => $assignment->id,
        ]);
    }

    public function test_location_must_be_valid(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff)->postJson(route('delivery.location'), [
            'latitude' => 200,
            'longitude' => 69.2,
        ])->assertStatus(422)->assertJsonValidationErrors('latitude');
    }

    public function test_old_locations_are_trimmed(): void
    {
        $staff = $this->makeStaff();

        DeliveryLocation::create([
            'delivery_user_id' => $staff->id,
            'latitude' => 34.0,
            'longitude' => 69.0,
            'recorded_at' => now()->subDays(DeliveryTracking::KEEP_DAYS + 1),
        ]);

        $this->postLocation($staff)->assertOk();

        $this->assertSame(1, DeliveryLocation::where('delivery_user_id', $staff->id)->count());
        $this->assertSame(
            0,
            DeliveryLocation::where('delivery_user_id', $staff->id)
                ->where('recorded_at', '<', now()->subDays(DeliveryTracking::KEEP_DAYS))
                ->count()
        );
    }

    protected function makeAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guests_cannot_open_the_map(): void
    {
        $this->get('/admin/delivery/map')->assertRedirect(route('login'));
    }

    public function test_customers_cannot_open_the_map(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get('/admin/delivery/map')->assertForbidden();
        $this->actingAs($customer)->getJson('/admin/delivery/map/locations')->assertForbidden();
    }

    public function test_admin_can_open_the_map(): void
    {
        $this->actingAs($this->makeAdmin())->get('/admin/delivery/map')->assertOk();
    }

    public function test_map_locations_report_the_latest_position_and_trail(): void
    {
        $staff = $this->makeStaff();
        $this->makeAssignment($staff);

        $this->postLocation($staff, 34.5000, 69.1000);
        $this->postLocation($staff, 34.6000, 69.2000);

        $response = $this->actingAs($this->makeAdmin())
            ->getJson('/admin/delivery/map/locations')
            ->assertOk();

        $response->assertJsonPath('staff.0.id', $staff->id)
            ->assertJsonPath('staff.0.status', 'live')
            ->assertJsonPath('staff.0.lat', 34.6)
            ->assertJsonPath('staff.0.lng', 69.2)
            ->assertJsonPath('staff.0.active_orders', 1);

        $this->assertCount(2, $response->json('staff.0.trail'));
    }

    public function test_map_locations_mark_a_silent_staff_as_offline(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($this->makeAdmin())
            ->getJson('/admin/delivery/map/locations')
            ->assertOk()
            ->assertJsonPath('staff.0.id', $staff->id)
            ->assertJsonPath('staff.0.status', 'offline')
            ->assertJsonPath('staff.0.lat', null);
    }

    public function test_a_stale_position_is_not_reported_as_live(): void
    {
        $staff = $this->makeStaff();

        DeliveryLocation::create([
            'delivery_user_id' => $staff->id,
            'latitude' => 34.5,
            'longitude' => 69.2,
            'recorded_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($this->makeAdmin())
            ->getJson('/admin/delivery/map/locations')
            ->assertOk()
            ->assertJsonPath('staff.0.status', 'stale');
    }
}
