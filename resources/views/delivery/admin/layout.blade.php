<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'az', 'dv', 'fa', 'he', 'ku', 'ps', 'ur'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Delivery') — ASAAN</title>
    <style>
        :root { --ink:#1f2933; --muted:#6b7280; --line:#e5e7eb; --brand:#1c5b3a; --brand-soft:#e8f2ec; --danger:#b42318; }
        * { box-sizing: border-box; }
        body { margin:0; font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color:var(--ink); background:#f5f7f6; }
        a { color:var(--brand); }
        .wrap { max-width: 60rem; margin-inline:auto; padding: 1.5rem 1rem 4rem; }
        header.top { background:#fff; border-block-end:1px solid var(--line); }
        .top-inner { max-width:60rem; margin-inline:auto; padding:0.9rem 1rem; display:flex; gap:1rem; align-items:center; flex-wrap:wrap; }
        .brand { font-weight:700; letter-spacing:0.02em; }
        nav.menu { display:flex; gap:0.75rem; flex-wrap:wrap; }
        nav.menu a { text-decoration:none; padding:0.35rem 0.7rem; border-radius:0.5rem; color:var(--muted); }
        nav.menu a.active, nav.menu a:hover { background:var(--brand-soft); color:var(--brand); }
        h1 { font-size:1.4rem; margin:0 0 1rem; }
        .card { background:#fff; border:1px solid var(--line); border-radius:0.75rem; padding:1rem; }
        .alert { padding:0.75rem 1rem; border-radius:0.5rem; margin-block-end:1rem; }
        .alert.ok { background:var(--brand-soft); color:var(--brand); }
        .alert.bad { background:#fdecea; color:var(--danger); }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:start; padding:0.6rem 0.5rem; border-block-end:1px solid var(--line); vertical-align:middle; }
        th { color:var(--muted); font-weight:600; font-size:0.85rem; }
        .muted { color:var(--muted); }
        .pill { display:inline-block; padding:0.15rem 0.55rem; border-radius:999px; font-size:0.8rem; }
        .pill.on { background:var(--brand-soft); color:var(--brand); }
        .pill.off { background:#f1f1f1; color:var(--muted); }
        .btn { display:inline-block; border:1px solid var(--line); background:#fff; color:var(--ink); padding:0.45rem 0.85rem; border-radius:0.5rem; text-decoration:none; cursor:pointer; font-size:0.9rem; }
        .btn.primary { background:var(--brand); border-color:var(--brand); color:#fff; }
        .btn.danger { color:var(--danger); border-color:#f3c9c4; }
        .btn.link { border:none; background:none; padding:0.2rem; }
        .actions { display:flex; gap:0.4rem; flex-wrap:wrap; align-items:center; }
        .toolbar { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-block-end:1rem; flex-wrap:wrap; }
        .field { margin-block-end:1rem; }
        label { display:block; font-weight:600; margin-block-end:0.3rem; }
        input[type=text], input[type=email], input[type=password] { width:100%; max-width:26rem; padding:0.55rem 0.7rem; border:1px solid var(--line); border-radius:0.5rem; font-size:1rem; }
        select { padding:0.45rem 0.6rem; border:1px solid var(--line); border-radius:0.5rem; font-size:0.9rem; background:#fff; max-width:16rem; }
        td form { display:inline-flex; gap:0.4rem; align-items:center; }
        .assigned { margin-block-end:0.35rem; }
        .err { color:var(--danger); font-size:0.85rem; margin-block-start:0.3rem; }
        .hint { color:var(--muted); font-size:0.85rem; margin-block-start:0.3rem; }
        :focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
        .history-nav { display:none; }
        html.os-desktop .history-nav { display:inline-flex; gap:0.4rem; }
    </style>
    @include('delivery.partials.os')
    @stack('head')
</head>
<body @if ($errors->any()) data-unsaved="1" @endif>
    <header class="top">
        <div class="top-inner">
            <span class="brand">ASAAN Delivery</span>
            @include('delivery.partials.history')
            <nav class="menu">
                <a href="{{ route('delivery.admin.orders.index') }}" class="{{ request()->routeIs('delivery.admin.orders.*') ? 'active' : '' }}">Orders</a>
                <a href="{{ route('delivery.admin.map') }}" class="{{ request()->routeIs('delivery.admin.map') ? 'active' : '' }}">Map</a>
                <a href="{{ route('delivery.admin.staff.index') }}" class="{{ request()->routeIs('delivery.admin.staff.*') ? 'active' : '' }}">Staff</a>
            </nav>
        </div>
    </header>

    <div class="wrap">
        @if (session('status'))
            <div class="alert ok">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert bad">{{ session('error') }}</div>
        @endif

        @yield('content')
    </div>

    @include('delivery.partials.unsaved-guard')
    @stack('scripts')
</body>
</html>
