<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class MonthlyReportService
{
    /**
     * Kumpulkan dan kalkulasi data ringkasan transaksi bulanan,
     * rekapitulasi per teller, dan audit laporan kas yang disetujui.
     */
    public function getMonthlyData(int $month, int $year, bool $includeTransactions = false): array
    {
        $startDate = now()->setDate($year, $month, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        $transactions = Transaction::query()
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->where('transactions.status', 'completed')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('daily_reports')
                    ->whereRaw('daily_reports.report_date = DATE(transactions.created_at)')
                    ->where('daily_reports.status', 'approved');
            });

        $summary = [
            'deposit'    => (clone $transactions)->where('transactions.type', 'deposit')->sum('transactions.amount'),
            'withdrawal' => (clone $transactions)->where('transactions.type', 'withdrawal')->sum('transactions.amount'),
            'count'      => (clone $transactions)->count(),
        ];

        $tellerSummaries = (clone $transactions)
            ->join('users', 'transactions.teller_id', '=', 'users.id')
            ->select('users.id', 'users.name')
            ->selectRaw("SUM(CASE WHEN transactions.type = 'deposit' THEN transactions.amount ELSE 0 END) as total_deposit")
            ->selectRaw("SUM(CASE WHEN transactions.type = 'withdrawal' THEN transactions.amount ELSE 0 END) as total_withdrawal")
            ->selectRaw('COUNT(transactions.id) as transaction_count')
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name')
            ->get();

        $dailyReports = DailyReport::with(['teller', 'reviewedBy'])
            ->whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('status', 'approved')
            ->orderByDesc('report_date')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $transactionDetails = $includeTransactions
            ? (clone $transactions)
                ->with(['customer.user', 'teller'])
                ->orderBy('transactions.created_at')
                ->orderBy('transactions.teller_id')
                ->get()
            : collect();

        return compact('month', 'year', 'summary', 'tellerSummaries', 'dailyReports', 'transactionDetails');
    }
}
