<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PinSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PinSessionController extends Controller
{
    /**
     * Teller membuat sesi PIN baru untuk penarikan tunai.
     * Dipanggil via AJAX dari halaman transaksi teller.
     */
    public function create(Request $request, Customer $customer)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'note'   => 'nullable|string|max:500',
        ]);

        // Hapus sesi lama yang masih waiting/expired untuk nasabah ini
        PinSession::where('customer_id', $customer->id)
            ->whereIn('status', ['waiting', 'expired'])
            ->delete();

        $token = Str::random(48);

        $pinSession = PinSession::create([
            'customer_id' => $customer->id,
            'teller_id'   => Auth::id(),
            'token'       => $token,
            'status'      => 'waiting',
            'amount'      => $request->input('amount'),
            'note'        => $request->input('note'),
            'expires_at'  => now()->addMinutes(5),
        ]);

        $mobileUrl = route('pin.entry', $token);

        return response()->json([
            'token'      => $token,
            'mobile_url' => $mobileUrl,
            'expires_at' => $pinSession->expires_at->toISOString(),
        ]);
    }

    /**
     * Halaman mobile untuk nasabah mengisi PIN (tanpa perlu login).
     */
    public function showEntry(string $token)
    {
        $pinSession = PinSession::with(['customer.user'])
            ->where('token', $token)
            ->firstOrFail();

        // Tandai sebagai expired jika sudah lewat waktu
        if ($pinSession->isExpired() && $pinSession->status === 'waiting') {
            $pinSession->update(['status' => 'expired']);
        }

        return view('mobile.pin_entry', compact('pinSession', 'token'));
    }

    /**
     * Nasabah submit PIN dari halaman mobile.
     */
    public function submitPin(Request $request, string $token)
    {
        $request->validate([
            'pin' => 'required|digits:6',
        ]);

        $pinSession = PinSession::with('customer')
            ->where('token', $token)
            ->firstOrFail();

        // Sudah expired
        if ($pinSession->isExpired() || $pinSession->status !== 'waiting') {
            return back()->with('error', 'Sesi PIN sudah tidak valid atau kadaluarsa. Minta teller untuk memulai ulang.');
        }

        $customer = $pinSession->customer;

        // Validasi PIN
        if (! Hash::check($request->pin, $customer->pin)) {
            return back()->with('error', 'PIN salah. Silakan coba lagi.');
        }

        // PIN benar — tandai verified
        $pinSession->update(['status' => 'verified']);

        return view('mobile.pin_success', compact('pinSession'));
    }

    /**
     * Polling endpoint: teller cek apakah PIN sudah diverifikasi nasabah.
     */
    public function checkStatus(string $token)
    {
        $pinSession = PinSession::where('token', $token)->firstOrFail();

        // Auto-expire jika sudah lewat waktu
        if ($pinSession->isExpired() && $pinSession->status === 'waiting') {
            $pinSession->update(['status' => 'expired']);
        }

        return response()->json([
            'status'     => $pinSession->status,
            'expires_at' => $pinSession->expires_at->toISOString(),
        ]);
    }
}
