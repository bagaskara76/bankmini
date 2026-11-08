<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan {{ sprintf('%04d-%02d', $year, $month) }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #202b37; font-size: 12px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 15px; margin: 24px 0 10px; }
        .muted { color: #64748b; }
        .summary { margin: 20px 0; padding: 12px; background: #f1f5f9; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table td { width: 25%; padding: 4px; }
        .summary-table strong { display: block; font-size: 14px; margin-top: 4px; }
        table.details { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .details th, .details td { border: 1px solid #d8dee8; padding: 6px; vertical-align: top; }
        .details th { background: #f1f5f9; text-align: left; }
        .number { text-align: right; white-space: nowrap; }
        .details tr { page-break-inside: avoid; }
        .details thead { display: table-header-group; }
        .section-note { margin-top: 4px; font-size: 10px; }
        .footer { margin-top: 24px; font-size: 10px; color: #64748b; }
    </style>
</head>
<body>
    <h1>Laporan Kas Bulanan</h1>
    <div class="muted">Periode {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}</div>

    <div class="summary">
        <table class="summary-table">
            <tr>
                <td>Laporan disetujui<strong>{{ number_format($dailyReports->count(), 0, ',', '.') }}</strong></td>
                <td>Total kas sistem<strong>Rp {{ number_format($dailyReports->sum('system_cash'), 0, ',', '.') }}</strong></td>
                <td>Total setoran<strong>Rp {{ number_format($summary['deposit'], 0, ',', '.') }}</strong></td>
                <td>Total penarikan<strong>Rp {{ number_format($summary['withdrawal'], 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td colspan="4">Transaksi berhasil: <strong>{{ number_format($summary['count'], 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    <h2>Rincian Laporan Kas Disetujui</h2>
    <table class="details">
        <thead>
            <tr><th>Tanggal</th><th>Teller</th><th class="number">Modal awal</th><th class="number">Setoran</th><th class="number">Penarikan</th><th class="number">Kas sistem</th><th class="number">Kas fisik</th><th>Diperiksa oleh</th></tr>
        </thead>
        <tbody>
            @forelse($dailyReports as $report)
                <tr>
                    <td>{{ $report->report_date->format('d/m/Y') }}</td>
                    <td>{{ optional($report->teller)->name ?? 'Teller tidak ditemukan' }}</td>
                    <td class="number">Rp {{ number_format($report->total_capital, 0, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format($report->total_deposit, 0, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format($report->total_withdrawal, 0, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format($report->system_cash, 0, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format($report->physical_cash, 0, ',', '.') }}</td>
                    <td>{{ optional($report->reviewedBy)->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">Belum ada laporan kas yang disetujui pada bulan ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Rincian Transaksi Berhasil</h2>
    <table class="details">
        <thead>
            <tr><th>Waktu</th><th>Teller</th><th>Nasabah</th><th>Jenis</th><th class="number">Nominal</th><th>Catatan</th></tr>
        </thead>
        <tbody>
            @forelse($transactionDetails as $transaction)
                <tr>
                    <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ optional($transaction->teller)->name ?? '-' }}</td>
                    <td>{{ optional(optional($transaction->customer)->user)->name ?? 'Nasabah tidak ditemukan' }}</td>
                    <td>{{ $transaction->type === 'deposit' ? 'Setoran' : 'Penarikan' }}</td>
                    <td class="number">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                    <td>{{ $transaction->note ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Tidak ada transaksi berhasil pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Dicetak pada {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>