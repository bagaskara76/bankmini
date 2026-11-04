<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\AccountingJournal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TellerController extends Controller
{
    public function dashboard()
    {
        return view('teller.dashboard');
    }

    public function searchCustomer(Request $request)
    {
        $qr_code = $request->input('qr_code');
        $account_number = $request->input('account_number');

        $customer = Customer::with('user');
        
        if ($qr_code) {
            $customer = $customer->where('qr_code_string', $qr_code);
        } elseif ($account_number) {
            $customer = $customer->where('account_number', $account_number);
        } else {
            return back()->with('error', 'Masukkan QR Code atau Nomor Rekening');
        }

        $customer = $customer->first();

        if (!$customer) {
            return back()->with('error', 'Nasabah tidak ditemukan');
        }

        return view('teller.transaction', compact('customer'));
    }

    public function processDeposit(Request $request, $customer_id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $customer = Customer::findOrFail($customer_id);
        $amount = $request->amount;

        try {
            DB::transaction(function () use ($customer, $amount) {
                // Update Balance
                $customer->balance += $amount;
                $customer->save();

                // Create Transaction
                $trx = Transaction::create([
                    'customer_id' => $customer->id,
                    'teller_id' => Auth::id(),
                    'type' => 'deposit',
                    'amount' => $amount,
                    'status' => 'completed'
                ]);

                // Double Entry Journal (101 Kas, 201 Tabungan)
                // Setoran: Kas Bertambah (Debit), Tabungan Bertambah (Kredit)
                AccountingJournal::create([
                    'transaction_id' => $trx->id,
                    'account_code' => '101', // Kas
                    'type' => 'debit',
                    'amount' => $amount
                ]);

                AccountingJournal::create([
                    'transaction_id' => $trx->id,
                    'account_code' => '201', // Tabungan
                    'type' => 'credit',
                    'amount' => $amount
                ]);
            });

            return redirect()->route('teller.dashboard')->with('success', 'Setoran tunai berhasil. Saldo saat ini: Rp ' . number_format($customer->balance, 0, ',', '.'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses setoran: ' . $e->getMessage());
        }
    }

    public function processWithdrawal(Request $request, $customer_id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'pin' => 'required'
        ]);

        $customer = Customer::findOrFail($customer_id);
        $amount = $request->amount;
        $pin = $request->pin;

        // Validasi PIN
        if (!Hash::check($pin, $customer->pin)) {
            return back()->with('error', 'PIN Nasabah tidak valid!');
        }

        // Validasi Saldo Mengendap (Minimal 10.000)
        if (($customer->balance - $amount) < 10000) {
            return back()->with('error', 'Saldo tidak mencukupi! Saldo minimal mengendap adalah Rp 10.000. Saldo saat ini: Rp ' . number_format($customer->balance, 0, ',', '.'));
        }

        try {
            DB::transaction(function () use ($customer, $amount) {
                // Update Balance
                $customer->balance -= $amount;
                $customer->save();

                // Create Transaction
                $trx = Transaction::create([
                    'customer_id' => $customer->id,
                    'teller_id' => Auth::id(),
                    'type' => 'withdrawal',
                    'amount' => $amount,
                    'status' => 'completed'
                ]);

                // Double Entry Journal (101 Kas, 201 Tabungan)
                // Penarikan: Tabungan Berkurang (Debit), Kas Berkurang (Kredit)
                AccountingJournal::create([
                    'transaction_id' => $trx->id,
                    'account_code' => '201', // Tabungan
                    'type' => 'debit',
                    'amount' => $amount
                ]);

                AccountingJournal::create([
                    'transaction_id' => $trx->id,
                    'account_code' => '101', // Kas
                    'type' => 'credit',
                    'amount' => $amount
                ]);
            });

            return redirect()->route('teller.dashboard')->with('success', 'Penarikan tunai berhasil. Sisa Saldo: Rp ' . number_format($customer->balance, 0, ',', '.'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses penarikan: ' . $e->getMessage());
        }
    }
}
