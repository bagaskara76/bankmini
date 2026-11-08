@extends('layouts.app')

@push('styles')
<style>
    .promotion-card { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; }
    .promotion-help { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; }
    .student-row td { vertical-align: middle; }
    .student-row:hover { background: #f8fafc; }
</style>
@endpush

@section('content')
<div class="py-3">
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Kenaikan Kelas &amp; Kelulusan Siswa</h3>
        <p class="text-muted mb-0">Centang siswa yang naik kelas atau lulus. Siswa yang tidak dicentang akan tetap di kelas asal.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="promotion-card p-3 h-100">
                <div class="small text-muted">Total siswa aktif</div>
                <div class="fs-3 fw-bold">{{ $customers->count() }}</div>
                <div class="small text-muted">Pada kelas asal yang dipilih</div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="promotion-help p-3 h-100">
                <div class="fw-semibold mb-1">Ketentuan proses</div>
                <ul class="small mb-0 ps-3">
                    <li>Siswa yang tidak dicentang tetap terdaftar di kelas asal.</li>
                    @if($destinationGrade)
                        <li>Siswa yang dicentang akan masuk ke kelas tujuan tingkat {{ $destinationGrade }}.</li>
                        <li>Kapasitas setiap rombel tujuan maksimal {{ \App\Models\Customer::MAX_CLASS_SIZE }} siswa.</li>
                    @else
                        <li>Siswa kelas 12 yang dicentang akan ditandai lulus dan tetap tercatat pada kelas terakhirnya.</li>
                    @endif
                    <li>Rekening dan seluruh riwayat transaksi tetap tersimpan.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="promotion-card p-3 mb-3">
        <form method="GET" action="{{ route('admin.class_promotion') }}" class="row g-2 align-items-end" id="sourceFilter">
            <div class="col-md-5">
                <label for="grade" class="form-label fw-semibold">Tingkat asal</label>
                <select id="grade" name="grade" class="form-select">
                    @foreach([10, 11, 12] as $availableGrade)
                        <option value="{{ $availableGrade }}" @selected($grade === $availableGrade)>Kelas {{ $availableGrade }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label for="source_class" class="form-label fw-semibold">Pilih rombel asal siswa</label>
                <select id="source_class" name="source_class" class="form-select">
                    <option value="">Pilih rombel</option>
                    @foreach($sourceClasses as $availableClass)
                        <option value="{{ $availableClass }}" @selected($sourceClass === $availableClass)>
                            Kelas {{ $grade }} {{ $availableClass }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" type="submit">Terapkan Filter</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.class_promotion', ['grade' => $grade]) }}">Reset</a>
            </div>
        </form>
    </div>

    <form method="POST" action="{{ route('admin.class_promotion.process') }}" id="promotionForm">
        @csrf
        <input type="hidden" name="source_grade" value="{{ $grade }}">
        <input type="hidden" name="source_class" value="{{ $sourceClass }}">
        <div class="promotion-card overflow-hidden">
            <div class="p-3 border-bottom d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Daftar Siswa {{ $sourceClass ? 'Kelas '.$grade.' '.$sourceClass : 'Rombel Belum Dipilih' }}</h5>
                    <div class="small text-muted">{{ $customers->count() }} siswa terdaftar di kelas ini</div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-end">
                    @if($destinationGrade)
                        <div>
                            <label for="destination_class" class="form-label small fw-semibold mb-1">Rombel tujuan</label>
                            <select id="destination_class" name="destination_class" class="form-select form-select-sm" required>
                                <option value="">Pilih kelas tujuan</option>
                                @foreach($destinationClasses as $destinationClass)
                                    @php
                                        $occupied = (int) ($destinationCounts[$destinationClass] ?? 0);
                                        $remaining = \App\Models\Customer::MAX_CLASS_SIZE - $occupied;
                                        $isFull = $remaining <= 0;
                                    @endphp
                                    <option value="{{ $destinationClass }}"
                                        data-remaining="{{ max(0, $remaining) }}"
                                        @selected(old('destination_class') === $destinationClass)
                                        @disabled($isFull)>
                                        Kelas {{ $destinationGrade }} {{ $destinationClass }}
                                        ({{ $occupied }}/{{ \App\Models\Customer::MAX_CLASS_SIZE }} terisi, sisa {{ max(0, $remaining) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary btn-sm promotion-submit" type="submit">Proses Kenaikan Kelas</button>
                    @else
                        <button class="btn btn-primary btn-sm promotion-submit" type="submit">Proses Kelulusan</button>
                    @endif
                    <span class="badge text-bg-light border"><span id="selectedCount">0</span> dipilih</span>
                </div>
            </div>

            @if($destinationGrade)
                <div id="capacityWarning" class="alert alert-warning rounded-0 mb-0 py-2 d-none" role="status">
                    Jumlah siswa yang dipilih melebihi sisa kursi rombel tujuan. Pilih rombel lain atau kurangi pilihan.
                </div>
            @endif

            <div class="px-3 py-2 small bg-light border-bottom">
                Nasabah yang <strong>tidak dicentang</strong> akan tetap berada di kelas {{ $grade }} (tinggal kelas).
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:48px">
                                <input class="form-check-input" type="checkbox" id="selectAll" aria-label="Pilih semua siswa">
                            </th>
                            <th style="width:70px">No.</th>
                            <th>No. Rekening</th>
                            <th>Nama Siswa</th>
                            <th>Username</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $index => $customer)
                            <tr class="student-row">
                                <td class="ps-3">
                                    <input class="form-check-input student-checkbox" type="checkbox" name="customer_ids[]"
                                           value="{{ $customer->id }}" @checked(in_array($customer->id, old('customer_ids', [])))>
                                </td>
                                <td class="text-muted">{{ $index + 1 }}</td>
                                <td><code>{{ $customer->account_number }}</code></td>
                                <td class="fw-semibold">{{ optional($customer->user)->name ?? '-' }}</td>
                                <td>{{ optional($customer->user)->username ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    {{ $sourceClass ? 'Tidak ada siswa aktif pada rombel ini.' : 'Pilih rombel asal untuk menampilkan daftar siswa.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const studentCheckboxes = [...document.querySelectorAll('.student-checkbox')];
    const selectedCount = document.getElementById('selectedCount');
    const selectAll = document.getElementById('selectAll');
    const submitButton = document.querySelector('.promotion-submit');
    const destinationSelect = document.getElementById('destination_class');
    const capacityWarning = document.getElementById('capacityWarning');
    const gradeFilter = document.getElementById('grade');
    const sourceFilter = document.getElementById('sourceFilter');

    gradeFilter.addEventListener('change', () => {
        document.getElementById('source_class').value = '';
        sourceFilter.submit();
    });

    function updateSelection() {
        const selected = studentCheckboxes.filter((checkbox) => checkbox.checked).length;
        selectedCount.textContent = selected;
        selectAll.checked = studentCheckboxes.length > 0 && selected === studentCheckboxes.length;
        selectAll.indeterminate = selected > 0 && selected < studentCheckboxes.length;
        const selectedOption = destinationSelect?.selectedOptions[0];
        const exceedsCapacity = Boolean(selected && selectedOption?.value &&
            selected > Number(selectedOption.dataset.remaining));
        if (capacityWarning) capacityWarning.classList.toggle('d-none', !exceedsCapacity);
        submitButton.disabled = selected === 0 || exceedsCapacity || Boolean(destinationSelect && !selectedOption?.value);
    }

    studentCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
    destinationSelect?.addEventListener('change', updateSelection);
    selectAll.addEventListener('change', () => {
        studentCheckboxes.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
        updateSelection();
    });
    updateSelection();
</script>
@endpush
