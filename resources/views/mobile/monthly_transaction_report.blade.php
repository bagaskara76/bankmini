<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi {{ $period->locale('id')->translatedFormat('F Y') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h2 { margin: 0 0 5px; color: #1e3c72; }
        p { margin: 4px 0; }
        .summary { width: 100%; margin: 18px 0; border-collapse: collapse; }
        .summary td { width: 50%; padding: 10px; border: 1px solid #dbe3ee; background: #f4f7fb; }
        table.transactions { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .transactions th, .transactions td { padding: 7px; border: 1px solid #cbd5e1; text-align: left; }
        .transactions th { background: #e9eff9; color: #1e3c72; }
        .amount { text-align: right !important; white-space: nowrap; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <h2>Laporan Transaksi Bulanan</h2>
    <p>Periode: {{ $period->locale('id')->translatedFormat('F Y') }}</p>
    <p>Nama: {{ optional($customer->user)->name }}</p>
    <p>Nomor rekening: {{ $customer->account_number }}</p>

    <table class="summary">
        <tr>
            <td><strong>Total setoran selesai</strong><br>Rp {{ number_format($totalDeposit, 0, ',', '.') }}</td>
            <td><strong>Total penarikan selesai</strong><br>Rp {{ number_format($totalWithdrawal, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="transactions">
        <thead>
            <tr>
                <th>Tanggal &amp; Waktu</th>
                <th>Jenis transaksi</th>
                <th>Status</th>
                <th class="amount">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->created_at->format('d-m-Y H:i') }}</td>
                    <td>{{ $transaction->type === 'deposit' ? 'Setoran' : 'Penarikan' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $transaction->status)) }}</td>
                    <td class="amount">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted" style="text-align: center;">Tidak ada transaksi pada bulan ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
