<?php

namespace App\Services;

use App\Models\AccountingJournal;
use App\Models\Customer;
use App\Models\DailyReport;
use App\Models\PinSession;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class BankingTransactionService
{
    public function __construct(
        protected DailyReportService $dailyReportService
    ) {}

    /**
     * Proses transaksi setoran tunai lengkap dengan pembukuan jurnal berpasangan.
     */
    public function processDeposit(Customer $customer, int $amount, ?string $note, int $tellerId): Transaction
    {
        return DB::transaction(function () use ($customer, $amount, $note, $tellerId) {
            // 1. Update Saldo Nasabah
            $customer->balance += $amount;
            $customer->save();

            // 2. Buat Record Transaksi
            $trx = Transaction::create([
                'customer_id' => $customer->id,
                'teller_id'   => $tellerId,
                'type'        => 'deposit',
                'amount'      => $amount,
                'note'        => $note,
                'status'      => 'completed',
            ]);

            // 3. Jurnal Berpasangan Double Entry (101 Kas, 201 Tabungan)
            // Setoran: Kas Bertambah (Debit), Tabungan Bertambah (Kredit)
            AccountingJournal::create([
                'transaction_id' => $trx->id,
                'account_code'   => '101',
                'type'           => 'debit',
                'amount'         => $amount,
            ]);

            AccountingJournal::create([
                'transaction_id' => $trx->id,
                'account_code'   => '201',
                'type'           => 'credit',
                'amount'         => $amount,
            ]);

            // 4. Sinkronisasi Kas Harian
            $report = $this->dailyReportService->todayReport($tellerId);
            $this->dailyReportService->refreshSystemCash($report);

            return $trx;
        });
    }

    /**
     * Proses transaksi penarikan tunai lengkap dengan otorisasi PIN session dan jurnal.
     */
    public function processWithdrawal(
        Customer $customer,
        int $amount,
        ?string $note,
        int $tellerId,
        PinSession $pinSession
    ): Transaction {
        return DB::transaction(function () use ($customer, $amount, $note, $tellerId, $pinSession) {
            // 1. Potong Saldo Nasabah
            $customer->balance -= $amount;
            $customer->save();

            // 2. Buat Record Transaksi
            $trx = Transaction::create([
                'customer_id' => $customer->id,
                'teller_id'   => $tellerId,
                'type'        => 'withdrawal',
                'amount'      => $amount,
                'note'        => $note,
                'status'      => 'completed',
            ]);

            // 3. Jurnal Berpasangan Double Entry
            // Penarikan: Tabungan Berkurang (Debit), Kas Berkurang (Kredit)
            AccountingJournal::create([
                'transaction_id' => $trx->id,
                'account_code'   => '201',
                'type'           => 'debit',
                'amount'         => $amount,
            ]);

            AccountingJournal::create([
                'transaction_id' => $trx->id,
                'account_code'   => '101',
                'type'           => 'credit',
                'amount'         => $amount,
            ]);

            // 4. Sinkronisasi Kas Harian
            $report = $this->dailyReportService->todayReport($tellerId);
            $this->dailyReportService->refreshSystemCash($report);

            // 5. Invalidasi PIN Session sekali pakai
            $pinSession->update(['status' => 'expired']);

            return $trx;
        });
    }

    /**
     * Catat jurnal saldo awal saat akun anggota/nasabah baru diaktifkan.
     */
    public function recordMemberActivation(Customer $customer): void
    {
        AccountingJournal::create([
            'transaction_id' => null,
            'account_code'   => '101',
            'type'           => 'debit',
            'amount'         => $customer->balance,
        ]);

        AccountingJournal::create([
            'transaction_id' => null,
            'account_code'   => '201',
            'type'           => 'credit',
            'amount'         => $customer->balance,
        ]);
    }

    /**
     * Catat pemindahan kas harian teller yang disetujui admin ke kas brankas sekolah.
     */
    public function recordVaultTransfer(int $amount): void
    {
        AccountingJournal::create([
            'transaction_id' => null,
            'account_code'   => '301', // Kas Brankas
            'type'           => 'debit',
            'amount'         => $amount,
        ]);

        AccountingJournal::create([
            'transaction_id' => null,
            'account_code'   => '101', // Kas Teller
            'type'           => 'credit',
            'amount'         => $amount,
        ]);
    }
}

