# 🏦 E-Teller Bank Mini

**E-Teller Bank Mini** adalah aplikasi berbasis web yang dirancang untuk mensimulasikan sistem perbankan mini (school banking system). Aplikasi ini dikembangkan menggunakan framework **Laravel** untuk memenuhi kebutuhan ujian kompetensi kejuruan (UKK) serta manajemen transaksi perbankan secara terstruktur.

---

## ✨ Fitur Utama

- **Multi-Role Access Control**: Hak akses yang terbagi secara spesifik untuk **Admin**, **Teller**, dan **Nasabah/Customer**.
- **Landing Page Interaktif**: Halaman utama informatif yang menyajikan pengenalan sistem, fitur, serta alur penggunaan aplikasi[cite: 12].
- **Manajemen Nasabah & Akun**: Pendaftaran nasabah baru, aktivasi akun, dan impor data anggota secara massal.
- **Transaksi Perbankan (Teller)**: Memproses setoran tunai (*deposit*), penarikan tunai (*withdrawal*), penambahan modal awal, hingga pemulihan PIN nasabah.
- **Laporan & Jurnal Keuangan**: Rekapitulasi laporan harian, jurnal akuntansi, laporan bulanan berformat PDF, dan verifikasi persetujuan transaksi.

---

## 🛠️ Teknologi yang Digunakan

- **Backend**: PHP, Laravel Framework
- **Frontend**: Blade Templates, HTML5, CSS3, JavaScript
- **Database**: MySQL / MariaDB
- **Deployment**: GitHub Actions (Automated FTP Deployment ke cPanel Hosting)

---

## 🚀 Alur Penggunaan (Workflow)

1. Pengguna mengakses halaman utama dan diarahkan ke **Landing Page**[cite: 12].
2. Pengguna memilih tombol **Login** untuk masuk ke sistem sesuai dengan peran (*role*) masing-masing.
3. **Admin** mengelola data pengguna, konfigurasi sistem, dan verifikasi laporan.
4. **Teller** memproses transaksi keuangan harian nasabah.
5. **Nasabah** dapat memantau saldo serta riwayat transaksi melalui *dashboard* masing-masing.
