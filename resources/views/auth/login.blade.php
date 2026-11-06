@extends('layouts.app')

@section('body-class', 'login-body')

@push('styles')
<style>
    body.login-body {
        background:
            radial-gradient(ellipse at 12% 8%, rgba(42, 82, 152, .09), transparent 34rem),
            radial-gradient(ellipse at 88% 92%, rgba(30, 60, 114, .06), transparent 30rem),
            #f3f6fa;
    }

    body.login-body main.container {
        width: 100%;
        max-width: none;
        padding: 2rem 1rem;
    }

    .login-page {
        display: grid;
        min-height: calc(100vh - 4rem);
        place-items: center;
    }

    .login-panel {
        width: 100%;
        max-width: 520px;
    }

    .login-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(226, 232, 240, .9);
        border-radius: 18px;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 20px 55px rgba(30, 60, 114, .12);
    }

    .login-card::before {
        position: absolute;
        inset: 0 0 auto;
        height: 4px;
        background: linear-gradient(90deg, #1e3c72, #4d79bd);
        content: "";
    }

    .login-card .card-body {
        padding: 2.75rem 2.5rem;
    }

    .login-logo {
        display: inline-grid;
        width: 112px;
        height: 112px;
        place-items: center;
        border: 1px solid #e8eef8;
        border-radius: 50%;
        background: #f7f9fd;
    }

    .login-logo img {
        width: 82px;
        height: 82px;
        object-fit: contain;
    }

    .login-heading {
        margin-top: 1rem;
        color: #1f2937;
        font-weight: 700;
        letter-spacing: -.025em;
    }

    .login-form .form-label {
        margin-bottom: .45rem;
        color: #334155;
        font-size: .92rem;
        font-weight: 600;
    }

    .login-form .form-control {
        min-height: 46px;
        border-color: #dbe3ee;
        border-radius: 9px;
    }

    .login-form .form-control:focus {
        border-color: #7da2db;
        box-shadow: 0 0 0 .2rem rgba(42, 82, 152, .12);
    }

    .login-form .input-group .form-control {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .login-form .password-toggle {
        border-color: #dbe3ee;
        border-top-right-radius: 9px;
        border-bottom-right-radius: 9px;
        background: #fff;
        color: #64748b;
    }

    .login-form .btn-primary {
        min-height: 46px;
        box-shadow: 0 5px 12px rgba(42, 82, 152, .16);
    }

    .login-footer {
        color: #64748b;
    }

    .login-navigation {
        display: grid;
        gap: .75rem;
        margin-top: 1.25rem;
    }

    .login-navigation__link {
        display: flex;
        min-height: 46px;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        border-radius: 9px;
        font-weight: 600;
        text-decoration: none;
        transition: background-color .2s ease, border-color .2s ease, color .2s ease, transform .2s ease;
    }

    .login-navigation__link:hover {
        transform: translateY(-1px);
    }

    .login-navigation__link--recovery {
        border: 1px solid #c9d8f0;
        background: #f2f6fc;
        color: #234b85;
    }

    .login-navigation__link--recovery:hover {
        border-color: #2a5298;
        background: #e7eef9;
        color: #1e3c72;
    }

    .login-navigation__link--home {
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #526174;
    }

    .login-navigation__link--home:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
        color: #334155;
    }

    .password-toggle {
        display: inline-flex;
        width: 3.25rem;
        align-items: center;
        justify-content: center;
    }

    .password-toggle svg {
        display: block !important;
        width: 1.2rem;
        height: 1.2rem;
    }

    @media (max-width: 575.98px) {
        body.login-body main.container {
            padding: 1rem;
        }

        .login-page {
            min-height: calc(100vh - 2rem);
        }

        .login-card .card-body {
            padding: 2.25rem 1.5rem;
        }
    }
</style>
@endpush

@section('content')
<div class="login-page">
    <div class="login-panel">
        <div class="card login-card">
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="login-logo mb-3">
                        <img src="{{ asset('images/logo-sekolah.png') }}" alt="Logo SMK Negeri 1 Bantul">
                    </div>
                    <h3 class="login-heading">E-Teller Bank Mini</h3>
                    <p class="text-muted">Silakan login untuk mengakses sistem</p>
                </div>

                @if(session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="/login" method="POST" class="login-form">
                    @csrf
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" required autofocus placeholder="Masukkan Username">
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan Password">
                            <button class="btn btn-outline-secondary password-toggle" type="button" id="togglePassword" aria-label="Tampilkan password" title="Tampilkan password">
                                <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                    <path fill="currentColor" d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.77 5.9 3.5 8 3.5s3.88 1.27 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.2.288c-.31.42-.78.996-1.36 1.565C11.88 11.23 10.1 12.5 8 12.5s-3.88-1.27-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                    <path fill="currentColor" d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        Login
                    </button>
                </form>

                @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var toggle = document.getElementById('togglePassword');
                        var password = document.getElementById('password');

                        if (toggle && password) {
                            toggle.addEventListener('click', function () {
                                var type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                                password.setAttribute('type', type);
                                var isVisible = type === 'text';
                                this.setAttribute('aria-label', isVisible ? 'Sembunyikan password' : 'Tampilkan password');
                                this.setAttribute('title', isVisible ? 'Sembunyikan password' : 'Tampilkan password');
                                this.innerHTML = isVisible
                                    ? '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13.359 11.238C15.06 9.79 16 8 16 8s-3-5.5-8-5.5a7.3 7.3 0 0 0-3.06.666l.777.778A6.2 6.2 0 0 1 8 3.5c2.1 0 3.88 1.27 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.2.288a12 12 0 0 1-1.975 2.15z"/><path fill="currentColor" d="M11.297 9.176A3.5 3.5 0 0 0 6.824 4.703l.8.8A2.5 2.5 0 0 1 10.497 8.38zM3.35 5.464A13 13 0 0 0 1.172 8a13 13 0 0 0 1.66 2.043C4.12 11.23 5.9 12.5 8 12.5a6.7 6.7 0 0 0 2.166-.36l.81.81A7.3 7.3 0 0 1 8 13.5C3 13.5 0 8 0 8s1.107-2.03 3.05-3.612z"/><path fill="currentColor" d="m2.146 1.146.708-.708 12.708 12.708-.708.708z"/></svg>'
                                    : '<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.77 5.9 3.5 8 3.5s3.88 1.27 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.2.288c-.31.42-.78.996-1.36 1.565C11.88 11.23 10.1 12.5 8 12.5s-3.88-1.27-5.168-2.457A13 13 0 0 1 1.172 8z"/><path fill="currentColor" d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>';
                            });
                        }
                    });
                </script>
                @endpush

                <div class="login-navigation">
                    <a href="{{ route('password.request') }}" class="login-navigation__link login-navigation__link--recovery">
                        Lupa Password?
                    </a>
                    <a href="{{ route('home') }}" class="login-navigation__link login-navigation__link--home">
                        Kembali ke halaman utama
                    </a>
                </div>
            </div>
        </div>
        <div class="login-footer text-center mt-4 small">
            &copy; 2026 Uji Kompetensi Keahlian - Rekayasa Perangkat Lunak
        </div>
    </div>
</div>
@endsection
