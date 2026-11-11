<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3c72">
    <title>Verifikasi PIN – E-Teller Bank Mini</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(160deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            color: #fff;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            padding: 32px 24px;
            width: 100%;
            max-width: 380px;
            color: #1a1a2e;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .logo {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo img {
            width: 56px;
            height: 56px;
            border-radius: 12px;
        }

        .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: #1e3c72;
            margin-top: 8px;
        }

        h2 {
            font-size: 20px;
            font-weight: 700;
            text-align: center;
            color: #1e3c72;
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 13px;
            text-align: center;
            color: #6b7280;
            margin-bottom: 20px;
        }

        .info-box {
            background: #f0f4ff;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 24px;
        }

        .info-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #1e3c72;
        }

        .info-amount {
            font-size: 22px;
            font-weight: 700;
            color: #dc3545;
        }

        .pin-label {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
            text-align: center;
        }

        /* PIN dots display */
        .pin-dots {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .pin-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #d1d5db;
            background: transparent;
            transition: all 0.15s ease;
        }

        .pin-dot.filled {
            background: #1e3c72;
            border-color: #1e3c72;
        }

        /* Numpad */
        .numpad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .numpad-btn {
            height: 68px;
            border: none;
            border-radius: 14px;
            font-size: 22px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.1s ease;
            background: #f3f4f6;
            color: #1a1a2e;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }

        .numpad-btn:active {
            transform: scale(0.93);
            background: #e5e7eb;
        }

        .numpad-btn.delete {
            background: #fee2e2;
            color: #dc3545;
            font-size: 18px;
        }

        .numpad-btn.delete:active {
            background: #fecaca;
        }

        .numpad-btn.empty {
            background: transparent;
            cursor: default;
        }

        .submit-btn {
            width: 100%;
            height: 54px;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            background: #1e3c72;
            color: #fff;
            transition: all 0.15s ease;
        }

        .submit-btn:active { opacity: 0.85; }
        .submit-btn:disabled { background: #9ca3af; cursor: not-allowed; }

        .timer {
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            margin-top: 16px;
        }

        .timer.warning { color: #dc3545; font-weight: 600; }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            color: #991b1b;
            margin-bottom: 16px;
            text-align: center;
        }

        /* Status pages */
        .status-icon {
            font-size: 64px;
            text-align: center;
            margin-bottom: 12px;
        }

        .status-title {
            font-size: 22px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
        }

        .status-desc {
            font-size: 14px;
            text-align: center;
            color: #6b7280;
        }
    </style>
</head>
<body>

@if($pinSession->status === 'verified')
    {{-- Halaman sudah diverifikasi sebelumnya --}}
    <div class="card">
        <div class="status-icon">✅</div>
        <div class="status-title" style="color:#16a34a">PIN Sudah Diverifikasi</div>
        <div class="status-desc">Transaksi penarikan sedang diproses oleh teller.</div>
    </div>

@elseif($pinSession->status === 'expired' || $pinSession->isExpired())
    {{-- Sudah kadaluarsa --}}
    <div class="card">
        <div class="status-icon">⏰</div>
        <div class="status-title" style="color:#dc3545">Sesi Kadaluarsa</div>
        <div class="status-desc">Sesi PIN ini sudah habis waktu. Minta teller untuk memulai ulang transaksi.</div>
    </div>

@else
    {{-- Form PIN aktif --}}
    <div class="card">
        <div class="logo">
            <div class="logo-text">🏦 E-Teller Bank Mini</div>
        </div>

        <h2>Masukkan PIN Anda</h2>
        <p class="subtitle">PIN hanya Anda yang tahu — teller tidak melihat layar ini</p>

        <div class="info-box">
            <div class="info-label">Nasabah</div>
            <div class="info-value mb-1">{{ $pinSession->customer->user->name ?? '-' }}</div>
            <div class="info-label mt-2" style="margin-top:8px">Nominal Penarikan</div>
            <div class="info-amount">Rp {{ number_format($pinSession->amount, 0, ',', '.') }}</div>
        </div>

        @if(session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('pin.submit', $token) }}" id="pinForm">
            @csrf
            <input type="hidden" name="pin" id="pinHidden">

            <p class="pin-label">PIN 6 Digit</p>

            {{-- Dots visual --}}
            <div class="pin-dots">
                @for($i = 0; $i < 6; $i++)
                    <div class="pin-dot" id="dot-{{ $i }}"></div>
                @endfor
            </div>

            {{-- Numpad --}}
            <div class="numpad">
                @foreach([1,2,3,4,5,6,7,8,9] as $n)
                    <button type="button" class="numpad-btn" onclick="pressKey('{{ $n }}')">{{ $n }}</button>
                @endforeach
                <button type="button" class="numpad-btn empty" disabled></button>
                <button type="button" class="numpad-btn" onclick="pressKey('0')">0</button>
                <button type="button" class="numpad-btn delete" onclick="deleteKey()">⌫</button>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn" disabled>Verifikasi PIN</button>
        </form>

        <div class="timer" id="timerEl">Sesi berlaku 5 menit</div>
    </div>

    <script>
        var pin = '';
        var maxLen = 6;
        var expiresAt = new Date('{{ $pinSession->expires_at->toISOString() }}');

        function pressKey(key) {
            if (pin.length >= maxLen) return;
            pin += key;
            updateDots();
        }

        function deleteKey() {
            pin = pin.slice(0, -1);
            updateDots();
        }

        function updateDots() {
            for (var i = 0; i < maxLen; i++) {
                var dot = document.getElementById('dot-' + i);
                dot.classList.toggle('filled', i < pin.length);
            }
            var btn = document.getElementById('submitBtn');
            btn.disabled = pin.length < maxLen;
        }

        document.getElementById('pinForm').addEventListener('submit', function(e) {
            if (pin.length < maxLen) {
                e.preventDefault();
                return;
            }
            document.getElementById('pinHidden').value = pin;
        });

        // Timer countdown
        function updateTimer() {
            var now = new Date();
            var diff = Math.max(0, Math.floor((expiresAt - now) / 1000));
            var mins = Math.floor(diff / 60);
            var secs = diff % 60;
            var el = document.getElementById('timerEl');
            el.textContent = 'Sesi berlaku: ' + mins + ':' + (secs < 10 ? '0' : '') + secs;
            if (diff <= 60) el.classList.add('warning');
            if (diff === 0) {
                el.textContent = 'Sesi sudah kadaluarsa. Muat ulang halaman.';
                document.getElementById('submitBtn').disabled = true;
            }
        }

        setInterval(updateTimer, 1000);
        updateTimer();
    </script>
@endif

</body>
</html>

