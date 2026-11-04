<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\RecoveryOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showForgotPasswordForm(Request $request)
    {
        $username = $request->query('username', old('username'));
        $resetId = $request->query('reset_id');
        $otpRequest = $resetId
            ? RecoveryOtp::whereKey($resetId)
                ->where('purpose', 'password')
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->where('attempts', '<', 5)
                ->first()
            : null;
        $isOtpVerified = $otpRequest
            && (int) $request->session()->get('recovery_verified_id') === $otpRequest->id;

        if ($isOtpVerified) {
            return redirect()->route('recovery.credential.form', $otpRequest->id);
        }

        return view('auth.forgot-password', compact('username', 'otpRequest'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $role = Auth::user()->role;
            if ($role == 'admin') {
                return redirect()->intended('admin/dashboard');
            } elseif ($role == 'teller') {
                return redirect()->intended('teller/dashboard');
            } elseif ($role == 'customer') {
                return redirect()->intended('customer/dashboard');
            }

            return redirect('/');
        }

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    public function requestRecoveryOtp(Request $request)
    {
        $request->session()->forget('recovery_verified_id');

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $user = User::where('username', $validated['username'])
            ->where('email', strtolower($validated['email']))
            ->where('role', 'customer')
            ->first();

        if (! $user) {
            return redirect()->route('password.request')
                ->with('status', 'Jika data akun cocok dan memiliki email terdaftar, kode OTP akan dikirim ke email tersebut.');
        }

        try {
            $otpRequest = $this->createAndSendRecoveryOtp($user, 'password');
        } catch (\Exception $exception) {
            return back()->withInput()->with('error', 'Email OTP gagal dikirim. Silakan coba lagi atau hubungi admin.');
        }

        return redirect()->route('password.request', [
            'username' => $user->username,
            'reset_id' => $otpRequest->id,
        ])->with('status', 'Jika data akun cocok dan memiliki email terdaftar, kode OTP telah dikirim. Periksa kotak masuk atau spam.');
    }

    public function showPinRecoveryForm(Request $request, Customer $customer)
    {
        $user = $customer->user;
        abort_unless($user && $user->role === 'customer', 404);

        $resetId = $request->query('reset_id');
        $otpRequest = $resetId
            ? RecoveryOtp::whereKey($resetId)
                ->where('user_id', $user->id)
                ->where('purpose', 'pin')
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->where('attempts', '<', 5)
                ->first()
            : null;

        if ($otpRequest && (int) $request->session()->get('recovery_verified_id') === $otpRequest->id) {
            return redirect()->route('recovery.credential.form', $otpRequest->id);
        }

        return view('teller.pin-recovery', compact('customer', 'user', 'otpRequest'));
    }

    public function requestPinRecoveryOtp(Request $request, Customer $customer)
    {
        $request->session()->forget('recovery_verified_id');
        $user = $customer->user;
        abort_unless($user && $user->role === 'customer', 404);

        if (! $user->email) {
            return back()->with('error', 'Email nasabah belum terdaftar. Minta admin memperbarui email terlebih dahulu.');
        }

        try {
            $otpRequest = $this->createAndSendRecoveryOtp($user, 'pin');
        } catch (\Exception $exception) {
            return back()->with('error', 'Email OTP gagal dikirim. Silakan coba lagi atau hubungi admin.');
        }

        return redirect()->route('teller.pin_recovery', [
            'customer' => $customer->id,
            'reset_id' => $otpRequest->id,
        ])->with('status', 'Kode OTP reset PIN telah dikirim ke email nasabah. Minta nasabah memeriksa emailnya.');
    }

    private function createAndSendRecoveryOtp(User $user, string $purpose): RecoveryOtp
    {
        $otp = (string) random_int(100000, 999999);
        $otpRequest = DB::transaction(function () use ($user, $purpose, $otp) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            RecoveryOtp::where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            return RecoveryOtp::create([
                'user_id' => $user->id,
                'purpose' => $purpose,
                'otp_hash' => Hash::make($otp),
                'expires_at' => now()->addMinutes(10),
            ]);
        });

        try {
            $credential = $purpose === 'password' ? 'password' : 'PIN';
            Mail::mailer('smtp')->raw(
                "Kode OTP untuk mengganti {$credential} Bank Mini SMEA 01 Anda adalah: {$otp}\n\n"
                .'Kode berlaku selama 10 menit. Jangan berikan kode ini kepada siapa pun.',
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Kode OTP Pemulihan Akun Bank Mini');
                }
            );
        } catch (\Exception $exception) {
            $otpRequest->delete();

            throw $exception;
        }

        return $otpRequest;
    }

    public function verifyRecoveryOtp(Request $request)
    {
        $validated = $request->validate([
            'reset_id' => ['required', 'integer'],
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus terdiri dari 6 angka.',
        ]);

        $result = DB::transaction(function () use ($validated) {
            $otpRequest = RecoveryOtp::whereKey($validated['reset_id'])->lockForUpdate()->first();
            if (! $otpRequest || ! in_array($otpRequest->purpose, ['password', 'pin'], true)
                || $otpRequest->consumed_at || $otpRequest->expires_at->isPast() || $otpRequest->attempts >= 5) {
                return ['error' => 'expired'];
            }

            if (! Hash::check($validated['otp'], $otpRequest->otp_hash)) {
                $otpRequest->increment('attempts');

                return ['error' => 'wrong', 'attempts' => $otpRequest->attempts];
            }

            return ['purpose' => $otpRequest->purpose];
        });

        if (isset($result['error'])) {
            if ($result['error'] === 'wrong') {
                return back()->withErrors([
                    'otp' => 'Kode OTP salah. Sisa percobaan: '.max(0, 5 - $result['attempts']).'.',
                ])->withInput($request->except('otp'));
            }

            return redirect()->route('password.request')
                ->with('error', 'Kode OTP tidak berlaku atau telah kedaluwarsa. Silakan minta kode baru.');
        }

        $request->session()->put('recovery_verified_id', (int) $validated['reset_id']);

        return redirect()->route('recovery.credential.form', $validated['reset_id']);
    }

    public function showResetCredentialForm(Request $request, RecoveryOtp $otpRequest)
    {
        if ((int) $request->session()->get('recovery_verified_id') !== $otpRequest->id
            || $otpRequest->consumed_at
            || $otpRequest->expires_at->isPast()
            || $otpRequest->attempts >= 5
            || ! in_array($otpRequest->purpose, ['password', 'pin'], true)) {
            $request->session()->forget('recovery_verified_id');

            return redirect()->route('password.request')
                ->with('error', 'Verifikasi OTP diperlukan atau kodenya sudah kedaluwarsa. Silakan minta kode baru.');
        }

        return view('auth.reset-credential', compact('otpRequest'));
    }

    public function resetCredential(Request $request)
    {
        $validated = $request->validate([
            'reset_id' => ['required', 'integer'],
        ]);

        if ((int) $request->session()->get('recovery_verified_id') !== (int) $validated['reset_id']) {
            return redirect()->route('password.request')
                ->with('error', 'Verifikasi OTP diperlukan sebelum mengganti password atau PIN.');
        }

        $otpRequest = RecoveryOtp::whereKey($validated['reset_id'])
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', 5)
            ->first();

        if (! $otpRequest || ! in_array($otpRequest->purpose, ['password', 'pin'], true)) {
            $request->session()->forget('recovery_verified_id');

            return redirect()->route('password.request')
                ->with('error', 'Kode OTP tidak berlaku atau telah kedaluwarsa. Silakan minta kode baru.');
        }

        $field = $otpRequest->purpose === 'password' ? 'password' : 'pin';
        $rules = [
            $field => $field === 'password'
                ? ['required', 'string', 'min:5', 'confirmed']
                : ['required', 'digits:6', 'confirmed'],
        ];
        $messages = [
            "{$field}.required" => $field === 'password' ? 'Password baru wajib diisi.' : 'PIN baru wajib diisi.',
            "{$field}.confirmed" => 'Konfirmasi tidak cocok.',
            "{$field}.min" => 'Password harus terdiri dari minimal 5 karakter.',
            "{$field}.digits" => 'PIN harus terdiri dari 6 angka.',
        ];

        if ($field === 'password') {
            $account = User::whereKey($otpRequest->user_id)->where('role', 'customer')->first();
            if (! $account) {
                $request->session()->forget('recovery_verified_id');

                return redirect()->route('password.request')
                    ->with('error', 'Data akun tidak ditemukan. Silakan hubungi admin.');
            }

            $rules[$field][] = function (string $attribute, mixed $value, \Closure $fail) use ($account) {
                if (Hash::check($value, $account->getAuthPassword())) {
                    $fail('Password baru tidak boleh sama dengan password lama.');
                }
            };
        }

        $credential = $request->validate($rules, $messages);

        $result = DB::transaction(function () use ($validated, $credential, $request, $field) {
            $otpRequest = RecoveryOtp::whereKey($validated['reset_id'])->lockForUpdate()->first();
            if ((int) $request->session()->get('recovery_verified_id') !== (int) $validated['reset_id']
                || ! $otpRequest || ! in_array($otpRequest->purpose, ['password', 'pin'], true)
                || $otpRequest->consumed_at || $otpRequest->expires_at->isPast() || $otpRequest->attempts >= 5) {
                return ['error' => 'expired'];
            }

            if ($otpRequest->purpose !== $field) {
                return ['error' => 'expired'];
            }

            $user = User::whereKey($otpRequest->user_id)->where('role', 'customer')->first();
            $customer = $otpRequest->purpose === 'pin'
                ? Customer::where('user_id', $otpRequest->user_id)->first()
                : null;

            if (! $user || ($otpRequest->purpose === 'pin' && ! $customer)) {
                return ['error' => 'account'];
            }

            if ($otpRequest->purpose === 'password') {
                $user->password = Hash::make($credential['password']);
                $user->plain_password = $credential['password'];
                $user->save();
            } else {
                $customer->pin = Hash::make($credential['pin']);
                $customer->save();
            }

            $otpRequest->consumed_at = now();
            $otpRequest->save();

            return [
                'changed' => $otpRequest->purpose === 'password' ? 'Password' : 'PIN',
                'purpose' => $otpRequest->purpose,
                'customer_id' => $customer?->id,
            ];
        });

        if (isset($result['error'])) {
            $request->session()->forget('recovery_verified_id');

            return redirect()->route('password.request')->with(
                'error',
                $result['error'] === 'account'
                    ? 'Data akun tidak ditemukan. Silakan hubungi admin.'
                    : 'Kode OTP tidak berlaku atau telah kedaluwarsa. Silakan minta kode baru.'
            );
        }

        $request->session()->forget('recovery_verified_id');

        if ($result['purpose'] === 'pin') {
            return redirect()->route('teller.transaction', $result['customer_id'])
                ->with('success', 'PIN nasabah berhasil diperbarui setelah OTP terverifikasi.');
        }

        return redirect()->route('login')->with('status', 'Password berhasil diganti. Silakan login kembali.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
