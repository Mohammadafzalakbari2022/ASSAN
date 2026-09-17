<?php

namespace App\Support;

use App\Models\DeliveryAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Access to the shop's own order tables.
 *
 * The shop (Aimeos) keeps orders in plain database tables rather than in
 * application models, so this small helper gathers the pieces the delivery
 * board needs (the order, the delivery address, the items) and writes the
 * delivery result back into the shop's order status.
 */
class ShopOrders
{
    public const STATUS_UNFINISHED = -1;
    public const STATUS_DELETED = 0;
    public const STATUS_PENDING = 1;
    public const STATUS_PROGRESS = 2;
    public const STATUS_DISPATCHED = 3;
    public const STATUS_DELIVERED = 4;
    public const STATUS_LOST = 5;
    public const STATUS_REFUSED = 6;
    public const STATUS_RETURNED = 7;

    /**
     * Delivery statuses that mean the order is finished and must not appear on
     * the delivery board any more.
     *
     * @var array<int, int>
     */
    protected const CLOSED_STATUSES = [
        self::STATUS_DELETED,
        self::STATUS_DELIVERED,
        self::STATUS_LOST,
        self::STATUS_REFUSED,
        self::STATUS_RETURNED,
    ];

    /**
     * @var array<int, string>
     */
    protected const ORDER_COLUMNS = [
        'id',
        'invoiceno',
        'customerid',
        'statuspayment',
        'statusdelivery',
        'price',
        'currencyid',
        'ctime',
    ];

    public function isOpen(int $statusDelivery): bool
    {
        return !in_array($statusDelivery, self::CLOSED_STATUSES, true);
    }

    public static function statusLabel(int $statusDelivery): string
    {
        return match ($statusDelivery) {
            self::STATUS_UNFINISHED => 'New',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROGRESS => 'In progress',
            self::STATUS_DISPATCHED => 'Dispatched',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_LOST => 'Lost',
            self::STATUS_REFUSED => 'Refused',
            self::STATUS_RETURNED => 'Returned',
            self::STATUS_DELETED => 'Deleted',
            default => 'Open',
        };
    }

    /**
     * Orders that are still out for delivery, newest first, each carrying its
     * delivery address and current (active) assignment when one exists.
     *
     * @return Collection<int, object>
     */
    public function openOrders(int $limit = 200): Collection
    {
        $orders = DB::table('mshop_order')
            ->whereNotIn('statusdelivery', self::CLOSED_STATUSES)
            ->orderByDesc('id')
            ->limit($limit)
            ->get(self::ORDER_COLUMNS);

        if ($orders->isEmpty()) {
            return collect();
        }

        $ids = $orders->pluck('id')->all();
        $addresses = $this->addressesByOrder($ids);
        $assignments = DeliveryAssignment::query()
            ->with('deliveryUser:id,name,phone')
            ->whereIn('order_id', $ids)
            ->whereIn('status', [DeliveryAssignment::STATUS_ASSIGNED, DeliveryAssignment::STATUS_STARTED])
            ->get()
            ->keyBy('order_id');

        return $orders->map(function (object $order) use ($addresses, $assignments): object {
            $order->address = $addresses[$order->id] ?? null;
            $order->assignment = $assignments[$order->id] ?? null;

            return $order;
        });
    }

    public function order(int $id): ?object
    {
        $order = DB::table('mshop_order')
            ->where('id', $id)
            ->first(self::ORDER_COLUMNS);

        if ($order === null) {
            return null;
        }

        $order->address = $this->addressesByOrder([$id])[$id] ?? null;

        return $order;
    }

    /**
     * Fetch a set of orders by their ids, keyed by order id, each carrying its
     * delivery address.
     *
     * @param  array<int, int|string>  $ids
     * @return Collection<int, object>
     */
    public function byIds(array $ids): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if (empty($ids)) {
            return collect();
        }

        $orders = DB::table('mshop_order')
            ->whereIn('id', $ids)
            ->get(self::ORDER_COLUMNS);

        $addresses = $this->addressesByOrder($ids);

        return $orders->map(function (object $order) use ($addresses): object {
            $order->address = $addresses[$order->id] ?? null;

            return $order;
        })->keyBy('id');
    }

    /**
     * The products belonging to one order, in the order they were added.
     *
     * @return Collection<int, object>
     */
    public function items(int $orderId): Collection
    {
        return DB::table('mshop_order_product')
            ->where('parentid', $orderId)
            ->orderBy('pos')
            ->get(['name', 'quantity', 'price', 'currencyid']);
    }

    /**
     * Write the delivery result back into the shop's own order, so the admin
     * order panel and the customer's order history agree with the delivery app.
     *
     * Refuses to touch an order that is already finished (delivered, refused,
     * returned, lost or deleted).
     */
    public function setDeliveryStatus(int $orderId, int $status, string $editor = 'asan-delivery'): bool
    {
        return (bool) DB::transaction(function () use ($orderId, $status, $editor) {
            $order = DB::table('mshop_order')
                ->where('id', $orderId)
                ->lockForUpdate()
                ->first(['id', 'siteid', 'statusdelivery']);

            if ($order === null || !$this->isOpen((int) $order->statusdelivery)) {
                return false;
            }

            $now = now();

            DB::table('mshop_order')->where('id', $orderId)->update([
                'statusdelivery' => $status,
                'mtime' => $now,
                'editor' => $editor,
            ]);

            DB::table('mshop_order_status')->insert([
                'siteid' => $order->siteid,
                'parentid' => $orderId,
                'type' => 'status-delivery',
                'value' => (string) $status,
                'mtime' => $now,
                'ctime' => $now,
                'editor' => $editor,
            ]);

            return true;
        });
    }

    public static function customerName(?object $order): string
    {
        $address = $order->address ?? null;
        $name = trim(($address->company ?? '') !== ''
            ? $address->company
            : trim(($address->firstname ?? '') . ' ' . ($address->lastname ?? '')));

        return $name !== '' ? $name : 'Customer';
    }

    public static function customerPhone(?object $order): string
    {
        $address = $order->address ?? null;

        return ($address->mobile ?? '') ?: ($address->telephone ?? '');
    }

    public static function customerAddress(?object $order): string
    {
        $address = $order->address ?? null;

        if ($address === null) {
            return '';
        }

        $parts = array_filter([
            $address->address1 ?? '',
            $address->address2 ?? '',
            $address->address3 ?? '',
            $address->city ?? '',
            $address->state ?? '',
        ], fn ($part) => trim((string) $part) !== '');

        return implode(', ', array_map('trim', $parts));
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, object>
     */
    protected function addressesByOrder(array $ids): array
    {
        $rows = DB::table('mshop_order_address')
            ->whereIn('parentid', $ids)
            ->get([
                'parentid',
                'type',
                'company',
                'firstname',
                'lastname',
                'address1',
                'address2',
                'address3',
                'city',
                'state',
                'postal',
                'countryid',
                'email',
                'telephone',
                'mobile',
                'longitude',
                'latitude',
            ]);

        $result = [];

        foreach ($rows as $row) {
            if (!isset($result[$row->parentid])) {
                $result[$row->parentid] = $row;
                continue;
            }

            if ($row->type === 'delivery' && $result[$row->parentid]->type !== 'delivery') {
                $result[$row->parentid] = $row;
            }
        }

        return $result;
    }
}
