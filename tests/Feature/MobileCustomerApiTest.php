<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\RecoveryOtp;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class MobileCustomerApiTest extends TestCase
{
    private ?string $otp = null;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $isMemorySqlite = config("database.connections.{$connection}.driver") === 'sqlite'
            && $database === ':memory:';

        if (! $isMemorySqlite && ! str_ends_with((string) $database, '_test')) {
            $this->markTestSkipped('Gunakan database testing khusus dengan nama berakhiran _test.');
        }

        $schema = DB::connection()->getSchemaBuilder();
        if (! $schema->hasTable('recovery_otps')
            || ! $schema->hasColumn('recovery_otps', 'verified_at')
            || ! $schema->hasTable('personal_access_tokens')) {
            $this->markTestSkipped('Jalankan migrasi pada database testing khusus terlebih dahulu.');
        }

        DB::beginTransaction();
        $this->beforeApplicationDestroyed(function () {
            DB::rollBack();
        });
    }

    public function test_customer_can_log_in_and_receives_no_sensitive_profile_fields(): void
    {
        $customer = $this->createCustomer();

        $response = $this->postJson('/api/mobile/login', [
            'username' => $customer->username,
            'password' => 'OldPass123!',
        ])->assertOk()
            ->assertJsonPath('data.name', $customer->name)
            ->assertJsonPath('data.account_number', '1000000001');

        $this->assertNotEmpty($response->json('token'));
        $this->assertArrayNotHasKey('password', $response->json('data'));
        $this->assertArrayNotHasKey('pin', $response->json('data'));
        $this->assertArrayNotHasKey('plain_password', $response->json('data'));
    }

    public function test_admin_and_teller_accounts_cannot_log_in(): void
    {
        foreach (['admin', 'teller'] as $role) {
            $user = User::factory()->create([
                'username' => $role.'_user',
                'role' => $role,
                'password' => Hash::make('OldPass123!'),
            ]);

            $this->postJson('/api/mobile/login', [
                'username' => $user->username,
                'password' => 'OldPass123!',
            ])->assertUnauthorized()->assertJsonMissingPath('token');

            $this->withToken($user->createToken('test')->plainTextToken)
                ->getJson('/api/mobile/profile')
                ->assertForbidden();
        }
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        $customer = $this->createCustomer();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/mobile/login', [
                'username' => $customer->username,
                'password' => 'incorrect',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/mobile/login', [
            'username' => $customer->username,
            'password' => 'incorrect',
        ])->assertTooManyRequests();
    }

    public function test_dashboard_and_profile_only_expose_the_authenticated_customer(): void
    {
        $customer = $this->createCustomer();
        $otherCustomer = $this->createCustomer('other_customer', '1000000002');
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/mobile/dashboard')
            ->assertOk()
            ->assertJsonPath('data.username', $customer->username)
            ->assertJsonPath('data.account_number', '1000000001')
            ->assertJsonPath('data.balance', 25000)
            ->assertJsonMissing(['username' => $otherCustomer->username]);

        $this->withToken($token)->getJson('/api/mobile/profile')
            ->assertOk()
            ->assertJsonPath('data.email', $customer->email)
            ->assertJsonMissing(['account_number' => '1000000002']);
    }

    public function test_transaction_history_is_latest_first_and_isolated_to_the_customer(): void
    {
        $customer = $this->createCustomer();
        $otherCustomer = $this->createCustomer('other_customer', '1000000002');
        $teller = User::factory()->create(['role' => 'teller']);
        $old = $this->createTransaction($customer, $teller, 'deposit', 1000);
        $new = $this->createTransaction($customer, $teller, 'withdrawal', 500);
        $this->createTransaction($otherCustomer, $teller, 'deposit', 9000);
        $old->forceFill(['created_at' => now()->subDay()])->save();
        $new->forceFill(['created_at' => now()])->save();
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/mobile/transactions')
            ->assertOk()
            ->assertJsonPath('data.0.id', $new->id)
            ->assertJsonPath('data.0.type', 'withdrawal')
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonPath('data.1.id', $old->id)
            ->assertJsonMissing(['amount' => 9000]);
    }

    public function test_mobile_transaction_history_can_be_filtered_by_month_and_exported_as_a_monthly_pdf(): void
    {
        $customer = $this->createCustomer();
        $otherCustomer = $this->createCustomer('other_customer', '1000000002');
        $teller = User::factory()->create(['role' => 'teller']);
        $currentMonth = now()->startOfMonth();
        $previousMonth = $currentMonth->copy()->subMonth();
        $deposit = $this->createTransaction($customer, $teller, 'deposit', 1200);
        $withdrawal = $this->createTransaction($customer, $teller, 'withdrawal', 200);
        $otherTransaction = $this->createTransaction($otherCustomer, $teller, 'deposit', 9000);
        $deposit->forceFill(['created_at' => $previousMonth->copy()->addDays(2)])->save();
        $withdrawal->forceFill(['created_at' => $previousMonth->copy()->addDays(5)])->save();
        $otherTransaction->forceFill(['created_at' => $previousMonth->copy()->addDays(3)])->save();
        $token = $customer->createToken('test')->plainTextToken;
        $year = $previousMonth->year;
        $month = $previousMonth->month;

        $this->withToken($token)->getJson('/api/mobile/transactions?year='.$year.'&month='.$month)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $withdrawal->id)
            ->assertJsonPath('data.1.id', $deposit->id);

        $this->withToken($token)->getJson('/api/mobile/transactions?month='.$month)
            ->assertUnprocessable();

        $this->withToken($token)->getJson('/api/mobile/transactions/monthly-reports')
            ->assertOk()
            ->assertJsonPath('data.0.year', $year)
            ->assertJsonPath('data.0.month', $month)
            ->assertJsonPath('data.0.transaction_count', 2)
            ->assertJsonPath('data.0.total_deposit', 1200)
            ->assertJsonPath('data.0.total_withdrawal', 200)
            ->assertJsonMissing(['amount' => 9000]);

        $this->withToken($token)->get('/api/mobile/transactions/monthly-reports/'.$year.'/'.$month.'/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=laporan-transaksi-'.$year.'-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT).'.pdf');
    }

    public function test_authenticated_customer_can_change_password_with_current_password(): void
    {
        $customer = $this->createCustomer();
        $token = $customer->createToken('current-device')->plainTextToken;
        $otherToken = $customer->createToken('other-device')->plainTextToken;

        $this->withToken($token)->postJson('/api/mobile/password/change', [
            'current_password' => 'wrong-password',
            'password' => 'NewPass456!',
            'password_confirmation' => 'NewPass456!',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Password saat ini tidak sesuai.');

        $this->withToken($token)->postJson('/api/mobile/password/change', [
            'current_password' => 'OldPass123!',
            'password' => 'OldPass123!',
            'password_confirmation' => 'OldPass123!',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Password baru tidak boleh sama dengan password saat ini.');

        $this->withToken($token)->postJson('/api/mobile/password/change', [
            'current_password' => 'OldPass123!',
            'password' => 'NewPass456!',
            'password_confirmation' => 'NewPass456!',
        ])->assertOk()
            ->assertJsonPath('message', 'Password berhasil diubah. Sesi pada perangkat lain telah dikeluarkan.');

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'plain_password' => 'NewPass456!',
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'token' => hash('sha256', explode('|', $token, 2)[1]),
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', explode('|', $otherToken, 2)[1]),
        ]);
        $this->postJson('/api/mobile/login', [
            'username' => $customer->username,
            'password' => 'NewPass456!',
        ])->assertOk();
    }

    public function test_authenticated_customer_can_change_transaction_pin_with_current_pin(): void
    {
        $customer = $this->createCustomer();
        $token = $customer->createToken('current-device')->plainTextToken;

        $this->withToken($token)->postJson('/api/mobile/pin/change', [
            'current_pin' => '000000',
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'PIN transaksi saat ini tidak sesuai.');

        $this->withToken($token)->postJson('/api/mobile/pin/change', [
            'current_pin' => '123456',
            'pin' => '123456',
            'pin_confirmation' => '123456',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'PIN baru tidak boleh sama dengan PIN saat ini.');

        $this->withToken($token)->postJson('/api/mobile/pin/change', [
            'current_pin' => '123456',
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ])->assertOk()
            ->assertJsonPath('message', 'PIN transaksi berhasil diubah.');

        $customer->customer->refresh();
        $this->assertTrue(Hash::check('654321', $customer->customer->pin));
        $this->assertFalse(Hash::check('123456', $customer->customer->pin));
    }

    public function test_password_recovery_requires_email_otp_and_a_new_password(): void
    {
        $customer = $this->createCustomer();
        $this->mockOtpEmail();

        $requestResponse = $this->postJson('/api/mobile/password/otp', [
            'username' => $customer->username,
            'email' => $customer->email,
        ])->assertOk();

        $this->assertStringNotContainsString($this->otp, $requestResponse->getContent());
        $otpRequest = RecoveryOtp::where('user_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($otpRequest);
        $this->assertNotNull($this->otp);
        $this->assertNotSame($this->otp, $otpRequest->otp_hash);

        $this->postJson('/api/mobile/password/otp/verify', [
            'username' => $customer->username,
            'email' => $customer->email,
            'otp' => '000000',
        ])->assertUnprocessable();

        $verification = $this->postJson('/api/mobile/password/otp/verify', [
            'username' => $customer->username,
            'email' => $customer->email,
            'otp' => $this->otp,
        ])->assertOk()->assertJsonPath('message', 'OTP berhasil diverifikasi. Silakan buat password baru.');
        $resetToken = $verification->json('reset_token');
        $this->assertNotEmpty($resetToken);

        $this->postJson('/api/mobile/password/reset', [
            'username' => $customer->username,
            'email' => $customer->email,
            'reset_token' => $resetToken,
            'password' => 'OldPass123!',
            'password_confirmation' => 'OldPass123!',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Password baru tidak boleh sama dengan password lama.');

        $this->postJson('/api/mobile/password/reset', [
            'username' => $customer->username,
            'email' => $customer->email,
            'reset_token' => $resetToken,
            'password' => 'NewPass456!',
            'password_confirmation' => 'NewPass456!',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'plain_password' => 'NewPass456!',
        ]);

        $this->postJson('/api/mobile/login', [
            'username' => $customer->username,
            'password' => 'OldPass123!',
        ])->assertUnauthorized();

        $this->postJson('/api/mobile/login', [
            'username' => $customer->username,
            'password' => 'NewPass456!',
        ])->assertOk();

        $this->postJson('/api/mobile/password/reset', [
            'username' => $customer->username,
            'email' => $customer->email,
            'reset_token' => $resetToken,
            'password' => 'AnotherPass789!',
            'password_confirmation' => 'AnotherPass789!',
        ])->assertUnprocessable();
    }

    public function test_expired_otp_cannot_be_verified(): void
    {
        $customer = $this->createCustomer();
        RecoveryOtp::create([
            'user_id' => $customer->id,
            'purpose' => 'password',
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinute(),
        ]);

        $this->postJson('/api/mobile/password/otp/verify', [
            'username' => $customer->username,
            'email' => $customer->email,
            'otp' => '123456',
        ])->assertUnprocessable();
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $customer = $this->createCustomer();
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/mobile/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout berhasil.');

        $this->withToken($token)->getJson('/api/mobile/profile')->assertUnauthorized();
    }

    private function createCustomer(string $username = 'customer_user', string $accountNumber = '1000000001'): User
    {
        $user = User::factory()->create([
            'name' => 'Nasabah Uji',
            'username' => $username,
            'email' => $username.'@example.test',
            'role' => 'customer',
            'password' => Hash::make('OldPass123!'),
        ]);

        Customer::create([
            'user_id' => $user->id,
            'account_number' => $accountNumber,
            'pin' => Hash::make('123456'),
            'qr_code_string' => 'QR-'.$username,
            'balance' => 25000,
            'grade' => 11,
            'kelas' => 'RPL 1',
        ]);

        return $user;
    }

    private function createTransaction(User $customer, User $teller, string $type, int $amount): Transaction
    {
        return Transaction::create([
            'customer_id' => $customer->customer->id,
            'teller_id' => $teller->id,
            'type' => $type,
            'amount' => $amount,
            'status' => 'completed',
        ]);
    }

    private function mockOtpEmail(): void
    {
        $mailer = Mockery::mock();
        $mailer->shouldReceive('raw')->once()->andReturnUsing(
            function (string $body, callable $callback) {
                preg_match('/adalah: ([0-9]{6})/', $body, $matches);
                $this->otp = $matches[1] ?? null;

                $message = Mockery::mock();
                $message->shouldReceive('to')->once()->with('customer_user@example.test')->andReturnSelf();
                $message->shouldReceive('subject')->once()->with('Kode OTP Pemulihan Akun Bank Mini')->andReturnSelf();
                $callback($message);
            }
        );
        Mail::shouldReceive('mailer')->once()->with('smtp')->andReturn($mailer);
    }
}
