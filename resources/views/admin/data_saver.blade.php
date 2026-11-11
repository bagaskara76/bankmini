@extends('layouts.app')

@push('styles')
<style>
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-12 mt-4">
        <h3>Data Saver</h3>
        <p class="text-muted">Daftar nasabah yang sudah diaktifkan dan memiliki akun rekening.</p>

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
            <div class="mb-3">
                <label for="customerSearch" class="visually-hidden">Cari nasabah aktif</label>
                <input id="customerSearch" type="search" class="form-control"
                       placeholder="Cari nasabah berdasarkan nama, username, email, kelas, atau nomor rekening...">
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Email OTP</th>
                            <th>Kelas</th>
                            <th>No. Rekening</th>
                            <th>Saldo</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="customerRows">
                        @forelse($customers as $i => $customer)
                            @php($user = $customer->user)
                            <tr data-customer-search="{{ strtolower(implode(' ', array_filter([
                                $user?->name,
                                $user?->username,
                                $user?->email,
                                $customer->grade,
                                $customer->kelas,
                                $customer->account_number,
                            ]))) }}">
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td>{{ $user?->name ?? '-' }}</td>
                                <td>{{ $user?->username ?? '-' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.customers.email', $customer->id) }}"
                                          class="d-flex gap-1">
                                        @csrf
                                        <input type="email" name="email" class="form-control form-control-sm"
                                               value="{{ $user?->email }}" placeholder="Email nasabah"
                                               aria-label="Email OTP {{ $user?->name }}" required>
                                        <button class="btn btn-sm btn-outline-primary" title="Simpan email OTP">
                                            Simpan
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    @if($customer->grade && $customer->kelas)
                                        <span class="badge bg-primary">{{ $customer->grade }} {{ $customer->kelas }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td><code>{{ $customer->account_number }}</code></td>
                                <td>Rp {{ number_format($customer->balance, 0, ',', '.') }}</td>
                                <td>
                                    @if($user?->is_blocked)
                                        <span class="badge bg-danger">Diblokir</span>
                                    @else
                                        <span class="badge bg-success">Aktif</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#editCustomerModal{{ $customer->id }}">
                                        Edit
                                    </button>

                                    @if($user?->is_blocked)
                                        <form method="POST" action="{{ route('admin.customers.unblock', $customer->id) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success">Buka Blokir</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.customers.block', $customer->id) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-warning">Blokir</button>
                                        </form>
                                    @endif

                                    @php
                                        $canDelete = $customer->balance <= \App\Models\Customer::MINIMUM_BALANCE
                                                     && !$customer->transactions()->exists();
                                    @endphp
                                    @if($canDelete)
                                    <form method="POST" action="{{ route('admin.customers.delete', $customer->id) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Hapus nasabah {{ addslashes($user?->name ?? '') }}? Histori transaksi tetap tersimpan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                    @else
                                    <button class="btn btn-sm btn-danger" disabled
                                            title="@if($customer->balance > \App\Models\Customer::MINIMUM_BALANCE)Saldo masih aktif (Rp {{ number_format($customer->balance, 0, ',', '.') }})@else Memiliki riwayat transaksi @endif">
                                        Hapus
                                    </button>
                                    @endif
                                </td>
                            </tr>

                            <!-- Modal Edit Customer -->
                            <div class="modal fade" id="editCustomerModal{{ $customer->id }}" tabindex="-1" aria-labelledby="editCustomerModalLabel{{ $customer->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('admin.customers.update', $customer->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title fs-6 fw-bold" id="editCustomerModalLabel{{ $customer->id }}">Edit Data Nasabah: {{ $user?->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Nama Lengkap</label>
                                                    <input type="text" name="name" class="form-control" value="{{ old('name', $user?->name) }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Username</label>
                                                    <input type="text" name="username" class="form-control" value="{{ old('username', $user?->username) }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Email OTP</label>
                                                    <input type="email" name="email" class="form-control" value="{{ old('email', $user?->email) }}" placeholder="email@contoh.com">
                                                </div>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Tingkat Kelas</label>
                                                        <select name="grade" class="form-select" required>
                                                            <option value="10" {{ (int)$customer->grade === 10 ? 'selected' : '' }}>Kelas 10</option>
                                                            <option value="11" {{ (int)$customer->grade === 11 ? 'selected' : '' }}>Kelas 11</option>
                                                            <option value="12" {{ (int)$customer->grade === 12 ? 'selected' : '' }}>Kelas 12</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Kelas</label>
                                                        <select name="kelas" class="form-select" required>
                                                            @foreach($kelasList ?? [] as $k)
                                                                <option value="{{ $k }}" {{ $customer->kelas === $k ? 'selected' : '' }}>{{ $k }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Password Baru (Opsional)</label>
                                                    <input type="password" name="password" class="form-control" minlength="12" placeholder="Kosongkan jika tidak ingin mengubah password">
                                                    <div class="form-text small">Jika diisi, minimal 12 karakter kombinasi huruf besar, kecil, angka, dan simbol.</div>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label fw-semibold">PIN Baru 6 Digit (Opsional)</label>
                                                    <input type="password" name="pin" class="form-control" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Kosongkan jika tidak ingin mengubah PIN">
                                                    <div class="form-text small">PIN 6 digit angka untuk transaksi tarik tunai.</div>
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
                        @empty
                            <tr id="emptyCustomersRow">
                                <td colspan="9" class="text-center text-muted py-3">Belum ada nasabah yang diaktifkan.</td>
                            </tr>
                        @endforelse
                        @if($customers->isNotEmpty())
                            <tr id="noCustomerSearchResults" class="d-none">
                                <td colspan="9" class="text-center text-muted py-3">Nasabah tidak ditemukan.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('customerSearch').addEventListener('input', function () {
    const query = this.value.trim().toLowerCase();
    const rows = Array.from(document.querySelectorAll('#customerRows tr[data-customer-search]'));
    let visibleRows = 0;

    rows.forEach(function (row) {
        const matches = row.dataset.customerSearch.includes(query);
        row.classList.toggle('d-none', !matches);
        if (matches) {
            visibleRows += 1;
        }
    });

    const emptyResult = document.getElementById('noCustomerSearchResults');
    if (emptyResult) {
        emptyResult.classList.toggle('d-none', visibleRows > 0);
    }
});
</script>
@endpush
