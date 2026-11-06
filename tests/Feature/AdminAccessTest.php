<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_access_combined_admin_operations(): void
    {
        $admin = User::factory()->make(['role' => 'admin']);
        $this->actingAs($admin);

        $sidebarRoutes = [
            'admin.dashboard',
            'admin.users',
            'admin.members.index',
            'admin.data_saver',
            'admin.nasabah_kelas',
            'admin.verify_reports',
            'admin.monthly_reports',
            'admin.audit_journal',
            'admin.dispute_resolution',
            'admin.transfer_approval',
        ];

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Daftar Nasabah per Kelas')
            ->assertDontSee('aria-label="Menu administrator"', false);
        $verifyReportsPage = $this->get('/admin/verify-reports')
            ->assertOk()
            ->assertSee('Menu Administrator');
        foreach ($sidebarRoutes as $routeName) {
            $verifyReportsPage->assertSee(route($routeName), false);
        }
        $verifyReportsPage->assertDontSee('Konfigurasi Sistem');
        $this->get('/admin/config')->assertNotFound();
        $this->get('/admin/nasabah-kelas')
            ->assertOk()
            ->assertDontSee('Rangkuman Data Nasabah per Kelas')
            ->assertSee('Daftar nama nasabah aktif dikelompokkan berdasarkan tingkat dan kelas')
            ->assertDontSee('Navigasi anggota dan nasabah')
            ->assertSee('LPS 1')
            ->assertSee('LPS 2');
        $this->get('/supervisor/dashboard')->assertNotFound();
    }

    public function test_class_roster_contains_only_active_customer_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activeCustomer = User::factory()->create([
            'name' => 'Nasabah Aktif RPL',
            'role' => 'customer',
        ]);
        $teller = User::factory()->create([
            'name' => 'Teller Bukan Nasabah',
            'role' => 'teller',
        ]);

        foreach ([
            [$activeCustomer, '1234567890', 'QR-AKTIF'],
            [$teller, '0987654321', 'QR-TELLER'],
        ] as [$user, $accountNumber, $qrCode]) {
            \App\Models\Customer::create([
                'user_id' => $user->id,
                'account_number' => $accountNumber,
                'pin' => 'hashed-pin',
                'qr_code_string' => $qrCode,
                'balance' => 10000,
                'grade' => 10,
                'kelas' => 'RPL 1',
            ]);
        }

        $this->actingAs($admin)
            ->get('/admin/nasabah-kelas')
            ->assertOk()
            ->assertSee('Nasabah Aktif RPL')
            ->assertDontSee('Teller Bukan Nasabah');
    }
}
