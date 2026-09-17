@extends('delivery.admin.layout')

@section('title', 'Orders')

@php
    use App\Support\ShopOrders;
    use Illuminate\Support\Carbon;
@endphp

@section('content')
    <div class="toolbar">
        <h1>Orders waiting for delivery</h1>
        <a class="btn" href="{{ route('delivery.admin.staff.index') }}">Manage staff</a>
    </div>

    @if ($staff->isEmpty())
        <div class="alert bad">
            There are no active delivery people yet. Add one under <a href="{{ route('delivery.admin.staff.create') }}">Staff</a> before assigning orders.
        </div>
    @endif

    <div class="card">
        @if ($orders->isEmpty())
            <p class="muted">There are no orders waiting for delivery right now.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Placed</th>
                        <th>Customer</th>
                        <th>Address</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Delivery person</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        @php
                            $address = $order->address;
                            $name = trim(($address->firstname ?? '') . ' ' . ($address->lastname ?? ''));
                            if ($name === '') {
                                $name = $address->company ?? '';
                            }
                            $phone = ($address->mobile ?? '') ?: ($address->telephone ?? '');
                            $placed = $order->ctime ? Carbon::parse($order->ctime)->format('Y-m-d H:i') : '—';
                        @endphp
                        <tr>
                            <td>#{{ $order->invoiceno !== '' ? $order->invoiceno : $order->id }}</td>
                            <td class="muted">{{ $placed }}</td>
                            <td>
                                {{ $name !== '' ? $name : 'Customer' }}
                                @if ($phone !== '')
                                    <div class="muted">{{ $phone }}</div>
                                @endif
                            </td>
                            <td class="muted">
                                {{ $address->address1 ?? '—' }}@if (!empty($address->city)), {{ $address->city }}@endif
                            </td>
                            <td>{{ number_format((float) $order->price, 0) }} {{ $order->currencyid }}</td>
                            <td><span class="pill on">{{ ShopOrders::statusLabel((int) $order->statusdelivery) }}</span></td>
                            <td>
                                @if ($order->assignment)
                                    <div class="assigned">
                                        <strong>{{ $order->assignment->deliveryUser->name ?? 'Unknown' }}</strong>
                                        @if ($order->assignment->status === \App\Models\DeliveryAssignment::STATUS_STARTED)
                                            <span class="muted">(on the way)</span>
                                        @endif
                                    </div>
                                    <div class="actions">
                                        @if (!$staff->isEmpty())
                                            <form method="POST" action="{{ route('delivery.admin.orders.assign', $order->id) }}">
                                                @csrf
                                                <select name="delivery_user_id" aria-label="Change delivery person">
                                                    @foreach ($staff as $person)
                                                        <option value="{{ $person->id }}" @selected($person->id === $order->assignment->delivery_user_id)>{{ $person->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="btn" type="submit">Change</button>
                                            </form>
                                        @endif
                                        @if ($order->assignment->status === \App\Models\DeliveryAssignment::STATUS_ASSIGNED)
                                            <form method="POST" action="{{ route('delivery.admin.orders.unassign', $order->assignment) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn danger" type="submit">Unassign</button>
                                            </form>
                                        @endif
                                    </div>
                                @else
                                    @if ($staff->isEmpty())
                                        <span class="muted">No active staff</span>
                                    @else
                                        <form method="POST" action="{{ route('delivery.admin.orders.assign', $order->id) }}">
                                            @csrf
                                            <select name="delivery_user_id" aria-label="Delivery person">
                                                <option value="">Choose…</option>
                                                @foreach ($staff as $person)
                                                    <option value="{{ $person->id }}">{{ $person->name }}@if ($person->phone) — {{ $person->phone }}@endif</option>
                                                @endforeach
                                            </select>
                                            <button class="btn primary" type="submit">Assign</button>
                                        </form>
                                    @endif
                                @endif
                                @error('delivery_user_id')<div class="err">{{ $message }}</div>@enderror
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
