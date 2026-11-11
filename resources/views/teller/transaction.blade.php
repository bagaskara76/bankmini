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
                        <input type="text" class="form-control form-control-lg amount-display" id="depositDisplay"
                               inputmode="numeric" placeholder="0" autocomplete="off">
                        <input type="hidden" name="amount" id="depositAmount">
                        <div class="form-text">Pastikan uang fisik telah dihitung dan sesuai.</div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-amount" data-target="depositDisplay" data-amount="10000">+10.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-amount" data-target="depositDisplay" data-amount="20000">+20.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-amount" data-target="depositDisplay" data-amount="50000">+50.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-amount" data-target="depositDisplay" data-amount="100000">+100.000</button>
                        </div>
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
                {{-- Form aktual dikirim setelah PIN diverifikasi lewat HP nasabah --}}
                <form action="{{ route('teller.withdraw', $customer->id) }}" method="POST" id="withdrawForm">
                    @csrf
                    <input type="hidden" name="amount" id="withdrawAmount">
                    <input type="hidden" name="note"   id="withdrawNoteHidden">
                    <input type="hidden" name="pin_session_token" id="pinSessionToken">

                    <div class="mb-3">
                        <label class="form-label">Nominal Penarikan (Rp)</label>
                        <input type="text" class="form-control form-control-lg amount-display" id="withdrawDisplay"
                               inputmode="numeric" placeholder="0" autocomplete="off">
                        <div class="form-text text-danger">Saldo minimum mengendap: Rp 10.000</div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-amount" data-target="withdrawDisplay" data-amount="10000">+10.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-amount" data-target="withdrawDisplay" data-amount="20000">+20.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-amount" data-target="withdrawDisplay" data-amount="50000">+50.000</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm quick-amount" data-target="withdrawDisplay" data-amount="100000">+100.000</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="withdrawalNote">Catatan</label>
                        <textarea id="withdrawalNote" class="form-control" rows="2" maxlength="500"></textarea>
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

                    {{-- Tombol: Minta PIN via HP nasabah --}}
                    <button type="button" class="btn transaction-action-button transaction-withdrawal-button w-100"
                            id="requestPinButton"
                            @disabled($dailyReport->initial_capital <= 0 || $dailyReport->status !== 'draft')>
                        <i class="bi bi-phone"></i> Minta PIN via HP Nasabah
                    </button>
                </form>

                {{-- Panel Menunggu Otorisasi PIN di Aplikasi Mobile --}}
                <div id="pinWaitPanel" class="mt-3" style="display:none;">
                    <div class="card border-primary shadow-sm">
                        <div class="card-body text-center py-3">
                            <div class="mb-2">
                                <span class="badge bg-primary px-3 py-2 fs-6">
                                    <i class="bi bi-phone me-1"></i> Otorisasi di Aplikasi Mobile
                                </span>
                            </div>
                            <p class="small text-muted mb-2">
                                Silakan minta nasabah membuka <strong>Aplikasi Mobile (HP)</strong> dan menekan tombol <strong>"Otorisasi PIN Penarikan"</strong>.
                            </p>
                            <div class="d-flex align-items-center justify-content-center gap-2 my-2">
                                <div class="spinner-border spinner-border-sm text-primary" role="status" id="pinSpinner"></div>
                                <span class="small fw-semibold text-secondary" id="pinStatus">Menunggu konfirmasi PIN di HP nasabah…</span>
                            </div>
                            <div class="small" id="pinTimer"></div>
                        </div>
                    </div>
                </div>

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

        // ── Format angka dengan pemisah ribuan (gaya Indonesia: titik) ──
        function parseAmount(str) {
            // Hapus semua karakter non-digit
            return parseInt((str || '').replace(/\D/g, ''), 10) || 0;
        }

        function formatRupiah(val) {
            if (!val) return '';
            return val.toLocaleString('id-ID');
        }

        // ── Pasang event untuk setiap input display amount ──
        document.querySelectorAll('.amount-display').forEach(function (displayInput) {
            var hiddenId = displayInput.id === 'depositDisplay' ? 'depositAmount' : 'withdrawAmount';
            var hiddenInput = document.getElementById(hiddenId);

            displayInput.addEventListener('input', function () {
                var raw = parseAmount(this.value);
                this.value = raw ? formatRupiah(raw) : '';
                if (hiddenInput) hiddenInput.value = raw || '';
            });

            displayInput.addEventListener('keydown', function (e) {
                // Izinkan: Backspace, Delete, Tab, Escape, Enter, arrows, home, end
                var allowed = [8, 9, 13, 27, 35, 36, 37, 38, 39, 40, 46];
                if (allowed.indexOf(e.keyCode) !== -1) return;
                // Blokir non-digit
                if (e.key && !/^\d$/.test(e.key)) e.preventDefault();
            });
        });

        // ── Tombol nominal cepat ──
        document.querySelectorAll('.quick-amount').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = this.getAttribute('data-target');
                var addAmount = parseInt(this.getAttribute('data-amount'), 10);
                var displayInput = document.getElementById(targetId);
                if (!displayInput) return;

                var hiddenId = targetId === 'depositDisplay' ? 'depositAmount' : 'withdrawAmount';
                var hiddenInput = document.getElementById(hiddenId);

                var current = parseAmount(displayInput.value);
                var newVal = current + addAmount;
                displayInput.value = formatRupiah(newVal);
                if (hiddenInput) hiddenInput.value = newVal;
            });
        });

        // ── Konfirmasi Setoran ──
        var depositButton = document.getElementById('depositConfirmButton');

        if (depositButton) {
            depositButton.addEventListener('click', function () {
                var amount = parseInt(document.getElementById('depositAmount').value || '0', 10);

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

        // ══════════════════════════════════════════════════════════════
        // ── PENARIKAN: PIN via HP Nasabah ─────────────────────────
        // ══════════════════════════════════════════════════════════════
        var requestPinButton = document.getElementById('requestPinButton');
        var pollingInterval  = null;

        if (requestPinButton) {
            requestPinButton.addEventListener('click', function () {
                var amount = parseInt(document.getElementById('withdrawAmount').value || '0', 10);
                var note   = document.getElementById('withdrawalNote').value;

                if (!amount || amount <= 0) {
                    Swal.fire({ icon: 'error', title: 'Nominal tidak valid', text: 'Masukkan nominal penarikan terlebih dahulu.' });
                    return;
                }

                // Buat PIN Session via AJAX
                requestPinButton.disabled = true;
                requestPinButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

                fetch(@json(route('pin.create', $customer->id)), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ amount: amount, note: note })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (!data.token) throw new Error('Token tidak diterima');
                    showWaitPanel(data.expires_at);
                    startPolling(data.token, amount, note);
                })
                .catch(function() {
                    requestPinButton.disabled = false;
                    requestPinButton.innerHTML = '<i class="bi bi-phone"></i> Minta PIN via HP Nasabah';
                    Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal membuat sesi PIN. Coba lagi.' });
                });
            });
        }

        function showWaitPanel(expiresAtIso) {
            var panel = document.getElementById('pinWaitPanel');
            panel.style.display = 'block';

            // Countdown timer
            var expiresAt = new Date(expiresAtIso);
            clearInterval(window._pinTimerInterval);
            window._pinTimerInterval = setInterval(function() {
                var diff = Math.max(0, Math.floor((expiresAt - new Date()) / 1000));
                var m = Math.floor(diff / 60), s = diff % 60;
                var timerEl = document.getElementById('pinTimer');
                timerEl.textContent = 'Sesi habis dalam: ' + m + ':' + (s < 10 ? '0' : '') + s;
                timerEl.className = 'mt-2 small ' + (diff <= 60 ? 'text-danger fw-bold' : 'text-muted');
                if (diff === 0) { clearInterval(window._pinTimerInterval); timerEl.textContent = 'Sesi kadaluarsa.'; }
            }, 1000);
        }

        function startPolling(token, amount, note) {
            clearInterval(pollingInterval);
            var statusUrl = @json(url('pin')) + '/' + token + '/status';

            pollingInterval = setInterval(function() {
                fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.status === 'verified') {
                        clearInterval(pollingInterval);
                        clearInterval(window._pinTimerInterval);

                        document.getElementById('pinStatus').innerHTML =
                            '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> PIN Terverifikasi di Aplikasi!</span>';
                        document.getElementById('pinSpinner').style.display = 'none';
                        document.getElementById('pinWaitPanel').querySelector('.card').classList.replace('border-primary', 'border-success');

                        // Isi hidden fields lalu submit form otomatis
                        document.getElementById('withdrawAmount').value     = amount;
                        document.getElementById('withdrawNoteHidden').value = note;
                        document.getElementById('pinSessionToken').value    = token;

                        setTimeout(function() {
                            document.getElementById('withdrawForm').submit();
                        }, 1200);

                    } else if (data.status === 'expired') {
                        clearInterval(pollingInterval);
                        document.getElementById('pinStatus').innerHTML =
                            '<span class="text-danger">Sesi kadaluarsa. Ulangi proses.</span>';
                        document.getElementById('pinSpinner').style.display = 'none';
                        requestPinButton.disabled = false;
                        requestPinButton.innerHTML = '<i class="bi bi-phone"></i> Minta PIN via HP Nasabah';
                    }
                })
                .catch(function() {});
            }, 2000);
        }
    });
</script>
@endpush
