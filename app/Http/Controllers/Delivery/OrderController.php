<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAssignment;
use App\Support\ShopOrders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(protected ShopOrders $orders)
    {
    }

    public function index(Request $request): View
    {
        $assignments = $this->activeAssignmentsFor($request->user()->id);
        $orders = $this->orders->byIds($assignments->pluck('order_id')->all());

        return view('delivery.app.orders.index', [
            'assignments' => $assignments,
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, DeliveryAssignment $assignment): View
    {
        $this->authorizeAssignment($request, $assignment);
        $this->abortIfFinished($assignment);

        $order = $this->orders->byIds([$assignment->order_id])->get($assignment->order_id);

        abort_if($order === null, 404);

        return view('delivery.app.orders.show', [
            'assignment' => $assignment,
            'order' => $order,
            'items' => $this->orders->items($assignment->order_id),
        ]);
    }

    public function start(Request $request, DeliveryAssignment $assignment): RedirectResponse
    {
        $this->authorizeAssignment($request, $assignment);

        if ($assignment->status !== DeliveryAssignment::STATUS_ASSIGNED) {
            return redirect()->route('delivery.orders.show', $assignment)
                ->with('error', 'This delivery has already started.');
        }

        $assignment->update([
            'status' => DeliveryAssignment::STATUS_STARTED,
            'started_at' => now(),
        ]);

        return redirect()->route('delivery.orders.show', $assignment)
            ->with('status', 'Delivery started.');
    }

    public function deliver(Request $request, DeliveryAssignment $assignment): RedirectResponse
    {
        $this->authorizeAssignment($request, $assignment);

        if ($this->isFinished($assignment)) {
            return redirect()->route('delivery.orders.index')
                ->with('error', 'This delivery was already finished.');
        }

        $assignment->update([
            'status' => DeliveryAssignment::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        return redirect()->route('delivery.orders.index')
            ->with('status', 'Marked as delivered.');
    }

    public function fail(Request $request, DeliveryAssignment $assignment): RedirectResponse
    {
        $this->authorizeAssignment($request, $assignment);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'reason.required' => 'Write why the order could not be delivered.',
            'reason.min' => 'Please write a little more about what happened.',
            'reason.max' => 'Please keep the reason under 500 characters.',
        ]);

        if ($this->isFinished($assignment)) {
            return redirect()->route('delivery.orders.index')
                ->with('error', 'This delivery was already finished.');
        }

        $assignment->update([
            'status' => DeliveryAssignment::STATUS_FAILED,
            'note' => $data['reason'],
            'failed_at' => now(),
        ]);

        return redirect()->route('delivery.orders.index')
            ->with('status', 'Marked as not delivered.');
    }

    protected function activeAssignmentsFor(int $userId)
    {
        return DeliveryAssignment::query()
            ->where('delivery_user_id', $userId)
            ->whereIn('status', [DeliveryAssignment::STATUS_ASSIGNED, DeliveryAssignment::STATUS_STARTED])
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->get();
    }

    protected function authorizeAssignment(Request $request, DeliveryAssignment $assignment): void
    {
        abort_unless((int) $assignment->delivery_user_id === (int) $request->user()->id, 404);
    }

    protected function isFinished(DeliveryAssignment $assignment): bool
    {
        return in_array(
            $assignment->status,
            [DeliveryAssignment::STATUS_DELIVERED, DeliveryAssignment::STATUS_FAILED],
            true
        );
    }

    protected function abortIfFinished(DeliveryAssignment $assignment): void
    {
        abort_if($this->isFinished($assignment), 404);
    }
}
