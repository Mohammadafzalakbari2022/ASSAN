@extends('delivery.admin.layout')

@section('title', 'Delivery staff')

@section('content')
    <div class="toolbar">
        <h1>Delivery staff</h1>
        <a class="btn primary" href="{{ route('delivery.admin.staff.create') }}">Add delivery person</a>
    </div>

    <div class="card">
        @if ($staff->isEmpty())
            <p class="muted">No delivery accounts yet. Use “Add delivery person” to create the first one.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Deliveries</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($staff as $person)
                        <tr>
                            <td>{{ $person->name }}</td>
                            <td>{{ $person->phone }}</td>
                            <td class="muted">{{ $person->email }}</td>
                            <td>
                                @if ($person->active)
                                    <span class="pill on">Active</span>
                                @else
                                    <span class="pill off">Disabled</span>
                                @endif
                            </td>
                            <td class="muted">
                                @php($c = $counts[$person->id] ?? collect())
                                {{ (int) ($c['assigned'] ?? 0) }} waiting,
                                {{ (int) ($c['started'] ?? 0) }} running,
                                {{ (int) ($c['delivered'] ?? 0) }} delivered
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="btn" href="{{ route('delivery.admin.staff.edit', $person) }}">Edit</a>
                                    <form method="POST" action="{{ route('delivery.admin.staff.toggle', $person) }}">
                                        @csrf
                                        <button class="btn" type="submit">{{ $person->active ? 'Disable' : 'Enable' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('delivery.admin.staff.destroy', $person) }}" onsubmit="return confirm('Remove this delivery account?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn danger" type="submit">Remove</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
