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

                                    <form method="POST" action="{{ route('admin.customers.delete', $customer->id) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Hapus nasabah {{ addslashes($user?->name ?? '') }}? Histori transaksi tetap tersimpan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
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
