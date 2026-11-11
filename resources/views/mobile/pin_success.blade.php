<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#16a34a">
    <title>PIN Terverifikasi – E-Teller Bank Mini</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(160deg, #14532d 0%, #16a34a 100%);
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            padding: 40px 28px;
            width: 100%;
            max-width: 360px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .icon { font-size: 72px; margin-bottom: 16px; }
        h2 { font-size: 22px; font-weight: 700; color: #16a34a; margin-bottom: 10px; }
        p { font-size: 14px; color: #6b7280; line-height: 1.6; }
        .amount {
            display: inline-block;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 8px 20px;
            font-size: 20px;
            font-weight: 700;
            color: #16a34a;
            margin: 16px 0;
        }
        .note { font-size: 12px; color: #9ca3af; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">✅</div>
        <h2>PIN Berhasil Diverifikasi!</h2>
        <div class="amount">Rp {{ number_format($pinSession->amount, 0, ',', '.') }}</div>
        <p>Transaksi penarikan tunai Anda sedang diproses oleh teller.</p>
        <p class="note">Anda dapat menyerahkan HP kembali kepada teller.</p>
    </div>
</body>
</html>
