<?php

namespace Database\Seeders;

use App\Models\AccountingJournal;
use App\Models\Customer;
use App\Models\DailyReport;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $teller = User::where('role', 'teller')->first();
        $admin  = User::where('role', 'admin')->first();

        if (! $teller || ! $admin) {
            return;
        }

        $customers = Customer::all();
        if ($customers->isEmpty()) {
            return;
        }

        // ── Buat minimal 10 Daily Reports (10 hari berbeda) ──
        $baseDate = Carbon::now()->subDays(12);

        for ($day = 0; $day < 10; $day++) {
            $date = $baseDate->copy()->addDays($day)->toDateString();

            $report = DailyReport::firstOrCreate(
                ['report_date' => $date, 'teller_id' => $teller->id],
                [
                    'reviewed_by'        => $admin->id,
                    'initial_capital'    => 500000,
                    'additional_capital' => 0,
                    'total_deposit'      => 0,
                    'total_withdrawal'   => 0,
                    'system_cash'        => 500000,
                    'physical_cash'      => 500000,
                    'status'             => 'approved',
                ]
            );

            // Buat 2–3 transaksi untuk setiap hari (Total 20+ transaksi)
            $dayCustomers = $customers->slice($day % 3, 3);
            $dayDeposit    = 0;
            $dayWithdrawal = 0;

            foreach ($dayCustomers as $idx => $customer) {
                $isDeposit = ($idx % 2 === 0);
                $amount    = ($idx + 1) * 25000; // 25.000, 50.000, 75.000

                if ($isDeposit) {
                    $trx = Transaction::create([
                        'customer_id' => $customer->id,
                        'teller_id'   => $teller->id,
                        'type'        => 'deposit',
                        'amount'      => $amount,
                        'status'      => 'completed',
                        'note'        => 'Setoran tabungan harian',
                        'created_at'  => Carbon::parse($date)->addHours(8 + $idx * 2),
                        'updated_at'  => Carbon::parse($date)->addHours(8 + $idx * 2),
                    ]);

                    AccountingJournal::create([
                        'transaction_id' => $trx->id,
                        'account_code'   => '101',
                        'type'           => 'debit',
                        'amount'         => $amount,
                        'created_at'     => $trx->created_at,
                        'updated_at'     => $trx->updated_at,
                    ]);

                    AccountingJournal::create([
                        'transaction_id' => $trx->id,
                        'account_code'   => '201',
                        'type'           => 'credit',
                        'amount'         => $amount,
                        'created_at'     => $trx->created_at,
                        'updated_at'     => $trx->updated_at,
                    ]);

                    $dayDeposit += $amount;
                } else {
                    $safeAmount = 20000;
                    $trx = Transaction::create([
                        'customer_id' => $customer->id,
                        'teller_id'   => $teller->id,
                        'type'        => 'withdrawal',
                        'amount'      => $safeAmount,
                        'status'      => 'completed',
                        'note'        => 'Penarikan uang saku',
                        'created_at'  => Carbon::parse($date)->addHours(9 + $idx * 2),
                        'updated_at'  => Carbon::parse($date)->addHours(9 + $idx * 2),
                    ]);

                    AccountingJournal::create([
                        'transaction_id' => $trx->id,
                        'account_code'   => '201',
                        'type'           => 'debit',
                        'amount'         => $safeAmount,
                        'created_at'     => $trx->created_at,
                        'updated_at'     => $trx->updated_at,
                    ]);

                    AccountingJournal::create([
                        'transaction_id' => $trx->id,
                        'account_code'   => '101',
                        'type'           => 'credit',
                        'amount'         => $safeAmount,
                        'created_at'     => $trx->created_at,
                        'updated_at'     => $trx->updated_at,
                    ]);

                    $dayWithdrawal += $safeAmount;
                }
            }

            // Update saldo report jika masih 0
            if ($report->total_deposit == 0 && $report->total_withdrawal == 0) {
                $report->total_deposit    = $dayDeposit;
                $report->total_withdrawal = $dayWithdrawal;
                $report->system_cash      = $report->initial_capital + $dayDeposit - $dayWithdrawal;
                $report->physical_cash    = $report->system_cash;
                $report->save();
            }
        }
    }
}
