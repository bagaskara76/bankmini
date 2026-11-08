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
}
