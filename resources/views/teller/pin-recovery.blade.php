@extends('layouts.app')

@section('content')
<div class="row justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="col-md-6">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
                    <h3 class="mt-3 fw-bold">Reset PIN Nasabah</h3>
                    <p class="text-muted mb-1">{{ $user->name }} ({{ $user->username }})</p>
                    @if($user->email)
                        <p class="text-muted">OTP dikirim ke {{ $user->email }}</p>
                    @else
                        <p class="text-danger">Email nasabah belum terdaftar. Hubungi admin untuk menambah email terlebih dahulu.</p>
                    @endif
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

                @if(!$otpRequest)
                    <form action="{{ route('teller.pin_recovery.otp', $customer->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 py-2" {{ $user->email ? '' : 'disabled' }}>
                            <i class="bi bi-envelope"></i> Kirim OTP ke Email Nasabah
                        </button>
                    </form>
                @else
                    <form action="{{ route('recovery.otp.verify') }}" method="POST">
                        @csrf
                        <input type="hidden" name="reset_id" value="{{ $otpRequest->id }}">
                        <div class="mb-3">
                            <label for="otp" class="form-label">Kode OTP 6 digit</label>
                            <input type="text" class="form-control" id="otp" name="otp"
                                   inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                                   autocomplete="one-time-code" required autofocus>
                            <div class="form-text">Kode berlaku 10 menit dan maksimal 5 kali percobaan.</div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2">Verifikasi OTP</button>
                    </form>
                    <form action="{{ route('teller.pin_recovery.otp', $customer->id) }}" method="POST" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100">Kirim OTP Baru</button>
                    </form>
                @endif

                <div class="text-center mt-3">
                    <a href="{{ route('teller.transaction', $customer->id) }}" class="text-decoration-none">Kembali ke Transaksi Teller</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
