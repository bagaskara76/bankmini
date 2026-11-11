<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kenali Bank Mini sekolah dan layanan E-Teller untuk mencatat serta mengelola transaksi tabungan siswa.">
    <title>E-Teller Bank Mini Sekolah</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-smea-lama.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --ink: #172b4d;
            --muted: #68778c;
            --green: #2a5298;
            --green-dark: #1e3c72;
            --mint: #e9eff9;
            --paper: #f8faff;
            --line: #e1e8f2;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            background:
                radial-gradient(ellipse at 8% 12%, rgba(42, 82, 152, .24), transparent 34rem),
                radial-gradient(ellipse at 92% 88%, rgba(48, 119, 143, .19), transparent 32rem),
                radial-gradient(ellipse at 78% 6%, rgba(125, 162, 219, .15), transparent 26rem),
                linear-gradient(135deg, #e6edf8 0%, #f4f7fa 52%, #e8f1f2 100%);
            background-attachment: fixed;
            color: var(--ink);
            font-family: "DM Sans", sans-serif;
            line-height: 1.65;
        }
        a { color: inherit; }
        .site-header {
            position: sticky;
            z-index: 10;
            top: 0;
            border-bottom: 1px solid rgba(227, 235, 229, .85);
            background: rgba(251, 252, 248, .92);
            backdrop-filter: blur(14px);
        }
        .nav-wrap, .container { width: min(1120px, calc(100% - 40px)); margin-inline: auto; }
        .nav-wrap { min-height: 78px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
        .brand { display: inline-flex; align-items: center; gap: 12px; text-decoration: none; font-weight: 800; letter-spacing: -.03em; }
        .brand img { width: 44px; height: 44px; object-fit: contain; }
        .brand small { display: block; color: var(--muted); font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
        .nav-links { display: flex; align-items: center; gap: 28px; }
        .nav-links > a:not(.button) { color: var(--muted); font-size: 14px; font-weight: 600; text-decoration: none; }
        .nav-links > a:not(.button):hover { color: var(--green); }
        .button {
            display: inline-flex;
            min-height: 48px;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 0 21px;
            border: 1px solid transparent;
            border-radius: 9px;
            background: var(--green);
            color: white;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: background .2s ease, transform .2s ease;
        }
        .button:hover { transform: translateY(-2px); background: var(--green-dark); color: white; }
        .button-outline { border-color: #c2d0e5; background: transparent; color: var(--green-dark); }
        .button-outline:hover { background: var(--mint); color: var(--green-dark); }
        .hero { overflow: hidden; padding: 82px 0 76px; }
        .hero-grid { display: grid; grid-template-columns: 1.08fr .92fr; align-items: center; gap: 72px; }
        .eyebrow { display: inline-flex; align-items: center; gap: 9px; color: var(--green); font-size: 12px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
        .eyebrow::before { width: 25px; height: 2px; background: currentColor; content: ""; }
        h1, h2, h3 { font-family: "Manrope", sans-serif; line-height: 1.16; }
        h1 { max-width: 650px; margin: 20px 0; font-size: clamp(40px, 5.2vw, 66px); font-weight: 800; letter-spacing: -.055em; }
        h1 span { color: var(--green); }
        .hero-copy { max-width: 565px; margin: 0; color: var(--muted); font-size: 17px; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 13px; margin-top: 30px; }
        .hero-note { display: flex; align-items: center; gap: 10px; margin-top: 24px; color: var(--muted); font-size: 13px; }
        .hero-note strong { color: var(--ink); }
        .visual { position: relative; min-height: 410px; display: grid; place-items: center; }
        .visual-glow { position: absolute; width: 360px; height: 360px; border-radius: 50%; background: #e2eafa; filter: blur(1px); }
        .account-card {
            position: relative;
            z-index: 1;
            width: min(100%, 390px);
            padding: 28px;
            border: 1px solid rgba(255,255,255,.5);
            border-radius: 22px;
            background: linear-gradient(145deg, #1e3c72, #2a5298);
            box-shadow: 0 28px 65px rgba(23, 59, 61, .22);
            color: white;
            transform: rotate(2deg);
        }
        .card-top, .card-bottom { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .card-label { color: rgba(255,255,255,.68); font-size: 11px; font-weight: 700; letter-spacing: .12em; }
        .card-title { margin: 29px 0 5px; font-family: "Manrope", sans-serif; font-size: 23px; font-weight: 800; }
        .card-subtitle { margin: 0; color: rgba(255,255,255,.72); font-size: 13px; }
        .card-divider { height: 1px; margin: 27px 0 18px; background: rgba(255,255,255,.2); }
        .card-bottom { align-items: end; }
        .card-bottom strong { display: block; margin-top: 5px; font-size: 15px; }
        .card-chip { width: 38px; height: 28px; border-radius: 7px; background: linear-gradient(135deg,#d9c88d,#a99452); }
        .section { padding: 78px 0; }
        .section-soft { background: #eef3fb; }
        .section-heading { max-width: 680px; margin: 0 auto 42px; text-align: center; }
        .section-heading h2 { margin: 13px 0; font-size: clamp(29px, 4vw, 42px); font-weight: 800; letter-spacing: -.04em; }
        .section-heading p { margin: 0; color: var(--muted); }
        .about-grid { display: grid; grid-template-columns: .85fr 1.15fr; align-items: start; gap: 64px; }
        .about-grid h2 { margin: 13px 0 16px; font-size: clamp(30px, 4vw, 43px); font-weight: 800; letter-spacing: -.045em; }
        .about-grid p { color: var(--muted); }
        .about-panel { padding: 30px; border: 1px solid var(--line); border-radius: 17px; background: white; }
        .about-panel h3 { margin: 0 0 12px; font-size: 18px; }
        .about-panel p { margin: 0; }
        .feature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        .feature-card { min-height: 218px; padding: 25px; border: 1px solid var(--line); border-radius: 15px; background: white; }
        .feature-card h3 { margin: 0 0 9px; font-size: 17px; font-weight: 800; }
        .feature-card p { margin: 0; color: var(--muted); font-size: 14px; }
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; }
        .step { position: relative; padding-top: 18px; border-top: 2px solid #cfdaeb; }
        .step-number { color: var(--green); font-family: "Manrope", sans-serif; font-size: 13px; font-weight: 800; }
        .step h3 { margin: 10px 0 8px; font-size: 17px; }
        .step p { margin: 0; color: var(--muted); font-size: 14px; }
        .cta { padding: 50px; border-radius: 19px; background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; text-align: center; }
        .cta h2 { margin: 0 0 12px; font-size: clamp(26px, 4vw, 38px); font-weight: 800; letter-spacing: -.04em; }
        .cta p { max-width: 600px; margin: 0 auto 23px; color: rgba(255,255,255,.72); }
        .cta .button { background: #fff; color: var(--green-dark); }
        .cta .button:hover { background: var(--mint); }
        footer { padding: 28px 0; color: var(--muted); font-size: 13px; }
        .footer-wrap { display: flex; justify-content: space-between; gap: 18px; }

        @media (max-width: 820px) {
            .hero { padding: 55px 0; }
            .hero-grid, .about-grid { grid-template-columns: 1fr; gap: 35px; }
            .visual { min-height: 360px; }
            .feature-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 560px) {
            .nav-wrap, .container { width: min(100% - 30px, 1120px); }
            .nav-wrap { min-height: 68px; }
            .brand { gap: 8px; font-size: 14px; }
            .brand img { width: 38px; height: 38px; }
            .nav-links { gap: 0; }
            .nav-links > a:not(.button) { display: none; }
            .nav-links .button { min-height: 42px; padding-inline: 15px; }
            .hero-copy { font-size: 15px; }
            .visual { min-height: 310px; }
            .visual-glow { width: 280px; height: 280px; }
            .account-card { padding: 23px; }
            .section { padding: 58px 0; }
            .feature-grid, .steps { grid-template-columns: 1fr; }
            .feature-card { min-height: auto; }
            .cta { padding: 34px 22px; }
            .footer-wrap { flex-direction: column; }
        }

        /* Manual Book Styles */
        .manual-tabs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 34px;
            flex-wrap: wrap;
        }
        .manual-tab-btn {
            padding: 11px 22px;
            border-radius: 9px;
            border: 1px solid #cfdaeb;
            background: white;
            font-family: "Manrope", sans-serif;
            font-weight: 700;
            font-size: 14px;
            color: var(--muted);
            cursor: pointer;
            transition: all .2s ease;
        }
        .manual-tab-btn:hover {
            color: var(--green);
            border-color: #b3c7e8;
            background: var(--mint);
        }
        .manual-tab-btn.active {
            background: var(--green);
            color: white;
            border-color: var(--green);
            box-shadow: 0 4px 14px rgba(42, 82, 152, .25);
        }
        .manual-content {
            display: none;
        }
        .manual-content.active {
            display: block;
            animation: manualFadeIn .3s ease-in-out;
        }
        @keyframes manualFadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .manual-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .manual-card {
            padding: 26px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: white;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .manual-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(23, 43, 77, .06);
        }
        .manual-card__badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            background: var(--mint);
            color: var(--green-dark);
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            margin-bottom: 12px;
            letter-spacing: .02em;
        }
        .manual-card h3 {
            margin: 0 0 10px;
            font-size: 18px;
            color: var(--ink);
        }
        .manual-card p {
            color: var(--muted);
            font-size: 14px;
            margin: 0 0 14px;
            line-height: 1.6;
        }
        .manual-steps-list {
            margin: 0;
            padding-left: 20px;
            color: var(--ink);
            font-size: 14px;
            line-height: 1.65;
        }
        .manual-steps-list li {
            margin-bottom: 8px;
        }
        .manual-steps-list li:last-child {
            margin-bottom: 0;
        }
        .manual-steps-list li strong {
            color: var(--green-dark);
        }
        .manual-note {
            background: #fff8e6;
            border-left: 4px solid #f59e0b;
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 13px;
            color: #92400e;
            margin-top: 14px;
            line-height: 1.5;
        }
        @media (max-width: 820px) {
            .manual-grid { grid-template-columns: 1fr; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <nav class="nav-wrap" aria-label="Navigasi utama">
            <a class="brand" href="{{ route('home') }}">
                <img src="{{ asset('images/logo-smea-lama.png') }}" alt="">
                <span>E-Teller Bank Mini<small>SMK Negeri 1 Bantul</small></span>
            </a>
            <div class="nav-links">
                <a href="#tentang">Tentang</a>
                <a href="#fitur">Fitur</a>
                <a href="#panduan">Manual Book</a>
                <a class="button" href="{{ route('login') }}">Login</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-grid">
                <div>
                    <span class="eyebrow">Layanan keuangan sekolah</span>
                    <h1>Belajar mengelola uang, <span>dimulai dari sekolah.</span></h1>
                    <p class="hero-copy">E-Teller Bank Mini membantu siswa dan petugas sekolah mencatat layanan tabungan dengan lebih teratur, mudah dipantau, dan transparan.</p>
                    <div class="hero-actions">
                        <a class="button" href="{{ route('login') }}">Login</a>
                        <a class="button button-outline" href="#panduan">Buku Panduan</a>
                        <a class="button button-outline" href="{{ asset('app-release.apk') }}" download="e-teller-bank-mini.apk">Download Aplikasi Android</a>
                    </div>
                    <div class="hero-note"><span aria-hidden="true">●</span><span><strong>Untuk lingkungan sekolah</strong> &nbsp;Pembelajaran dan layanan tabungan siswa</span></div>
                </div>
                <div class="visual" aria-label="Ilustrasi kartu tabungan Bank Mini sekolah">
                    <div class="visual-glow"></div>
                    <div class="account-card">
                        <div class="card-top">
                            <span class="card-label">BANK MINI SEKOLAH</span>
                        </div>
                        <p class="card-title">Tabungan Siswa</p>
                        <p class="card-subtitle">Belajar menabung dengan tertib</p>
                        <div class="card-divider"></div>
                        <div class="card-bottom">
                            <div><span class="card-label">LAYANAN</span><strong>Setor &bull; Tarik</strong></div>
                            <span class="card-chip" aria-hidden="true"></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section section-soft" id="tentang">
            <div class="container about-grid">
                <div>
                    <span class="eyebrow">Apa itu Bank Mini?</span>
                    <h2>Simulasi layanan perbankan di lingkungan sekolah.</h2>
                </div>
                <div class="about-panel">
                    <h3>Tempat belajar mengelola tabungan secara nyata dan bertanggung jawab.</h3>
                    <p>Bank Mini sekolah merupakan layanan pendidikan yang mengenalkan proses sederhana seperti menabung, melakukan setoran dan penarikan melalui petugas, serta memeriksa catatan transaksi. E-Teller mendukung proses tersebut secara digital agar siswa dapat belajar mengelola keuangan, sementara petugas dapat membantu pencatatan layanan sekolah.</p>
                </div>
            </div>
        </section>

        <section class="section" id="fitur">
            <div class="container">
                <div class="section-heading">
                    <span class="eyebrow">Fungsi aplikasi</span>
                    <h2>Layanan yang membantu kegiatan Bank Mini</h2>
                    <p>Satu aplikasi untuk mendukung kebutuhan nasabah siswa, teller, dan pengelola Bank Mini sekolah.</p>
                </div>
                <div class="feature-grid">
                    <article class="feature-card">
                        <h3>Informasi tabungan</h3>
                        <p>Nasabah dapat melihat ringkasan rekening, saldo, dan riwayat transaksi melalui dashboard.</p>
                    </article>
                    <article class="feature-card">
                        <h3>Setoran dan penarikan</h3>
                        <p>Teller memproses transaksi nasabah dan aplikasi menyimpan catatannya untuk ditinjau kembali.</p>
                    </article>
                    <article class="feature-card">
                        <h3>Kartu QR nasabah</h3>
                        <p>QR membantu teller mengenali rekening nasabah saat memberikan layanan transaksi.</p>
                    </article>
                    <article class="feature-card">
                        <h3>Pengelolaan oleh admin</h3>
                        <p>Admin mengelola pengguna dan data nasabah serta meninjau laporan dan transaksi tertentu.</p>
                    </article>
                    <article class="feature-card">
                        <h3>Laporan dan pencatatan</h3>
                        <p>Fitur laporan membantu petugas merangkum kegiatan dan pencatatan Bank Mini sekolah.</p>
                    </article>
                    <article class="feature-card">
                        <h3>Akses sesuai peran</h3>
                        <p>Halaman dan tugas dibedakan untuk admin, teller, dan nasabah yang sudah memiliki akun.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section section-soft" id="panduan">
            <div class="container">
                <div class="section-heading">
                    <span class="eyebrow">Petunjuk Penggunaan</span>
                    <h2>Manual Book E-Teller Bank Mini</h2>
                    <p>Panduan lengkap operasional dan tata cara penggunaan aplikasi untuk Nasabah, Teller, dan Administrator.</p>
                </div>

                <div class="manual-tabs" role="tablist">
                    <button class="manual-tab-btn active" data-tab="manual-nasabah" type="button" role="tab" aria-selected="true">
                        Panduan Nasabah (Siswa)
                    </button>
                    <button class="manual-tab-btn" data-tab="manual-teller" type="button" role="tab" aria-selected="false">
                        Panduan Teller
                    </button>
                    <button class="manual-tab-btn" data-tab="manual-admin" type="button" role="tab" aria-selected="false">
                        Panduan Administrator
                    </button>
                    <button class="manual-tab-btn" data-tab="manual-keamanan" type="button" role="tab" aria-selected="false">
                        Keamanan &amp; Password
                    </button>
                </div>

                <!-- TAB 1: NASABAH -->
                <div class="manual-content active" id="manual-nasabah" role="tabpanel">
                    <div class="manual-grid">
                        <article class="manual-card">
                            <span class="manual-card__badge">Tahap 1</span>
                            <h3>Aktivasi Akun &amp; Login</h3>
                            <p>Cara memulai penggunaan akun tabungan siswa di aplikasi E-Teller Bank Mini:</p>
                            <ol class="manual-steps-list">
                                <li>Dapatkan <strong>Nomor Rekening</strong> dan <strong>Username</strong> dari pihak sekolah/administrator Bank Mini.</li>
                                <li>Buka web E-Teller atau unduh aplikasi Android resmi melalui tautan download di halaman ini.</li>
                                <li>Masuk menggunakan username dan password yang diberikan saat aktivasi.</li>
                                <li>Saat pertama kali login, disarankan segera memperbarui password demi keamanan akun.</li>
                            </ol>
                        </article>

                        <article class="manual-card">
                            <span class="manual-card__badge">Tahap 2</span>
                            <h3>Layanan Setor &amp; Tarik Tunai</h3>
                            <p>Prosedur melakukan transaksi tabungan di meja teller Bank Mini sekolah:</p>
                            <ol class="manual-steps-list">
                                <li>Datang ke meja loket Teller Bank Mini sekolah pada jam operasional.</li>
                                <li>Tunjukkan <strong>QR Code Kartu Nasabah</strong> dari aplikasi mobile atau sebutkan nomor rekening.</li>
                                <li><strong>Setoran:</strong> Serahkan uang tunai ke teller untuk dihitung dan dicatat ke saldo tabungan.</li>
                                <li><strong>Penarikan:</strong> Tentukan nominal yang ditarik, lalu ketik <strong>6 digit PIN</strong> rahasia Anda pada perangkat teller untuk otorisasi.</li>
                                <li>Terima lembar struk bukti transaksi dari teller dan pastikan saldo Anda telah terupdate.</li>
                            </ol>
                        </article>

                        <article class="manual-card">
                            <span class="manual-card__badge">Tahap 3</span>
                            <h3>Cek Saldo &amp; Mutasi Tabungan</h3>
                            <p>Pantau catatan keuangan dan perkembangan tabungan secara real-time:</p>
                            <ol class="manual-steps-list">
                                <li>Buka dashboard akun nasabah melalui aplikasi mobile atau web.</li>
                                <li>Periksa informasi <strong>Saldo Aktif</strong> di halaman utama.</li>
                                <li>Lihat daftar riwayat seluruh transaksi masuk (setoran) dan transaksi keluar (penarikan) secara detail dan transparan.</li>
                            </ol>
                        </article>

                        <article class="manual-card">
                            <span class="manual-card__badge">Bantuan</span>
                            <h3>Lupa Password atau Lupa PIN?</h3>
                            <p>Langkah pemulihan keamanan jika kendala lupa kata sandi atau PIN terjadi:</p>
                            <ol class="manual-steps-list">
                                <li><strong>Lupa Password:</strong> Klik menu <em>"Lupa Password"</em> di halaman login, masukkan username &amp; email terdaftar untuk menerima kode verifikasi OTP.</li>
                                <li><strong>Lupa PIN / PIN Terblokir:</strong> Kunjungi meja Teller sekolah. Teller akan memfasilitasi reset PIN dengan mengirimkan kode OTP ke email nasabah.</li>
                            </ol>
                            <div class="manual-note">
                                <strong>Penting:</strong> Jangan pernah memberitahukan kode OTP maupun PIN 6 digit kepada orang lain.
                            </div>
                        </article>
                    </div>
                </div>

                <!-- TAB 2: TELLER -->
                <div class="manual-content" id="manual-teller" role="tabpanel">
                    <div class="manual-grid">
                        <article class="manual-card">
                            <span class="manual-card__badge">Operasional Pagi</span>
                            <h3>Pencatatan Modal Awal Kas</h3>
                            <p>Langkah wajib teller setiap awal hari kerja sebelum melayani nasabah:</p>
                            <ol class="manual-steps-list">
                                <li>Login dengan akun Teller yang telah terdaftar.</li>
                                <li>Pada kotak <strong>Modal Awal Hari Ini</strong> di Dashboard, masukkan nominal kas awal operasional laci kasir.</li>
                                <li>Klik <strong>Simpan</strong>. Sistem secara otomatis mencatat dan menyinkronkan modal awal ini untuk seluruh teller yang bertugas hari itu.</li>
                                <li>Jika ada suntikan dana operasional tambahan di tengah hari, gunakan tombol <em>"Tambah Modal Awal"</em>.</li>
                            </ol>
                        </article>

                        <article class="manual-card">
                            <span class="manual-card__badge">Layanan Transaksi</span>
                            <h3>Setor Tunai &amp; Tarik Tunai</h3>
                            <p>Tata cara memproses transaksi harian nasabah siswa:</p>
                            <ol class="manual-steps-list">
                                <li><strong>Cari Nasabah:</strong> Scan kartu QR nasabah dengan webcam/kamera, atau ketik nomor rekening pada kolom pencarian.</li>
                                <li><strong>Setoran:</strong> Masukkan jumlah nominal setoran, hitung uang fisik nasabah, lalu klik <em>"Proses Setoran"</em>.</li>
                                <li><strong>Penarikan:</strong> Masukkan jumlah penarikan. Minta nasabah memasukkan <strong>PIN 6 Digit</strong> pada form otorisasi sebelum transaksi disetujui sistem.</li>
                                <li>Cetak struk bukti transaksi untuk diserahkan kepada nasabah sebagai tanda bukti sah.</li>
                            </ol>
                        </article>

                        <article class="manual-card">
                            <span class="manual-card__badge">Bantuan Nasabah</span>
                            <h3>Pemulihan PIN Nasabah</h3>
                            <p>Prosedur membuka blokir atau mereset PIN siswa yang lupa:</p>
                            <ol class="manual-steps-list">
                                <li>Buka data nasabah yang bersangkutan di menu transaksi teller.</li>
                                <li>Pilih opsi <strong>Reset PIN Keamanan</strong>.</li>
                                <li>Klik <em>Kirim OTP ke Email Nasabah</em> untuk mengirim kode verifikasi aman.</li>
                                <li>Setelah siswa menyebutkan kode OTP yang diterima di emailnya, teller memverifikasi dan siswa dapat mengatur 6 digit PIN baru.</li>
                            </ol>
                        </article>

                        <article class="manual-card">
                            <span class="manual-card__badge">Operasional Sore</span>
                            <h3>Laporan Kas Harian Serentak</h3>
                            <p>Penutupan buku kas di akhir jam operasional:</p>
                            <ol class="manual-steps-list">
                                <li>Hitung seluruh uang tunai fisik yang ada pada brankas/laci teller.</li>
                                <li>Pada menu <strong>Laporan Kas Harian</strong>, masukkan total nominal fisik kas.</li>
                                <li>Periksa apakah status kas tercatat <strong>Seimbang</strong> (fisik sama dengan catatan transaksi sistem).</li>
                                <li>Klik <strong>Kirim Laporan</strong>. Laporan ini otomatis tersinkronisasi antar-teller dan dikirimkan ke Administrator untuk diverifikasi.</li>
                            </ol>
                        </article>
                    </div>
                </div>

                <!-- TAB 3: ADMINISTRATOR -->
                <div class="manual-content" id="manual-admin" role="tabpanel">
                    <div class="manual-grid">
                        <article class="manual-card">
                            <span class="manual-card__badge">Pengelolaan User</span>
                            <h3>Manajemen User &amp; Hak Akses</h3>
                            <p>Pengelolaan akun petugas dan staf internal Bank Mini:</p>
                            <ol class="manual-steps-list">
                                <li>Buka menu <strong>Manajemen User</strong> pada dashboard admin.</li>
                                <li>Isi form nama, username, password (min. 12 karakter), dan tentukan role (Administrator atau Teller).</li>
                                <li>Kelola daftar user aktif untuk menjamin hanya petugas berwenang yang dapat mengakses sistem.</li>
                            </ol>
                        </article>

                        <article class="manual-card">
                            <span class="manual-card__badge">Data Anggota</span>
                            <h3>Import &amp; Aktivasi Rekening Nasabah</h3>
                            <p>Pendaftaran massal dan pembukaan rekening siswa baru:</p>
                            <ol class="manual-steps-list">
                                <li><strong>Import Member:</strong> Unduh template Excel di menu <em>Member</em>, isi data siswa, lalu upload file untuk import massal calon nasabah.</li>
                                <li><strong>Aktivasi Nasabah:</strong> Klik tombol <em>Aktivasi</em> pada data siswa yang menunggu, tentukan username, password awal, 6 digit PIN, dan setoran awal minimum.</li>
                                <li>Setelah aktivasi, nomor rekening resmi akan terbit dan akun siswa otomatis tercatat aktif di menu <em>Data Saver</em>.</li>
                            </ol>
                        </article>

                        <article class="manual-card">
                            <span class="manual-card__badge">Akademik</span>
                            <h3>Kenaikan Kelas &amp; Kelulusan</h3>
                            <p>Penyesuaian tingkatan kelas nasabah setiap pergantian tahun ajaran:</p>
                            <ol class="manual-steps-list">
                                <li>Buka menu <strong>Kenaikan Kelas</strong>.</li>
                                <li>Pilih tingkat kelas saat ini (Tingkat 10, 11, atau 12).</li>
                                <li>Pilih kelas tujuan kenaikan (atau status Lulus untuk kelas 12).</li>
                                <li>Centang siswa yang berhak naik kelas, lalu klik <strong>Proses Kenaikan Kelas</strong>. Data saldo dan nomor rekening tetap utuh dan aman.</li>
                            </ol>
                        </article>

                        <article class="manual-card">
                            <span class="manual-card__badge">Audit &amp; Keuangan</span>
                            <h3>Verifikasi Kas, Selisih &amp; Brankas</h3>
                            <p>Otorisasi laporan kas dan pembukuan brankas sekolah:</p>
                            <ol class="manual-steps-list">
                                <li><strong>Verifikasi Laporan:</strong> Periksa laporan harian yang diajukan teller di menu <em>Verifikasi Laporan Harian</em>. Teliti rincian mutasi lalu lakukan <strong>Approve</strong> atau <strong>Reject</strong>.</li>
                                <li><strong>Kas Brankas:</strong> Kas harian yang disetujui otomatis dipindahkan ke pembukuan Kas Brankas sekolah.</li>
                                <li><strong>Laporan Bulanan &amp; PDF:</strong> Tinjau rekapitulasi mutasi bulanan dan cetak laporan PDF resmi untuk arsip akuntansi sekolah.</li>
                            </ol>
                        </article>
                    </div>
                </div>

                <!-- TAB 4: KEAMANAN -->
                <div class="manual-content" id="manual-keamanan" role="tabpanel">
                    <div class="manual-grid">
                        <article class="manual-card">
                            <span class="manual-card__badge">Ketentuan</span>
                            <h3>Standar Password Baru (Min. 12 Karakter)</h3>
                            <p>Demi menjaga keamanan data keuangan sekolah, seluruh pembuatan dan penggantian password wajib memenuhi kriteria berikut:</p>
                            <ol class="manual-steps-list">
                                <li>Panjang password minimal <strong>12 karakter</strong>.</li>
                                <li>Mengandung kombinasi <strong>huruf kapital</strong> (A-Z) dan <strong>huruf kecil</strong> (a-z).</li>
                                <li>Mengandung minimal <strong>1 angka</strong> (0-9).</li>
                                <li>Mengandung minimal <strong>1 karakter spesial / simbol</strong> (misalnya <code>@</code>, <code>#</code>, <code>$</code>, <code>%</code>, <code>!</code>, <code>&amp;</code>, <code>*</code>).</li>
                                <li>Password baru tidak boleh sama dengan password sebelumnya.</li>
                            </ol>
                            <div class="manual-note">
                                <strong>Contoh password yang kuat &amp; valid:</strong> <code>BankMini@2026#Smea</code>
                            </div>
                        </article>

                        <article class="manual-card">
                            <span class="manual-card__badge">Privasi</span>
                            <h3>Keamanan PIN Transaksi &amp; OTP</h3>
                            <p>Pedoman menjaga kerahasiaan otorisasi penarikan dana nasabah:</p>
                            <ol class="manual-steps-list">
                                <li><strong>PIN 6 Digit:</strong> Digunakan secara eksklusif untuk otorisasi penarikan tunai tabungan di hadapan teller.</li>
                                <li>Nasabah wajib mengetik sendiri PIN tanpa memberitahukannya kepada siapa pun termasuk petugas teller.</li>
                                <li><strong>Kode OTP (One-Time Password):</strong> Hanya dikirimkan ke email terdaftar nasabah saat pemulihan password atau reset PIN. Kode ini memiliki batas waktu kedaluwarsa singkat.</li>
                                <li>Segera hubungi Administrator sekolah jika mendapati aktivitas yang mencurigakan pada akun Anda.</li>
                            </ol>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="section section-soft">
            <div class="container">
                <div class="section-heading">
                    <span class="eyebrow">Alur sederhana</span>
                    <h2>Mulai dari layanan, berakhir dengan catatan yang rapi</h2>
                </div>
                <div class="steps">
                    <article class="step">
                        <span class="step-number">LANGKAH 01</span>
                        <h3>Masuk sesuai akun</h3>
                        <p>Admin, teller, dan nasabah menggunakan akun yang telah disediakan sekolah.</p>
                    </article>
                    <article class="step">
                        <span class="step-number">LANGKAH 02</span>
                        <h3>Lakukan layanan</h3>
                        <p>Teller membantu transaksi setoran atau penarikan sesuai prosedur Bank Mini.</p>
                    </article>
                    <article class="step">
                        <span class="step-number">LANGKAH 03</span>
                        <h3>Pantau pencatatan</h3>
                        <p>Informasi transaksi dan laporan dapat dilihat kembali sesuai hak akses masing-masing.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <div class="cta">
                    <h2>Siap menggunakan E-Teller?</h2>
                    <p>Masuk menggunakan akun yang diberikan oleh pengelola Bank Mini sekolah untuk membuka layanan sesuai peran Anda.</p>
                    <a class="button" href="{{ route('login') }}">Login ke E-Teller</a>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container footer-wrap">
            <span>&copy; {{ date('Y') }} E-Teller Bank Mini Sekolah</span>
            <span>Media pembelajaran dan layanan tabungan di lingkungan sekolah.</span>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabBtns = document.querySelectorAll('.manual-tab-btn');
            const contents = document.querySelectorAll('.manual-content');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-tab');

                    tabBtns.forEach(b => {
                        b.classList.remove('active');
                        b.setAttribute('aria-selected', 'false');
                    });
                    contents.forEach(c => c.classList.remove('active'));

                    this.classList.add('active');
                    this.setAttribute('aria-selected', 'true');
                    const targetEl = document.getElementById(targetId);
                    if (targetEl) {
                        targetEl.classList.add('active');
                    }
                });
            });
        });
    </script>
</body>
</html>
