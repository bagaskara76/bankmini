<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\Transaction;

class CustomerController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $customer = Customer::where('user_id', $user->id)->first();

        if (!$customer) {
            return redirect('/login')->with('error', 'Data nasabah tidak ditemukan.');
        }

        $transactions = Transaction::where('customer_id', $customer->id)
                            ->orderBy('created_at', 'desc')
                            ->take(10)
                            ->get();

        return view('customer.dashboard', compact('customer', 'transactions'));
    }
}
