<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kigali Route Manager')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --primary: #0d6e4f;
            --primary-dark: #095a40;
            --accent: #f4a024;
            --bg: #f5f7f6;
            --card: #ffffff;
            --text: #1a2e28;
            --muted: #5c6f68;
            --border: #dde5e1;
            --success: #198754;
            --danger: #dc3545;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }
        .navbar {
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,.12);
        }
        .navbar a { color: white; text-decoration: none; font-weight: 500; }
        .navbar .brand { font-size: 1.25rem; font-weight: 700; }
        .nav-links { display: flex; gap: 1.5rem; align-items: center; }
        .nav-user { font-size: 0.85rem; opacity: 0.9; }
        .container { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; }
        .alert {
            padding: .875rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .alert-success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-error { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .card {
            background: var(--card);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            margin-bottom: 1.5rem;
        }
        .card h2, .card h3 { margin-bottom: .75rem; }
        .btn {
            display: inline-block;
            padding: .6rem 1.25rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: .9rem;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-accent { background: var(--accent); color: var(--text); }
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        .btn-sm { padding: .4rem .85rem; font-size: .85rem; }
        .grid { display: grid; gap: 1.5rem; }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
        .badge {
            display: inline-block;
            padding: .2rem .6rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 600;
        }
        .badge-active { background: #d1e7dd; color: #0f5132; }
        .badge-idle { background: #e9ecef; color: #495057; }
        .eta-box {
            background: linear-gradient(135deg, #e8f5f0, #f0faf6);
            border: 1px solid #b8dfd0;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-top: .75rem;
        }
        .eta-box .value { font-size: 1.75rem; font-weight: 700; color: var(--primary); }
        .stop-list { list-style: none; }
        .stop-list li {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: .75rem 0;
            border-bottom: 1px solid var(--border);
        }
        .stop-list li:last-child { border-bottom: none; }
        .stop-number {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .85rem;
            flex-shrink: 0;
        }
        .stop-number.passed { background: #adb5bd; }
        .stop-number.current { background: var(--accent); color: var(--text); }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: .35rem; font-size: .9rem; }
        .form-group input, .form-group select {
            width: 100%;
            padding: .6rem .75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: .95rem;
        }
        .hero {
            background: linear-gradient(135deg, var(--primary), #12875f);
            color: white;
            padding: 3rem 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
        }
        .hero h1 { font-size: 2rem; margin-bottom: .5rem; }
        .hero p { opacity: .9; font-size: 1.1rem; }
        .muted { color: var(--muted); font-size: .9rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .75rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { font-weight: 600; color: var(--muted); font-size: .85rem; text-transform: uppercase; }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="navbar">
        <a href="{{ route('home') }}" class="brand">Kigali Route Manager</a>
        <div class="nav-links">
            <a href="{{ route('home') }}">Home</a>
            @auth
                @if(in_array(auth()->user()->role, ['driver', 'admin']))
                    <a href="{{ route('drivers.index') }}">Drivers</a>
                @endif
                @if(in_array(auth()->user()->role, ['passenger', 'admin']))
                    <a href="{{ route('passengers.index') }}">Passengers</a>
                @endif
                <span class="nav-user">{{ auth()->user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-sm" style="background:rgba(255,255,255,.2);color:white;">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}">Login</a>
            @endauth
        </div>
    </nav>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
