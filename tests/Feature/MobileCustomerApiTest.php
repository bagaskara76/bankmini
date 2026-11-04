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
