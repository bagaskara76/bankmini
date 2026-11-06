<nav aria-label="Navigasi administrator">
    <div class="admin-sidebar__group">
        <p class="admin-sidebar__title">Beranda</p>
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
           href="{{ route('admin.dashboard') }}"
           @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
            Dashboard
        </a>
    </div>

    <div class="admin-sidebar__group">
        <p class="admin-sidebar__title">Manajemen &amp; Data</p>
        <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}"
           href="{{ route('admin.users') }}"
           @if(request()->routeIs('admin.users*')) aria-current="page" @endif>
            Manajemen User
        </a>
        <a class="nav-link {{ request()->routeIs('admin.members.*', 'admin.master_accounts') ? 'active' : '' }}"
           href="{{ route('admin.members.index') }}"
           @if(request()->routeIs('admin.members.*', 'admin.master_accounts')) aria-current="page" @endif>
            Member
        </a>
        <a class="nav-link {{ request()->routeIs('admin.data_saver', 'admin.customers.*') ? 'active' : '' }}"
           href="{{ route('admin.data_saver') }}"
           @if(request()->routeIs('admin.data_saver', 'admin.customers.*')) aria-current="page" @endif>
            Data Saver &amp; Email OTP
        </a>
        <a class="nav-link {{ request()->routeIs('admin.nasabah_kelas') ? 'active' : '' }}"
           href="{{ route('admin.nasabah_kelas') }}"
           @if(request()->routeIs('admin.nasabah_kelas')) aria-current="page" @endif>
            Nasabah per Kelas
        </a>
    </div>

    <div class="admin-sidebar__group">
        <p class="admin-sidebar__title">Laporan &amp; Operasional Kas</p>
        <a class="nav-link {{ request()->routeIs('admin.verify_reports', 'admin.review_report', 'admin.approve_report', 'admin.reject_report', 'admin.print_report') ? 'active' : '' }}"
           href="{{ route('admin.verify_reports') }}"
           @if(request()->routeIs('admin.verify_reports', 'admin.review_report', 'admin.approve_report', 'admin.reject_report', 'admin.print_report')) aria-current="page" @endif>
            Verifikasi Laporan Harian
        </a>
        <a class="nav-link {{ request()->routeIs('admin.monthly_reports*') ? 'active' : '' }}"
           href="{{ route('admin.monthly_reports') }}"
           @if(request()->routeIs('admin.monthly_reports*')) aria-current="page" @endif>
            Laporan Bulanan
        </a>
        <a class="nav-link {{ request()->routeIs('admin.audit_journal') ? 'active' : '' }}"
           href="{{ route('admin.audit_journal') }}"
           @if(request()->routeIs('admin.audit_journal')) aria-current="page" @endif>
            Audit Jurnal Akuntansi
        </a>
        <a class="nav-link {{ request()->routeIs('admin.dispute_resolution') ? 'active' : '' }}"
           href="{{ route('admin.dispute_resolution') }}"
           @if(request()->routeIs('admin.dispute_resolution')) aria-current="page" @endif>
            Penyelesaian Selisih Kas
        </a>
        <a class="nav-link {{ request()->routeIs('admin.transfer_approval', 'admin.approve_transfer') ? 'active' : '' }}"
           href="{{ route('admin.transfer_approval') }}"
           @if(request()->routeIs('admin.transfer_approval', 'admin.approve_transfer')) aria-current="page" @endif>
            Otorisasi Pemindahan Kas
        </a>
    </div>

    <div class="admin-sidebar__group">
        <p class="admin-sidebar__title">Sistem</p>
        <a class="nav-link {{ request()->routeIs('admin.config') ? 'active' : '' }}"
           href="{{ route('admin.config') }}"
           @if(request()->routeIs('admin.config')) aria-current="page" @endif>
            Konfigurasi Sistem
        </a>
    </div>
</nav>
