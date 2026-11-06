@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12 mt-4">
        <h3><i class="bi bi-file-earmark-check"></i> Verifikasi Laporan Harian</h3>

        @if(session('success'))
            <div class="alert alert-success mt-2">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger mt-2">{{ session('error') }}</div>
        @endif
        @error('rejection_note')
            <div class="alert alert-danger mt-2">{{ $message }}</div>
        @enderror

        <div class="card p-3 mt-3">
            @if($reports->isNotEmpty())
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Teller</th><th>Tanggal</th><th>Total Setoran</th><th>Total Penarikan</th>
                                <th>Modal Awal</th><th>Kas Sistem</th><th>Kas Fisik</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $report)
                            <tr>
                                <td>{{ optional($report->teller)->name }}</td>
                                <td>{{ $report->report_date->format('d M Y') }}</td>
                                <td>Rp {{ number_format($report->total_deposit, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($report->total_withdrawal, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($report->initial_capital, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($report->system_cash, 0, ',', '.') }}</td>
                                <td class="{{ $report->physical_cash == $report->system_cash ? 'text-success' : 'text-danger' }}">Rp {{ number_format($report->physical_cash, 0, ',', '.') }}</td>
                                <td>
                                    <a href="{{ route('admin.review_report', $report->id) }}" class="btn btn-sm btn-primary"><i class="bi bi-eye"></i> Lihat laporan</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mb-0">Tidak ada laporan menunggu verifikasi.</p>
            @endif
        </div>
    </div>
</div>
@endsection