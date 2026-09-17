<?php

namespace App\Support;

use App\Models\DeliveryAssignment;
use App\Models\DeliveryLocation;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Reads the positions sent by delivery phones and turns them into what the
 * admin map needs: one dot per on-duty delivery person, how long ago they were
 * last seen, and a short trail behind them.
 *
 * A dot is "live" while we have heard from the phone in the last two minutes,
 * "stale" up to fifteen minutes, and "offline" beyond that (or if we never
 * heard from it). The browser can only send position while the delivery page is
 * open, so "offline" here means the page was closed or the signal was lost.
 */
class DeliveryTracking
{
    public const LIVE_SECONDS = 120;
    public const STALE_SECONDS = 900;
    public const TRAIL_LIMIT = 50;
    public const TRAIL_HOURS = 3;
    public const KEEP_DAYS = 7;

    /**
     * On-duty delivery people (active delivery accounts), ordered by name.
     *
     * @return Collection<int, User>
     */
    public function staff(): Collection
    {
        return User::query()
            ->where('role', 'delivery')
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);
    }

    /**
     * Everything the map needs, as plain data ready to be turned into JSON.
     *
     * @return array{server_time: string, staff: array<int, array<string, mixed>>}
     */
    public function snapshot(): array
    {
        $now = now();

        $staff = $this->staff()->map(function (User $user) use ($now): array {
            $last = $this->latestFor($user->id);
            $age = $last !== null ? (int) $last->recorded_at->diffInSeconds($now) : null;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'status' => $this->statusFor($age),
                'last_seen' => $last?->recorded_at?->toIso8601String(),
                'last_seen_human' => $last?->recorded_at?->diffForHumans(),
                'accuracy' => $last?->accuracy !== null ? (float) $last->accuracy : null,
                'speed' => $last?->speed !== null ? (float) $last->speed : null,
                'lat' => $last?->latitude !== null ? (float) $last->latitude : null,
                'lng' => $last?->longitude !== null ? (float) $last->longitude : null,
                'active_orders' => $this->activeOrderCount($user->id),
                'trail' => $this->trailFor($user->id)
                    ->map(fn (DeliveryLocation $point): array => [
                        (float) $point->latitude,
                        (float) $point->longitude,
                    ])
                    ->all(),
            ];
        })->values()->all();

        return [
            'server_time' => $now->toIso8601String(),
            'staff' => $staff,
        ];
    }

    protected function statusFor(?int $ageInSeconds): string
    {
        if ($ageInSeconds === null) {
            return 'offline';
        }

        if ($ageInSeconds <= self::LIVE_SECONDS) {
            return 'live';
        }

        return $ageInSeconds <= self::STALE_SECONDS ? 'stale' : 'offline';
    }

    protected function latestFor(int $userId): ?DeliveryLocation
    {
        return DeliveryLocation::query()
            ->where('delivery_user_id', $userId)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * The most recent points, oldest first, so they can be drawn as a line.
     *
     * @return Collection<int, DeliveryLocation>
     */
    protected function trailFor(int $userId): Collection
    {
        return DeliveryLocation::query()
            ->where('delivery_user_id', $userId)
            ->where('recorded_at', '>=', now()->subHours(self::TRAIL_HOURS))
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(self::TRAIL_LIMIT)
            ->get()
            ->reverse()
            ->values();
    }

    protected function activeOrderCount(int $userId): int
    {
        return DeliveryAssignment::query()
            ->where('delivery_user_id', $userId)
            ->whereIn('status', [DeliveryAssignment::STATUS_ASSIGNED, DeliveryAssignment::STATUS_STARTED])
            ->count();
    }
}
