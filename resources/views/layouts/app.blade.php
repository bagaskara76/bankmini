<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Teller Bank Mini Sekolah</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sekolah.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6;
            color: #333;
        }
        .navbar {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 700;
            color: #fff !important;
            letter-spacing: 0.5px;
        }
        .navbar-brand__logo {
            width: 36px;
            height: 36px;
            object-fit: contain;
        }
        .bi,
        svg {
            display: none !important;
        }
        .nav-link {
            color: rgba(255,255,255,0.85) !important;
            font-weight: 500;
        }
        .nav-link:hover {
            color: #fff !important;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s ease-in-out;
        }
        .card:hover {
            transform: translateY(-2px);
        }
        .btn-primary {
            background-color: #2a5298;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #1e3c72;
            box-shadow: 0 4px 10px rgba(42, 82, 152, 0.4);
        }
        .admin-layout {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            max-width: 1440px;
            min-height: calc(100vh - 76px);
            padding-top: 1.5rem;
            padding-bottom: 2rem;
        }
        .admin-sidebar {
            flex: 0 0 250px;
            position: sticky;
            top: 1rem;
            max-height: calc(100vh - 2rem);
            overflow-y: auto;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .admin-sidebar__title {
            margin: 0 0 .75rem;
            color: #64748b;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .admin-sidebar .nav-link {
            display: block;
            padding: .6rem .75rem;
            border-radius: 8px;
            color: #334155 !important;
            font-size: .9rem;
            font-weight: 500;
            text-decoration: none;
        }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link:focus {
            background: #eff6ff;
            color: #1e3c72 !important;
        }
        .admin-sidebar .nav-link.active {
            background: #2a5298;
            color: #fff !important;
            font-weight: 600;
        }
        .admin-sidebar__group + .admin-sidebar__group {
            margin-top: 1.25rem;
        }
        .admin-content {
            flex: 1 1 auto;
            min-width: 0;
        }
        .admin-sidebar-toggle {
            width: 100%;
            margin-bottom: .75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            color: #1e3c72;
            padding: .65rem .75rem;
            text-align: left;
            font-weight: 600;
        }
        @media (max-width: 991.98px) {
            .admin-layout {
                display: block;
                padding-top: 1rem;
            }
            .admin-sidebar {
                position: static;
                max-height: none;
                margin-bottom: 1rem;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="@yield('body-class')">
    @auth
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand d-inline-flex align-items-center gap-2" href="#">
                <img class="navbar-brand__logo" src="{{ asset('images/logo-sekolah.png') }}" alt="Logo SMK Negeri 1 Bantul">
                <span>E-Teller Bank Mini</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-toggle="target" aria-controls="navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">{{ Auth::user()->name }} ({{ ucfirst(Auth::user()->role) }})</span>
                    </li>
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-link nav-link">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    @endauth

    @if(request()->routeIs('admin.*') && !request()->routeIs('admin.dashboard'))
        <main class="container-fluid admin-layout">
            <aside class="admin-sidebar" aria-label="Menu administrator">
                <button class="admin-sidebar-toggle d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebarNavigation" aria-expanded="false" aria-controls="adminSidebarNavigation">
                    Menu Administrator
                </button>
                <div class="collapse d-lg-block" id="adminSidebarNavigation">
                    @include('admin.partials.sidebar')
                </div>
            </aside>
            <section class="admin-content">
                @yield('content')
            </section>
        </main>
    @else
        <main class="container">
            @yield('content')
        </main>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    @stack('scripts')
</body>
</html>
