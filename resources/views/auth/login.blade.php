@extends('layouts.app')

@push('styles')
<style>
    .login-navigation {
        display: grid;
        gap: .75rem;
        margin-top: 1.5rem;
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
</style>
@endpush

@section('content')
<div class="row justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="col-md-5">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <img src="{{ asset('images/logo-sekolah.png') }}" alt="Logo SMK Negeri 1 Bantul" class="mb-3" style="width: 100px; height: 100px; object-fit: contain;">
                    <h3 class="font-weight-bold">E-Teller Bank Mini</h3>
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

                <form action="/login" method="POST">
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
        <div class="text-center mt-4 text-muted small">
            &copy; 2026 Uji Kompetensi Keahlian - Rekayasa Perangkat Lunak
        </div>
    </div>
</div>
@endsection
