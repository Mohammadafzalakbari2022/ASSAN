<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAssignment;
use App\Models\User;
use App\Support\ShopOrders;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryOrderController extends Controller
{
    public function __construct(protected ShopOrders $orders)
    {
    }

    public function index(): View
    {
        $staff = User::query()
            ->where('role', 'delivery')
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('delivery.admin.orders.index', [
            'orders' => $this->orders->openOrders(),
            'staff' => $staff,
        ]);
    }

    public function assign(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate([
            'delivery_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'delivery'),
            ],
        ], [
            'delivery_user_id.required' => 'Choose a delivery person.',
            'delivery_user_id.exists' => 'That delivery account does not exist.',
        ]);

        $shopOrder = $this->orders->order($order);

        if ($shopOrder === null) {
            return back()->withErrors(['order_' . $order => 'That order was not found.']);
        }

        if (!$this->orders->isOpen((int) $shopOrder->statusdelivery)) {
            return back()->withErrors(['order_' . $order => 'That order is already finished and cannot be assigned.']);
        }

        $staff = User::find($data['delivery_user_id']);

        if ($staff === null || !$staff->active) {
            return back()->withErrors(['order_' . $order => 'That delivery account is disabled and cannot be assigned.']);
        }

        try {
            $reassigned = DB::transaction(function () use ($order, $staff, $request): bool {
                $active = DeliveryAssignment::query()
                    ->where('order_id', $order)
                    ->whereIn('status', [DeliveryAssignment::STATUS_ASSIGNED, DeliveryAssignment::STATUS_STARTED])
                    ->first();

                if ($active !== null) {
                    $active->update([
                        'delivery_user_id' => $staff->id,
                        'assigned_by' => $request->user()->id,
                        'status' => DeliveryAssignment::STATUS_ASSIGNED,
                        'started_at' => null,
                        'assigned_at' => now(),
                    ]);

                    return true;
                }

                DeliveryAssignment::create([
                    'order_id' => $order,
                    'delivery_user_id' => $staff->id,
                    'assigned_by' => $request->user()->id,
                    'status' => DeliveryAssignment::STATUS_ASSIGNED,
                    'assigned_at' => now(),
                ]);

                return false;
            });
        } catch (QueryException $e) {
            return back()->withErrors(['order_' . $order => 'This order is already assigned to someone else.']);
        }

        return back()->with('status', $reassigned
            ? 'Delivery person changed to ' . $staff->name . '.'
            : 'Order assigned to ' . $staff->name . '.');
    }

    public function unassign(DeliveryAssignment $assignment): RedirectResponse
    {
        if ($assignment->status !== DeliveryAssignment::STATUS_ASSIGNED) {
            return back()->withErrors(['order_' . $assignment->order_id => 'This order is already being delivered and cannot be unassigned.']);
        }

        $assignment->delete();

        return back()->with('status', 'Order unassigned.');
    }
}

