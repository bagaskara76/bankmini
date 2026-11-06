@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12 mt-4">
        <h3><i class="bi bi-calendar-month"></i> Laporan Bulanan</h3>

        <div class="card p-3 mt-3 mb-4">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
                <form method="GET" action="{{ route('admin.monthly_reports') }}" class="d-flex flex-wrap align-items-end gap-2">
                    <div>
                        <label for="month" class="form-label mb-1">Bulan</label>
                        <select id="month" name="month" class="form-select">
                            @foreach(range(1, 12) as $monthOption)
                                <option value="{{ $monthOption }}" @selected($month === $monthOption)>{{ \Carbon\Carbon::create()->month($monthOption)->translatedFormat('F') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="year" class="form-label mb-1">Tahun</label>
                        <input id="year" type="number" name="year" class="form-control" min="2000" max="2100" value="{{ $year }}" required style="width: 112px">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Tampilkan</button>
                </form>
                <a class="btn btn-outline-primary" href="{{ route('admin.monthly_reports.pdf', ['month' => $month, 'year' => $year]) }}">
                    <i class="bi bi-file-earmark-pdf"></i> Unduh PDF Bulanan
                </a>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-baseline justify-content-between mb-3">
            <h5 class="mb-1">Laporan {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}</h5>
            <span class="text-muted small">{{ $dailyReports->count() }} laporan disetujui</span>
        </div>
        <div class="card p-3">
            @if($dailyReports->isNotEmpty())
                <ul class="list-group">
                    @foreach($dailyReports as $report)
                        <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <strong>{{ optional($report->teller)->name ?? 'Teller tidak ditemukan' }}</strong>
                                <div class="small text-muted">{{ $report->report_date->format('d M Y') }} - Rp {{ number_format($report->system_cash, 0, ',', '.') }}</div>
                            </div>
                            <span class="badge text-bg-success">Disetujui</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mb-0 text-muted">Belum ada laporan kas yang disetujui pada bulan ini.</p>
            @endif
        </div>
    </div>
</div>
@endsection