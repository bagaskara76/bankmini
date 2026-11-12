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
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $req->id }}" title="Tolak Pengajuan">
                                            <i class="bi bi-x-circle"></i> Tolak
                                        </button>

                                        <!-- Modal Tolak Pengajuan -->
                                        <div class="modal fade" id="rejectModal{{ $req->id }}" tabindex="-1" aria-labelledby="rejectModalLabel{{ $req->id }}" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content text-start">
                                                    <form method="POST" action="{{ route('admin.withdrawal_requests.reject', $req->id) }}">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fs-6 fw-bold text-danger" id="rejectModalLabel{{ $req->id }}">
                                                                <i class="bi bi-exclamation-triangle"></i> Tolak Pengajuan Penarikan
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="mb-2">
                                                                Nasabah: <strong>{{ $req->customer?->user?->name }}</strong><br>
                                                                Nominal: <strong>Rp {{ number_format($req->amount, 0, ',', '.') }}</strong>
                                                            </p>
                                                            <div class="mb-3">
                                                                <label for="admin_note_{{ $req->id }}" class="form-label fw-semibold">Catatan / Alasan Penolakan <span class="text-danger">*</span></label>
                                                                <textarea name="admin_note" id="admin_note_{{ $req->id }}" class="form-control" rows="3" placeholder="Contoh: Saldo tidak mencukupi atau kas cabang belum siap..." maxlength="500" required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-danger btn-sm">Tolak Pengajuan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
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
@endsection
