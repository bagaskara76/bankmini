@extends('layouts.app')

@push('styles')
<style>
    .admin-dashboard {
        padding-top: 1.5rem;
        padding-bottom: 2rem;
    }

    .admin-dashboard__header {
        margin-bottom: 2rem;
        text-align: center;
    }

    .admin-dashboard__section + .admin-dashboard__section {
        margin-top: 2rem;
    }

    .admin-dashboard__section-title {
        margin-bottom: 1rem;
        padding-bottom: .65rem;
        border-bottom: 1px solid #dce5e2;
        font-size: 1.1rem;
        font-weight: 700;
    }

    .admin-menu-card {
        position: relative;
        min-height: 154px;
        height: 100%;
        padding: 1.15rem;
        border: 1px solid #e7ecea;
        border-radius: 8px;
        background: #fff;
    }

    .admin-menu-card:hover {
        border-color: #b9cec7;
        box-shadow: 0 6px 18px rgba(24, 65, 51, .08);
    }

    .admin-menu-card__heading {
        display: flex;
        align-items: flex-start;
        gap: .7rem;
        margin-bottom: .65rem;
        font-size: 1rem;
        font-weight: 600;
        line-height: 1.4;
    }

    .admin-menu-card__icon {
        flex: 0 0 1.25rem;
        padding-top: .1rem;
        color: #2a5298;
    }

    .admin-menu-card__description {
        margin-bottom: 0;
        color: #66716e;
        font-size: .9rem;
        line-height: 1.55;
    }
</style>
@endpush

@section('content')
<div class="admin-dashboard">
    <header class="admin-dashboard__header">
        <h1 class="h2 fw-bold mb-2">Dashboard Administrator</h1>
        <p class="text-muted mb-0">Kelola akun, data nasabah, laporan teller, dan otorisasi kas.</p>
    </header>

    <section class="admin-dashboard__section" aria-labelledby="admin-management-title">
        <h2 class="admin-dashboard__section-title" id="admin-management-title">Manajemen &amp; Data</h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
            <div class="col">
                <article class="card admin-menu-card">
                    <h3 class="admin-menu-card__heading"><i class="bi bi-people admin-menu-card__icon"></i><span>Manajemen User</span></h3>
                    <p class="admin-menu-card__description">Buat dan kelola akun Administrator atau Teller.</p>
                    <a href="{{ route('admin.users') }}" class="stretched-link" aria-label="Buka Manajemen User"></a>
                </article>
            </div>
            <div class="col">
                <article class="card admin-menu-card">
                    <h3 class="admin-menu-card__heading"><i class="bi bi-people admin-menu-card__icon"></i><span>Member</span></h3>
                    <p class="admin-menu-card__description">Kelola data anggota yang belum memiliki akun dan aktivasi nasabah.</p>
                    <a href="{{ route('admin.members.index') }}" class="stretched-link" aria-label="Buka Member"></a>
                </article>
            </div>
            <div class="col">
                <article class="card admin-menu-card">
                    <h3 class="admin-menu-card__heading"><i class="bi bi-safe admin-menu-card__icon"></i><span>Data Saver</span></h3>
                    <p class="admin-menu-card__description">Kelola nasabah dengan akun yang sudah diaktifkan.</p>
                    <a href="{{ route('admin.data_saver') }}" class="stretched-link" aria-label="Buka Data Saver"></a>
                </article>
            </div>
            <div class="col">
                <article class="card admin-menu-card">
                    <h3 class="admin-menu-card__heading"><i class="bi bi-person-lines-fill admin-menu-card__icon"></i><span>Data Nasabah</span></h3>
                    <p class="admin-menu-card__description">Lihat nama nasabah aktif yang dikelompokkan berdasarkan tingkat dan kelas.</p>
                    <a href="{{ route('admin.nasabah_kelas') }}" class="stretched-link" aria-label="Buka Data Nasabah"></a>
                </article>
            </div>
            <div class="col">
                <article class="card admin-menu-card">
                    <h3 class="admin-menu-card__heading"><i class="bi bi-arrow-up-right-circle admin-menu-card__icon"></i><span>Kenaikan Kelas</span></h3>
                    <p class="admin-menu-card__description">Pilih siswa yang naik kelas atau lulus. Siswa yang tidak dicentang tetap di kelas asal.</p>
                    <a href="{{ route('admin.class_promotion') }}" class="stretched-link" aria-label="Buka fitur kenaikan kelas"></a>
                </article>
            </div>
        </div>
    </section>

    <section class="admin-dashboard__section" aria-labelledby="admin-operations-title">
        <h2 class="admin-dashboard__section-title" id="admin-operations-title">Laporan &amp; Operasional Kas</h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
            <div class="col">
                <article class="card admin-menu-card">
                    <h3 class="admin-menu-card__heading"><i class="bi bi-file-earmark-check admin-menu-card__icon"></i><span>Verifikasi Laporan Harian</span></h3>
                    <p class="admin-menu-card__description">Periksa, setujui, atau tolak laporan kas harian teller.</p>
                    <a href="{{ route('admin.verify_reports') }}" class="stretched-link" aria-label="Buka Verifikasi Laporan Harian"></a>
                </article>
            </div>
            <div class="col">
                <article class="card admin-menu-card">
                    <h3 class="admin-menu-card__heading"><i class="bi bi-calendar-month admin-menu-card__icon"></i><span>Laporan Bulanan</span></h3>
                    <p class="admin-menu-card__description">Lihat ringkasan transaksi dan status laporan kas per bulan.</p>
                    <a href="{{ route('admin.monthly_reports') }}" class="stretched-link" aria-label="Buka Laporan Bulanan"></a>
                </article>
            </div>
            <div class="col">
                <article class="card admin-menu-card">
                    <h3 class="admin-menu-card__heading"><i class="bi bi-search-heart admin-menu-card__icon"></i><span>Selisih Kas</span></h3>
                    <p class="admin-menu-card__description">Tinjau laporan yang ditolak dan selesaikan selisih kas.</p>
                    <a href="{{ route('admin.dispute_resolution') }}" class="stretched-link" aria-label="Buka Selisih Kas"></a>
                </article>
            </div>
            <div class="col">
                <article class="card admin-menu-card">
                    <h3 class="admin-menu-card__heading"><i class="bi bi-archive admin-menu-card__icon"></i><span>Kas Brankas</span></h3>
                    <p class="admin-menu-card__description">Lihat kas yang otomatis dipindahkan setelah laporan disetujui dan unduh PDF.</p>
                    <a href="{{ route('admin.transfer_approval') }}" class="stretched-link" aria-label="Buka Kas Brankas"></a>
                </article>
            </div>
        </div>
    </section>

    <section class="admin-dashboard__section" aria-labelledby="admin-settings-title">
        <h2 class="admin-dashboard__section-title" id="admin-settings-title">Pengaturan Akun</h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
            <div class="col">
                <article class="card admin-menu-card">
                    <h3 class="admin-menu-card__heading"><i class="bi bi-key admin-menu-card__icon"></i><span>Ganti Password</span></h3>
                    <p class="admin-menu-card__description">Perbarui kata sandi akun Administrator Anda demi menjaga keamanan sistem.</p>
                    <a href="{{ route('admin.change_password') }}" class="stretched-link" aria-label="Buka Ganti Password"></a>
                </article>
            </div>
        </div>
    </section>
</div>
@endsection
