<style>
    .transaction-receipt {
        max-width: 100%;
        border: 1px solid #aebbc2;
        background: #fff;
        color: #20282d;
        font-family: 'Courier New', monospace;
    }
    .transaction-receipt header,
    .transaction-receipt section,
    .transaction-receipt footer { padding: 1rem 1.25rem; }
    .transaction-receipt header { border-bottom: 1px dashed #89949b; text-align: center; }
    .transaction-receipt header h5 { margin: 0 0 .35rem; font-family: sans-serif; }
    .transaction-receipt dl { display: grid; grid-template-columns: 1fr auto; gap: .55rem 1rem; margin: 0; }
    .transaction-receipt dt { font-weight: 400; color: #52616a; }
    .transaction-receipt dd { margin: 0; text-align: right; font-weight: 700; }
    .transaction-receipt .receipt-note { max-width: 16rem; white-space: pre-wrap; overflow-wrap: anywhere; }
    .transaction-receipt .receipt-amount { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #89949b; font-size: 1.05rem; }
    .transaction-receipt footer { border-top: 1px dashed #89949b; text-align: center; color: #52616a; }
    .transaction-receipt .receipt-question { margin: 1rem 1rem .25rem; text-align: center; font-family: sans-serif; }
    .transaction-receipt .receipt-print { display: block; margin: .75rem auto 1rem; }
    @media print {
        body * { visibility: hidden; }
        .swal2-container, .swal2-container * { visibility: visible; }
        .swal2-container { position: absolute !important; inset: 0 !important; padding: 0 !important; background: #fff !important; }
        .swal2-popup { width: 100% !important; max-width: none !important; box-shadow: none !important; }
        .swal2-actions, .swal2-close, .receipt-question, .receipt-print { display: none !important; }
        .transaction-receipt header,
        .transaction-receipt section,
        .transaction-receipt footer { padding: .7rem; }
    }
</style>

@php
    $receiptCustomer = $transaction->customer;
    $isDeposit = $transaction->type === 'deposit';
@endphp

<template id="transactionReceiptTemplate">
    <section class="transaction-receipt" aria-label="Struk transaksi">
        <header>
            <h5>E-TELLER BANK MINI</h5>
            <div>BUKTI {{ $isDeposit ? 'SETORAN' : 'PENARIKAN' }} TUNAI</div>
        </header>
        <section>
            <dl>
                <dt>No. Transaksi</dt>
                <dd>{{ str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT) }}</dd>
                <dt>Tanggal</dt>
                <dd>{{ $transaction->created_at->format('d/m/Y H:i:s') }}</dd>
                <dt>Nasabah</dt>
                <dd>{{ optional(optional($receiptCustomer)->user)->name ?? 'Nasabah tidak ditemukan' }}</dd>
                <dt>No. Rekening</dt>
                <dd>{{ optional($receiptCustomer)->account_number ?? '-' }}</dd>
                <dt>Teller</dt>
                <dd>{{ optional($transaction->teller)->name ?? '-' }}</dd>
                @if(filled($transaction->note))
                    <dt>Catatan</dt>
                    <dd class="receipt-note">{{ $transaction->note }}</dd>
                @endif
            </dl>
            <dl class="receipt-amount">
                <dt>{{ $isDeposit ? 'Jumlah Setoran' : 'Jumlah Penarikan' }}</dt>
                <dd>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</dd>
                <dt>Saldo Setelah Transaksi</dt>
                <dd>Rp {{ number_format(optional($receiptCustomer)->balance ?? 0, 0, ',', '.') }}</dd>
            </dl>
        </section>
        <footer>
            <div>Status: {{ $transaction->status === 'completed' ? 'Berhasil' : ucfirst($transaction->status) }}</div>
            <div>Simpan struk ini sebagai bukti transaksi.</div>
        </footer>
        <p class="receipt-question">Apakah ingin melanjutkan transaksi?</p>
        <button id="receiptModalPrintButton" type="button" class="receipt-print btn btn-outline-primary">
            <i class="bi bi-printer me-1"></i> Cetak Struk
        </button>
    </section>
</template>
