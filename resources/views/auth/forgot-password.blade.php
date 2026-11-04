@extends('layouts.app')

@section('content')
<div class="row justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="col-md-6">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
                    <h3 class="mt-3 fw-bold">Lupa Password</h3>
                    <p class="text-muted">Kami akan mengirim kode OTP 6 digit ke email yang terdaftar pada akun nasabah.</p>
                </div>

                @if(session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('recovery.otp.request') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username"
                               value="{{ old('username', $username ?? '') }}" required autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Terdaftar</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="{{ old('email') }}" required autocomplete="email"
                               placeholder="Email yang didaftarkan ke Bank Mini">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-envelope"></i> Kirim Kode OTP
                    </button>
                </form>

                @if($otpRequest)
                    <hr class="my-4">
                    <h5 class="mb-3">Verifikasi OTP</h5>
                    <form action="{{ route('recovery.otp.verify') }}" method="POST">
                        @csrf
                        <input type="hidden" name="reset_id" value="{{ $otpRequest->id }}">
                        <div class="mb-3">
                            <label for="otp" class="form-label">Kode OTP 6 digit</label>
                            <input type="text" class="form-control" id="otp" name="otp"
                                   inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                                   autocomplete="one-time-code" required>
                            <div class="form-text">Kode hanya berlaku 10 menit dan maksimal 5 kali percobaan.</div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2">Verifikasi Kode OTP</button>
                    </form>
                @endif

                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="text-decoration-none">Kembali ke Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
