@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <h4 class="fw-bold">Dashboard Teller</h4>
        <p class="text-muted">Selamat bertugas, pastikan ketelitian dalam melayani transaksi nasabah.</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@error('physical_cash')
<div class="alert alert-danger alert-dismissible fade show">
    {{ $message }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@enderror
@error('amount')
<div class="alert alert-danger alert-dismissible fade show">
    {{ $message }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@enderror
@error('initial_capital')
<div class="alert alert-danger alert-dismissible fade show">
    {{ $message }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@enderror

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card"><div class="card-body">
            <h5 class="fw-bold">Modal Awal Hari Ini</h5>
            <p class="fs-4 mb-3">Rp {{ number_format($dailyReport->total_capital, 0, ',', '.') }}</p>
            @if($dailyReport->status === 'draft')
                @if($dailyReport->initial_capital <= 0)
                <form method="POST" action="{{ route('teller.initial_capital') }}">
                    @csrf
                    <label for="initial_capital" class="form-label">Catat modal awal</label>
                    <div class="input-group"><span class="input-group-text">Rp</span><input id="initial_capital" type="number" name="initial_capital" min="0" step="1" class="form-control" value="{{ old('initial_capital', $dailyReport->initial_capital) }}" required><button class="btn btn-primary">Simpan</button></div>
                </form>
                @else
                <button class="btn btn-outline-primary w-100" type="button" data-bs-toggle="modal" data-bs-target="#additionalCapitalModal">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Modal Awal
                </button>
                @endif
            @endif
        </div></div>
    </div>
    <div class="col-md-8">
        <div class="card h-100"><div class="card-body">
            <h5 class="fw-bold">Laporan Kas Harian</h5>
            <div class="row text-center mb-3">
                <div class="col-md-4"><small class="text-muted">Laporan Web (Kas Sistem)</small><div class="fs-5 fw-bold">Rp {{ number_format($dailyReport->system_cash, 0, ',', '.') }}</div></div>
                <div class="col-md-4"><small class="text-muted">Kas Fisik Teller</small><div class="fs-5 fw-bold">{{ $dailyReport->status === 'draft' ? 'Diisi saat mengajukan' : 'Rp '.number_format($dailyReport->physical_cash, 0, ',', '.') }}</div></div>
                <div class="col-md-4"><small class="text-muted">Status</small><div class="fs-5 fw-bold">{{ ucfirst($dailyReport->status) }}</div></div>
            </div>
            @if($dailyReport->status === 'rejected')
            <div class="alert alert-danger">
                <strong>Laporan perlu diperiksa ulang.</strong>
                <div class="mt-1">Catatan admin: {{ $dailyReport->rejection_note ?: 'Admin tidak memberikan catatan.' }}</div>
            </div>
            <div class="row mb-3">
                <div class="col-6"><small class="text-muted">Total Setoran</small><div>Rp {{ number_format($dailyReport->total_deposit, 0, ',', '.') }}</div></div>
                <div class="col-6"><small class="text-muted">Total Penarikan</small><div>Rp {{ number_format($dailyReport->total_withdrawal, 0, ',', '.') }}</div></div>
            </div>
            <form method="POST" action="{{ route('teller.daily_report.submit') }}">
                @csrf
                <label for="physical_cash" class="form-label">Kas fisik setelah dihitung ulang</label>
                <div class="input-group mb-2"><span class="input-group-text">Rp</span><input id="physical_cash" type="number" name="physical_cash" min="0" step="1" class="form-control" value="{{ old('physical_cash', $dailyReport->physical_cash) }}" required></div>
                <button class="btn btn-warning">Kirim Ulang Laporan</button>
            </form>
            @elseif($dailyReport->status === 'draft')
            <form method="POST" action="{{ route('teller.daily_report.submit') }}">
                @csrf
                <p class="text-muted mb-2">Hitung uang tunai yang benar-benar ada di teller, lalu masukkan jumlahnya. Nilai ini akan dibandingkan Admin dengan laporan web.</p>
                <label for="physical_cash" class="form-label">Jumlah kas fisik di teller</label>
                <div class="input-group mb-3"><span class="input-group-text">Rp</span><input id="physical_cash" type="number" name="physical_cash" min="0" step="1" class="form-control" value="{{ old('physical_cash') }}" required></div>
                <button class="btn btn-success">Ajukan Laporan</button>
            </form>
            @else
            <p class="mb-0 text-muted">Laporan hari ini {{ $dailyReport->status === 'pending' ? 'sedang pending' : 'sudah ' . $dailyReport->status }}.</p>
            @endif
        </div></div>
    </div>
</div>

@if($dailyReport->status === 'draft' && $dailyReport->initial_capital > 0)
<div class="modal fade" id="additionalCapitalModal" tabindex="-1" aria-labelledby="additionalCapitalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('teller.additional_capital') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="additionalCapitalModalLabel">Tambah Modal Awal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Modal tambahan akan langsung dijumlahkan ke modal awal dan kas harian.</p>
                    <label for="additional_capital_amount" class="form-label">Nominal tambahan</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input id="additional_capital_amount" type="number" name="amount" min="1" step="1" class="form-control" value="{{ old('amount') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Modal Tambahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<div class="row">
    <!-- QR Scanner Card -->
    <div class="col-md-5 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-qr-code-scan me-1" viewBox="0 0 16 16">
                        <path d="M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5M.5 12a.5.5 0 0 1 .5.5V15h2.5a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5m15 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H15v-2.5a.5.5 0 0 1 .5-.5M4 4h1v1H4z"/>
                        <path d="M7 2H2v5h5zM3 3h3v3H3zm2 8H4v1h1z"/>
                        <path d="M7 9H2v5h5zm-4 1h3v3H3zm8-6h1v1h-1z"/>
                        <path d="M9 2h5v5H9zm1 1v3h3V3zM8 8v2h1v1H8v1h2v-2h1v2h1v-1h2v-1h-3V8zm2 2H9V9h1zm4 2h-1v1h-2v1h3zm-4 2v-1h1v1z"/>
                        <path d="M12 9h2V8h-2z"/>
                    </svg>
                    Scan QR Code Nasabah
                </h5>

                <!-- Tab Buttons -->
                <div class="d-flex gap-2 mb-3">
                    <button id="btn-camera" onclick="showTab('camera')"
                        class="btn btn-primary btn-sm flex-fill">
                        Kamera
                    </button>
                    <button id="btn-file" onclick="showTab('file')"
                        class="btn btn-outline-secondary btn-sm flex-fill">
                        Upload Foto
                    </button>
                </div>

                <!-- === BAGIAN KAMERA === -->
                <div id="camera-section">
                    <div id="reader" style="width:100%;"></div>
                    <p class="small text-muted text-center mt-2">
                        Klik "Start Scanning" lalu arahkan QR Code ke kamera.
                    </p>
                </div>

                <!-- === BAGIAN UPLOAD FOTO === -->
                <div id="file-section" style="display:none;">
                    <div class="border-2 border-dashed rounded p-3 text-center mb-3"
                         style="border: 2px dashed #dee2e6; cursor:pointer;"
                         onclick="document.getElementById('qr-file-input').click()">
                        <div id="upload-placeholder">
                            <p class="mb-0 fw-medium">Klik di sini untuk pilih foto QR Code</p>
                            <p class="small text-muted mb-0">Format: JPG, PNG, GIF</p>
                        </div>
                        <img id="preview-img" style="display:none; max-width:100%; max-height:200px; border-radius:8px;">
                    </div>
                    <input type="file" id="qr-file-input" accept="image/*" style="display:none;" onchange="onFileSelected()">
                    <canvas id="qr-canvas" style="display:none;"></canvas>

                    <div id="scan-msg" class="d-none mb-3"></div>

                    <button id="scan-btn" onclick="scanFromFile()" class="btn btn-success w-100" style="display:none;">
                        Baca QR Code dari Foto Ini
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Input Card -->
    <div class="col-md-7 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="fw-bold mb-4">Input Manual Nomor Rekening</h5>

                <form action="{{ route('teller.search') }}" method="POST" id="searchForm">
                    @csrf
                    <input type="hidden" name="qr_code" id="qr_code_input">
                    <div class="mb-3">
                        <label for="account_number" class="form-label fw-medium">Nomor Rekening Nasabah</label>
                        <input type="text" class="form-control form-control-lg" id="account_number"
                               name="account_number" placeholder="Contoh: 2026001">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        Cari Data Nasabah
                    </button>
                </form>

                <hr class="my-4">

                <div class="row text-center">
                    <div class="col-6">
                        <div class="p-3 border rounded bg-light">
                            <h3 class="mb-0 fw-bold text-success">Rp {{ number_format($totalDeposit ?? 0, 0, ',', '.') }}</h3>
                            <p class="mb-1 text-muted">Total Setoran Hari Ini</p>
                            <p class="mb-0 fw-bold">{{ $countDeposit ?? 0 }} kali</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border rounded bg-light">
                            <h3 class="mb-0 fw-bold text-danger">Rp {{ number_format($totalWithdrawal ?? 0, 0, ',', '.') }}</h3>
                            <p class="mb-1 text-muted">Total Penarikan Hari Ini</p>
                            <p class="mb-0 fw-bold">{{ $countWithdrawal ?? 0 }} kali</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4"><div class="card-body">
    <h5 class="fw-bold">Transaksi Hari Ini</h5>
    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Waktu</th><th>Nasabah</th><th>Jenis</th><th>Nominal</th></tr></thead><tbody>
    @forelse($transactions as $transaction)
        <tr><td>{{ $transaction->created_at->format('H:i:s') }} WIB</td><td>{{ optional(optional($transaction->customer)->user)->name ?? 'Nasabah tidak ditemukan' }}</td><td>{{ $transaction->type === 'deposit' ? 'Setoran' : 'Penarikan' }}</td><td>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td></tr>
    @empty
        <tr><td colspan="4" class="text-center text-muted">Belum ada transaksi hari ini.</td></tr>
    @endforelse
    </tbody></table></div>
</div></div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
    let cameraScanner = null;

    /* ─── Navigasi Tab ─────────────────────────────── */
    function showTab(tab) {
        const isCamera = tab === 'camera';
        document.getElementById('camera-section').style.display = isCamera ? 'block' : 'none';
        document.getElementById('file-section').style.display   = isCamera ? 'none'  : 'block';
        document.getElementById('btn-camera').className = isCamera
            ? 'btn btn-primary btn-sm flex-fill'
            : 'btn btn-outline-secondary btn-sm flex-fill';
        document.getElementById('btn-file').className = isCamera
            ? 'btn btn-outline-secondary btn-sm flex-fill'
            : 'btn btn-primary btn-sm flex-fill';

        if (isCamera) {
            startCamera();
        } else {
            if (cameraScanner) { cameraScanner.clear().catch(() => {}); cameraScanner = null; }
        }
    }

    /* ─── Kamera ───────────────────────────────────── */
    function startCamera() {
        if (cameraScanner) return;
        cameraScanner = new Html5QrcodeScanner("reader", {
            fps: 15,
            qrbox: { width: 230, height: 230 },
            rememberLastUsedCamera: true,
        }, false);
        cameraScanner.render(
            (text) => { cameraScanner.clear().catch(() => {}); submitQR(text); },
            () => {}
        );
    }

    /* ─── Upload Foto ──────────────────────────────── */
    function onFileSelected() {
        const file = document.getElementById('qr-file-input').files[0];
        if (!file) return;

        // Tampilkan preview
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('preview-img');
            preview.src = e.target.result;
            preview.style.display = 'block';
            document.getElementById('upload-placeholder').style.display = 'none';
            document.getElementById('scan-btn').style.display = 'block';
            document.getElementById('scan-msg').className = 'd-none';
        };
        reader.readAsDataURL(file);
    }

    function scanFromFile() {
        const file = document.getElementById('qr-file-input').files[0];
        if (!file) return;

        showMsg('Sedang membaca QR Code...', 'info');

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const result = tryDecode(img);
                if (result) {
                    showMsg('Berhasil! Mengalihkan ke data nasabah...', 'success');
                    setTimeout(() => submitQR(result), 600);
                } else {
                    showMsg('QR Code tidak terbaca. Pastikan foto jelas dan QR Code tidak buram. Coba foto ulang dari jarak dekat atau gunakan input manual.', 'danger');
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function tryDecode(img) {
        const canvas = document.getElementById('qr-canvas');
        const ctx    = canvas.getContext('2d');

        // Coba berbagai skala dan mode kontras
        const scales = [1, 2, 3, 1.5, 0.75];
        for (let scale of scales) {
            const w = Math.round(img.width * scale);
            const h = Math.round(img.height * scale);
            canvas.width  = w;
            canvas.height = h;

            // Normal
            ctx.drawImage(img, 0, 0, w, h);
            let code = jsQR(ctx.getImageData(0,0,w,h).data, w, h, { inversionAttempts: 'attemptBoth' });
            if (code) return code.data;

            // Kontras tinggi
            boostContrast(ctx, w, h);
            code = jsQR(ctx.getImageData(0,0,w,h).data, w, h, { inversionAttempts: 'attemptBoth' });
            if (code) return code.data;
        }
        return null;
    }

    function boostContrast(ctx, w, h) {
        const d = ctx.getImageData(0, 0, w, h);
        for (let i = 0; i < d.data.length; i += 4) {
            d.data[i]   = clamp(3 * (d.data[i]   - 128) + 128);
            d.data[i+1] = clamp(3 * (d.data[i+1] - 128) + 128);
            d.data[i+2] = clamp(3 * (d.data[i+2] - 128) + 128);
        }
        ctx.putImageData(d, 0, 0);
    }

    function clamp(v) { return Math.min(255, Math.max(0, v)); }

    /* ─── Helper ───────────────────────────────────── */
    function submitQR(value) {
        document.getElementById('qr_code_input').value   = value;
        document.getElementById('account_number').value  = '';
        document.getElementById('searchForm').submit();
    }

    function showMsg(html, type) {
        const d = document.getElementById('scan-msg');
        d.className = 'alert alert-' + type + ' small mb-3';
        d.innerHTML = html;
    }

    // Mulai kamera saat halaman terbuka
    startCamera();
</script>
@endpush
