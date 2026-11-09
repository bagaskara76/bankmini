<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TellerDailyReportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_teller_must_enter_the_physical_cash_before_submitting_a_report(): void
    {
        $teller = User::factory()->create(['role' => 'teller']);

        $this->actingAs($teller)
            ->get('/teller/dashboard')
            ->assertOk()
            ->assertSee('Jumlah kas fisik di teller')
            ->assertSee('name="physical_cash"', false);

        $this->post(route('teller.daily_report.submit'))
            ->assertSessionHasErrors('physical_cash');

        $this->assertDatabaseHas('daily_reports', [
            'teller_id' => $teller->id,
            'status' => 'draft',
            'physical_cash' => 0,
        ]);
    }

    public function test_admin_can_review_a_discrepancy_and_teller_can_resubmit_after_rejection(): void
    {
        $teller = User::factory()->create(['name' => 'Teller Kas', 'role' => 'teller']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($teller);
        $this->post(route('teller.initial_capital'), ['initial_capital' => 100000])
            ->assertRedirect();
        $this->post(route('teller.daily_report.submit'), ['physical_cash' => 95000])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report = DailyReport::where('teller_id', $teller->id)->firstOrFail();
        $this->assertSame('pending', $report->status);
        $this->assertSame(100000, (int) $report->system_cash);
        $this->assertSame(95000, (int) $report->physical_cash);

        $this->actingAs($admin)
            ->get(route('admin.verify_reports'))
            ->assertOk()
            ->assertSee('Laporan Web (Kas Sistem)')
            ->assertSee('Kas Fisik Teller')
            ->assertSee('Rp 100.000')
            ->assertSee('Rp 95.000');

        $this->get(route('admin.review_report', $report))
            ->assertOk()
            ->assertSee('Laporan web (kas sistem)')
            ->assertSee('Kas fisik hasil hitung teller')
            ->assertSee('Rp -5.000');

        $this->post(route('admin.reject_report', $report), [
            'rejection_note' => 'Selisih kas fisik dengan laporan web sebesar Rp 5.000.',
        ])->assertRedirect(route('admin.verify_reports'));

        $report->refresh();
        $this->assertSame('rejected', $report->status);

        $this->get(route('admin.dispute_resolution'))
            ->assertOk()
            ->assertSee('Teller Kas')
            ->assertSee('Laporan web (kas sistem): Rp 100.000')
            ->assertSee('Kas fisik teller: Rp 95.000')
            ->assertSee('Selisih (fisik - sistem): Rp -5.000')
            ->assertSee('Selisih kas fisik dengan laporan web sebesar Rp 5.000.');

        $this->actingAs($teller)
            ->post(route('teller.daily_report.submit'), ['physical_cash' => 100000])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('pending', $report->status);
        $this->assertSame(100000, (int) $report->physical_cash);
        $this->assertNull($report->rejection_note);

        $this->actingAs($admin)
            ->get(route('admin.dispute_resolution'))
            ->assertDontSee('Teller Kas');
    }

    public function test_teller_can_add_capital_to_the_daily_cash_after_saving_initial_capital(): void
    {
        $teller = User::factory()->create(['role' => 'teller']);

        $this->actingAs($teller)
            ->post(route('teller.initial_capital'), ['initial_capital' => 100000])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->get(route('teller.dashboard'))
            ->assertOk()
            ->assertSee('Tambah Modal Awal');

        $this->post(route('teller.additional_capital'), ['amount' => 50000])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report = DailyReport::where('teller_id', $teller->id)->firstOrFail();
        $this->assertSame(100000, (int) $report->initial_capital);
        $this->assertSame(50000, (int) $report->additional_capital);
        $this->assertSame(150000, (int) $report->system_cash);

        $this->post(route('teller.daily_report.submit'), ['physical_cash' => 150000])
            ->assertRedirect()
            ->assertSessionHas('success');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('admin.verify_reports'))
            ->assertOk()
            ->assertSee('Modal Awal')
            ->assertSee('Rp 150.000')
            ->assertDontSee('Modal Tambahan');

        $this->actingAs($teller);
        $this->post(route('teller.additional_capital'), ['amount' => 25000])
            ->assertRedirect()
            ->assertSessionHas('error');

        $report->refresh();
        $this->assertSame(50000, (int) $report->additional_capital);
        $this->assertSame(150000, (int) $report->system_cash);
    }

    public function test_withdrawal_is_blocked_until_teller_records_initial_capital(): void
    {
        $teller = User::factory()->create(['role' => 'teller']);
        $customerUser = User::factory()->create(['role' => 'customer']);
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'account_number' => '2026110601',
            'pin' => Hash::make('123456'),
            'qr_code_string' => 'QR-2026110601',
            'balance' => 20000,
            'grade' => 10,
            'kelas' => 'RPL 1',
        ]);

        $this->actingAs($teller)
            ->get(route('teller.transaction', $customer))
            ->assertOk()
            ->assertSee('Catat modal awal hari ini di Dashboard Teller sebelum melakukan penarikan.')
            ->assertSee('id="withdrawConfirmButton" disabled', false);

        $this->post(route('teller.withdraw', $customer), [
            'amount' => 1000,
            'pin' => '123456',
        ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Penarikan tidak dapat diproses sebelum teller mencatat modal awal hari ini.');

        $this->assertSame(20000, (int) $customer->fresh()->balance);
        $this->assertDatabaseMissing('transactions', [
            'customer_id' => $customer->id,
            'teller_id' => $teller->id,
            'type' => 'withdrawal',
        ]);
    }

    public function test_multiple_tellers_share_modal_awal_and_transactions_and_submission(): void
    {
        $tellerA = User::factory()->create(['name' => 'Teller 1', 'role' => 'teller']);
        $tellerB = User::factory()->create(['name' => 'Teller 2', 'role' => 'teller']);

        $customerUser = User::factory()->create(['name' => 'Siswa Nasabah', 'role' => 'customer']);
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'account_number' => '2026110901',
            'pin' => Hash::make('123456'),
            'qr_code_string' => 'QR-2026110901',
            'balance' => 50000,
            'grade' => 10,
            'kelas' => 'RPL 1',
        ]);

        // Teller 1 mencatat modal awal
        $this->actingAs($tellerA)
            ->post(route('teller.initial_capital'), ['initial_capital' => 200000])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Teller 2 melihat modal awal sudah tersimpan otomatis
        $this->actingAs($tellerB)
            ->get(route('teller.dashboard'))
            ->assertOk()
            ->assertSee('Rp 200.000')
            ->assertSee('Tambah Modal Awal');

        // Teller 1 memproses setoran nasabah Rp 30.000
        $this->actingAs($tellerA)
            ->post(route('teller.deposit', $customer), [
                'amount' => 30000,
                'note' => 'Setoran Teller 1',
            ])
            ->assertRedirect();

        // Teller 2 memproses penarikan nasabah Rp 10.000 (bisa karena modal awal sudah tersimpan oleh Teller 1)
        $this->actingAs($tellerB)
            ->post(route('teller.withdraw', $customer), [
                'amount' => 10000,
                'pin' => '123456',
                'note' => 'Penarikan Teller 2',
            ])
            ->assertRedirect();

        // Cek laporan harian bersama:
        // Modal awal 200.000 + Setoran 30.000 - Penarikan 10.000 = Kas Sistem 220.000
        $report = DailyReport::whereDate('report_date', now()->toDateString())->firstOrFail();
        $this->assertSame(200000, (int) $report->initial_capital);
        $this->assertSame(30000, (int) $report->total_deposit);
        $this->assertSame(10000, (int) $report->total_withdrawal);
        $this->assertSame(220000, (int) $report->system_cash);

        // Teller 2 mengajukan laporan fisik bersama
        $this->actingAs($tellerB)
            ->post(route('teller.daily_report.submit'), ['physical_cash' => 220000])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('pending', $report->status);

        // Teller 1 membuka dashboard dan melihat status sudah pending
        $this->actingAs($tellerA)
            ->get(route('teller.dashboard'))
            ->assertOk()
            ->assertSee('Laporan hari ini sedang pending.');

        // Teller 1 tidak dapat lagi melakukan transaksi baru karena laporan harian sudah diajukan
        $this->actingAs($tellerA)
            ->post(route('teller.deposit', $customer), [
                'amount' => 5000,
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Transaksi baru tidak dapat diproses karena laporan harian sudah diajukan.');
    }
}
