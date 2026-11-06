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
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Tampilkan password">Tampilkan</button>
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
                                this.textContent = type === 'password' ? 'Tampilkan' : 'Sembunyikan';
                                this.setAttribute('aria-label', type === 'password' ? 'Tampilkan password' : 'Sembunyikan password');
                            });
                        }
                    });
                </script>
                @endpush

                <div class="login-navigation">
                    <a href="{{ route('password.request') }}" class="login-navigation__link login-navigation__link--recovery">
                        Lupa Password? <span aria-hidden="true">&rarr;</span>
                    </a>
                    <a href="{{ route('home') }}" class="login-navigation__link login-navigation__link--home">
                        <span aria-hidden="true">&larr;</span> Kembali ke halaman utama
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
