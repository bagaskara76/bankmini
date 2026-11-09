@extends('layouts.app')

@section('content')
@push('styles')
<style>
    .transaction-customer-card {
        background: linear-gradient(145deg, #1e3c72, #2a5298);
        box-shadow: 0 10px 24px rgba(30, 60, 114, .16);
    }

    .transaction-customer-card .text-light {
        color: rgba(255, 255, 255, .76) !important;
    }

    .transaction-form-card {
        overflow: hidden;
        border: 1px solid #e1e8f2;
        border-top: 4px solid #2a5298;
        background: #fff;
    }

    .transaction-form-card .card-header {
        padding: 14px 18px;
        border: 0;
        background: #e9eff9;
        color: #1e3c72;
    }

    .transaction-form-card--withdrawal {
        border-top-color: #5879ad;
    }

    .transaction-form-card--withdrawal .card-header {
        background: #eef3fb;
    }

    .transaction-action-button {
        border: 0;
        background: #2a5298;
        color: #fff;
    }

    .transaction-action-button:hover,
    .transaction-action-button:focus-visible {
        background: #1e3c72;
        color: #fff;
    }

    .transaction-withdrawal-button {
        background: #5879ad;
    }

    .transaction-withdrawal-button:hover,
    .transaction-withdrawal-button:focus-visible {
        background: #3e5f91;
    }
</style>
@endpush

<div class="row">
    <div class="col-md-12 mb-4">
        <a href="{{ route('teller.dashboard') }}" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
        <h4 class="fw-bold">Proses Transaksi</h4>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(isset($receiptTransaction) && $receiptTransaction)
    @include('teller.receipt', ['transaction' => $receiptTransaction])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var receiptTemplate = document.getElementById('transactionReceiptTemplate');

            if (!receiptTemplate) {
                return;
            }

            Swal.fire({
                title: 'Transaksi Berhasil',
                html: receiptTemplate.innerHTML,
                width: '42rem',
                showCancelButton: true,
                confirmButtonText: 'Lanjut Transaksi',
                cancelButtonText: 'Selesai',
                showCloseButton: true,
                allowOutsideClick: false,
                didOpen: function () {
                    var printButton = document.getElementById('receiptModalPrintButton');
                    if (printButton) {
                        printButton.addEventListener('click', function () {
                            window.print();
                        });
                    }
                }
            }).then(function (result) {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    window.location.href = @json(route('teller.dashboard'));
                }
            });
        });
    </script>
@endif

<div class="row">
    <!-- Informasi Nasabah -->
    <div class="col-md-4 mb-4">
        <div class="card transaction-customer-card text-white">
            <div class="card-body">
                <h5 class="card-title fw-bold border-bottom border-light pb-2 mb-3">Informasi Nasabah</h5>
                <p class="mb-1 text-light small">Nama Nasabah</p>
                <h5 class="fw-bold mb-3">{{ $customer->user->name }}</h5>
                
                <p class="mb-1 text-light small">Nomor Rekening</p>
                <h5 class="fw-bold mb-3">{{ $customer->account_number }}</h5>
                
                <p class="mb-1 text-light small">Saldo Saat Ini</p>
                <h3 class="fw-bold mb-0">Rp {{ number_format($customer->balance, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    <!-- Form Setoran -->
    <div class="col-md-4 mb-4">
        <div class="card h-100 transaction-form-card">
            <div class="card-header fw-bold">
                <i class="bi bi-arrow-down-circle"></i> Setoran Tunai
            </div>
            <div class="card-body">
                <form id="depositForm" action="{{ route('teller.deposit', $customer->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nominal Setoran (Rp)</label>
                        <input type="number" class="form-control form-control-lg" name="amount" min="1" required>
                        <div class="form-text">Pastikan uang fisik telah dihitung dan sesuai.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="depositNote">Catatan</label>
                        <textarea id="depositNote" class="form-control" name="note" rows="2" maxlength="500"></textarea>
                    </div>
                    @if($dailyReport->status !== 'draft')
                        <div class="alert alert-danger small mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Transaksi baru tidak dapat diproses karena laporan kas harian sudah diajukan (Status: <strong>{{ ucfirst($dailyReport->status) }}</strong>).
                        </div>
                    @endif
                    <button type="button" class="btn transaction-action-button w-100" id="depositConfirmButton" @disabled($dailyReport->status !== 'draft')>
                        Proses Setoran
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Form Penarikan -->
    <div class="col-md-4 mb-4">
        <div class="card h-100 transaction-form-card transaction-form-card--withdrawal">
            <div class="card-header fw-bold">
                <i class="bi bi-arrow-up-circle"></i> Penarikan Tunai
            </div>
            <div class="card-body">
                <form action="{{ route('teller.withdraw', $customer->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nominal Penarikan (Rp)</label>
                        <input type="number" class="form-control form-control-lg" name="amount" min="1" required>
                        <div class="form-text text-danger">Saldo minimum mengendap: Rp 10.000</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PIN Nasabah (Otorisasi)</label>
                        <input type="password" class="form-control text-center fs-4 letter-spacing-2" name="pin" maxlength="6" required placeholder="******">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="withdrawalNote">Catatan</label>
                        <textarea id="withdrawalNote" class="form-control" name="note" rows="2" maxlength="500"></textarea>
                    </div>
                    @if($dailyReport->status !== 'draft')
                        <div class="alert alert-danger small mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Transaksi baru tidak dapat diproses karena laporan kas harian sudah diajukan (Status: <strong>{{ ucfirst($dailyReport->status) }}</strong>).
                        </div>
                    @endif
                    @if($dailyReport->initial_capital <= 0)
                        <div class="alert alert-warning small">
                            Catat modal awal hari ini di Dashboard Teller sebelum melakukan penarikan.
                        </div>
                    @endif
                    <button type="button" class="btn transaction-action-button transaction-withdrawal-button w-100" id="withdrawConfirmButton" @disabled($dailyReport->initial_capital <= 0 || $dailyReport->status !== 'draft')>
                        Proses Penarikan
                    </button>
                </form>
                <hr>
                <div class="small fw-semibold mb-2">Lupa PIN nasabah?</div>
                <p class="small text-muted">Kirim OTP ke email terdaftar nasabah untuk verifikasi sebelum PIN diganti.</p>
                <a href="{{ route('teller.pin_recovery', $customer->id) }}"
                   class="btn btn-outline-secondary btn-sm w-100">Reset PIN dengan OTP</a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var depositButton = document.getElementById('depositConfirmButton');
        var withdrawButton = document.getElementById('withdrawConfirmButton');

        if (depositButton) {
            depositButton.addEventListener('click', function () {
                var amountInput = document.querySelector('#depositForm input[name="amount"]');
                var amount = amountInput ? parseInt(amountInput.value, 10) : 0;

                if (!amount || amount <= 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Nominal tidak valid',
                        text: 'Masukkan nominal setoran yang benar.',
                    });
                    return;
                }

                Swal.fire({
                    title: 'Konfirmasi Setoran',
                    html: 'Apakah Anda yakin data setoran sudah benar?<br><strong>Rp ' + new Intl.NumberFormat('id-ID').format(amount) + '</strong>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Proses Setoran',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('depositForm').submit();
                    }
                });
            });
        }

        if (withdrawButton) {
            withdrawButton.addEventListener('click', function () {
                var withdrawForm = withdrawButton.closest('form');
                var amountInput = withdrawForm.querySelector('input[name="amount"]');
                var pinInput = withdrawForm.querySelector('input[name="pin"]');
                var amount = amountInput ? parseInt(amountInput.value, 10) : 0;
                var pin = pinInput ? pinInput.value : '';

                if (!amount || amount <= 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Nominal tidak valid',
                        text: 'Masukkan nominal penarikan yang benar.',
                    });
                    return;
                }

                if (!pin || pin.length < 1) {
                    Swal.fire({
                        icon: 'error',
                        title: 'PIN dibutuhkan',
                        text: 'Masukkan PIN nasabah untuk otorisasi.',
                    });
                    return;
                }

                Swal.fire({
                    title: 'Konfirmasi Penarikan',
                    html: 'Apakah Anda yakin data penarikan sudah benar?<br><strong>Rp ' + new Intl.NumberFormat('id-ID').format(amount) + '</strong>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Proses Penarikan',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        withdrawForm.submit();
                    }
                });
            });
        }
    });
</script>
@endpush
