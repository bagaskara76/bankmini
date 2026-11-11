<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DailyReportService
{
    /**
     * Dapatkan atau buat laporan kas harian untuk hari ini.
     */
    public function todayReport(?int $tellerId = null): DailyReport
    {
        $today = now()->toDateString();
        $report = DailyReport::whereDate('report_date', $today)->first();

        if (! $report) {
            $report = DailyReport::create([
                'teller_id'          => $tellerId ?? Auth::id(),
                'report_date'        => $today,
                'status'             => 'draft',
                'initial_capital'    => 0,
                'additional_capital' => 0,
                'total_deposit'      => 0,
                'total_withdrawal'   => 0,
                'system_cash'        => 0,
                'physical_cash'      => 0,
            ]);
        }

        return $report;
    }

    /**
     * Hitung ulang total deposit, penarikan, dan saldo sistem kas harian.
     */
    public function refreshSystemCash(DailyReport $report): void
    {
        $date = $report->getRawOriginal('report_date') ?? $report->report_date->toDateString();

        $totalDeposit = (int) Transaction::whereDate('created_at', $date)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');

        $totalWithdrawal = (int) Transaction::whereDate('created_at', $date)
            ->where('type', 'withdrawal')
            ->where('status', 'completed')
            ->sum('amount');

        $report->total_deposit    = $totalDeposit;
        $report->total_withdrawal = $totalWithdrawal;
        $report->system_cash      = (int) $report->initial_capital
            + (int) $report->additional_capital
            + $totalDeposit
            - $totalWithdrawal;
        $report->save();
    }

    /**
     * Tambahkan modal kas tambahan hari ini dengan lock database yang aman.
     */
    public function addAdditionalCapital(int $amount): bool
    {
        return DB::transaction(function () use ($amount) {
            $today = now()->toDateString();
            $report = DailyReport::query()
                ->whereDate('report_date', $today)
                ->lockForUpdate()
                ->firstOrFail();

            if ($report->status !== 'draft' || $report->initial_capital <= 0) {
                return false;
            }

            $report->additional_capital += $amount;
            $this->refreshSystemCash($report);

            return true;
        });
    }
}

