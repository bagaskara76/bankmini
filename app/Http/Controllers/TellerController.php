<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DailyReport;
use App\Models\PinSession;
use App\Models\Transaction;
use App\Services\BankingTransactionService;
use App\Services\DailyReportService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class TellerController extends Controller
{
    public function __construct(
        protected DailyReportService $dailyReportService,
        protected BankingTransactionService $transactionService
    ) {}

    /**
     * Dashboard operasional kas harian teller.
     */
    public function dashboard()
    {
        $today = now()->toDateString();
        $dailyReport = $this->dailyReportService->todayReport();
        $this->dailyReportService->refreshSystemCash($dailyReport);

        $totalDeposit = (int) Transaction::whereDate('created_at', $today)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');

        $countDeposit = Transaction::whereDate('created_at', $today)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->count();

        $totalWithdrawal = (int) Transaction::whereDate('created_at', $today)
            ->where('type', 'withdrawal')
            ->where('status', 'completed')
            ->sum('amount');

        $countWithdrawal = Transaction::whereDate('created_at', $today)
            ->where('type', 'withdrawal')
            ->where('status', 'completed')
            ->count();

        $transactions = Transaction::with(['customer.user', 'teller'])
            ->whereDate('created_at', $today)
            ->where('status', 'completed')
            ->latest()
            ->get();

        return view('teller.dashboard', compact(
            'totalDeposit',
            'countDeposit',
            'totalWithdrawal',
            'countWithdrawal',
            'dailyReport',
            'transactions'
        ));
    }

    /**
     * Catat modal awal fisik teller pada awal jam operasional.
     */
    public function addInitialCapital(Request $request)
    {
        $validated = $request->validate(['initial_capital' => 'required|integer|min:0']);
        $report = $this->dailyReportService->todayReport();

        if ($report->status !== 'draft') {
            return back()->with('error', 'Laporan yang sudah diajukan tidak dapat diubah.');
        }

        if ($report->initial_capital > 0) {
            return back()->with('error', 'Modal awal hari ini sudah tersimpan. Gunakan menu Tambah Modal Awal untuk menambah kas.');
        }

        $report->initial_capital = $validated['initial_capital'];
        $this->dailyReportService->refreshSystemCash($report);

        return back()->with('success', 'Modal awal berhasil dicatat.');
    }

    /**
     * Catat penambahan modal kas di tengah operasional berjalan.
     */
    public function addAdditionalCapital(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
        ], [
            'amount.required' => 'Nominal modal tambahan wajib diisi.',
            'amount.integer'  => 'Nominal modal tambahan harus berupa bilangan bulat.',
            'amount.min'      => 'Nominal modal tambahan harus lebih dari 0.',
        ]);

        $report = $this->dailyReportService->todayReport();

        if ($report->status !== 'draft') {
            return back()->with('error', 'Modal tambahan tidak dapat dicatat karena laporan harian sudah diajukan.');
        }

        if ($report->initial_capital <= 0) {
            return back()->with('error', 'Catat modal awal hari ini sebelum menambahkan modal.');
        }

        $added = $this->dailyReportService->addAdditionalCapital($validated['amount']);

        if (! $added) {
            return back()->with('error', 'Laporan harian tidak lagi dapat menerima modal tambahan.');
        }

        return back()->with('success', 'Modal tambahan berhasil dicatat ke kas harian.');
    }

    /**
     * Kirim laporan kas harian ke admin untuk verifikasi.
     */
    public function submitDailyReport(Request $request)
    {
        $report = $this->dailyReportService->todayReport();

        if (! in_array($report->status, ['draft', 'rejected'], true)) {
            return back()->with('error', 'Laporan ini sudah diajukan atau diproses.');
        }

        $validated = $request->validate([
            'physical_cash' => 'required|integer|min:0',
        ], [
            'physical_cash.required' => 'Kas fisik hasil penghitungan wajib diisi.',
            'physical_cash.integer'  => 'Kas fisik harus berupa bilangan bulat.',
            'physical_cash.min'      => 'Kas fisik tidak boleh kurang dari 0.',
        ]);

        $this->dailyReportService->refreshSystemCash($report);
        $report->teller_id      = Auth::id();
        $report->physical_cash  = $validated['physical_cash'];
        $report->status         = 'pending';
        $report->reviewed_by    = null;
        $report->rejection_note = null;
        $report->save();

        return back()->with('success', 'Laporan harian berhasil dikirim ulang untuk diperiksa admin.');
    }

    /**
     * Buka kembali laporan kas harian ke status DRAFT (batalkan pengajuan).
     * Memungkinkan teller melanjutkan transaksi nasabah kembali.
     */
    public function reopenDailyReport()
    {
        $report = $this->dailyReportService->todayReport();

        $report->status         = 'draft';
        $report->physical_cash  = 0;
        $report->reviewed_by    = null;
        $report->rejection_note = null;
        $report->save();

        return back()->with('success', 'Kas harian berhasil dibuka kembali ke status DRAFT. Anda sekarang dapat melanjutkan transaksi!');
    }

    /**
     * Tampilkan formulir transaksi setoran dan penarikan nasabah.
     */
    public function showTransaction(Customer $customer)
    {
        $dailyReport = $this->dailyReportService->todayReport();
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

    /**
     * Cari nasabah berdasarkan QR Code atau Nomor Rekening.
     */
    public function searchCustomer(Request $request)
    {
        $qrCode        = $request->input('qr_code');
        $accountNumber = $request->input('account_number');

        $customer = Customer::with('user');

        if ($qrCode) {
            $customer = $customer->where('qr_code_string', $qrCode);
        } elseif ($accountNumber) {
            $customer = $customer->where('account_number', $accountNumber);
        } else {
            return back()->with('error', 'Masukkan QR Code atau Nomor Rekening');
        }

        $customer = $customer->first();

        if (! $customer) {
            return back()->with('error', 'Nasabah tidak ditemukan');
        }

        $dailyReport = $this->dailyReportService->todayReport();

        return view('teller.transaction', compact('customer', 'dailyReport'));
    }

    /**
     * Proses transaksi setoran tunai nasabah.
     */
    public function processDeposit(Request $request, $customer_id)
    {
        $amount = $this->sanitizeAmount($request->input('amount'));
        $request->merge(['amount' => $amount]);

        $request->validate([
            'amount' => 'required|integer|min:1',
            'note'   => 'nullable|string|max:500',
        ]);

        if ($this->dailyReportService->todayReport()->status !== 'draft') {
            return back()->with('error', 'Transaksi baru tidak dapat diproses karena laporan harian sudah diajukan.');
        }

        $customer = Customer::findOrFail($customer_id);
        $note     = $request->input('note');

        try {
            $transaction = $this->transactionService->processDeposit(
                $customer,
                $amount,
                $note,
                Auth::id()
            );

            return redirect()->route('teller.transaction', $customer->id)
                ->with('success', 'Setoran tunai berhasil. Saldo saat ini: Rp ' . number_format($customer->balance, 0, ',', '.'))
                ->with('receiptTransactionId', $transaction->id);
        } catch (Exception $e) {
            Log::error('Gagal memproses setoran.', [
                'customer_id' => $customer->id,
                'teller_id'   => Auth::id(),
                'exception'   => $e->getMessage(),
            ]);

            return redirect()->route('teller.transaction', $customer->id)
                ->with('error', 'Setoran gagal diproses. Saldo tidak berubah. Silakan periksa kembali dan ulangi.');
        }
    }

    /**
     * Proses transaksi penarikan tunai nasabah dengan otorisasi PIN Mobile.
     */
    public function processWithdrawal(Request $request, $customer_id)
    {
        $amount = $this->sanitizeAmount($request->input('amount'));
        $request->merge(['amount' => $amount]);

        $request->validate([
            'amount'            => 'required|integer|min:1',
            'pin_session_token' => 'required|string',
            'note'              => 'nullable|string|max:500',
        ], [
            'amount.required'            => 'Nominal penarikan wajib diisi.',
            'amount.integer'             => 'Nominal penarikan harus berupa angka.',
            'amount.min'                 => 'Nominal penarikan harus lebih dari 0.',
            'pin_session_token.required' => 'Verifikasi PIN nasabah diperlukan.',
        ]);

        $report = $this->dailyReportService->todayReport();

        if ($report->status !== 'draft') {
            return back()->with('error', 'Transaksi baru tidak dapat diproses karena laporan harian sudah diajukan.');
        }

        if ($report->initial_capital <= 0) {
            return back()->with('error', 'Penarikan tidak dapat diproses sebelum teller mencatat modal awal hari ini.');
        }

        // Validasi PIN Session
        $pinSession = PinSession::where('token', $request->pin_session_token)
            ->where('customer_id', $customer_id)
            ->where('teller_id', Auth::id())
            ->where('status', 'verified')
            ->first();

        if (! $pinSession) {
            return back()->with('error', 'Sesi verifikasi PIN tidak valid atau sudah kadaluarsa. Ulangi proses verifikasi PIN.');
        }

        // Validasi kesesuaian nominal dengan sesi PIN
        if ((int) $pinSession->amount !== $amount) {
            return back()->with('error', 'Nominal penarikan tidak sesuai dengan yang telah diverifikasi. Ulangi proses.');
        }

        $customer = Customer::findOrFail($customer_id);
        $note     = $request->input('note', $pinSession->note);

        // Validasi saldo minimum mengendap
        if (($customer->balance - $amount) < Customer::MINIMUM_BALANCE) {
            return redirect()->route('teller.transaction', $customer->id)
                ->with('error', 'Maaf, saldo tidak mencukupi.');
        }

        try {
            $transaction = $this->transactionService->processWithdrawal(
                $customer,
                $amount,
                $note,
                Auth::id(),
                $pinSession
            );

            return redirect()->route('teller.transaction', $customer->id)
                ->with('success', 'Penarikan tunai berhasil. Sisa Saldo: Rp ' . number_format($customer->balance, 0, ',', '.'))
                ->with('receiptTransactionId', $transaction->id);
        } catch (Exception $e) {
            Log::error('Gagal memproses penarikan.', [
                'customer_id' => $customer->id,
                'teller_id'   => Auth::id(),
                'exception'   => $e->getMessage(),
            ]);

            return redirect()->route('teller.transaction', $customer->id)
                ->with('error', 'Penarikan gagal diproses. Saldo tidak berubah. Silakan periksa kembali dan ulangi.');
        }
    }

    /**
     * Formulir ganti password akun teller.
     */
    public function showChangePassword()
    {
        return view('teller.change_password');
    }

    /**
     * Simpan password baru akun teller.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required'         => 'Password baru wajib diisi.',
            'password.min'              => 'Password baru minimal 12 karakter.',
            'password.mixed_case'       => 'Password harus mengandung huruf kapital dan huruf kecil.',
            'password.numbers'          => 'Password harus mengandung minimal satu angka.',
            'password.symbols'          => 'Password harus mengandung minimal satu karakter spesial.',
            'password.confirmed'        => 'Konfirmasi password baru tidak cocok.',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
        }

        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Password baru tidak boleh sama dengan password lama.']);
        }

        $user->password       = Hash::make($request->password);
        $user->plain_password = $request->password;
        $user->save();

        return back()->with('success', 'Password berhasil diperbarui.');
    }

    // ─────────────────────────────────────────────────────────────
    // Helper Methods Privat
    // ─────────────────────────────────────────────────────────────

    /**
     * Konversi string nominal yang memiliki format titik/koma menjadi angka integer bersih.
     */
    private function sanitizeAmount(mixed $amount): int
    {
        return (int) preg_replace('/\D/', '', (string) $amount);
    }

    /**
     * Kompatibilitas mundur untuk pemanggilan internal todayReport.
     */
    private function todayReport(): DailyReport
    {
        return $this->dailyReportService->todayReport();
    }

    /**
     * Kompatibilitas mundur untuk pemanggilan internal refreshSystemCash.
     */
    private function refreshSystemCash(DailyReport $report): void
    {
        $this->dailyReportService->refreshSystemCash($report);
    }
}
