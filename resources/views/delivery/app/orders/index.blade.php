@extends('delivery.app.layout')

@section('title', 'My deliveries')

@section('content')
    <h1>My deliveries</h1>

    @if ($assignments->isEmpty())
        <div class="card">
            <p class="muted">No deliveries are assigned to you right now.</p>
        </div>
    @else
        @foreach ($assignments as $assignment)
            @php($order = $orders->get($assignment->order_id))
            <div class="card">
                <h2>
                    Order #{{ $order && $order->invoiceno !== '' ? $order->invoiceno : $assignment->order_id }}
                    @if ($assignment->status === \App\Models\DeliveryAssignment::STATUS_STARTED)
                        <span class="pill started">On the way</span>
                    @else
                        <span class="pill assigned">Assigned</span>
                    @endif
                </h2>

                <p style="margin:0 0 0.3rem;"><strong>{{ \App\Support\ShopOrders::customerName($order) }}</strong></p>
                @if (\App\Support\ShopOrders::customerAddress($order) !== '')
                    <p class="muted" style="margin:0 0 0.6rem;">{{ \App\Support\ShopOrders::customerAddress($order) }}</p>
                @endif

                <a class="btn primary block" href="{{ route('delivery.orders.show', $assignment) }}">Open order</a>
            </div>
        @endforeach
    @endif
@endsection
