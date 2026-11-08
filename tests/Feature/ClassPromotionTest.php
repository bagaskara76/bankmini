<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClassPromotionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_selected_students_move_and_unselected_students_stay_without_losing_transactions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $promotedUser = User::factory()->create(['name' => 'Siswa Naik', 'role' => 'customer']);
        $repeaterUser = User::factory()->create(['name' => 'Siswa Tinggal', 'role' => 'customer']);
        $teller = User::factory()->create(['role' => 'teller']);

        $promoted = $this->createCustomer($promotedUser, '1000000001', 'QR-PROMOTE', 10, 'RPL 1');
        $repeater = $this->createCustomer($repeaterUser, '1000000002', 'QR-REPEAT', 10, 'RPL 1');
        $transaction = Transaction::create([
            'customer_id' => $promoted->id,
            'teller_id' => $teller->id,
            'type' => 'deposit',
            'amount' => 25000,
            'status' => 'completed',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.class_promotion.process'), [
                'source_grade' => 10,
                'source_class' => 'RPL 1',
                'customer_ids' => [$promoted->id],
                'destination_class' => 'RPL 2',
            ])
            ->assertRedirect(route('admin.class_promotion', ['grade' => 10, 'source_class' => 'RPL 1']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('customers', [
            'id' => $promoted->id,
            'grade' => 11,
            'kelas' => 'RPL 2',
            'is_graduated' => false,
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $repeater->id,
            'grade' => 10,
            'kelas' => 'RPL 1',
            'is_graduated' => false,
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'customer_id' => $promoted->id,
        ]);
    }

    public function test_selected_grade_twelve_student_becomes_alumnus_and_remains_visible_with_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['name' => 'Siswa Lulus', 'role' => 'customer']);
        $teller = User::factory()->create(['role' => 'teller']);
        $customer = $this->createCustomer($student, '1000000012', 'QR-GRADUATE', 12, 'RPL 1');
        $transaction = Transaction::create([
            'customer_id' => $customer->id,
            'teller_id' => $teller->id,
            'type' => 'deposit',
            'amount' => 30000,
            'status' => 'completed',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.class_promotion.process'), [
                'source_grade' => 12,
                'source_class' => 'RPL 1',
                'customer_ids' => [$customer->id],
            ])
            ->assertRedirect(route('admin.class_promotion', ['grade' => 12, 'source_class' => 'RPL 1']));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'grade' => 12,
            'kelas' => 'RPL 1',
            'is_graduated' => true,
        ]);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'customer_id' => $customer->id,
        ]);

        $this->get(route('admin.nasabah_kelas'))
            ->assertOk()
            ->assertSee('Siswa Lulus')
            ->assertSee('Nasabah Lulus / Alumni');
    }

    public function test_promotion_is_rejected_when_destination_class_is_full(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'customer']);
        $customer = $this->createCustomer($student, '1000000020', 'QR-FULL-TEST', 10, 'RPL 1');

        foreach (range(1, Customer::MAX_CLASS_SIZE) as $index) {
            $destinationStudent = User::factory()->create(['role' => 'customer']);
            $this->createCustomer(
                $destinationStudent,
                sprintf('200000%04d', $index),
                'QR-FULL-'.$index,
                11,
                'RPL 2'
            );
        }

        $this->actingAs($admin)
            ->from(route('admin.class_promotion', ['grade' => 10, 'source_class' => 'RPL 1']))
            ->post(route('admin.class_promotion.process'), [
                'source_grade' => 10,
                'source_class' => 'RPL 1',
                'customer_ids' => [$customer->id],
                'destination_class' => 'RPL 2',
            ])
            ->assertRedirect(route('admin.class_promotion', ['grade' => 10, 'source_class' => 'RPL 1']))
            ->assertSessionHasErrors('destination_class');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'grade' => 10,
            'kelas' => 'RPL 1',
            'is_graduated' => false,
        ]);
    }

    private function createCustomer(User $user, string $accountNumber, string $qrCode, int $grade, string $class): Customer
    {
        return Customer::create([
            'user_id' => $user->id,
            'account_number' => $accountNumber,
            'pin' => 'hashed-pin',
            'qr_code_string' => $qrCode,
            'balance' => 10000,
            'grade' => $grade,
            'kelas' => $class,
        ]);
    }
}
