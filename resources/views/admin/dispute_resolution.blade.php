@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12 mt-4">
        <h3><i class="bi bi-search"></i> Selisih Kas</h3>
        <div class="card p-3 mt-3">
            @if($reports->isNotEmpty())
                <ul class="list-group">
                    @foreach($reports as $report)
                        <li class="list-group-item">
                            <strong>{{ optional($report->teller)->name }}</strong> - {{ $report->report_date->format('d M Y') }}
                            <div class="small text-muted">Laporan web (kas sistem): Rp {{ number_format($report->system_cash, 0, ',', '.') }}</div>
                            <div class="small text-muted">Kas fisik teller: Rp {{ number_format($report->physical_cash, 0, ',', '.') }}</div>
                            <div class="small text-danger">Selisih (fisik - sistem): Rp {{ number_format($report->physical_cash - $report->system_cash, 0, ',', '.') }}</div>
                            <div class="mt-2"><strong>Catatan penolakan:</strong> {{ $report->rejection_note ?: 'Admin tidak memberikan catatan.' }}</div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mb-0">Tidak ada laporan berselisih saat ini.</p>
            @endif
        </div>
    </div>
</div>
@endsection