@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12 mt-4">
        <h3><i class="bi bi-megaphone"></i> Info Bank Mini</h3>
        <p class="text-muted">Kelola pengumuman dan informasi Bank Mini untuk nasabah mobile.</p>

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

        <!-- Form Tambah Pengumuman -->
        <div class="card p-3 mt-3 shadow-sm">
            <h5 class="card-title mb-3"><i class="bi bi-plus-circle"></i> Buat Pengumuman Baru</h5>
            <form method="POST" action="{{ route('admin.announcements.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="title" class="form-label fw-semibold">Judul Pengumuman</label>
                        <input type="text" name="title" id="title" class="form-control" placeholder="Contoh: Jam Operasional Libur Nasional" value="{{ old('title') }}" maxlength="200" required>
                    </div>
                    <div class="col-md-4">
                        <label for="type" class="form-label fw-semibold">Tipe Pengumuman</label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="info" {{ old('type') === 'info' ? 'selected' : '' }}>Info</option>
                            <option value="warning" {{ old('type') === 'warning' ? 'selected' : '' }}>Peringatan (Warning)</option>
                            <option value="important" {{ old('type') === 'important' ? 'selected' : '' }}>Penting (Important)</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="content" class="form-label fw-semibold">Isi Pengumuman</label>
                        <textarea name="content" id="content" class="form-control" rows="3" placeholder="Tuliskan isi detail pengumuman yang akan dibaca nasabah..." required>{{ old('content') }}</textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Pengumuman
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabel Daftar Pengumuman -->
        <div class="card p-3 mt-4 shadow-sm">
            <h5 class="card-title mb-3"><i class="bi bi-card-list"></i> Daftar Pengumuman</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Judul &amp; Konten</th>
                            <th style="width: 120px;">Tipe</th>
                            <th style="width: 100px;">Status</th>
                            <th>Dibuat Oleh</th>
                            <th>Tanggal</th>
                            <th class="text-center" style="width: 200px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($announcements as $announcement)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $announcement->title }}</div>
                                    <div class="small text-muted text-truncate" style="max-width: 450px;">{{ Str::limit($announcement->content, 120) }}</div>
                                </td>
                                <td>
                                    @if($announcement->type === 'important')
                                        <span class="badge bg-danger">Penting</span>
                                    @elseif($announcement->type === 'warning')
                                        <span class="badge bg-warning text-dark">Peringatan</span>
                                    @else
                                        <span class="badge bg-info text-dark">Info</span>
                                    @endif
                                </td>
                                <td>
                                    @if($announcement->is_active)
                                        <span class="badge bg-success">Aktif</span>
                                    @else
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td>{{ $announcement->createdBy?->name ?? 'Admin' }}</td>
                                <td><small class="text-muted">{{ $announcement->created_at ? $announcement->created_at->format('d M Y H:i') : '-' }}</small></td>
                                <td class="text-center text-nowrap">
                                    <form method="POST" action="{{ route('admin.announcements.toggle', $announcement->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $announcement->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} me-1" title="{{ $announcement->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <i class="bi {{ $announcement->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                            {{ $announcement->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.announcements.destroy', $announcement->id) }}" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus pengumuman ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Pengumuman">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Belum ada pengumuman yang dibuat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
