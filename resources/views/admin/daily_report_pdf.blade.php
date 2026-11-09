<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Harian {{ $report->report_date->format('Y-m-d') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        .amount { text-align: right; }
        .section-title { margin-top: 24px; margin-bottom: 6px; }
    </style>
</head>
<body>
    <h2>Laporan Kas Harian Teller</h2>
    <div>Tanggal: {{ $report->report_date->format('d-m-Y') }}</div>
    <div>Teller: {{ optional($report->teller)->name }}</div>
    <table>
        <tr><th>Modal Awal</th><td class="amount">Rp {{ number_format($report->total_capital, 0, ',', '.') }}</td></tr>
        <tr><th>Total Setoran</th><td class="amount">Rp {{ number_format($report->total_deposit, 0, ',', '.') }}</td></tr>
        <tr><th>Total Penarikan</th><td class="amount">Rp {{ number_format($report->total_withdrawal, 0, ',', '.') }}</td></tr>
        <tr><th>Kas Sistem</th><td class="amount">Rp {{ number_format($report->system_cash, 0, ',', '.') }}</td></tr>
        <tr><th>Kas Fisik</th><td class="amount">Rp {{ number_format($report->physical_cash, 0, ',', '.') }}</td></tr>
        <tr><th>Status</th><td>{{ ucfirst($report->status) }}</td></tr>
    </table>
    <p>Diperiksa oleh: {{ optional($report->reviewedBy)->name }}</p>

    <h3 class="section-title">Detail Transaksi Hari Itu</h3>
    <table>
        <thead>
            <tr><th>No.</th><th>Jam</th><th>Teller</th><th>Nasabah</th><th>Jenis Transaksi</th><th class="amount">Nominal</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $transaction->created_at->format('H:i:s') }} WIB</td>
                    <td>{{ optional($transaction->teller)->name ?? '-' }}</td>
                    <td>{{ optional(optional($transaction->customer)->user)->name ?? 'Nasabah tidak ditemukan' }}</td>
                    <td>{{ $transaction->type === 'deposit' ? 'Setoran' : 'Penarikan' }}</td>
                    <td class="amount">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $transaction->status)) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align: center;">Tidak ada transaksi pada hari ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>