<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\AccountingJournal;
use App\Models\DailyReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TellerController extends Controller
{
    public function dashboard()
    {
        $today = now()->toDateString();
        $dailyReport = DailyReport::firstOrCreate(
            ['teller_id' => Auth::id(), 'report_date' => $today],
            ['status' => 'draft']
        );

        $totalDeposit = Transaction::where('teller_id', Auth::id())
            ->where('type', 'deposit')
            ->whereDate('created_at', $today)
            ->sum('amount');

        $countDeposit = Transaction::where('teller_id', Auth::id())
            ->where('type', 'deposit')
            ->whereDate('created_at', $today)
            ->count();

        $totalWithdrawal = Transaction::where('teller_id', Auth::id())
            ->where('type', 'withdrawal')
            ->whereDate('created_at', $today)
            ->sum('amount');

        $countWithdrawal = Transaction::where('teller_id', Auth::id())
            ->where('type', 'withdrawal')
            ->whereDate('created_at', $today)
            ->count();

        $transactions = Transaction::with('customer.user')
            ->where('teller_id', Auth::id())
            ->whereDate('created_at', $today)
            ->latest()
            ->get();

        return view('teller.dashboard', compact('totalDeposit', 'countDeposit', 'totalWithdrawal', 'countWithdrawal', 'dailyReport', 'transactions'));
    }

    public function addInitialCapital(Request $request)
    {
        $validated = $request->validate(['initial_capital' => 'required|integer|min:0']);
        $report = $this->todayReport();

        if ($report->status !== 'draft') {
            return back()->with('error', 'Laporan yang sudah diajukan tidak dapat diubah.');
        }

        if ($report->initial_capital > 0) {
            return back()->with('error', 'Modal awal hari ini sudah tersimpan. Gunakan menu Tambah Modal Awal untuk menambah kas.');
        }

        $report->initial_capital = $validated['initial_capital'];
        $this->refreshSystemCash($report);

        return back()->with('success', 'Modal awal berhasil dicatat.');
    }

    public function addAdditionalCapital(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ], [
            'amount.required' => 'Nominal modal tambahan wajib diisi.',
            'amount.integer' => 'Nominal modal tambahan harus berupa bilangan bulat.',
            'amount.min' => 'Nominal modal tambahan harus lebih dari 0.',
        ]);

        $report = $this->todayReport();

        if ($report->status !== 'draft') {
            return back()->with('error', 'Modal tambahan tidak dapat dicatat karena laporan harian sudah diajukan.');
        }

        if ($report->initial_capital <= 0) {
            return back()->with('error', 'Catat modal awal hari ini sebelum menambahkan modal.');
        }

        $added = DB::transaction(function () use ($validated) {
            $report = DailyReport::query()
                ->where('teller_id', Auth::id())
                ->whereDate('report_date', now()->toDateString())
                ->lockForUpdate()
                ->firstOrFail();

            if ($report->status !== 'draft' || $report->initial_capital <= 0) {
                return false;
            }

            $report->additional_capital += $validated['amount'];
            $report->system_cash = $report->initial_capital + $report->additional_capital
                + $report->total_deposit - $report->total_withdrawal;
            $report->save();

            return true;
        });

        if (!$added) {
            return back()->with('error', 'Laporan harian tidak lagi dapat menerima modal tambahan.');
        }

        return back()->with('success', 'Modal tambahan berhasil dicatat ke kas harian.');
    }

    public function submitDailyReport(Request $request)
    {
        $report = $this->todayReport();

        if (!in_array($report->status, ['draft', 'rejected'], true)) {
            return back()->with('error', 'Laporan ini sudah diajukan atau diproses.');
        }

        $validated = $request->validate([
            'physical_cash' => 'required|integer|min:0',
        ], [
            'physical_cash.required' => 'Kas fisik hasil penghitungan wajib diisi.',
            'physical_cash.integer' => 'Kas fisik harus berupa bilangan bulat.',
            'physical_cash.min' => 'Kas fisik tidak boleh kurang dari 0.',
        ]);

        $report->system_cash = $report->initial_capital + $report->additional_capital
            + $report->total_deposit - $report->total_withdrawal;
        $report->physical_cash = $validated['physical_cash'];
        $report->status = 'pending';
        $report->reviewed_by = null;
        $report->rejection_note = null;
        $report->save();

        return back()->with('success', 'Laporan harian berhasil dikirim ulang untuk diperiksa admin.');
    }

    private function todayReport(): DailyReport
    {
        return DailyReport::firstOrCreate(
            ['teller_id' => Auth::id(), 'report_date' => now()->toDateString()],
            ['status' => 'draft']
        );
    }

    private function refreshSystemCash(DailyReport $report): void
    {
        $report->system_cash = $report->initial_capital + $report->additional_capital
            + $report->total_deposit - $report->total_withdrawal;
        $report->save();
    }

    public function showTransaction(Customer $customer)
    {
        $dailyReport = $this->todayReport();
        $receiptTransaction = null;
        $receiptTransactionId = session('receiptTransactionId');

        if ($receiptTransactionId) {
            $receiptTransaction = Transaction::with(['customer.user', 'teller'])
                ->where('teller_id', Auth::id())
                ->where('customer_id', $customer->id)
                ->find($receiptTransactionId);
        }

        return view('teller.transaction', compact('customer', 'dailyReport', 'receiptTransaction'));
    }

    public function searchCustomer(Request $request)
    {
        $qr_code = $request->input('qr_code');
        $account_number = $request->input('account_number');

        $customer = Customer::with('user');

        if ($qr_code) {
            $customer = $customer->where('qr_code_string', $qr_code);
        } elseif ($account_number) {
            $customer = $customer->where('account_number', $account_number);
        } else {
            return back()->with('error', 'Masukkan QR Code atau Nomor Rekening');
        }

        $customer = $customer->first();

        if (!$customer) {
            return back()->with('error', 'Nasabah tidak ditemukan');
        }

        $dailyReport = $this->todayReport();

        return view('teller.transaction', compact('customer', 'dailyReport'));
    }

    public function processDeposit(Request $request, $customer_id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:500',
        ]);

        if ($this->todayReport()->status !== 'draft') {
            return back()->with('error', 'Transaksi baru tidak dapat diproses karena laporan harian sudah diajukan.');
        }

        $customer = Customer::findOrFail($customer_id);
        $amount = $request->amount;
        $note = $request->input('note');

        try {
            $transaction = DB::transaction(function () use ($customer, $amount, $note) {
                // Update Balance
                $customer->balance += $amount;
                $customer->save();

                // Create Transaction
                $trx = Transaction::create([
                    'customer_id' => $customer->id,
                    'teller_id' => Auth::id(),
                    'type' => 'deposit',
                    'amount' => $amount,
                    'note' => $note,
                    'status' => 'completed'
                ]);

                // Double Entry Journal (101 Kas, 201 Tabungan)
                // Setoran: Kas Bertambah (Debit), Tabungan Bertambah (Kredit)
                AccountingJournal::create([
                    'transaction_id' => $trx->id,
                    'account_code' => '101', // Kas
                    'type' => 'debit',
                    'amount' => $amount
                ]);

                AccountingJournal::create([
                    'transaction_id' => $trx->id,
                    'account_code' => '201', // Tabungan
                    'type' => 'credit',
                    'amount' => $amount
                ]);

                $report = DailyReport::firstOrCreate(
                    [
                        'teller_id' => Auth::id(),
                        'report_date' => now()->toDateString(),
                    ],
                    [
                        'status' => 'draft',
                    ]
                );

                $report->total_deposit += $amount;
                $report->system_cash = $report->initial_capital + $report->additional_capital
                    + $report->total_deposit - $report->total_withdrawal;
                $report->save();

                return $trx;
            });

            return redirect()->route('teller.transaction', $customer->id)
                ->with('success', 'Setoran tunai berhasil. Saldo saat ini: Rp ' . number_format($customer->balance, 0, ',', '.'))
                ->with('receiptTransactionId', $transaction->id);
        } catch (\Exception $e) {
            Log::error('Gagal memproses setoran.', [
                'customer_id' => $customer->id,
                'teller_id' => Auth::id(),
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('teller.transaction', $customer->id)
                ->with('error', 'Setoran gagal diproses. Saldo tidak berubah. Silakan periksa kembali dan ulangi.');
        }
    }

    public function processWithdrawal(Request $request, $customer_id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'pin' => 'required|digits:6',
            'note' => 'nullable|string|max:500',
        ], [
            'amount.required' => 'Nominal penarikan wajib diisi.',
            'amount.numeric' => 'Nominal penarikan harus berupa angka.',
            'amount.min' => 'Nominal penarikan harus lebih dari 0.',
            'pin.required' => 'PIN wajib diisi.',
            'pin.digits' => 'PIN harus terdiri dari 6 angka.',
        ]);

        $report = $this->todayReport();

        if ($report->status !== 'draft') {
            return back()->with('error', 'Transaksi baru tidak dapat diproses karena laporan harian sudah diajukan.');
        }

        if ($report->initial_capital <= 0) {
            return back()->with('error', 'Penarikan tidak dapat diproses sebelum teller mencatat modal awal hari ini.');
        }

        $customer = Customer::findOrFail($customer_id);
        $amount = $request->amount;
        $pin = $request->pin;
        $note = $request->input('note');

        // Validasi PIN
        if (!Hash::check($pin, $customer->pin)) {
            return back()->with('error', 'PIN salah.');
        }

        // Validate the required minimum balance retained in the account.
        if (($customer->balance - $amount) < Customer::MINIMUM_BALANCE) {
            return redirect()->route('teller.transaction', $customer->id)
                ->with('error', 'maaf saldo tidak mencukupi');
        }

        try {
            $transaction = DB::transaction(function () use ($customer, $amount, $note) {
                // Update Balance
                $customer->balance -= $amount;
                $customer->save();

                // Create Transaction
                $trx = Transaction::create([
                    'customer_id' => $customer->id,
                    'teller_id' => Auth::id(),
                    'type' => 'withdrawal',
                    'amount' => $amount,
                    'note' => $note,
                    'status' => 'completed'
                ]);

                // Double Entry Journal (101 Kas, 201 Tabungan)
                // Penarikan: Tabungan Berkurang (Debit), Kas Berkurang (Kredit)
                AccountingJournal::create([
                    'transaction_id' => $trx->id,
                    'account_code' => '201', // Tabungan
                    'type' => 'debit',
                    'amount' => $amount
                ]);

                AccountingJournal::create([
                    'transaction_id' => $trx->id,
                    'account_code' => '101', // Kas
                    'type' => 'credit',
                    'amount' => $amount
                ]);

                $report = DailyReport::firstOrCreate(
                    [
                        'teller_id' => Auth::id(),
                        'report_date' => now()->toDateString(),
                    ],
                    [
                        'status' => 'draft',
                    ]
                );

                $report->total_withdrawal += $amount;
                $report->system_cash = $report->initial_capital + $report->additional_capital
                    + $report->total_deposit - $report->total_withdrawal;
                $report->save();

                return $trx;
            });

            return redirect()->route('teller.transaction', $customer->id)
                ->with('success', 'Penarikan tunai berhasil. Sisa Saldo: Rp '.number_format($customer->balance, 0, ',', '.'))
                ->with('receiptTransactionId', $transaction->id);
        } catch (\Exception $e) {
            Log::error('Gagal memproses penarikan.', [
                'customer_id' => $customer->id,
                'teller_id' => Auth::id(),
                'exception' => $e->getMessage(),
            ]);

            return redirect()->route('teller.transaction', $customer->id)
                ->with('error', 'Penarikan gagal diproses. Saldo tidak berubah. Silakan periksa kembali dan ulangi.');
        }
    }
}
