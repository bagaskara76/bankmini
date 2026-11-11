<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('landing');
})->name('home');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/recovery/password/otp', [AuthController::class, 'requestRecoveryOtp'])->middleware('throttle:5,1')->name('recovery.otp.request');
Route::post('/recovery/verify-otp', [AuthController::class, 'verifyRecoveryOtp'])->middleware('throttle:10,1')->name('recovery.otp.verify');
Route::get('/recovery/new-credential/{otpRequest}', [AuthController::class, 'showResetCredentialForm'])->name('recovery.credential.form');
Route::post('/recovery/complete', [AuthController::class, 'resetCredential'])->middleware('throttle:10,1')->name('recovery.credential.reset');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/logout', function () {
    return redirect()->route('login');
});

// Admin Routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('admin.dashboard');
    // Admin task routes
    Route::get('/admin/users', [\App\Http\Controllers\AdminController::class, 'users'])->name('admin.users');
    Route::post('/admin/users', [\App\Http\Controllers\AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::get('/admin/master-accounts', [\App\Http\Controllers\AdminController::class, 'masterAccounts'])->name('admin.master_accounts');
    Route::get('/admin/members', [\App\Http\Controllers\AdminController::class, 'members'])->name('admin.members.index');
    Route::post('/admin/members', [\App\Http\Controllers\AdminController::class, 'storeMember'])->name('admin.members.store');
    Route::get('/admin/members/template', [\App\Http\Controllers\AdminController::class, 'downloadMemberTemplate'])->name('admin.members.template');
    Route::post('/admin/members/import', [\App\Http\Controllers\AdminController::class, 'importMembers'])->name('admin.members.import');
    Route::delete('/admin/members/delete-all', [\App\Http\Controllers\AdminController::class, 'deleteAllPendingMembers'])->name('admin.members.delete_all');
    Route::get('/admin/data-saver', [\App\Http\Controllers\AdminController::class, 'dataSaver'])->name('admin.data_saver');
    Route::post('/admin/members/{pendingMember}/activate', [\App\Http\Controllers\AdminController::class, 'activateMember'])->name('admin.members.activate');
    Route::delete('/admin/members/{pendingMember}', [\App\Http\Controllers\AdminController::class, 'deletePendingMember'])->name('admin.members.delete');
    Route::post('/admin/customers/{customer}/email', [\App\Http\Controllers\AdminController::class, 'updateCustomerEmail'])->name('admin.customers.email');
    Route::post('/admin/customers/{customer}/block', [\App\Http\Controllers\AdminController::class, 'blockCustomer'])->name('admin.customers.block');
    Route::post('/admin/customers/{customer}/unblock', [\App\Http\Controllers\AdminController::class, 'unblockCustomer'])->name('admin.customers.unblock');
    Route::delete('/admin/customers/{customer}', [\App\Http\Controllers\AdminController::class, 'deleteCustomer'])->name('admin.customers.delete');
    Route::get('/admin/nasabah-kelas', [\App\Http\Controllers\AdminController::class, 'nasabahKelas'])->name('admin.nasabah_kelas');
    Route::get('/admin/kenaikan-kelas', [\App\Http\Controllers\AdminController::class, 'classPromotion'])->name('admin.class_promotion');
    Route::post('/admin/kenaikan-kelas', [\App\Http\Controllers\AdminController::class, 'promoteCustomers'])->name('admin.class_promotion.process');
    Route::get('/admin/verify-reports', [\App\Http\Controllers\AdminController::class, 'verifyReports'])->name('admin.verify_reports');
    Route::get('/admin/verify-reports/{report}', [\App\Http\Controllers\AdminController::class, 'reviewReport'])->name('admin.review_report');
    Route::get('/admin/monthly-reports', [\App\Http\Controllers\AdminController::class, 'monthlyReports'])->name('admin.monthly_reports');
    Route::get('/admin/monthly-reports/pdf', [\App\Http\Controllers\AdminController::class, 'monthlyReportsPdf'])->name('admin.monthly_reports.pdf');
    Route::post('/admin/verify-reports/{report}/approve', [\App\Http\Controllers\AdminController::class, 'approveReport'])->name('admin.approve_report');
    Route::post('/admin/verify-reports/{report}/reject', [\App\Http\Controllers\AdminController::class, 'rejectReport'])->name('admin.reject_report');
    Route::get('/admin/reports/{report}/print', [\App\Http\Controllers\AdminController::class, 'printReport'])->name('admin.print_report');
    Route::get('/admin/dispute-resolution', [\App\Http\Controllers\AdminController::class, 'disputeResolution'])->name('admin.dispute_resolution');
    Route::get('/admin/transfer-approval', [\App\Http\Controllers\AdminController::class, 'transferApproval'])->name('admin.transfer_approval');
    Route::get('/admin/change-password', [\App\Http\Controllers\AdminController::class, 'showChangePassword'])->name('admin.change_password');
    Route::post('/admin/change-password', [\App\Http\Controllers\AdminController::class, 'updatePassword'])->name('admin.update_password');
});

// Teller Routes
Route::middleware(['auth', 'role:teller'])->group(function () {
    Route::get('/teller/dashboard', [\App\Http\Controllers\TellerController::class, 'dashboard'])->name('teller.dashboard');
    Route::post('/teller/initial-capital', [\App\Http\Controllers\TellerController::class, 'addInitialCapital'])->name('teller.initial_capital');
    Route::post('/teller/additional-capital', [\App\Http\Controllers\TellerController::class, 'addAdditionalCapital'])->name('teller.additional_capital');
    Route::post('/teller/daily-report/submit', [\App\Http\Controllers\TellerController::class, 'submitDailyReport'])->name('teller.daily_report.submit');
    Route::get('/teller/transaction/{customer}', [\App\Http\Controllers\TellerController::class, 'showTransaction'])->name('teller.transaction');
    Route::get('/teller/customer/{customer}/pin-recovery', [AuthController::class, 'showPinRecoveryForm'])->name('teller.pin_recovery');
    Route::post('/teller/customer/{customer}/pin-recovery/otp', [AuthController::class, 'requestPinRecoveryOtp'])->middleware('throttle:5,1')->name('teller.pin_recovery.otp');
    Route::get('/teller/search', function() { return redirect()->route('teller.dashboard'); });
    Route::post('/teller/search', [\App\Http\Controllers\TellerController::class, 'searchCustomer'])->name('teller.search');
    Route::post('/teller/deposit/{customer}', [\App\Http\Controllers\TellerController::class, 'processDeposit'])->name('teller.deposit');
    Route::post('/teller/withdraw/{customer}', [\App\Http\Controllers\TellerController::class, 'processWithdrawal'])->name('teller.withdraw');
    Route::get('/teller/change-password', [\App\Http\Controllers\TellerController::class, 'showChangePassword'])->name('teller.change_password');
    Route::post('/teller/change-password', [\App\Http\Controllers\TellerController::class, 'updatePassword'])->name('teller.update_password');
});

// Customer Routes
Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/customer/dashboard', [\App\Http\Controllers\CustomerController::class, 'dashboard'])->name('customer.dashboard');
});
