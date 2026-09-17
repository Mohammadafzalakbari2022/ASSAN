@extends('delivery.admin.layout')

@php($editing = $staff->exists)

@section('title', $editing ? 'Edit delivery person' : 'Add delivery person')

@section('content')
    <h1>{{ $editing ? 'Edit delivery person' : 'Add delivery person' }}</h1>

    <div class="card">
        <form method="POST" action="{{ $editing ? route('delivery.admin.staff.update', $staff) : route('delivery.admin.staff.store') }}">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="field">
                <label for="name">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $staff->name) }}" required autofocus>
                @error('name')<div class="err">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="phone">Phone number</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone', $staff->phone) }}" required>
                @error('phone')<div class="err">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="email">Email (used to sign in)</label>
                <input id="email" type="email" name="email" value="{{ old('email', $staff->email) }}" required>
                @error('email')<div class="err">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" {{ $editing ? '' : 'required' }}>
                @if ($editing)
                    <div class="hint">Leave empty to keep the current password.</div>
                @else
                    <div class="hint">At least 8 characters.</div>
                @endif
                @error('password')<div class="err">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" {{ $editing ? '' : 'required' }}>
            </div>

            <div class="actions">
                <button class="btn primary" type="submit">{{ $editing ? 'Save changes' : 'Create account' }}</button>
                <a class="btn" href="{{ route('delivery.admin.staff.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
