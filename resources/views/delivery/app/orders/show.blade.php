@extends('delivery.app.layout')

@section('title', 'Order')

@php
    use App\Support\ShopOrders;
    $address = $order->address;
    $phone = ShopOrders::customerPhone($order);
    $addressLine = ShopOrders::customerAddress($order);
    $lat = (float) ($address->latitude ?? 0);
    $lng = (float) ($address->longitude ?? 0);
    $hasMap = $lat !== 0.0 && $lng !== 0.0;
@endphp

@section('content')
    <h1>
        Order #{{ $order->invoiceno !== '' ? $order->invoiceno : $order->id }}
        @if ($assignment->status === \App\Models\DeliveryAssignment::STATUS_STARTED)
            <span class="pill started">On the way</span>
        @else
            <span class="pill assigned">Assigned</span>
        @endif
    </h1>

    <div class="card">
        <h2>{{ ShopOrders::customerName($order) }}</h2>
        <dl>
            @if ($phone !== '')
                <dt>Phone</dt>
                <dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ $phone }}</a></dd>
            @endif

            @if ($addressLine !== '')
                <dt>Address</dt>
                <dd>{{ $addressLine }}</dd>
            @endif

            @if ($hasMap)
                <dd>
                    <a href="https://www.openstreetmap.org/?mlat={{ $lat }}&mlon={{ $lng }}#map=16/{{ $lat }}/{{ $lng }}" target="_blank" rel="noopener">Open on map</a>
                </dd>
            @endif

            <dt>Order total</dt>
            <dd>{{ number_format((float) $order->price, 0) }} {{ $order->currencyid }}</dd>
        </dl>
    </div>

    <div class="card">
        <h2>Items</h2>
        @if ($items->isEmpty())
            <p class="muted">No item details found for this order.</p>
        @else
            <ul class="items">
                @foreach ($items as $item)
                    <li>
                        <span>{{ $item->name }} <span class="muted">× {{ rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.') }}</span></span>
                        <span class="muted">{{ number_format((float) $item->price, 0) }} {{ $item->currencyid }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="card">
        <h2>Finish this delivery</h2>

        @if ($assignment->status === \App\Models\DeliveryAssignment::STATUS_ASSIGNED)
            <form method="POST" action="{{ route('delivery.orders.start', $assignment) }}" style="margin-block-end:0.6rem;">
                @csrf
                <button class="btn block" type="submit">Start delivery</button>
            </form>
        @endif

        <form method="POST" action="{{ route('delivery.orders.deliver', $assignment) }}" style="margin-block-end:0.6rem;">
            @csrf
            <button class="btn primary block" type="submit">Delivered</button>
        </form>

        <details>
            <summary>Couldn’t deliver</summary>
            <form method="POST" action="{{ route('delivery.orders.fail', $assignment) }}">
                @csrf
                <div class="field">
                    <label class="label" for="reason">What happened?</label>
                    <textarea id="reason" name="reason">{{ old('reason') }}</textarea>
                    @error('reason')<div class="err">{{ $message }}</div>@enderror
                </div>
                <button class="btn danger block" type="submit">Mark as not delivered</button>
            </form>
        </details>
    </div>
@endsection
