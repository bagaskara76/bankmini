<?php

namespace App\Http\Controllers;

use App\Models\AccountingJournal;
use App\Models\Customer;
use App\Models\DailyReport;
use App\Models\PendingMember;
use App\Models\RecoveryOtp;
use App\Models\Transaction;
use App\Models\User;
use App\Services\MemberImportNormalizer;
use App\Services\MemberXlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function verifyReports()
    {
        $reports = DailyReport::with('teller')->where('status', 'pending')->orderBy('report_date', 'desc')->get();

        return view('admin.verify_reports', compact('reports'));
    }

    public function reviewReport(DailyReport $report)
    {
        abort_unless($report->status === 'pending', 404);

        $report->load('teller');
        $transactions = Transaction::with('customer.user')
            ->where('teller_id', $report->teller_id)
            ->whereDate('created_at', $report->getRawOriginal('report_date'))
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->get();

        return view('admin.review_report', compact('report', 'transactions'));
    }

    public function monthlyReports(Request $request)
    {
        $data = $this->monthlyReportData($request);

        return view('admin.monthly_reports', $data);
    }

    public function monthlyReportsPdf(Request $request)
    {
        $data = $this->monthlyReportData($request, true);
        $period = sprintf('%04d-%02d', $data['year'], $data['month']);

        return Pdf::loadView('admin.monthly_report_pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download('laporan-bulanan-'.$period.'.pdf');
    }

    private function monthlyReportData(Request $request, bool $includeTransactions = false): array
    {
        $validated = $request->validate([
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:2000,2100',
        ]);

        $month = (int) ($validated['month'] ?? now()->month);
        $year = (int) ($validated['year'] ?? now()->year);
        $startDate = now()->setDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $transactions = Transaction::query()
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->where('transactions.status', 'completed')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('daily_reports')
                    ->whereColumn('daily_reports.teller_id', 'transactions.teller_id')
                    ->whereRaw('daily_reports.report_date = DATE(transactions.created_at)')
                    ->where('daily_reports.status', 'approved');
            });

        $summary = [
            'deposit' => (clone $transactions)->where('transactions.type', 'deposit')->sum('transactions.amount'),
            'withdrawal' => (clone $transactions)->where('transactions.type', 'withdrawal')->sum('transactions.amount'),
            'count' => (clone $transactions)->count(),
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

    public function approveReport(Request $request, DailyReport $report)
    {
        try {
            $error = DB::transaction(function () use ($report) {
                $lockedReport = DailyReport::query()
                    ->lockForUpdate()
                    ->findOrFail($report->id);

                if ($lockedReport->status !== 'pending') {
                    return 'Laporan ini tidak sedang menunggu approval.';
                }

                if ((int) $lockedReport->physical_cash !== (int) $lockedReport->system_cash) {
                    return 'Laporan tidak dapat di-approve karena kas fisik tidak balance. Gunakan tombol Reject.';
                }

                $lockedReport->reviewed_by = Auth::id();
                $lockedReport->status = 'approved';
                $lockedReport->save();

                $amount = $lockedReport->system_cash;
                AccountingJournal::create([
                    'transaction_id' => null,
                    'account_code' => '301',
                    'type' => 'debit',
                    'amount' => $amount,
                ]);
                AccountingJournal::create([
                    'transaction_id' => null,
                    'account_code' => '101',
                    'type' => 'credit',
                    'amount' => $amount,
                ]);

                return null;
            });

            if ($error !== null) {
                return back()->with('error', $error);
            }

            return redirect()->route('admin.verify_reports')->with('success', 'Laporan disetujui dan dipindahkan ke brankas.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyetujui laporan: '.$e->getMessage());
        }
    }

    public function rejectReport(Request $request, DailyReport $report)
    {
        if ($report->status !== 'pending') {
            return back()->with('error', 'Laporan ini tidak sedang menunggu approval.');
        }

        $validated = $request->validate([
            'rejection_note' => 'required|string|max:2000',
        ], [
            'rejection_note.required' => 'Catatan alasan penolakan wajib diisi.',
            'rejection_note.max' => 'Catatan alasan penolakan maksimal 2000 karakter.',
        ]);

        $report->reviewed_by = Auth::id();
        $report->status = 'rejected';
        $report->rejection_note = $validated['rejection_note'];
        $report->save();

        return redirect()->route('admin.verify_reports')->with('success', 'Laporan ditolak dan catatan berhasil dikirim ke teller.');
    }

    public function printReport(DailyReport $report)
    {
        abort_unless($report->status === 'approved', 404);

        $report->load(['teller', 'reviewedBy']);
        $transactions = Transaction::with('customer.user')
            ->where('teller_id', $report->teller_id)
            ->whereDate('created_at', $report->getRawOriginal('report_date'))
            ->orderBy('created_at')
            ->get();

        return Pdf::loadView('admin.daily_report_pdf', compact('report', 'transactions'))
            ->download('laporan-harian-'.$report->id.'.pdf');
    }

    public function disputeResolution()
    {
        $reports = DailyReport::where('status', 'rejected')->orderBy('report_date', 'desc')->get();

        return view('admin.dispute_resolution', compact('reports'));
    }

    public function transferApproval()
    {
        $reports = DailyReport::where('status', 'approved')
            ->orderByDesc('report_date')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.transfer_approval', compact('reports'));
    }

    public function storeMember(Request $request)
    {
        $validKelas = implode(',', Customer::availableKelas($request->input('grade')));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email', 'unique:pending_members,email'],
            'grade' => ['required', 'in:10,11,12'],
            'kelas' => ['required', 'in:'.$validKelas],
        ], [
            'email.unique' => 'Email sudah digunakan oleh anggota lain.',
            'grade.required' => 'Tingkat kelas (10/11/12) wajib dipilih.',
            'grade.in' => 'Tingkat kelas harus 10, 11, atau 12.',
            'kelas.required' => 'Kelas wajib dipilih.',
            'kelas.in' => 'Kelas yang dipilih tidak valid.',
        ]);

        PendingMember::create([
            'name' => $validated['name'],
            'email' => isset($validated['email']) ? strtolower($validated['email']) : null,
            'grade' => $validated['grade'],
            'kelas' => $validated['kelas'],
        ]);

        return redirect()->route('admin.members.index')
            ->with('success', 'Data anggota ditambahkan. Akun belum dibuat dan menunggu aktivasi admin.');
    }

    public function downloadMemberTemplate(MemberXlsx $xlsx)
    {
        return response($xlsx->template(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="template-anggota.xlsx"',
        ]);
    }

    public function importMembers(Request $request, MemberXlsx $xlsx, MemberImportNormalizer $normalizer)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx'],
        ], [
            'file.required' => 'Pilih dokumen XLSX yang akan diimpor.',
            'file.extensions' => 'Dokumen harus berformat .xlsx.',
        ]);

        try {
            $rows = $xlsx->read($validated['file']->getRealPath());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        if ($rows === []) {
            return back()->withErrors(['file' => 'Dokumen XLSX belum berisi data anggota.']);
        }

        $normalizedRows = [];
        $errors = [];
        $errorCount = 0;
        $seenErrorRows = [];
        $seenEmails = [];

        foreach ($rows as $row) {
            $data = $row['data'];
            $data['name'] = trim($data['name']);
            $data['email'] = strtolower(trim($data['email']));
            if ($data['email'] === '') {
                $data['email'] = null;
            }
            $data['grade'] = trim($data['grade']);
            $data['kelas'] = trim($data['kelas']);
            $data['grade'] = $normalizer->normalizeGrade($data['grade']);
            $data['kelas'] = $normalizer->normalizeClass($data['kelas'], $data['grade']);

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'grade' => ['required', 'in:10,11,12'],
                'kelas' => ['required', 'in:'.implode(',', Customer::availableKelas($data['grade']))],
            ], [
                'grade.in' => 'Tingkat harus 10, 11, 12 atau X, XI, XII.',
                'kelas.in' => 'Nilai kelas "'.$data['kelas'].'" tidak sesuai dengan tingkat '.$data['grade'].'. Untuk tingkat 10/11, pilih LPS 1 atau LPS 2; untuk tingkat 12, pilih LPS 1.',
            ]);

            $rowErrors = $validator->errors()->all();

            $emailKey = $data['email'];
            if ($emailKey !== null && isset($seenEmails[$emailKey])) {
                $rowErrors[] = 'Email duplikat di dalam file.';
            }
            if ($emailKey !== null) {
                $seenEmails[$emailKey] = true;
            }

            if ($rowErrors !== []) {
                $errorCount += count($rowErrors);
                if (count($seenErrorRows) < 10) {
                    $seenErrorRows[] = 'Baris '.$row['row'].': '.implode(' ', $rowErrors);
                }
            }

            $normalizedRows[] = ['row' => $row['row'], 'data' => $data];
        }

        $existingEmails = [];
        foreach (array_chunk(array_keys($seenEmails), 500) as $emailChunk) {
            $existingEmails = array_merge(
                $existingEmails,
                User::query()->whereIn('email', $emailChunk)
                    ->pluck('email')->map(fn ($email) => strtolower($email))->all()
            );
            $existingEmails = array_merge(
                $existingEmails,
                PendingMember::query()->whereIn('email', $emailChunk)
                    ->pluck('email')->map(fn ($email) => strtolower($email))->all()
            );
        }

        foreach ($normalizedRows as $row) {
            $data = $row['data'];
            if ($data['email'] !== null && in_array($data['email'], $existingEmails, true)) {
                $errorCount++;
                if (count($seenErrorRows) < 10) {
                    $seenErrorRows[] = 'Baris '.$row['row'].': Email sudah terdaftar.';
                }
            }
        }

        if ($errorCount > 0) {
            $displayErrors = $seenErrorRows;
            if ($errorCount > count($displayErrors)) {
                $displayErrors[] = 'Total '.$errorCount.' kesalahan; hanya 10 baris pertama yang ditampilkan.';
            }

            return back()->withErrors(['file' => implode(' ', $displayErrors)]);
        }

        try {
            DB::transaction(function () use ($normalizedRows) {
                foreach ($normalizedRows as $row) {
                    $data = $row['data'];
                    PendingMember::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'grade' => $data['grade'],
                        'kelas' => $data['kelas'],
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Failed to import pending members from XLSX: '.$e->getMessage());

            return back()->with('error', 'Impor anggota gagal. Tidak ada data yang disimpan; periksa log aplikasi.');
        }

        return redirect()->route('admin.members.index')
            ->with('success', count($normalizedRows).' anggota berhasil diimpor dan menunggu aktivasi.');
    }

    public function users()
    {
        $users = User::whereIn('role', ['admin', 'teller'])
            ->orderBy('name')
            ->get();

        return view('admin.users', compact('users'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:5',
            'role' => 'required|in:admin,teller',
        ], [
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password harus terdiri dari minimal 5 karakter.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'plain_password' => $request->password,
            'role' => $request->role,
        ]);

        return redirect()->route('admin.users')->with('success', 'User berhasil dibuat.');
    }

    public function masterAccounts()
    {
        return redirect()->route('admin.members.index');
    }

    public function members(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $memberSearch = trim($validated['search'] ?? '');

        $pendingMembers = PendingMember::query()
            ->when($memberSearch !== '', fn ($query) => $query->where('name', 'like', '%'.$memberSearch.'%'))
            ->orderBy('grade')
            ->orderBy('kelas')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();
        $pendingMemberCount = PendingMember::count();
        $gradeList = [10, 11, 12];
        $kelasList = Customer::availableKelas();

        return view('admin.master_accounts', compact(
            'pendingMembers',
            'pendingMemberCount',
            'gradeList',
            'kelasList',
            'memberSearch'
        ));
    }

    public function dataSaver()
    {
        $customers = Customer::with('user')
            ->whereHas('user', fn ($query) => $query->where('role', 'customer'))
            ->orderByDesc('id')
            ->get();

        return view('admin.data_saver', compact('customers'));
    }

    public function activateMember(Request $request, PendingMember $pendingMember)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:5'],
            'pin' => ['required', 'digits:6'],
            'initial_balance' => ['required', 'integer', 'min:'.Customer::MINIMUM_BALANCE, 'max:9223372036854775807'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'password.min' => 'Password minimal 5 karakter.',
            'pin.digits' => 'PIN harus terdiri dari 6 angka.',
            'initial_balance.min' => 'Saldo awal minimum Rp'.number_format(Customer::MINIMUM_BALANCE, 0, ',', '.').'.',
            'initial_balance.max' => 'Saldo awal melebihi batas maksimum database.',
        ]);

        try {
            DB::transaction(function () use ($pendingMember, $validated) {
                $member = PendingMember::query()->lockForUpdate()->findOrFail($pendingMember->id);
                $user = User::create([
                    'name' => $member->name,
                    'username' => $validated['username'],
                    'email' => $member->email,
                    'password' => Hash::make($validated['password']),
                    'role' => 'customer',
                ]);

                $accountNumber = $this->generateAccountNumber($user->id);
                $customer = Customer::create([
                    'user_id' => $user->id,
                    'account_number' => $accountNumber,
                    'pin' => Hash::make($validated['pin']),
                    'qr_code_string' => $accountNumber,
                    'balance' => $validated['initial_balance'],
                    'grade' => $member->grade,
                    'kelas' => $member->kelas,
                ]);

                AccountingJournal::create([
                    'transaction_id' => null,
                    'account_code' => '101',
                    'type' => 'debit',
                    'amount' => $customer->balance,
                ]);
                AccountingJournal::create([
                    'transaction_id' => null,
                    'account_code' => '201',
                    'type' => 'credit',
                    'amount' => $customer->balance,
                ]);

                $member->delete();
            });
        } catch (\Throwable $e) {
            Log::error('Failed to activate pending member: '.$e->getMessage());

            return back()->with('error', 'Aktivasi akun gagal. Data anggota tetap menunggu aktivasi.');
        }

        return redirect()->route('admin.members.index')
            ->with('success', 'Akun '.$pendingMember->name.' berhasil diaktifkan.');
    }

    public function deletePendingMember(PendingMember $pendingMember)
    {
        $pendingMember->delete();

        return redirect()->route('admin.members.index')
            ->with('success', 'Data anggota yang belum diaktifkan berhasil dihapus.');
    }

    public function deleteAllPendingMembers()
    {
        $deletedCount = PendingMember::query()->delete();

        if ($deletedCount === 0) {
            return redirect()->route('admin.members.index')
                ->with('error', 'Tidak ada anggota yang menunggu aktivasi untuk dihapus.');
        }

        return redirect()->route('admin.members.index')
            ->with('success', $deletedCount.' data anggota yang belum diaktifkan berhasil dihapus.');
    }

    public function updateCustomerEmail(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$customer->user_id],
        ], [
            'email.required' => 'Email nasabah wajib diisi untuk pemulihan akun.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
        ]);

        DB::transaction(function () use ($customer, $validated) {
            $customer->user()->update(['email' => strtolower($validated['email'])]);
            RecoveryOtp::where('user_id', $customer->user_id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);
        });

        return redirect()->route('admin.data_saver')->with('success', 'Email OTP nasabah berhasil diperbarui.');
    }

    public function blockCustomer(Customer $customer)
    {
        $user = $customer->user;
        if (! $user) {
            return back()->with('error', 'User tidak ditemukan.');
        }

        $user->is_blocked = true;
        $user->save();

        return redirect()->route('admin.data_saver')->with('success', 'Akun nasabah diblokir.');
    }

    public function unblockCustomer(Customer $customer)
    {
        $user = $customer->user;
        if (! $user) {
            return back()->with('error', 'User tidak ditemukan.');
        }

        $user->is_blocked = false;
        $user->save();

        return redirect()->route('admin.data_saver')->with('success', 'Akun nasabah dibuka blokirnya.');
    }

    public function deleteCustomer(Customer $customer)
    {
        $user = $customer->user;

        DB::transaction(function () use ($customer, $user) {
            // Keep account history so approved daily reports remain auditable.
            if ($user) {
                $user->is_blocked = true;
                $user->save();
            }

            $customer->delete();
        });

        return redirect()->route('admin.data_saver')
            ->with('success', 'Akun nasabah dihapus dan histori transaksinya tetap tersimpan.');
    }

    public function auditJournal()
    {
        return view('admin.audit_journal');
    }

    /**
     * Halaman data nasabah dikelompokkan per angkatan & kelas.
     */
    public function nasabahKelas(Request $request)
    {
        $gradeFilter = $request->get('grade');   // 10 | 11 | 12 | null
        $kelasFilter = $request->get('kelas');   // RPL 1 | ... | null

        $query = Customer::with('user')
            ->whereHas('user', fn ($query) => $query->where('role', 'customer'))
            ->orderBy('grade')
            ->orderBy('kelas');

        if ($gradeFilter) {
            $query->where('grade', $gradeFilter);
        }
        if ($kelasFilter) {
            $query->where('kelas', $kelasFilter);
        }

        // Kelompokkan: grade -> kelas -> daftar nasabah
        $grouped = $query->get()
            ->groupBy('grade')
            ->map(fn ($byGrade) => $byGrade->groupBy('kelas'));

        // Statistik ringkas
        $totalNasabah = $grouped->flatten(2)->count();

        $gradeList = [10, 11, 12];
        $kelasList = Customer::availableKelas();

        return view('admin.nasabah_kelas', compact(
            'grouped', 'totalNasabah',
            'gradeList', 'kelasList',
            'gradeFilter', 'kelasFilter'
        ));
    }

    // Example helper to generate an account number based on academic year and student id
    public function generateAccountNumber($studentId)
    {
        // Generate numeric-only account number: YYYY + zero-padded 6-digit sequence from user id
        $year = date('Y');
        $seq = str_pad($studentId, 6, '0', STR_PAD_LEFT);

        return $year.$seq; // e.g., 2026000001
    }
}
