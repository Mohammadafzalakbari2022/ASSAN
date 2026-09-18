@extends('delivery.app.layout')

@section('title', 'Sign in')

@section('content')
    @if ($signedInAs)
        <div class="alert info" style="margin-block-end:0.9rem;">
            This browser is signed in as {{ $signedInAs }}. Signing in here switches this browser to the delivery person's account.
        </div>
    @endif

    <div class="card">
        <h1>Delivery sign in</h1>

        <form method="POST" action="{{ route('delivery.login.store') }}">
            @csrf

            <div class="field">
                <label class="label" for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                @error('email')<div class="err">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label class="label" for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
                @error('password')<div class="err">{{ $message }}</div>@enderror
            </div>

            <label class="hint" style="display:flex; gap:0.5rem; align-items:center;">
                <input type="checkbox" name="remember" value="1"> Keep me signed in
            </label>

            <div style="margin-block-start:1rem;">
                <button class="btn primary block" type="submit">Sign in</button>
            </div>
        </form>
    </div>
@endsection
