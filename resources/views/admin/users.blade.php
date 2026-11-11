@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12 mt-4">
        <h3><i class="bi bi-people"></i> Manajemen User</h3>
        <p class="text-muted">Buat dan kelola akun internal. Akun nasabah dan akun yang sudah dihapus tidak ditampilkan di sini.</p>

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
            <form method="POST" action="{{ route('admin.users.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3">
                    <input name="name" class="form-control" placeholder="Nama" required>
                </div>
                <div class="col-md-2">
                    <input name="username" class="form-control" placeholder="Username" required>
                </div>
                <div class="col-md-2">
                    <input name="password" type="password" class="form-control" placeholder="Password (min. 12 karakter)" minlength="12" required title="Min. 12 karakter, huruf kapital, huruf kecil, angka, karakter spesial">
                </div>
                <div class="col-md-2">
                    <select name="role" class="form-select" required>
                        <option value="teller">Teller</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary">Buat User</button>
                </div>
            </form>

            <hr>

            <h6 class="mt-3">Daftar User</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th class="text-center" style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($users) && $users->isNotEmpty())
                            @foreach($users as $u)
                                <tr>
                                    <td class="fw-semibold">{{ $u->name }}</td>
                                    <td><code>{{ $u->username }}</code></td>
                                    <td>
                                        <span class="badge {{ $u->role === 'admin' ? 'bg-primary' : 'bg-secondary' }}">
                                            {{ ucfirst($u->role) }}
                                        </span>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}">
                                            Edit
                                        </button>
                                        @if(Auth::id() !== $u->id)
                                            <form method="POST" action="{{ route('admin.users.delete', $u->id) }}" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Yakin ingin menghapus user {{ addslashes($u->name) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Modal Edit User -->
                                <div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1" aria-labelledby="editUserModalLabel{{ $u->id }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('admin.users.update', $u->id) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title fs-6 fw-bold" id="editUserModalLabel{{ $u->id }}">Edit Data User: {{ $u->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Nama Lengkap</label>
                                                        <input type="text" name="name" class="form-control" value="{{ old('name', $u->name) }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Username</label>
                                                        <input type="text" name="username" class="form-control" value="{{ old('username', $u->username) }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Role</label>
                                                        <select name="role" class="form-select" required>
                                                            <option value="teller" {{ $u->role === 'teller' ? 'selected' : '' }}>Teller</option>
                                                            <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Administrator</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Password Baru (Opsional)</label>
                                                        <input type="password" name="password" class="form-control" minlength="12" placeholder="Kosongkan jika tidak ingin mengubah password">
                                                        <div class="form-text small">Jika diisi, minimal 12 karakter kombinasi huruf besar, kecil, angka, dan simbol.</div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary btn-sm px-3">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <tr><td colspan="4" class="text-center text-muted">Belum ada user internal.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
