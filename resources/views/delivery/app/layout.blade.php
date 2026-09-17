@php($rtl = in_array(app()->getLocale(), ['ar', 'az', 'dv', 'fa', 'he', 'ku', 'ps', 'ur'], true))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Delivery') — ASAAN</title>
    <style>
        :root { --ink:#1f2933; --muted:#6b7280; --line:#e5e7eb; --brand:#1c5b3a; --brand-soft:#e8f2ec; --danger:#b42318; }
        * { box-sizing:border-box; }
        html, body { margin:0; }
        body { font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color:var(--ink); background:#f5f7f6; -webkit-text-size-adjust:100%; }
        a { color:var(--brand); }
        header.top { background:#fff; border-block-end:1px solid var(--line); position:sticky; top:0; z-index:5; }
        .top-inner { max-width:40rem; margin-inline:auto; padding-block:0.7rem; padding-inline:1rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; }
        .brand { font-weight:700; letter-spacing:0.02em; }
        .top nav { display:flex; align-items:center; gap:0.9rem; }
        .top form { margin:0; }
        .wrap { max-width:40rem; margin-inline:auto; padding-block:1rem 3rem; padding-inline:1rem; }
        .alert { padding:0.75rem 1rem; border-radius:0.6rem; margin-block-end:1rem; }
        .alert.ok { background:var(--brand-soft); color:var(--brand); }
        .alert.bad { background:#fdecea; color:var(--danger); }
        h1 { font-size:1.25rem; margin:0 0 1rem; }
        .card { background:#fff; border:1px solid var(--line); border-radius:0.8rem; padding:1rem; margin-block-end:0.9rem; }
        .card h2 { margin:0 0 0.35rem; font-size:1.05rem; }
        .muted { color:var(--muted); }
        .pill { display:inline-block; padding:0.15rem 0.55rem; border-radius:999px; font-size:0.75rem; background:#f1f1f1; color:var(--muted); }
        .pill.assigned { background:#fef3c7; color:#92600a; }
        .pill.started { background:var(--brand-soft); color:var(--brand); }
        .btn { display:inline-flex; align-items:center; justify-content:center; min-height:2.75rem; border:1px solid var(--line); background:#fff; color:var(--ink); padding:0.6rem 1rem; border-radius:0.6rem; text-decoration:none; cursor:pointer; font:inherit; font-size:1rem; }
        .btn.primary { background:var(--brand); border-color:var(--brand); color:#fff; }
        .btn.danger { color:var(--danger); border-color:#f3c9c4; }
        .btn.block { width:100%; }
        .linkbtn { border:none; background:none; color:var(--brand); padding:0; cursor:pointer; font:inherit; }
        .label { font-weight:600; display:block; margin-block-end:0.3rem; }
        input[type=email], input[type=password], input[type=text], textarea { width:100%; padding:0.65rem 0.75rem; border:1px solid var(--line); border-radius:0.6rem; font:inherit; font-size:1rem; background:#fff; }
        textarea { min-height:5rem; resize:vertical; }
        .field { margin-block-end:1rem; }
        .err { color:var(--danger); font-size:0.85rem; margin-block-start:0.3rem; }
        .hint { color:var(--muted); font-size:0.85rem; margin-block-start:0.3rem; }
        .row { display:flex; gap:0.6rem; flex-wrap:wrap; }
        .row > * { flex:1 1 8rem; }
        dl { margin:0; }
        dt { color:var(--muted); font-size:0.8rem; }
        dd { margin:0 0 0.6rem; }
        ul.items { list-style:none; margin:0; padding:0; }
        ul.items li { display:flex; justify-content:space-between; gap:0.75rem; padding-block:0.4rem; border-block-end:1px solid var(--line); }
        details summary { cursor:pointer; font-weight:600; margin-block-end:0.6rem; }
        :focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
        .tracker { position:fixed; inset-block-end:0; inset-inline:0; background:#fff; border-block-start:1px solid var(--line); z-index:6; }
        .tracker-inner { max-width:40rem; margin-inline:auto; padding:0.6rem 1rem calc(0.6rem + env(safe-area-inset-bottom)); display:flex; align-items:center; justify-content:space-between; gap:0.75rem; }
        .tracker-text { font-size:0.85rem; color:var(--muted); }
        .tracker .btn { min-height:2.25rem; font-size:0.9rem; }
        body.has-tracker { padding-block-end:4.5rem; }
    </style>
    @stack('head')
</head>
<body>
    <header class="top">
        <div class="top-inner">
            <span class="brand">ASAAN Delivery</span>
            @auth
                @if (auth()->user()->isDelivery())
                    <nav>
                        <a href="{{ route('delivery.orders.index') }}">My deliveries</a>
                        <form method="POST" action="{{ route('delivery.logout') }}">
                            @csrf
                            <button type="submit" class="linkbtn">Sign out</button>
                        </form>
                    </nav>
                @endif
            @endauth
        </div>
    </header>

    <main class="wrap">
        @if (session('status'))
            <div class="alert ok">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert bad">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

    @auth
        @if (auth()->user()->isDelivery())
            @include('delivery.app.partials.tracker')
        @endif
    @endauth

    @stack('scripts')
</body>
</html>
