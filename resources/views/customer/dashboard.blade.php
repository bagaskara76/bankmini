@extends('layouts.app')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h4 class="fw-bold">Buku Tabungan Digital</h4>
        <p class="text-muted">Selamat datang, {{ Auth::user()->name }}</p>
    </div>
</div>

{{-- Kartu Saldo + QR Code --}}
<div class="row mb-4">
    {{-- Kartu Rekening --}}
    <div class="col-md-5 mb-3">
        <div class="card text-white h-100" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); border-radius: 16px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <p class="mb-0 opacity-75 small">BANK MINI SEKOLAH</p>
                        <p class="mb-0 opacity-75 small">Rekening Tabungan</p>
                    </div>
                    <img src="{{ asset('images/logo-sekolah.png') }}" alt="Logo SMK Negeri 1 Bantul" style="width: 52px; height: 52px; object-fit: contain;">
                </div>
                <p class="mb-1 opacity-75 small">Saldo Tersedia</p>
                <h2 class="fw-bold mb-3">Rp {{ number_format($customer->balance, 0, ',', '.') }}</h2>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-0 opacity-75 small">No. Rekening</p>
                        <p class="mb-0 fw-bold fs-5">{{ $customer->account_number }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- QR Code Kartu Nasabah --}}
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-0 shadow-sm text-center">
            <div class="card-body d-flex flex-column align-items-center justify-content-center p-3">
                <p class="fw-bold mb-2 small text-muted text-uppercase">Kartu QR Nasabah</p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($customer->qr_code_string) }}&margin=10"
                     alt="QR Code Nasabah"
                     class="img-fluid rounded shadow"
                     style="max-width: 180px; border: 3px solid #1e3c72;">
                <p class="mt-2 mb-0 small text-muted">Tunjukkan ke Teller saat transaksi</p>
            </div>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="col-md-4 mb-3">
        <div class="row h-100">
            <div class="col-6 mb-3">
                <div class="card h-100 border-0 shadow-sm text-center p-3">
                    <i class="bi bi-arrow-down-circle-fill text-success fs-2 mb-2"></i>
                    <p class="small text-muted mb-1">Total Setoran</p>
                    <h6 class="fw-bold text-success">
                        Rp {{ number_format($transactions->where('type','deposit')->sum('amount'), 0, ',', '.') }}
                    </h6>
                </div>
            </div>
            <div class="col-6 mb-3">
                <div class="card h-100 border-0 shadow-sm text-center p-3">
                    <i class="bi bi-arrow-up-circle-fill text-danger fs-2 mb-2"></i>
                    <p class="small text-muted mb-1">Total Penarikan</p>
                    <h6 class="fw-bold text-danger">
                        Rp {{ number_format($transactions->where('type','withdrawal')->sum('amount'), 0, ',', '.') }}
                    </h6>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm p-3 text-center">
                    <p class="small text-muted mb-1">Saldo Minimum Mengendap</p>
                    <h6 class="fw-bold text-warning"><i class="bi bi-shield-check"></i> Rp {{ number_format(\App\Models\Customer::MINIMUM_BALANCE, 0, ',', '.') }}</h6>
                    <p class="mb-0" style="font-size: 0.75rem; color: #999;">
                        Dana dapat ditarik: <strong>Rp {{ number_format(max(0, $customer->balance - \App\Models\Customer::MINIMUM_BALANCE), 0, ',', '.') }}</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Riwayat Mutasi --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-4 pb-0">
        <h5 class="fw-bold mb-0"><i class="bi bi-clock-history"></i> Riwayat Mutasi Rekening</h5>
        <p class="text-muted small">10 transaksi terakhir</p>
    </div>
    <div class="card-body p-0">
        @if($transactions->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
            <p>Belum ada riwayat transaksi</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Tanggal & Waktu</th>
                        <th>Jenis Transaksi</th>
                        <th class="text-end pe-4">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $trx)
                    <tr>
                        <td class="ps-4">
                            <span class="fw-medium">{{ $trx->created_at->format('d M Y') }}</span><br>
                            <small class="text-muted">{{ $trx->created_at->format('H:i') }} WIB</small>
                        </td>
                        <td>
                            @if($trx->type == 'deposit')
                                <span class="badge bg-success px-3 py-2">
                                    <i class="bi bi-arrow-down-circle"></i> Setoran Tunai
                                </span>
                            @else
                                <span class="badge bg-danger px-3 py-2">
                                    <i class="bi bi-arrow-up-circle"></i> Penarikan Tunai
                                </span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            @if($trx->type == 'deposit')
                                <span class="fw-bold text-success fs-6">+ Rp {{ number_format($trx->amount, 0, ',', '.') }}</span>
                            @else
                                <span class="fw-bold text-danger fs-6">- Rp {{ number_format($trx->amount, 0, ',', '.') }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
