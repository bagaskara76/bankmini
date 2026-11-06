@extends('layouts.app')

@push('styles')
<style>
    .member-delete-icon {
        display: inline-block !important;
        width: 1rem;
        height: 1rem;
        vertical-align: -0.15em;
    }

    .member-navigation .nav-link {
        color: #111827 !important;
        background-color: #e2e8f0;
        border: 1px solid #94a3b8;
        font-weight: 600;
    }

    .member-navigation .nav-link:hover,
    .member-navigation .nav-link:focus {
        color: #fff !important;
        background-color: #334155;
        border-color: #334155;
    }

    .member-navigation .nav-link.active {
        color: #fff !important;
        background-color: #0756c9;
        border-color: #0756c9;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12 mt-4">
        <h3><i class="bi bi-people"></i> Member</h3>
        <p class="text-muted">Kelola anggota yang belum memiliki akun. Aktivasi akun dilakukan oleh admin.</p>

        <nav class="nav nav-pills member-navigation gap-2 mb-3" aria-label="Navigasi anggota dan nasabah">
            <a class="nav-link active" aria-current="page" href="{{ route('admin.members.index') }}">Member</a>
            <a class="nav-link" href="{{ route('admin.data_saver') }}">Data Saver</a>
            <a class="nav-link" href="{{ route('admin.nasabah_kelas') }}">Nasabah per Kelas</a>
        </nav>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card p-3 mt-3">
            <h6 class="mb-2"><i class="bi bi-file-earmark-spreadsheet"></i> Import Anggota dari XLSX</h6>
            <p class="text-muted mb-3">
                Import hanya membuat data anggota yang menunggu aktivasi. Username, password, PIN, saldo,
                nomor rekening, dan QR belum dibuat. Jumlah anggota per file tidak dibatasi.
            </p>
            <div class="d-flex flex-wrap gap-2 align-items-start">
                <a href="{{ route('admin.members.template') }}" class="btn btn-outline-success">
                    <i class="bi bi-download"></i> Unduh Template XLSX
                </a>
                <form method="POST" action="{{ route('admin.members.import') }}"
                      enctype="multipart/form-data" class="d-flex flex-wrap gap-2">
                    @csrf
                    <input type="file" name="file" accept=".xlsx"
                           class="form-control @error('file') is-invalid @enderror" required>
                    <button class="btn btn-success">
                        <i class="bi bi-upload"></i> Import Anggota
                    </button>
                    @error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </form>
            </div>
            <small class="text-muted mt-2">
                Kolom template: name, email (opsional), grade (10/11/12), kelas. Email harus unik.
            </small>
        </div>

        <div class="card p-3 mt-3">
            <h6 class="mb-3"><i class="bi bi-person-plus-fill"></i> Tambah Anggota Menunggu Aktivasi</h6>
            <form method="POST" action="{{ route('admin.members.store') }}" class="row g-2">
                @csrf

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Nama Anggota</label>
                    <input name="name" class="form-control @error('name') is-invalid @enderror"
                           placeholder="Nama lengkap siswa" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Email OTP <small class="text-muted">(opsional)</small></label>
                    <input name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                           placeholder="Email aktif anggota" value="{{ old('email') }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Tingkat</label>
                    <select name="grade" class="form-select @error('grade') is-invalid @enderror" required>
                        <option value="">—</option>
                        @foreach($gradeList as $g)
                            <option value="{{ $g }}" {{ old('grade') == $g ? 'selected' : '' }}>
                                {{ $g }}
                            </option>
                        @endforeach
                    </select>
                    @error('grade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Kelas</label>
                    <select name="kelas" class="form-select @error('kelas') is-invalid @enderror" required>
                        <option value="">— Pilih Kelas —</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k }}"
                                    data-grades="{{ $k === 'LPS 2' ? '10,11' : '10,11,12' }}"
                                    {{ old('kelas') == $k ? 'selected' : '' }}>
                                {{ $k }}
                            </option>
                        @endforeach
                    </select>
                    @error('kelas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> Simpan Anggota
                    </button>
                </div>
            </form>

            <script>
                (() => {
                    const gradeSelect = document.querySelector('select[name="grade"]');
                    const classSelect = document.querySelector('select[name="kelas"]');
                    if (!gradeSelect || !classSelect) return;

                    const updateClasses = () => {
                        const grade = gradeSelect.value;
                        for (const option of classSelect.options) {
                            if (!option.value) continue;
                            const availableGrades = option.dataset.grades.split(',');
                            option.hidden = grade !== '' && !availableGrades.includes(grade);
                            option.disabled = option.hidden;
                        }
                        if (classSelect.selectedOptions[0]?.disabled) {
                            classSelect.value = '';
                        }
                    };

                    gradeSelect.addEventListener('change', updateClasses);
                    updateClasses();
                })();
            </script>

            <hr>

            <h6 class="mt-3">Daftar Anggota Belum Memiliki Akun</h6>
            <p class="small text-muted">Akun dan saldo baru dibuat ketika admin mengaktifkan anggota.</p>
            <form method="GET" action="{{ route('admin.members.index') }}" class="row g-2 mb-3">
                <div class="col-md-8 col-lg-6">
                    <label for="memberSearch" class="visually-hidden">Cari nama anggota</label>
                    <input id="memberSearch" name="search" type="search" class="form-control"
                           value="{{ $memberSearch }}" placeholder="Cari nama anggota...">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">Cari Nama</button>
                    @if($memberSearch !== '')
                        <a href="{{ route('admin.members.index') }}" class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </form>
            <form method="POST" action="{{ route('admin.members.delete_all') }}" class="mb-3"
                  onsubmit="return confirm('Hapus semua data anggota yang belum diaktifkan? Tindakan ini tidak dapat dibatalkan.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" {{ $pendingMemberCount === 0 ? 'disabled' : '' }}>
                    <svg class="member-delete-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                        <path fill="currentColor" d="M5.5 5.5a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                        <path fill="currentColor" d="M14.5 3a1 1 0 0 1-1 1H13v9.5a1.5 1.5 0 0 1-1.5 1.5h-7A1.5 1.5 0 0 1 3 13.5V4h-.5a1 1 0 0 1 0-2h3.086l.707-.707A1 1 0 0 1 7 1h2a1 1 0 0 1 .707.293L10.414 2H13.5a1 1 0 0 1 1 1M4 4v9.5a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 .5-.5V4zM6.414 2l-.5.5h4.172l-.5-.5z"/>
                    </svg>
                    Hapus Semua
                </button>
                <small class="text-muted ms-2">Menghapus semua anggota yang belum diaktifkan, termasuk yang tidak terlihat karena pencarian.</small>
            </form>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Email</th>
                            <th>Status Akun</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingMembers as $member)
                            <tr>
                                <td>{{ $member->name }}</td>
                                <td>{{ $member->grade }} {{ $member->kelas }}</td>
                                <td>{{ $member->email ?: '-' }}</td>
                                <td><span class="badge bg-warning text-dark">Belum dibuatkan akun</span></td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#activateMemberModal"
                                            data-member-name="{{ $member->name }}"
                                            data-activate-url="{{ route('admin.members.activate', $member) }}">
                                        <i class="bi bi-person-check"></i> Aktifkan Akun
                                    </button>
                                    <form method="POST" action="{{ route('admin.members.delete', $member) }}"
                                          class="d-inline" onsubmit="return confirm('Hapus data anggota {{ addslashes($member->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger member-delete-button"
                                                title="Hapus anggota" aria-label="Hapus anggota {{ $member->name }}">
                                            <svg class="member-delete-icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                                <path fill="currentColor" d="M5.5 5.5a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                                <path fill="currentColor" d="M14.5 3a1 1 0 0 1-1 1H13v9.5a1.5 1.5 0 0 1-1.5 1.5h-7A1.5 1.5 0 0 1 3 13.5V4h-.5a1 1 0 0 1 0-2h3.086l.707-.707A1 1 0 0 1 7 1h2a1 1 0 0 1 .707.293L10.414 2H13.5a1 1 0 0 1 1 1M4 4v9.5a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 .5-.5V4zM6.414 2l-.5.5h4.172l-.5-.5z"/>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    @if($memberSearch !== '')
                                        Tidak ada anggota dengan nama "{{ $memberSearch }}".
                                    @else
                                        Tidak ada anggota yang menunggu aktivasi.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($pendingMembers->total() > 0)
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                    <small class="text-muted">
                        Menampilkan {{ $pendingMembers->firstItem() }}–{{ $pendingMembers->lastItem() }}
                        dari {{ $pendingMembers->total() }} nama
                    </small>
                    {{ $pendingMembers->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif

        </div>
    </div>
</div>

<div class="modal fade" id="activateMemberModal" tabindex="-1" aria-labelledby="activateMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="activateMemberForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="activateMemberModalLabel">Aktifkan Akun Nasabah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Buat akun untuk <strong id="activateMemberName"></strong>.</p>
                    <div class="mb-3">
                        <label for="activationUsername" class="form-label">Username</label>
                        <input id="activationUsername" name="username" class="form-control" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label for="activationPassword" class="form-label">Password</label>
                        <input id="activationPassword" name="password" type="password" class="form-control"
                               minlength="5" required autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label for="activationPin" class="form-label">PIN Keamanan (6 digit)</label>
                        <input id="activationPin" name="pin" type="password" class="form-control"
                               inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="off">
                    </div>
                    <div class="mb-2">
                        <label for="activationBalance" class="form-label">Saldo Awal (Rp)</label>
                        <input id="activationBalance" name="initial_balance" type="number" class="form-control"
                               min="{{ \App\Models\Customer::MINIMUM_BALANCE }}" max="9223372036854775807"
                               step="1" value="{{ \App\Models\Customer::MINIMUM_BALANCE }}" required>
                        <small class="text-muted">
                            Minimum Rp{{ number_format(\App\Models\Customer::MINIMUM_BALANCE, 0, ',', '.') }}.
                            Saldo awal akan dicatat sebagai setoran pembukaan.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-person-check"></i> Buat Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('activateMemberModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('activateMemberName').textContent = button.dataset.memberName;
    document.getElementById('activateMemberForm').action = button.dataset.activateUrl;
});
</script>
@endpush
