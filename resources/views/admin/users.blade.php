@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12 mt-4">
        <h3><i class="bi bi-people"></i> Manajemen User</h3>
        <p class="text-muted">Buat dan kelola akun internal. Akun nasabah dan akun yang sudah dihapus tidak ditampilkan di sini.</p>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
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
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($users) && $users->isNotEmpty())
                            @foreach($users as $u)
                                <tr>
                                    <td>{{ $u->name }}</td>
                                    <td>{{ $u->username }}</td>
                                    <td>{{ ucfirst($u->role) }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr><td colspan="3" class="text-center text-muted">Belum ada user internal.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
