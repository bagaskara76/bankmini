@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12 mt-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
        <h3><i class="bi bi-archive"></i> Otorisasi Pemindahan Kas ke Brankas</h3>
        <div class="card p-3 mt-3">
            @if($reports->isNotEmpty())
                <ul class="list-group">
                    @foreach($reports as $report)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ optional($report->teller)->name }}</strong>
                                <div class="small text-muted">{{ $report->report_date->format('d M Y') }} - Rp {{ number_format($report->system_cash, 0, ',', '.') }}</div>
                            </div>
                            <a class="btn btn-sm btn-primary" href="{{ route('admin.print_report', $report->id) }}">Cetak PDF</a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mb-0">Tidak ada pemindahan menunggu persetujuan.</p>
            @endif
        </div>
    </div>
</div>
@endsection