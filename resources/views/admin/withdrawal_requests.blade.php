@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12 mt-4">
        <h3><i class="bi bi-file-earmark-text"></i> Perjanjian Penarikan Nasabah</h3>
        <p class="text-muted">Kelola persetujuan pengajuan perjanjian penarikan saldo nasabah di atas Rp 1.000.000.</p>

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

        <div class="card p-3 mt-3 shadow-sm">
            <h5 class="card-title mb-3"><i class="bi bi-list-check"></i> Daftar Pengajuan Penarikan</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nasabah</th>
                            <th>Nominal</th>
                            <th>Tanggal Rencana</th>
                            <th>Status</th>
                            <th>Catatan Nasabah</th>
                            <th>Catatan Admin</th>
                            <th>Tanggal Review</th>
                            <th class="text-center" style="width: 180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $req->customer?->user?->name ?? 'Nasabah' }}</div>
                                    <small class="text-muted">Rek: <code>{{ $req->customer?->account_number ?? '-' }}</code></small>
                                </td>
                                <td class="fw-semibold text-primary">
                                    Rp {{ number_format($req->amount, 0, ',', '.') }}
                                </td>
                                <td>
                                    {{ $req->withdrawal_date ? \Carbon\Carbon::parse($req->withdrawal_date)->format('d M Y') : '-' }}
                                    @if($req->is_expired)
                                        <div class="badge bg-secondary">Lewat Tanggal</div>
                                    @endif
                                </td>
                                <td>
                                    @if($req->status === 'approved')
                                        <span class="badge bg-success">Disetujui</span>
                                    @elseif($req->status === 'rejected')
                                        <span class="badge bg-danger">Ditolak</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-secondary">{{ $req->note ?: '-' }}</small>
                                </td>
                                <td>
                                    <small class="{{ $req->admin_note ? 'text-danger' : 'text-muted' }}">
                                        {{ $req->admin_note ?: '-' }}
                                    </small>
                                </td>
                                <td>
                                    @if($req->reviewed_at)
                                        <div><small class="text-dark">{{ \Carbon\Carbon::parse($req->reviewed_at)->format('d/m/Y H:i') }}</small></div>
                                        <small class="text-muted">oleh {{ $req->reviewedBy?->name ?? 'Admin' }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    @if($req->status === 'pending')
                                        <form method="POST" action="{{ route('admin.withdrawal_requests.approve', $req->id) }}" class="d-inline" onsubmit="return confirm('Setujui pengajuan penarikan sebesar Rp {{ number_format($req->amount, 0, ',', '.') }} untuk {{ addslashes($req->customer?->user?->name ?? 'Nasabah') }}?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success me-1" title="Setujui Pengajuan">
                                                <i class="bi bi-check-circle"></i> Setujui
                                            </button>
                                        </form>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#rejectModal" 
                                                data-action="{{ route('admin.withdrawal_requests.reject', $req->id) }}"
                                                data-name="{{ $req->customer?->user?->name ?? 'Nasabah' }}"
                                                data-amount="Rp {{ number_format($req->amount, 0, ',', '.') }}"
                                                title="Tolak Pengajuan">
                                            <i class="bi bi-x-circle"></i> Tolak
                                        </button>
                                    @else
                                        <span class="badge bg-light text-muted border">Selesai</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Belum ada pengajuan perjanjian penarikan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
                <div class="mt-3">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Tolak Pengajuan (Ditempatkan di luar tabel & card agar tidak terpotong / glitch) -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <form method="POST" id="rejectModalForm" action="">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fs-6 fw-bold text-danger" id="rejectModalLabel">
                        <i class="bi bi-exclamation-triangle"></i> Tolak Pengajuan Penarikan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="mb-1">
                            <span class="text-muted small">Nasabah:</span>
                            <div class="fw-bold text-dark" id="modalCustomerName">-</div>
                        </div>
                        <div>
                            <span class="text-muted small">Nominal Pengajuan:</span>
                            <div class="fw-bold text-primary" id="modalCustomerAmount">-</div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="modalAdminNote" class="form-label fw-semibold">
                            Catatan / Alasan Penolakan <span class="text-danger">*</span>
                        </label>
                        <textarea name="admin_note" id="modalAdminNote" class="form-control" rows="3" placeholder="Contoh: Saldo tidak mencukupi atau kas cabang belum siap..." maxlength="500" required></textarea>
                        <div class="form-text text-muted">
                            Alasan penolakan akan dapat dibaca langsung oleh nasabah di aplikasi mobile.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3">
                        <i class="bi bi-x-circle"></i> Tolak Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rejectModalEl = document.getElementById('rejectModal');
    if (rejectModalEl) {
        rejectModalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            const actionUrl = button.getAttribute('data-action') || '';
            const name = button.getAttribute('data-name') || '-';
            const amount = button.getAttribute('data-amount') || '-';

            const form = document.getElementById('rejectModalForm');
            const nameEl = document.getElementById('modalCustomerName');
            const amountEl = document.getElementById('modalCustomerAmount');
            const noteInput = document.getElementById('modalAdminNote');

            if (form) form.action = actionUrl;
            if (nameEl) nameEl.textContent = name;
            if (amountEl) amountEl.textContent = amount;
            if (noteInput) {
                noteInput.value = '';
            }
        });

        rejectModalEl.addEventListener('shown.bs.modal', function () {
            const noteInput = document.getElementById('modalAdminNote');
            if (noteInput) {
                noteInput.focus();
            }
        });
    }
});
</script>
@endpush
