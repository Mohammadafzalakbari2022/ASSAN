<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAssignment;
use App\Models\DeliveryLocation;
use App\Support\DeliveryTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Record one position sent by a delivery phone while the delivery page is
     * open. The time and the current order are set by the server; the phone
     * only supplies where it is.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'speed' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ], [
            'latitude.required' => 'The phone did not send a location.',
            'longitude.required' => 'The phone did not send a location.',
            'latitude.between' => 'That location is not valid.',
            'longitude.between' => 'That location is not valid.',
        ]);

        $user = $request->user();

        DeliveryLocation::create([
            'delivery_user_id' => $user->id,
            'assignment_id' => $this->activeAssignmentId($user->id),
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'] ?? null,
            'speed' => $data['speed'] ?? null,
            'recorded_at' => now(),
        ]);

        $this->pruneOldLocations($user->id);

        return response()->json(['ok' => true]);
    }

    /**
     * The order this person is currently delivering, so the point can be tied
     * to a delivery. Chosen on the server; the phone cannot claim another
     * person's order.
     */
    protected function activeAssignmentId(int $userId): ?int
    {
        return DeliveryAssignment::query()
            ->where('delivery_user_id', $userId)
            ->whereIn('status', [DeliveryAssignment::STATUS_ASSIGNED, DeliveryAssignment::STATUS_STARTED])
            ->orderByDesc('id')
            ->value('id');
    }

    protected function pruneOldLocations(int $userId): void
    {
        DeliveryLocation::query()
            ->where('delivery_user_id', $userId)
            ->where('recorded_at', '<', now()->subDays(DeliveryTracking::KEEP_DAYS))
            ->delete();
    }
}
