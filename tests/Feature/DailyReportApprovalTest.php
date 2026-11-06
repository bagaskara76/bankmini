<?php

namespace Tests\Feature;

use App\Models\AccountingJournal;
use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DailyReportApprovalTest extends TestCase
{
    use DatabaseTransactions;

    public function test_approving_balanced_report_transfers_cash_to_vault_and_exposes_pdf_downloads(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teller = User::factory()->create(['name' => 'Teller Brankas', 'role' => 'teller']);
        $report = DailyReport::create([
            'teller_id' => $teller->id,
            'report_date' => now()->toDateString(),
            'initial_capital' => 100000,
            'total_deposit' => 50000,
            'total_withdrawal' => 10000,
            'system_cash' => 140000,
            'physical_cash' => 140000,
            'status' => 'pending',
        ]);
        $existingVaultEntries = AccountingJournal::whereNull('transaction_id')->count();

        $this->actingAs($admin)
            ->post(route('admin.approve_report', $report))
            ->assertRedirect(route('admin.verify_reports'))
            ->assertSessionHas('success', 'Laporan disetujui dan dipindahkan ke brankas.');

        $this->assertSame('approved', $report->fresh()->status);
        $this->assertDatabaseHas('accounting_journals', [
            'transaction_id' => null,
            'account_code' => '301',
            'type' => 'debit',
            'amount' => 140000,
        ]);
        $this->assertDatabaseHas('accounting_journals', [
            'transaction_id' => null,
            'account_code' => '101',
            'type' => 'credit',
            'amount' => 140000,
        ]);
        $this->assertSame($existingVaultEntries + 2, AccountingJournal::whereNull('transaction_id')->count());

        $this->get(route('admin.transfer_approval'))
            ->assertOk()
            ->assertSee('Kas Brankas')
            ->assertSee('Teller Brankas')
            ->assertSee('Unduh PDF')
            ->assertSee(route('admin.print_report', $report), false);

        $this->get(route('admin.monthly_reports', [
            'month' => now()->month,
            'year' => now()->year,
        ]))
            ->assertOk()
            ->assertSee('Teller Brankas')
            ->assertSee('Unduh PDF')
            ->assertSee(route('admin.print_report', $report), false);

        $this->get(route('admin.print_report', $report))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=laporan-harian-'.$report->id.'.pdf');

        $this->post(route('admin.approve_report', $report))
            ->assertRedirect()
            ->assertSessionHas('error', 'Laporan ini tidak sedang menunggu approval.');

        $this->assertSame($existingVaultEntries + 2, AccountingJournal::whereNull('transaction_id')->count());
    }
}
