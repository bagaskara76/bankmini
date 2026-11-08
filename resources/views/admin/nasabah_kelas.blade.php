@extends('layouts.app')

@push('styles')
<style>
    /* ── Warna per Angkatan ── */
    .grade-10 { --grade-color: #2a5298; --grade-light: #dce8ff; }
    .grade-11 { --grade-color: #6d28d9; --grade-light: #ede9fe; }
    .grade-12 { --grade-color: #b45309; --grade-light: #fef3c7; }
    .alumni-grade { --grade-color: #0f766e; --grade-light: #ccfbf1; }

    .grade-header {
        background: var(--grade-color);
        color: #fff;
        padding: .75rem 1.25rem;
        border-radius: 12px 12px 0 0;
        display: flex;
        align-items: center;
        gap: .6rem;
    }
    .grade-header .badge-count {
        background: rgba(255,255,255,.25);
        font-size: .75rem;
        padding: .25rem .65rem;
        border-radius: 999px;
        font-weight: 600;
    }

    .kelas-card {
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
        transition: box-shadow .2s;
    }
    .kelas-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.09); }

    .kelas-card-header {
        background: var(--grade-light, #f1f5f9);
        padding: .55rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
    }
    .kelas-card-header .kelas-label {
        font-weight: 700;
        color: var(--grade-color, #374151);
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .kelas-card-header .toggle-icon {
        transition: transform .25s;
        color: var(--grade-color, #6b7280);
    }
    .kelas-card-header[aria-expanded="false"] .toggle-icon {
        transform: rotate(-90deg);
    }

    .kelas-table thead { background: var(--grade-light, #f8fafc); }
    .kelas-table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
    .kelas-table td { vertical-align: middle; }

    .stat-card {
        border-radius: 12px;
        padding: 1.1rem 1.4rem;
        border: none;
        color: #fff;
    }

    /* Filter bar */
    .filter-bar { background: #fff; border-radius: 12px; padding: 1rem 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,.06); }

    .empty-state { color: #94a3b8; padding: 3rem 0; }
</style>
@endpush

@section('content')
<div class="row mt-4">
    <div class="col-12 mb-3 d-flex align-items-center gap-3">
        <div>
            <h3 class="mb-0 fw-bold"><i class="bi bi-people-fill me-2 text-primary"></i>Data Nasabah</h3>
            <p class="text-muted mb-0 small">Daftar nasabah aktif dan alumni dikelompokkan berdasarkan tingkat dan kelas</p>
        </div>
        <a href="{{ route('admin.class_promotion') }}" class="btn btn-primary ms-auto text-nowrap">
            <i class="bi bi-arrow-up-right-circle me-1"></i> Kenaikan Kelas
        </a>
    </div>

    {{-- ── Statistik Ringkas ── --}}
    <div class="col-12">
        <div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-3 mb-3">
            <div class="col mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg,#2a5298,#1e3c72);">
                    <div class="small opacity-75 mb-1"><i class="bi bi-people me-1"></i> Total Nasabah Aktif</div>
                    <div class="fs-2 fw-bold">{{ $totalNasabah }}</div>
                </div>
            </div>
            <div class="col mb-3">
                <div class="stat-card" style="background: linear-gradient(135deg,#475569,#1e293b);">
                    <div class="small opacity-75 mb-1"><i class="bi bi-mortarboard me-1"></i> Total Alumni</div>
                    <div class="fs-2 fw-bold">{{ $totalAlumni }}</div>
                </div>
            </div>
            @foreach([10 => ['#2a5298','#1e3c72'], 11 => ['#6d28d9','#4c1d95'], 12 => ['#b45309','#78350f']] as $g => $colors)
                <div class="col mb-3">
                    <div class="stat-card" style="background: linear-gradient(135deg,{{ $colors[0] }},{{ $colors[1] }});">
                        <div class="small opacity-75 mb-1"><i class="bi bi-mortarboard me-1"></i> Kelas {{ $g }}</div>
                        <div class="fs-2 fw-bold">
                            {{ isset($grouped[$g]) ? $grouped[$g]->flatten(1)->count() : 0 }}
                            <span class="fs-6 fw-normal opacity-75">nasabah</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── Filter ── --}}
    <div class="col-12 mb-4">
        <div class="filter-bar">
            <form method="GET" action="{{ route('admin.nasabah_kelas') }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label fw-semibold mb-1 small">Angkatan</label>
                    <select name="grade" class="form-select form-select-sm" style="min-width:120px">
                        <option value="">Semua Angkatan</option>
                        @foreach($gradeList as $g)
                            <option value="{{ $g }}" {{ $gradeFilter == $g ? 'selected' : '' }}>Kelas {{ $g }}</option>
                        @endforeach
                        <option value="lulus" @selected($gradeFilter === 'lulus')>Siswa Lulus</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label fw-semibold mb-1 small">Kelas</label>
                    <select name="kelas" class="form-select form-select-sm" style="min-width:150px">
                        <option value="">Semua Kelas</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k }}" {{ $kelasFilter == $k ? 'selected' : '' }}>{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button class="btn btn-primary btn-sm px-3">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                    @if($gradeFilter || $kelasFilter)
                    <a href="{{ route('admin.nasabah_kelas') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                    @endif
                </div>
                <div class="col-auto ms-auto">
                    <input id="searchBox" type="text" class="form-control form-control-sm" placeholder="🔍 Cari nama nasabah…" style="min-width:220px">
                </div>
            </form>
        </div>
    </div>

    @unless($showAlumniOnly)
        <div class="col-12 mb-2">
            <h4 class="fw-bold mb-0">Nasabah Aktif</h4>
        </div>

        {{-- ── Konten Per Angkatan ── --}}
        <div class="col-12" id="nasabahList">
        @forelse($grouped->sortKeys() as $grade => $byKelas)
        <div class="mb-4 grade-{{ $grade }}">
            {{-- Header Angkatan --}}
            <div class="grade-header">
                <i class="bi bi-mortarboard-fill fs-5"></i>
                <span class="fw-bold fs-5">Angkatan Kelas {{ $grade }}</span>
                <span class="badge-count">
                    {{ $byKelas->flatten(1)->count() }} nasabah &nbsp;|&nbsp;
                    {{ $byKelas->count() }} kelas
                </span>
                <button class="btn btn-sm btn-link text-white ms-auto text-decoration-none"
                        data-bs-toggle="collapse"
                        data-bs-target="#grade-body-{{ $grade }}">
                    <i class="bi bi-chevron-up"></i>
                </button>
            </div>

            <div id="grade-body-{{ $grade }}" class="collapse show" style="border:1.5px solid var(--grade-color); border-top:0; border-radius:0 0 12px 12px; padding:1rem; background:#fff;">
                <div class="row g-3">
                    @foreach($byKelas->sortKeys() as $kelas => $students)
                    <div class="col-md-6 col-xl-4">
                        <div class="kelas-card">
                            {{-- Header Kelas --}}
                            <div class="kelas-card-header"
                                 data-bs-toggle="collapse"
                                 data-bs-target="#kelas-{{ $grade }}-{{ Str::slug($kelas) }}"
                                 aria-expanded="true">
                                <span class="kelas-label">
                                    <i class="bi bi-door-closed-fill"></i>
                                    Kelas {{ $grade }} {{ $kelas }}
                                </span>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill" style="background:var(--grade-color,#6b7280)">
                                        {{ $students->count() }} orang
                                    </span>
                                    <i class="bi bi-chevron-down toggle-icon"></i>
                                </div>
                            </div>

                            {{-- Tabel Nasabah --}}
                            <div id="kelas-{{ $grade }}-{{ Str::slug($kelas) }}" class="collapse show">
                                <table class="table table-sm mb-0 kelas-table">
                                    <thead>
                                        <tr>
                                            <th style="width:32px">#</th>
                                            <th>Nama Nasabah</th>
                                            <th>No. Rekening</th>
                                            <th>Saldo</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($students->sortBy(fn($s) => optional($s->user)->name) as $idx => $customer)
                                        <tr class="nasabah-row">
                                            <td class="text-muted small">{{ $idx + 1 }}</td>
                                            <td>
                                                <span class="nasabah-name fw-medium">{{ optional($customer->user)->name ?? '-' }}</span>
                                                @if(optional($customer->user)->is_blocked)
                                                    <span class="badge bg-danger ms-1" style="font-size:.65rem">Blokir</span>
                                                @endif
                                            </td>
                                            <td><code class="small">{{ $customer->account_number }}</code></td>
                                            <td class="small text-nowrap">Rp {{ number_format($customer->balance,0,',','.') }}</td>
                                            <td>
                                                @if(optional($customer->user)->is_blocked)
                                                    <form method="POST" action="{{ route('admin.customers.unblock', $customer->id) }}">
                                                        @csrf
                                                        <button class="btn btn-xs btn-success" style="font-size:.7rem;padding:.15rem .4rem;" title="Unblock">
                                                            <i class="bi bi-unlock-fill"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.customers.block', $customer->id) }}">
                                                        @csrf
                                                        <button class="btn btn-xs btn-warning" style="font-size:.7rem;padding:.15rem .4rem;" title="Block">
                                                            <i class="bi bi-lock-fill"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr style="background:#f8fafc;">
                                            <td colspan="3" class="small text-muted">Total saldo kelas</td>
                                            <td class="small fw-semibold" style="color:var(--grade-color)">
                                                Rp {{ number_format($students->sum('balance'),0,',','.') }}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @empty
        <div class="text-center empty-state">
            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
            <p class="fs-5">Tidak ada data nasabah ditemukan.</p>
            @if($gradeFilter || $kelasFilter)
                <a href="{{ route('admin.nasabah_kelas') }}" class="btn btn-outline-primary btn-sm">Reset Filter</a>
            @else
                <a href="{{ route('admin.members.index') }}" class="btn btn-primary btn-sm">Kelola Member</a>
            @endif
        </div>
        @endforelse
        </div>
    @endunless

    <div class="col-12 mt-3">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h4 class="fw-bold mb-0">Lulus</h4>
            <span class="badge text-bg-secondary">{{ $totalAlumni }} nasabah</span>
        </div>

        @forelse($alumniGrouped->sortKeys() as $alumniGrade => $alumniByKelas)
            <div class="mb-4 grade-{{ $alumniGrade }} alumni-grade">
                <div class="grade-header">
                    <i class="bi bi-mortarboard-fill fs-5"></i>
                    <span class="fw-bold fs-5">{{ (int) $alumniGrade === 12 ? 'Lulus' : 'Kelas '.$alumniGrade }}</span>
                    <span class="badge-count">
                        {{ $alumniByKelas->flatten(1)->count() }} nasabah &nbsp;|&nbsp;
                        {{ $alumniByKelas->count() }} kelas
                    </span>
                    <button class="btn btn-sm btn-link text-white ms-auto text-decoration-none"
                            data-bs-toggle="collapse"
                            data-bs-target="#alumni-grade-body-{{ $alumniGrade }}"
                            aria-expanded="true"
                            aria-controls="alumni-grade-body-{{ $alumniGrade }}">
                        <i class="bi bi-chevron-up"></i>
                    </button>
                </div>

                <div id="alumni-grade-body-{{ $alumniGrade }}" class="collapse show"
                     style="border:1.5px solid var(--grade-color); border-top:0; border-radius:0 0 12px 12px; padding:1rem; background:#fff;">
                    <div class="row g-3">
                        @foreach($alumniByKelas->sortKeys() as $alumniClass => $alumniCustomers)
                            @php
                                $alumniTableId = 'alumni-kelas-'.$alumniGrade.'-'.Str::slug($alumniClass);
                                $sortedAlumni = $alumniCustomers->sortBy(fn ($student) => optional($student->user)->name);
                            @endphp
                            <div class="col-md-6 col-xl-4">
                                <div class="kelas-card">
                                    <div class="kelas-card-header"
                                         data-bs-toggle="collapse"
                                         data-bs-target="#{{ $alumniTableId }}"
                                         aria-expanded="true"
                                         aria-controls="{{ $alumniTableId }}">
                                        <span class="kelas-label">
                                             <i class="bi bi-mortarboard-fill"></i>
                                             Siswa Lulus
                                        </span>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge rounded-pill" style="background:var(--grade-color,#6b7280)">
                                                {{ $alumniCustomers->count() }} orang
                                            </span>
                                            <i class="bi bi-chevron-down toggle-icon"></i>
                                        </div>
                                    </div>

                                    <div id="{{ $alumniTableId }}" class="collapse show">
                                        <table class="table table-sm mb-0 kelas-table">
                                            <thead>
                                                <tr>
                                                    <th style="width:32px">#</th>
                                                    <th>Nama Nasabah</th>
                                                    <th>No. Rekening</th>
                                                    <th>Saldo</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($sortedAlumni as $index => $alumni)
                                                    <tr class="alumni-row">
                                                        <td class="text-muted small">{{ $index + 1 }}</td>
                                                        <td>
                                                            <span class="nasabah-name fw-medium">{{ optional($alumni->user)->name ?? '-' }}</span>
                                                            @if(optional($alumni->user)->is_blocked)
                                                                <span class="badge bg-danger ms-1" style="font-size:.65rem">Blokir</span>
                                                            @endif
                                                        </td>
                                                        <td><code class="small">{{ $alumni->account_number }}</code></td>
                                                        <td class="small text-nowrap">Rp {{ number_format($alumni->balance, 0, ',', '.') }}</td>
                                                        <td><span class="badge text-bg-secondary">Lulus</span></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr style="background:#f8fafc;">
                                                    <td colspan="3" class="small text-muted">Total saldo kelas</td>
                                                    <td class="small fw-semibold" style="color:var(--grade-color)">
                                                        Rp {{ number_format($alumniCustomers->sum('balance'), 0, ',', '.') }}
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center empty-state">
                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                <p class="fs-5">Belum ada nasabah yang ditandai lulus.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Live search across all nasabah rows ──
document.getElementById('searchBox').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.nasabah-row, .alumni-row').forEach(row => {
        const name = row.querySelector('.nasabah-name')?.textContent.toLowerCase() ?? '';
        const acc  = row.querySelector('code')?.textContent.toLowerCase() ?? '';
        row.style.display = (!q || name.includes(q) || acc.includes(q)) ? '' : 'none';
    });
});

// ── Toggle icon direction when kelas card collapses ──
document.querySelectorAll('.kelas-card-header').forEach(header => {
    const target = document.querySelector(header.getAttribute('data-bs-target'));
    if (!target) return;
    target.addEventListener('show.bs.collapse',  () => header.setAttribute('aria-expanded','true'));
    target.addEventListener('hide.bs.collapse',  () => header.setAttribute('aria-expanded','false'));
});
</script>
@endpush
