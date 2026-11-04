@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12 mt-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
        </a>
        <h3><i class="bi bi-journal-text"></i> Audit Jurnal Akuntansi</h3>
        <p class="text-muted">Akses baca untuk tabel jurnal_akuntansi. (Placeholder)</p>
        <div class="card p-3 mt-3">
            <p>Daftar jurnal akuntansi dan tools audit akan muncul di sini.</p>
        </div>
    </div>
</div>
@endsection
