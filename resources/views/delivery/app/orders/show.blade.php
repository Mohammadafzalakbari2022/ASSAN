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

@push('head')
    <link rel="stylesheet" href="{{ asset('leaflet/leaflet.css') }}">
    <style>
        #delivery-map { height:50vh; min-height:16rem; border-radius:0.8rem; border:1px solid var(--line); margin-block-end:1rem; }
        .leaflet-popup-content { font:inherit; }
    </style>
@endpush

@section('content')
    <h1>
        Order #{{ $order->invoiceno !== '' ? $order->invoiceno : $order->id }}
        @if ($assignment->status === \App\Models\DeliveryAssignment::STATUS_STARTED)
            <span class="pill started">On the way</span>
        @else
            <span class="pill assigned">Assigned</span>
        @endif
    </h1>

    <div id="delivery-map"></div>

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

@push('scripts')
    <script src="{{ asset('leaflet/leaflet.js') }}"></script>
    <script>
        (function () {
            var mapEl = document.getElementById('delivery-map');
            if (!mapEl || typeof L === 'undefined') { return; }

            var destLat = @js($lat);
            var destLng = @js($lng);
            var hasDest = {{ $hasMap ? 'true' : 'false' }};

            var map = L.map(mapEl).setView([34.52, 69.17], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19
            }).addTo(map);

            var blue = L.divIcon({
                className: '',
                html: '<div style="width:14px;height:14px;background:#3b82f6;border:2px solid #fff;border-radius:50%;box-shadow:0 0 0 2px rgba(59,130,246,0.35);"></div>',
                iconSize: [14, 14],
                iconAnchor: [7, 7]
            });

            var red = L.divIcon({
                className: '',
                html: '<div style="width:14px;height:14px;background:#dc2626;border:2px solid #fff;border-radius:50%;box-shadow:0 0 0 2px rgba(220,38,38,0.35);"></div>',
                iconSize: [14, 14],
                iconAnchor: [7, 7]
            });

            var userMarker = null;
            var destMarker = null;
            var fitted = false;

            if (hasDest) {
                destMarker = L.marker([destLat, destLng], { icon: red }).addTo(map)
                    .bindPopup('Delivery destination');
            }

            function fitAll() {
                var pts = [];
                if (userMarker) { pts.push(userMarker.getLatLng()); }
                if (destMarker) { pts.push(destMarker.getLatLng()); }
                if (pts.length === 2) {
                    map.fitBounds(L.latLngBounds(pts), { padding: [50, 50], maxZoom: 16 });
                } else if (pts.length === 1) {
                    map.setView(pts[0], 16);
                }
                fitted = true;
            }

            if (!hasDest) {
                destMarker = L.marker([0, 0], { opacity: 0 }).addTo(map);
            }

            if ('geolocation' in navigator) {
                navigator.geolocation.watchPosition(
                    function (pos) {
                        var ll = [pos.coords.latitude, pos.coords.longitude];
                        if (!userMarker) {
                            userMarker = L.marker(ll, { icon: blue }).addTo(map)
                                .bindPopup('You are here');
                        } else {
                            userMarker.setLatLng(ll);
                        }
                        if (!fitted) { fitAll(); }
                    },
                    function () {},
                    { enableHighAccuracy: true, maximumAge: 5000 }
                );
            }

            if (hasDest) { fitAll(); }
        })();
    </script>
@endpush
