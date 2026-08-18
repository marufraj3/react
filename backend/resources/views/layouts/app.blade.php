<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Shop Genie'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/storefront.css') }}?v=4" />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand fw-bold" href="{{ url('/') }}">
                    <i class="fa-solid fa-store me-2" style="color:var(--c-primary)"></i>{{ config('app.name', 'Shop Genie') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto align-items-center gap-2">
                        @guest
                            <li class="nav-item"><a class="nav-link fw-semibold" href="{{ route('login') }}">Login</a></li>
                            <li class="nav-item"><a class="nav-link fw-semibold" href="{{ route('register') }}">Register</a></li>
                        @else
                            <li class="nav-item"><a class="nav-link fw-semibold" href="{{ route('users.index') }}">Users</a></li>
                            <li class="nav-item"><a class="nav-link fw-semibold" href="{{ route('roles.index') }}">Roles</a></li>
                            <li class="nav-item"><a class="nav-link fw-semibold" href="{{ route('products.index') }}">Products</a></li>
                            <li class="nav-item">
                                <a class="nav-link fw-semibold" href="{{ route('logout') }}"
                                   onclick="event.preventDefault();document.getElementById('logout-form').submit();">Logout</a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                            </li>
                            <li class="nav-item ms-2">
                                <span class="sf-badge sf-badge--navy"><i class="fa-regular fa-user"></i> {{ Auth::user()->name }}</span>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-5" style="background:var(--c-bg);min-height:calc(100vh - 56px)">
            <div class="container">
                @yield('content')
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
