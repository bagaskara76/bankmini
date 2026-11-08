@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12 mt-4">
        <a href="{{ route('admin.verify_reports') }}" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Kembali ke Verifikasi</a>
        <h3><i class="bi bi-clipboard-check"></i> Pemeriksaan Laporan Kas</h3>
        <p class="text-muted">Periksa ringkasan dan transaksi sebelum menyetujui atau menolak laporan.</p>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->has('rejection_note'))
            <div class="alert alert-danger">{{ $errors->first('rejection_note') }}</div>
        @endif

        <div class="card p-3 mb-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h5 class="mb-1">{{ optional($report->teller)->name ?? 'Teller tidak ditemukan' }}</h5>
                    <div class="text-muted">{{ $report->report_date->format('d M Y') }}</div>
                </div>
                <span class="badge text-bg-warning">Menunggu pemeriksaan</span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <tbody>
                        <tr><th>Modal awal</th><td class="text-end">Rp {{ number_format($report->total_capital, 0, ',', '.') }}</td></tr>
                        <tr><th>Total setoran</th><td class="text-end">Rp {{ number_format($report->total_deposit, 0, ',', '.') }}</td></tr>
                        <tr><th>Total penarikan</th><td class="text-end">Rp {{ number_format($report->total_withdrawal, 0, ',', '.') }}</td></tr>
                        <tr><th>Laporan web (kas sistem)</th><td class="text-end fw-bold">Rp {{ number_format($report->system_cash, 0, ',', '.') }}</td></tr>
                        <tr><th>Kas fisik hasil hitung teller</th><td class="text-end fw-bold {{ (int) $report->physical_cash === (int) $report->system_cash ? 'text-success' : 'text-danger' }}">Rp {{ number_format($report->physical_cash, 0, ',', '.') }}</td></tr>
                        <tr><th>Selisih</th><td class="text-end {{ (int) $report->physical_cash === (int) $report->system_cash ? 'text-success' : 'text-danger' }}">Rp {{ number_format($report->physical_cash - $report->system_cash, 0, ',', '.') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-3 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-3">
                <h5 class="mb-0">Transaksi hari tersebut</h5>
                <span class="small text-muted">{{ $transactions->count() }} transaksi berhasil</span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead><tr><th>Waktu</th><th>Nasabah</th><th>Jenis</th><th class="text-end">Nominal</th><th>Catatan</th></tr></thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('H:i:s') }}</td>
                                <td>{{ optional(optional($transaction->customer)->user)->name ?? 'Nasabah tidak ditemukan' }}</td>
                                <td>{{ $transaction->type === 'deposit' ? 'Setoran' : 'Penarikan' }}</td>
                                <td class="text-end">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                                <td>{{ $transaction->note ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada transaksi berhasil pada tanggal ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-end gap-2 mb-5">
            <button class="btn btn-danger" type="button" data-bs-toggle="modal" data-bs-target="#rejectReportModal"><i class="bi bi-x-circle"></i> Tolak laporan</button>
            <form method="POST" action="{{ route('admin.approve_report', $report->id) }}">
                @csrf
                <button class="btn btn-success" type="submit" @disabled((int) $report->physical_cash !== (int) $report->system_cash)><i class="bi bi-check-circle"></i> Setujui laporan</button>
            </form>
        </div>
        @if((int) $report->physical_cash !== (int) $report->system_cash)
            <p class="text-danger text-end">Kas fisik dan kas sistem belum balance. Laporan tidak dapat disetujui.</p>
        @endif
    </div>
</div>

<div class="modal fade" id="rejectReportModal" tabindex="-1" aria-labelledby="rejectReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.reject_report', $report->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectReportModalLabel">Tolak Laporan Harian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p>Berikan alasan penolakan untuk laporan {{ optional($report->teller)->name }} tanggal {{ $report->report_date->format('d M Y') }}.</p>
                    <label for="rejectionNote" class="form-label">Catatan alasan</label>
                    <textarea id="rejectionNote" name="rejection_note" class="form-control" rows="4" maxlength="2000" placeholder="Tuliskan alasan laporan ditolak" required>{{ old('rejection_note') }}</textarea>
                    @error('rejection_note')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-danger" type="submit">Konfirmasi Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>
@if($errors->has('rejection_note'))
    @push('scripts')
        <script>new bootstrap.Modal(document.getElementById('rejectReportModal')).show();</script>
    @endpush
@endif
@endsection