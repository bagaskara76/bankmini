@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7 col-md-9">
        <div class="mb-4">
            <h4 class="fw-bold mb-1">Ganti Password Teller</h4>
            <p class="text-muted mb-0">Perbarui kata sandi akun teller Anda demi menjaga keamanan operasional kas.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="fw-semibold mb-1">Gagal memperbarui password:</div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('teller.update_password') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold">Password Saat Ini</label>
                        <input type="password" 
                               class="form-control @error('current_password') is-invalid @enderror" 
                               id="current_password" 
                               name="current_password" 
                               required 
                               autocomplete="current-password"
                               placeholder="Masukkan password lama Anda">
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password Baru</label>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password" 
                               minlength="12"
                               required 
                               autocomplete="new-password"
                               placeholder="Masukkan minimal 12 karakter kombinasi">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-semibold">Konfirmasi Password Baru</label>
                        <input type="password" 
                               class="form-control" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               minlength="12"
                               required 
                               autocomplete="new-password"
                               placeholder="Ulangi password baru Anda">
                    </div>

                    <div class="card bg-light border-0 p-3 mb-4">
                        <div class="fw-semibold small text-secondary mb-1">Ketentuan Keamanan Password:</div>
                        <ul class="small text-muted mb-0 ps-3">
                            <li>Minimal <strong>12 karakter</strong></li>
                            <li>Mengandung kombinasi <strong>huruf kapital</strong> (A-Z) dan <strong>huruf kecil</strong> (a-z)</li>
                            <li>Mengandung minimal <strong>1 angka</strong> (0-9)</li>
                            <li>Mengandung minimal <strong>1 karakter khusus / simbol</strong> (@, #, $, %, !, dll.)</li>
                            <li>Tidak boleh sama dengan password sebelumnya</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('teller.dashboard') }}" class="btn btn-outline-secondary px-3">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">
                            Simpan Password Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

