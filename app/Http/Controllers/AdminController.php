<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DailyReport;
use App\Models\PendingMember;
use App\Models\RecoveryOtp;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BankingTransactionService;
use App\Services\MemberImportNormalizer;
use App\Services\MemberXlsx;
use App\Services\MonthlyReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class AdminController extends Controller
{
    public function __construct(
        protected MonthlyReportService $monthlyReportService,
        protected BankingTransactionService $transactionService
    ) {}

    /**
     * Dashboard utama administrator.
     */
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    /**
     * Daftar laporan kas harian teller yang menunggu verifikasi admin.
     */
    public function verifyReports()
    {
        $reports = DailyReport::with('teller')
            ->where('status', 'pending')
            ->orderBy('report_date', 'desc')
            ->get();

        return view('admin.verify_reports', compact('reports'));
    }

    /**
     * Tinjau detail laporan kas harian dan rincian transaksi teller.
     */
    public function reviewReport(DailyReport $report)
    {
        abort_unless($report->status === 'pending', 404);

        $report->load('teller');
        $reportDate = $report->getRawOriginal('report_date') ?? $report->report_date->toDateString();
        $transactions = Transaction::with(['customer.user', 'teller'])
            ->whereDate('created_at', $reportDate)
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->get();

        return view('admin.review_report', compact('report', 'transactions'));
    }

    /**
     * Halaman laporan rekapitulasi transaksi bulanan.
     */
    public function monthlyReports(Request $request)
    {
        $data = $this->monthlyReportData($request);

        return view('admin.monthly_reports', $data);
    }

    /**
     * Cetak atau unduh dokumen PDF laporan rekapitulasi bulanan.
     */
    public function monthlyReportsPdf(Request $request)
    {
        $data = $this->monthlyReportData($request, true);
        $period = sprintf('%04d-%02d', $data['year'], $data['month']);

        return Pdf::loadView('admin.monthly_report_pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download('laporan-bulanan-' . $period . '.pdf');
    }

    /**
     * Ambil data rekapitulasi transaksi bulanan melalui MonthlyReportService.
     */
    private function monthlyReportData(Request $request, bool $includeTransactions = false): array
    {
        $validated = $request->validate([
            'month' => 'nullable|integer|between:1,12',
            'year'  => 'nullable|integer|between:2000,2100',
        ]);

        $month = (int) ($validated['month'] ?? now()->month);
        $year  = (int) ($validated['year'] ?? now()->year);

        return $this->monthlyReportService->getMonthlyData($month, $year, $includeTransactions);
    }

    /**
     * Setujui laporan harian teller dan bukukan kas ke brankas sekolah.
     */
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
                $lockedReport->status      = 'approved';
                $lockedReport->save();

                // Bukukan pemindahan kas teller ke kas brankas sekolah via service
                $this->transactionService->recordVaultTransfer($lockedReport->system_cash);

                return null;
            });

            if ($error !== null) {
                return back()->with('error', $error);
            }

            return redirect()->route('admin.verify_reports')->with('success', 'Laporan disetujui dan dipindahkan ke brankas.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal menyetujui laporan: ' . $e->getMessage());
        }
    }

    /**
     * Tolak laporan kas harian teller dan berikan catatan evaluasi.
     */
    public function rejectReport(Request $request, DailyReport $report)
    {
        if ($report->status !== 'pending') {
            return back()->with('error', 'Laporan ini tidak sedang menunggu approval.');
        }

        $validated = $request->validate([
            'rejection_note' => 'required|string|max:2000',
        ], [
            'rejection_note.required' => 'Catatan alasan penolakan wajib diisi.',
            'rejection_note.max'      => 'Catatan alasan penolakan maksimal 2000 karakter.',
        ]);

        $report->reviewed_by    = Auth::id();
        $report->status         = 'rejected';
        $report->rejection_note = $validated['rejection_note'];
        $report->save();

        return redirect()->route('admin.verify_reports')->with('success', 'Laporan ditolak dan catatan berhasil dikirim ke teller.');
    }

    /**
     * Cetak dokumen PDF bukti laporan harian yang telah disetujui.
     */
    public function printReport(DailyReport $report)
    {
        abort_unless($report->status === 'approved', 404);

        $report->load(['teller', 'reviewedBy']);
        $reportDate   = $report->getRawOriginal('report_date') ?? $report->report_date->toDateString();
        $transactions = Transaction::with(['customer.user', 'teller'])
            ->whereDate('created_at', $reportDate)
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->get();

        return Pdf::loadView('admin.daily_report_pdf', compact('report', 'transactions'))
            ->download('laporan-harian-' . $report->id . '.pdf');
    }

    /**
     * Halaman penyelesaian selisih kas untuk laporan yang ditolak.
     */
    public function disputeResolution()
    {
        $reports = DailyReport::where('status', 'rejected')->orderBy('report_date', 'desc')->get();

        return view('admin.dispute_resolution', compact('reports'));
    }

    /**
     * Rekapitulasi kas yang telah disetujui dan masuk ke brankas.
     */
    public function transferApproval()
    {
        $reports = DailyReport::where('status', 'approved')
            ->orderByDesc('report_date')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.transfer_approval', compact('reports'));
    }

    /**
     * Tambah calon anggota nasabah baru (menunggu aktivasi).
     */
    public function storeMember(Request $request)
    {
        $validKelas = implode(',', Customer::availableKelas($request->input('grade')));

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email', 'unique:pending_members,email'],
            'grade' => ['required', 'in:10,11,12'],
            'kelas' => ['required', 'in:' . $validKelas],
        ], [
            'email.unique'   => 'Email sudah digunakan oleh anggota lain.',
            'grade.required' => 'Tingkat kelas (10/11/12) wajib dipilih.',
            'grade.in'       => 'Tingkat kelas harus 10, 11, atau 12.',
            'kelas.required' => 'Kelas wajib dipilih.',
            'kelas.in'       => 'Kelas yang dipilih tidak valid.',
        ]);

        PendingMember::create([
            'name'  => $validated['name'],
            'email' => isset($validated['email']) ? strtolower($validated['email']) : null,
            'grade' => $validated['grade'],
            'kelas' => $validated['kelas'],
        ]);

        return redirect()->route('admin.members.index')
            ->with('success', 'Data anggota ditambahkan. Akun belum dibuat dan menunggu aktivasi admin.');
    }

    /**
     * Unduh template format Excel untuk impor data anggota massal.
     */
    public function downloadMemberTemplate(MemberXlsx $xlsx)
    {
        return response($xlsx->template(), 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="template-anggota.xlsx"',
        ]);
    }

    /**
     * Impor daftar anggota dari dokumen Excel (.xlsx).
     */
    public function importMembers(Request $request, MemberXlsx $xlsx, MemberImportNormalizer $normalizer)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx'],
        ], [
            'file.required'   => 'Pilih dokumen XLSX yang akan diimpor.',
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

        // 1. Validasi dan normalisasi baris data Excel
        [$normalizedRows, $seenEmails, $errors] = $this->parseAndValidateImportRows($rows, $normalizer);

        // 2. Periksa duplikasi email terhadap database
        $existingEmailErrors = $this->checkImportDuplicateEmails($seenEmails, $normalizedRows);
        $errors = array_merge($errors, $existingEmailErrors);

        if ($errors !== []) {
            $displayErrors = array_slice($errors, 0, 10);
            if (count($errors) > count($displayErrors)) {
                $displayErrors[] = 'Total ' . count($errors) . ' kesalahan; hanya 10 baris pertama yang ditampilkan.';
            }

            return back()->withErrors(['file' => implode(' ', $displayErrors)]);
        }

        // 3. Simpan data anggota ke database dalam transaksi
        try {
            $this->saveImportedMembers($normalizedRows);
        } catch (Throwable $e) {
            Log::error('Failed to import pending members from XLSX: ' . $e->getMessage());

            return back()->with('error', 'Impor anggota gagal. Tidak ada data yang disimpan; periksa log aplikasi.');
        }

        return redirect()->route('admin.members.index')
            ->with('success', count($normalizedRows) . ' anggota berhasil diimpor dan menunggu aktivasi.');
    }

    /**
     * Daftar user internal (Administrator & Teller).
     */
    public function users()
    {
        $users = User::whereIn('role', ['admin', 'teller'])
            ->orderBy('name')
            ->get();

        return view('admin.users', compact('users'));
    }

    /**
     * Tambah user internal baru.
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => ['required', 'string', Password::min(12)->mixedCase()->numbers()->symbols()],
            'role'     => 'required|in:admin,teller',
        ], [
            'password.required'   => 'Password wajib diisi.',
            'password.min'        => 'Password harus terdiri dari minimal 12 karakter.',
            'password.mixed_case' => 'Password harus mengandung huruf kapital dan huruf kecil.',
            'password.numbers'    => 'Password harus mengandung minimal satu angka.',
            'password.symbols'    => 'Password harus mengandung minimal satu karakter spesial.',
        ]);

        User::create([
            'name'           => $request->name,
            'username'       => $request->username,
            'password'       => Hash::make($request->password),
            'plain_password' => $request->password,
            'role'           => $request->role,
        ]);

        return redirect()->route('admin.users')->with('success', 'User berhasil dibuat.');
    }

    /**
     * Perbarui data user internal (nama, username, role, password).
     */
    public function updateUser(Request $request, User $user)
    {
        if (! in_array($user->role, ['admin', 'teller'], true)) {
            return back()->with('error', 'User tidak valid untuk diedit di sini.');
        }

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'role'     => ['required', 'in:admin,teller'],
            'password' => ['nullable', 'string', Password::min(12)->mixedCase()->numbers()->symbols()],
        ], [
            'name.required'       => 'Nama wajib diisi.',
            'username.required'   => 'Username wajib diisi.',
            'username.unique'     => 'Username sudah digunakan oleh akun lain.',
            'password.min'        => 'Password baru minimal 12 karakter.',
            'password.mixed_case' => 'Password baru harus mengandung huruf kapital dan huruf kecil.',
            'password.numbers'    => 'Password baru harus mengandung minimal satu angka.',
            'password.symbols'    => 'Password baru harus mengandung minimal satu karakter spesial.',
        ]);

        $user->name     = $validated['name'];
        $user->username = $validated['username'];
        $user->role     = $validated['role'];

        if (! empty($validated['password'])) {
            $user->password       = Hash::make($validated['password']);
            $user->plain_password = $validated['password'];
        }

        $user->save();

        return redirect()->route('admin.users')->with('success', "Data user '{$user->name}' berhasil diperbarui.");
    }

    /**
     * Hapus akun user internal.
     */
    public function deleteUser(User $user)
    {
        if (Auth::id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        }

        if (! in_array($user->role, ['admin', 'teller'], true)) {
            return back()->with('error', 'User tidak valid.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users')->with('success', "User '{$name}' berhasil dihapus.");
    }

    /**
     * Redirect master accounts ke halaman members.
     */
    public function masterAccounts()
    {
        return redirect()->route('admin.members.index');
    }

    /**
     * Kelola data calon anggota yang menunggu aktivasi rekening.
     */
    public function members(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $memberSearch = trim($validated['search'] ?? '');

        $pendingMembers = PendingMember::query()
            ->when($memberSearch !== '', fn ($query) => $query->where('name', 'like', '%' . $memberSearch . '%'))
            ->orderByDesc('id')
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

    /**
     * Halaman Data Saver (daftar nasabah aktif dengan akun rekening).
     */
    public function dataSaver()
    {
        $customers = Customer::with('user')
            ->whereHas('user', fn ($query) => $query->where('role', 'customer'))
            ->orderByDesc('id')
            ->get();
        $kelasList = Customer::availableKelas();
        $gradeList = [10, 11, 12];

        return view('admin.data_saver', compact('customers', 'kelasList', 'gradeList'));
    }

    /**
     * Aktivasi akun rekening nasabah dari data calon anggota.
     */
    public function activateMember(Request $request, PendingMember $pendingMember)
    {
        $validated = $request->validate([
            'username'        => ['required', 'string', 'max:255', 'unique:users,username'],
            'password'        => ['required', 'string', Password::min(12)->mixedCase()->numbers()->symbols()],
            'pin'             => ['required', 'digits:6'],
            'initial_balance' => ['required', 'integer', 'min:' . Customer::MINIMUM_BALANCE, 'max:9223372036854775807'],
        ], [
            'username.required'   => 'Username wajib diisi.',
            'username.unique'     => 'Username sudah digunakan.',
            'password.min'        => 'Password minimal 12 karakter.',
            'password.mixed_case' => 'Password harus mengandung huruf kapital dan huruf kecil.',
            'password.numbers'    => 'Password harus mengandung minimal satu angka.',
            'password.symbols'    => 'Password harus mengandung minimal satu karakter spesial.',
            'pin.digits'          => 'PIN harus terdiri dari 6 angka.',
            'initial_balance.min' => 'Saldo awal minimum Rp' . number_format(Customer::MINIMUM_BALANCE, 0, ',', '.') . '.',
            'initial_balance.max' => 'Saldo awal melebihi batas maksimum database.',
        ]);

        try {
            DB::transaction(function () use ($pendingMember, $validated) {
                $member = PendingMember::query()->lockForUpdate()->findOrFail($pendingMember->id);

                // Buat user nasabah
                $user = User::create([
                    'name'     => $member->name,
                    'username' => $validated['username'],
                    'email'    => $member->email,
                    'password' => Hash::make($validated['password']),
                    'role'     => 'customer',
                ]);

                // Buat data customer & nomor rekening
                $accountNumber = $this->generateAccountNumber($user->id);
                $customer = Customer::create([
                    'user_id'        => $user->id,
                    'account_number' => $accountNumber,
                    'pin'            => Hash::make($validated['pin']),
                    'qr_code_string' => $accountNumber,
                    'balance'        => $validated['initial_balance'],
                    'grade'          => $member->grade,
                    'kelas'          => $member->kelas,
                ]);

                // Pembukuan saldo awal via BankingTransactionService
                $this->transactionService->recordMemberActivation($customer);

                $member->delete();
            });
        } catch (Throwable $e) {
            Log::error('Failed to activate pending member: ' . $e->getMessage());

            return back()->with('error', 'Aktivasi akun gagal. Data anggota tetap menunggu aktivasi.');
        }

        return redirect()->route('admin.members.index')
            ->with('success', 'Akun ' . $pendingMember->name . ' berhasil diaktifkan.');
    }

    /**
     * Hapus satu data calon anggota yang belum diaktifkan.
     */
    public function deletePendingMember(PendingMember $pendingMember)
    {
        $pendingMember->delete();

        return redirect()->route('admin.members.index')
            ->with('success', 'Data anggota yang belum diaktifkan berhasil dihapus.');
    }

    /**
     * Hapus seluruh data calon anggota yang belum diaktifkan.
     */
    public function deleteAllPendingMembers()
    {
        $deletedCount = PendingMember::query()->delete();

        if ($deletedCount === 0) {
            return redirect()->route('admin.members.index')
                ->with('error', 'Tidak ada anggota yang menunggu aktivasi untuk dihapus.');
        }

        return redirect()->route('admin.members.index')
            ->with('success', $deletedCount . ' data anggota yang belum diaktifkan berhasil dihapus.');
    }

    /**
     * Perbarui email OTP pemulihan nasabah.
     */
    public function updateCustomerEmail(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $customer->user_id],
        ], [
            'email.required' => 'Email nasabah wajib diisi untuk pemulihan akun.',
            'email.email'    => 'Format email tidak valid.',
            'email.unique'   => 'Email sudah digunakan oleh akun lain.',
        ]);

        DB::transaction(function () use ($customer, $validated) {
            $customer->user()->update(['email' => strtolower($validated['email'])]);
            RecoveryOtp::where('user_id', $customer->user_id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);
        });

        return redirect()->route('admin.data_saver')->with('success', 'Email OTP nasabah berhasil diperbarui.');
    }

    /**
     * Perbarui data profil nasabah (nama, username, kelas, password, PIN).
     */
    public function updateCustomer(Request $request, Customer $customer)
    {
        $user = $customer->user;
        if (! $user) {
            return back()->with('error', 'User nasabah tidak ditemukan.');
        }

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email'    => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'grade'    => ['required', 'in:10,11,12'],
            'kelas'    => ['required', 'string', 'max:50'],
            'password' => ['nullable', 'string', Password::min(12)->mixedCase()->numbers()->symbols()],
            'pin'      => ['nullable', 'digits:6'],
        ], [
            'name.required'       => 'Nama nasabah wajib diisi.',
            'username.required'   => 'Username nasabah wajib diisi.',
            'username.unique'     => 'Username sudah digunakan oleh akun lain.',
            'email.unique'        => 'Email sudah digunakan oleh akun lain.',
            'grade.required'      => 'Tingkat kelas wajib dipilih.',
            'kelas.required'      => 'Kelas wajib dipilih.',
            'password.min'        => 'Password baru minimal 12 karakter.',
            'password.mixed_case' => 'Password baru harus mengandung huruf kapital dan huruf kecil.',
            'password.numbers'    => 'Password baru harus mengandung minimal satu angka.',
            'password.symbols'    => 'Password baru harus mengandung minimal satu karakter spesial.',
            'pin.digits'          => 'PIN harus terdiri dari 6 angka.',
        ]);

        DB::transaction(function () use ($customer, $user, $validated) {
            $user->name     = $validated['name'];
            $user->username = $validated['username'];
            if (! empty($validated['email'])) {
                $user->email = strtolower($validated['email']);
            }
            if (! empty($validated['password'])) {
                $user->password       = Hash::make($validated['password']);
                $user->plain_password = $validated['password'];
            }
            $user->save();

            $customer->grade = (int) $validated['grade'];
            $customer->kelas = $validated['kelas'];
            if (! empty($validated['pin'])) {
                $customer->pin = Hash::make($validated['pin']);
            }
            $customer->save();
        });

        return redirect()->route('admin.data_saver')->with('success', "Data nasabah '{$user->name}' berhasil diperbarui.");
    }

    /**
     * Blokir akses login nasabah.
     */
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

    /**
     * Buka blokir akun nasabah.
     */
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

    /**
     * Hapus nasabah (dengan validasi proteksi saldo dan riwayat transaksi).
     */
    public function deleteCustomer(Customer $customer)
    {
        $user = $customer->user;

        // Tolak hapus jika saldo nasabah masih aktif (> saldo minimum Rp 10.000)
        if ($customer->balance > Customer::MINIMUM_BALANCE) {
            return redirect()->route('admin.data_saver')
                ->with('error', 'Nasabah tidak dapat dihapus karena masih memiliki saldo aktif (Rp ' . number_format($customer->balance, 0, ',', '.') . '). Tarik saldo terlebih dahulu.');
        }

        // Tolak hapus jika nasabah memiliki riwayat transaksi
        if ($customer->transactions()->exists()) {
            return redirect()->route('admin.data_saver')
                ->with('error', 'Nasabah tidak dapat dihapus karena memiliki riwayat transaksi. Gunakan fitur Blokir untuk menonaktifkan akun.');
        }

        DB::transaction(function () use ($customer, $user) {
            if ($user) {
                $user->is_blocked = true;
                $user->save();
            }

            $customer->delete();
        });

        return redirect()->route('admin.data_saver')
            ->with('success', 'Akun nasabah berhasil dihapus.');
    }

    /**
     * Halaman data nasabah dikelompokkan per angkatan & rombel kelas.
     */
    public function nasabahKelas(Request $request)
    {
        $validated = $request->validate([
            'grade' => ['nullable', 'in:10,11,12,lulus'],
            'kelas' => ['nullable', 'string', 'in:' . implode(',', Customer::availableKelas())],
        ]);
        $gradeFilter    = $validated['grade'] ?? null;
        $kelasFilter    = $validated['kelas'] ?? null;
        $showAlumniOnly = $gradeFilter === 'lulus';

        $query = Customer::with('user')
            ->whereHas('user', fn ($q) => $q->where('role', 'customer'))
            ->where('is_graduated', false)
            ->orderBy('grade')
            ->orderBy('kelas');

        if ($showAlumniOnly) {
            $query->whereRaw('1 = 0');
        }

        $alumniQuery = Customer::with('user')
            ->whereHas('user', fn ($q) => $q->where('role', 'customer'))
            ->where('is_graduated', true)
            ->orderBy('grade')
            ->orderBy('kelas');

        if ($gradeFilter && ! $showAlumniOnly) {
            $query->where('grade', $gradeFilter);
            $alumniQuery->where('grade', $gradeFilter);
        }
        if ($kelasFilter) {
            $query->where('kelas', $kelasFilter);
            $alumniQuery->where('kelas', $kelasFilter);
        }

        $grouped = $query->get()
            ->groupBy('grade')
            ->map(fn ($byGrade) => $byGrade->groupBy('kelas'));
        $alumniGrouped = $alumniQuery->get()
            ->groupBy('grade')
            ->map(fn ($byGrade) => $byGrade->groupBy('kelas'));

        $totalNasabah = $grouped->flatten(2)->count();
        $totalAlumni  = $alumniGrouped->flatten(2)->count();

        $gradeList = [10, 11, 12];
        $kelasList = Customer::availableKelas();

        return view('admin.nasabah_kelas', compact(
            'grouped', 'alumniGrouped', 'totalNasabah', 'totalAlumni',
            'gradeList', 'kelasList',
            'gradeFilter', 'kelasFilter', 'showAlumniOnly'
        ));
    }

    /**
     * Halaman proses kenaikan kelas atau kelulusan massal.
     */
    public function classPromotion(Request $request)
    {
        $validated = $request->validate([
            'grade' => ['nullable', 'integer', 'in:10,11,12'],
        ]);
        $grade = (int) ($validated['grade'] ?? 10);
        $sourceClasses = Customer::availableKelas((string) $grade);
        $sourceClass = $request->validate([
            'source_class' => ['nullable', 'string', 'in:' . implode(',', $sourceClasses)],
        ])['source_class'] ?? null;

        $customers = $sourceClass
            ? Customer::with('user')
                ->where('grade', $grade)
                ->where('kelas', $sourceClass)
                ->where('is_graduated', false)
                ->whereHas('user', fn ($query) => $query->where('role', 'customer'))
                ->orderBy('id')
                ->get()
            : collect();

        $destinationGrade = $grade < 12 ? $grade + 1 : null;
        $destinationClass = $this->destinationClassFor($sourceClass, $destinationGrade);

        return view('admin.class_promotion', compact(
            'grade',
            'sourceClass',
            'sourceClasses',
            'customers',
            'destinationGrade',
            'destinationClass'
        ));
    }

    /**
     * Proses eksekusi kenaikan kelas atau kelulusan siswa terpilih.
     */
    public function promoteCustomers(Request $request)
    {
        $validated = $request->validate([
            'source_grade'   => ['required', 'integer', 'in:10,11,12'],
            'source_class'   => [
                'required',
                'string',
                'in:' . implode(',', Customer::availableKelas((string) $request->input('source_grade'))),
            ],
            'customer_ids'   => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['required', 'integer', 'distinct', 'exists:customers,id'],
        ], [
            'customer_ids.required' => 'Pilih setidaknya satu nasabah untuk diproses.',
            'customer_ids.min'      => 'Pilih setidaknya satu nasabah untuk diproses.',
        ]);

        $sourceGrade      = (int) $validated['source_grade'];
        $destinationGrade = $sourceGrade < 12 ? $sourceGrade + 1 : null;
        $destinationClass = $this->destinationClassFor($validated['source_class'], $destinationGrade);

        if ($destinationGrade && ! $destinationClass) {
            throw ValidationException::withMessages([
                'source_class' => 'Tidak ditemukan rombel tujuan yang sesuai untuk kenaikan kelas ini.',
            ]);
        }

        $processedCount = DB::transaction(function () use ($validated, $sourceGrade, $destinationGrade, $destinationClass) {
            $customers = Customer::query()
                ->whereIn('id', $validated['customer_ids'])
                ->where('grade', $sourceGrade)
                ->where('kelas', $validated['source_class'])
                ->where('is_graduated', false)
                ->whereHas('user', fn ($q) => $q->where('role', 'customer'))
                ->lockForUpdate()
                ->get();

            if ($customers->count() !== count($validated['customer_ids'])) {
                throw ValidationException::withMessages([
                    'customer_ids' => 'Sebagian nasabah sudah berubah kelas atau tidak lagi aktif. Muat ulang halaman.',
                ]);
            }

            if ($destinationGrade) {
                foreach ($customers as $customer) {
                    $customer->grade = $destinationGrade;
                    $customer->kelas = $destinationClass;
                    $customer->save();
                }

                return $customers->count();
            }

            foreach ($customers as $customer) {
                $customer->is_graduated = true;
                $customer->save();
            }

            return $customers->count();
        });

        $message = $destinationGrade
            ? $processedCount . ' nasabah berhasil dinaikkan ke kelas ' . $destinationGrade . ' ' . $destinationClass . '.'
            : $processedCount . ' nasabah berhasil ditandai sebagai lulusan.';

        return redirect()->route('admin.class_promotion', [
            'grade'        => $sourceGrade,
            'source_class' => $validated['source_class'],
        ])->with('success', $message);
    }

    /**
     * Tentukan kelas tujuan kenaikan secara otomatis berdasarkan rombel asal.
     */
    private function destinationClassFor(?string $sourceClass, ?int $destinationGrade): ?string
    {
        if (! $sourceClass || ! $destinationGrade) {
            return null;
        }

        return in_array($sourceClass, Customer::availableKelas((string) $destinationGrade), true)
            ? $sourceClass
            : null;
    }

    /**
     * Generate nomor rekening nasabah unik (Tahun Akademik + Urutan ID).
     */
    public function generateAccountNumber($studentId)
    {
        $year = date('Y');
        $seq  = str_pad($studentId, 6, '0', STR_PAD_LEFT);

        return $year . $seq; // Contoh: 2026000001
    }

    /**
     * Formulir ganti password akun administrator.
     */
    public function showChangePassword()
    {
        return view('admin.change_password');
    }

    /**
     * Simpan pembaruan kata sandi administrator.
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
    // Helper Methods Privat untuk Impor Data Anggota Massal
    // ─────────────────────────────────────────────────────────────

    /**
     * Validasi dan sanitasi baris data dari file Excel anggota.
     */
    private function parseAndValidateImportRows(array $rows, MemberImportNormalizer $normalizer): array
    {
        $normalizedRows = [];
        $seenEmails     = [];
        $errors         = [];

        foreach ($rows as $row) {
            $data = $row['data'];
            $data['name']  = trim($data['name']);
            $data['email'] = strtolower(trim($data['email']));
            if ($data['email'] === '') {
                $data['email'] = null;
            }
            $data['grade'] = trim($data['grade']);
            $data['kelas'] = trim($data['kelas']);
            $data['grade'] = $normalizer->normalizeGrade($data['grade']);
            $data['kelas'] = $normalizer->normalizeClass($data['kelas'], $data['grade']);

            $validator = Validator::make($data, [
                'name'  => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'grade' => ['required', 'in:10,11,12'],
                'kelas' => ['required', 'in:' . implode(',', Customer::availableKelas($data['grade']))],
            ], [
                'grade.in' => 'Tingkat harus 10, 11, 12 atau X, XI, XII.',
                'kelas.in' => 'Nilai kelas "' . $data['kelas'] . '" tidak sesuai dengan tingkat ' . $data['grade'] . '. Pilih rombel yang tersedia untuk tingkat tersebut.',
            ]);

            $rowErrors = $validator->errors()->all();
            $emailKey  = $data['email'];

            if ($emailKey !== null && isset($seenEmails[$emailKey])) {
                $rowErrors[] = 'Email duplikat di dalam file.';
            }
            if ($emailKey !== null) {
                $seenEmails[$emailKey] = true;
            }

            if ($rowErrors !== []) {
                $errors[] = 'Baris ' . $row['row'] . ': ' . implode(' ', $rowErrors);
            }

            $normalizedRows[] = ['row' => $row['row'], 'data' => $data];
        }

        return [$normalizedRows, $seenEmails, $errors];
    }

    /**
     * Cek apakah ada email pada file impor yang sudah terdaftar di database.
     */
    private function checkImportDuplicateEmails(array $seenEmails, array $normalizedRows): array
    {
        $errors = [];
        $existingEmails = [];

        foreach (array_chunk(array_keys($seenEmails), 500) as $emailChunk) {
            $existingEmails = array_merge(
                $existingEmails,
                User::query()->whereIn('email', $emailChunk)
                    ->pluck('email')->map(fn ($email) => strtolower($email))->all(),
                PendingMember::query()->whereIn('email', $emailChunk)
                    ->pluck('email')->map(fn ($email) => strtolower($email))->all()
            );
        }

        foreach ($normalizedRows as $row) {
            $email = $row['data']['email'];
            if ($email !== null && in_array($email, $existingEmails, true)) {
                $errors[] = 'Baris ' . $row['row'] . ': Email sudah terdaftar.';
            }
        }

        return $errors;
    }

    /**
     * Simpan record calon anggota hasil impor ke database.
     */
    private function saveImportedMembers(array $normalizedRows): void
    {
        DB::transaction(function () use ($normalizedRows) {
            foreach ($normalizedRows as $row) {
                $data = $row['data'];
                PendingMember::create([
                    'name'  => $data['name'],
                    'email' => $data['email'],
                    'grade' => $data['grade'],
                    'kelas' => $data['kelas'],
                ]);
            }
        });
    }
}
