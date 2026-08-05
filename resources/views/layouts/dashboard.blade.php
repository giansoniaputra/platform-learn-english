<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Sepuluh</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;1,400&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('/assets/style.css') }}?v={{ filemtime(public_path('assets/style.css')) }}">
</head>

<body style="background:var(--paper)">
    <div class="dashboard-shell">
        <div class="dashboard-sticky-header">
            <div class="dashboard-header">
                <div class="brandmark"><span></span>Sepuluh · Dashboard</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="dashboard-logout" type="submit">Keluar</button>
                </form>
            </div>

            <nav class="dashboard-nav">
                <a href="{{ route('app') }}">← Buka aplikasi</a>
            </nav>
        </div>

        @if (session('status'))
        <p class="status-banner">{{ session('status') }}</p>
        @endif

        @yield('content')
    </div>
</body>

</html>
