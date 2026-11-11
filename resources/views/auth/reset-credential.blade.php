@extends('layouts.app')

@section('content')
<div class="row justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="col-md-6">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-check text-success" style="font-size: 3rem;"></i>
                    <h3 class="mt-3 fw-bold">Ganti {{ $otpRequest->purpose === 'password' ? 'Password' : 'PIN' }}</h3>
                    <p class="text-muted">Kode OTP valid. Silakan masukkan {{ $otpRequest->purpose === 'password' ? 'password' : 'PIN' }} baru.</p>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('recovery.credential.reset') }}" method="POST">
                    @csrf
                    <input type="hidden" name="reset_id" value="{{ $otpRequest->id }}">

                    @if($otpRequest->purpose === 'password')
                        <div class="mb-3">
                            <label for="password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   minlength="12" required autocomplete="new-password" autofocus>
                            <div class="form-text">Password minimal 12 karakter, kombinasi huruf kapital, huruf kecil, angka, dan karakter spesial.</div>
                        </div>
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="password_confirmation"
                                   name="password_confirmation" minlength="12" required autocomplete="new-password">
                        </div>
                    @else
                        <div class="mb-3">
                            <label for="pin" class="form-label">PIN Baru</label>
                            <input type="password" class="form-control" id="pin" name="pin"
                                   inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="new-password" autofocus>
                            <div class="form-text">PIN harus terdiri dari 6 angka.</div>
                        </div>
                        <div class="mb-4">
                            <label for="pin_confirmation" class="form-label">Konfirmasi PIN Baru</label>
                            <input type="password" class="form-control" id="pin_confirmation" name="pin_confirmation"
                                   inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="new-password">
                        </div>
                    @endif

                    <button type="submit" class="btn btn-success w-100 py-2">Simpan {{ $otpRequest->purpose === 'password' ? 'Password' : 'PIN' }} Baru</button>
                </form>

                <div class="text-center mt-3">
                    <a href="{{ route('password.request') }}" class="text-decoration-none">Batalkan dan kembali</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
