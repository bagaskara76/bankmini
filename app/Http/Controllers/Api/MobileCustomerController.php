<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\RecoveryOtp;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class MobileCustomerController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $key = $this->rateLimitKey('login', $request, $validated['username']);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan login. Silakan coba lagi nanti.',
            ], 429, ['Retry-After' => (string) RateLimiter::availableIn($key)]);
        }

        $user = User::where('username', $validated['username'])->first();
        if (! $user || $user->role !== 'customer'
            || ! Hash::check($validated['password'], $user->password)
            || ! Customer::where('user_id', $user->id)->exists()) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'message' => 'Username atau password salah.',
            ], 401);
        }

        RateLimiter::clear($key);
        $token = $user->createToken('mobile-customer')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'data' => $this->customerData($user),
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->customerData($request->user())]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->customerData($request->user())]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $customer = $this->customerFor($request->user());
        $transactions = Transaction::where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'type', 'amount', 'status', 'created_at']);

        return response()->json([
            'data' => $transactions->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => (int) $transaction->amount,
                'status' => $transaction->status,
                'created_at' => $transaction->created_at?->toISOString(),
            ]),
        ]);
    }

    public function requestPasswordOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'email.required' => 'Email terdaftar wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $key = $this->rateLimitKey(
            'password-otp',
            $request,
            $validated['username']
        );
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'message' => 'Terlalu banyak permintaan OTP. Silakan coba lagi nanti.',
            ], 429, ['Retry-After' => (string) RateLimiter::availableIn($key)]);
        }
        RateLimiter::hit($key, 900);

        $user = User::where('username', $validated['username'])
            ->whereRaw('LOWER(email) = ?', [strtolower($validated['email'])])
            ->where('role', 'customer')
            ->whereHas('customer')
            ->first();

        if ($user) {
            $otp = (string) random_int(100000, 999999);
            $otpRequest = DB::transaction(function () use ($user, $otp) {
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();

                RecoveryOtp::where('user_id', $user->id)
                    ->where('purpose', 'password')
                    ->whereNull('consumed_at')
                    ->update(['consumed_at' => now()]);

                return RecoveryOtp::create([
                    'user_id' => $user->id,
                    'purpose' => 'password',
                    'otp_hash' => Hash::make($otp),
                    'expires_at' => now()->addMinutes(10),
                ]);
            });

            try {
                Mail::mailer('smtp')->raw(
                    "Kode OTP pemulihan password Bank Mini SMEA 01 Anda adalah: {$otp}\n\n"
                    .'Kode berlaku selama 10 menit. Jangan berikan kode ini kepada siapa pun.',
                    function ($message) use ($user) {
                        $message->to($user->email)
                            ->subject('Kode OTP Pemulihan Akun Bank Mini');
                    }
                );
            } catch (\Exception) {
                $otpRequest->delete();

                return response()->json([
                    'message' => 'OTP belum dapat dikirim. Silakan coba kembali beberapa saat lagi.',
                ], 503);
            }
        }

        return response()->json([
            'message' => 'Jika data akun cocok dan memiliki email terdaftar, kode OTP akan dikirim ke email tersebut.',
        ]);
    }

    public function verifyPasswordOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'digits:6'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'email.required' => 'Email terdaftar wajib diisi.',
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus terdiri dari 6 angka.',
        ]);

        $key = $this->rateLimitKey(
            'verify-password-otp',
            $request,
            $validated['username']
        );
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan verifikasi OTP. Minta kode baru untuk mencoba lagi.',
            ], 429, ['Retry-After' => (string) RateLimiter::availableIn($key)]);
        }

        $resetToken = Str::random(64);
        $result = DB::transaction(function () use ($validated, $resetToken) {
            $user = User::where('username', $validated['username'])
                ->whereRaw('LOWER(email) = ?', [strtolower($validated['email'])])
                ->where('role', 'customer')
                ->whereHas('customer')
                ->first();

            if (! $user) {
                return 'invalid';
            }

            $otpRequest = RecoveryOtp::where('user_id', $user->id)
                ->where('purpose', 'password')
                ->whereNull('consumed_at')
                ->whereNull('verified_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $otpRequest || $otpRequest->expires_at->isPast() || $otpRequest->attempts >= 5) {
                return 'invalid';
            }

            if (! Hash::check($validated['otp'], $otpRequest->otp_hash)) {
                $otpRequest->increment('attempts');

                return 'invalid';
            }

            $otpRequest->forceFill([
                'verified_at' => now(),
                'reset_token_hash' => hash('sha256', $resetToken),
            ])->save();

            return 'verified';
        });

        if ($result !== 'verified') {
            RateLimiter::hit($key, 900);

            return response()->json([
                'message' => 'Kode OTP tidak valid atau telah kedaluwarsa. Periksa kembali atau minta kode baru.',
            ], 422);
        }

        RateLimiter::clear($key);

        return response()->json([
            'message' => 'OTP berhasil diverifikasi. Silakan buat password baru.',
            'reset_token' => $resetToken,
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'reset_token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.max' => 'Password baru maksimal 255 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $result = DB::transaction(function () use ($validated) {
            $user = User::where('username', $validated['username'])
                ->whereRaw('LOWER(email) = ?', [strtolower($validated['email'])])
                ->where('role', 'customer')
                ->lockForUpdate()
                ->first();

            if (! $user || ! Customer::where('user_id', $user->id)->exists()) {
                return 'invalid';
            }

            $otpRequest = RecoveryOtp::where('user_id', $user->id)
                ->where('purpose', 'password')
                ->whereNotNull('verified_at')
                ->whereNull('consumed_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $otpRequest || $otpRequest->expires_at->isPast()
                || $otpRequest->attempts >= 5
                || ! hash_equals(
                    (string) $otpRequest->reset_token_hash,
                    hash('sha256', $validated['reset_token'])
                )) {
                return 'invalid';
            }

            if (Hash::check($validated['password'], $user->password)) {
                return 'same_password';
            }

            $user->password = Hash::make($validated['password']);
            $user->plain_password = $validated['password'];
            $user->save();
            $user->tokens()->delete();

            $otpRequest->forceFill([
                'consumed_at' => now(),
                'reset_token_hash' => null,
            ])->save();

            return 'updated';
        });

        if ($result === 'same_password') {
            return response()->json([
                'message' => 'Password baru tidak boleh sama dengan password lama.',
            ], 422);
        }

        if ($result !== 'updated') {
            return response()->json([
                'message' => 'Verifikasi OTP tidak berlaku atau telah kedaluwarsa. Minta dan verifikasi kode OTP kembali.',
            ], 422);
        }

        return response()->json([
            'message' => 'Password berhasil diperbarui. Silakan login kembali.',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.max' => 'Password baru maksimal 255 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = $request->user();
        $result = DB::transaction(function () use ($validated, $user, $request) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if (! Hash::check($validated['current_password'], $user->password)) {
                return 'invalid_current_password';
            }

            if (Hash::check($validated['password'], $user->password)) {
                return 'same_password';
            }

            $user->password = Hash::make($validated['password']);
            $user->plain_password = $validated['password'];
            $user->save();

            $currentToken = $request->user()->currentAccessToken();
            if ($currentToken) {
                $user->tokens()->where('id', '!=', $currentToken->getKey())->delete();
            }

            return 'updated';
        });

        if ($result === 'invalid_current_password') {
            return response()->json(['message' => 'Password saat ini tidak sesuai.'], 422);
        }

        if ($result === 'same_password') {
            return response()->json([
                'message' => 'Password baru tidak boleh sama dengan password saat ini.',
            ], 422);
        }

        return response()->json([
            'message' => 'Password berhasil diubah. Sesi pada perangkat lain telah dikeluarkan.',
        ]);
    }

    public function changePin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_pin' => ['required', 'digits:6'],
            'pin' => ['required', 'digits:6', 'confirmed'],
        ], [
            'current_pin.required' => 'PIN saat ini wajib diisi.',
            'current_pin.digits' => 'PIN saat ini harus terdiri dari 6 angka.',
            'pin.required' => 'PIN baru wajib diisi.',
            'pin.digits' => 'PIN baru harus terdiri dari 6 angka.',
            'pin.confirmed' => 'Konfirmasi PIN tidak cocok.',
        ]);

        $customer = $this->customerFor($request->user());
        $result = DB::transaction(function () use ($validated, $customer) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();

            if (! Hash::check($validated['current_pin'], $customer->pin)) {
                return 'invalid_current_pin';
            }

            if (Hash::check($validated['pin'], $customer->pin)) {
                return 'same_pin';
            }

            $customer->pin = Hash::make($validated['pin']);
            $customer->save();

            return 'updated';
        });

        if ($result === 'invalid_current_pin') {
            return response()->json(['message' => 'PIN transaksi saat ini tidak sesuai.'], 422);
        }

        if ($result === 'same_pin') {
            return response()->json([
                'message' => 'PIN baru tidak boleh sama dengan PIN saat ini.',
            ], 422);
        }

        return response()->json(['message' => 'PIN transaksi berhasil diubah.']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    private function customerData(User $user): array
    {
        $customer = $this->customerFor($user);

        return [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'account_number' => $customer->account_number,
            'balance' => (int) $customer->balance,
            'grade' => $customer->grade,
            'kelas' => $customer->kelas,
            'qr_code_string' => $customer->qr_code_string,
        ];
    }

    private function customerFor(User $user): Customer
    {
        return Customer::where('user_id', $user->id)->firstOrFail();
    }

    private function rateLimitKey(string $purpose, Request $request, string $identifier): string
    {
        return $purpose.':'.hash('sha256', strtolower($identifier).'|'.$request->ip());
    }
}
