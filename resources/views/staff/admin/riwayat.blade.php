{{-- resources/views/staff/admin/riwayat.blade.php --}}

<x-layouts.app title="Riwayat" :menu="$menu">

    <div class="container-fluid riwayat-container">

        <!-- Filter Section -->
        <div class="card riwayat-filter-card mb-4">
            <div class="card-body riwayat-filter-body">
                <h3>Filter Data</h3>
                <form id="filterForm" class="riwayat-filter-form">
                    <div class="riwayat-filter-group custom-select-wrapper">
                        <select name="alur_barang" class="form-select riwayat-filter-select custom-select"
                            onchange="this.form.submit()">
                            <option value="Semua" {{ request('alur_barang') == 'Semua' ? 'selected' : '' }}>Pilih Alur
                                Barang</option>
                            <option value="Keluar" {{ request('alur_barang') == 'Keluar' ? 'selected' : '' }}>Keluar
                            </option>
                            <option value="Masuk" {{ request('alur_barang') == 'Masuk' ? 'selected' : '' }}>Masuk
                            </option>
                        </select>
                        <span class="custom-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    </div>

                    <div class="riwayat-filter-group custom-select-wrapper">
                        <select name="gudang" class="form-select riwayat-filter-select custom-select"
                            onchange="this.form.submit()">
                            <option value="Semua" {{ request('gudang') == 'Semua' ? 'selected' : '' }}>Pilih Gudang
                            </option>
                            @foreach ($gudangList as $gudang)
                                <option value="{{ $gudang->gudang }}"
                                    {{ request('gudang') == $gudang->gudang ? 'selected' : '' }}>
                                    {{ $gudang->gudang }}
                                </option>
                            @endforeach
                        </select>
                        <span class="custom-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    </div>

                    <div class="riwayat-filter-group custom-select-wrapper">
                        <select name="periode" class="form-select riwayat-filter-select custom-select"
                            onchange="this.form.submit()">
                            <option value="">Pilih Periode</option>
                            <option value="1_minggu_terakhir"
                                {{ request('periode') == '1_minggu_terakhir' ? 'selected' : '' }}>1 Minggu Terakhir
                            </option>
                            <option value="1_bulan_terakhir"
                                {{ request('periode') == '1_bulan_terakhir' ? 'selected' : '' }}>1 Bulan Terakhir
                            </option>
                            <option value="1_tahun_terakhir"
                                {{ request('periode') == '1_tahun_terakhir' ? 'selected' : '' }}>1 Tahun Terakhir
                            </option>
                        </select>
                        <span class="custom-arrow">
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    </div>

                    <a href="{{ route('admin.riwayat.index') }}" class="btn riwayat-btn-reset">
                        <i class="bi bi-arrow-clockwise me-2"></i>Reset
                    </a>
                </form>
            </div>
        </div>

        <!-- Table Section -->
        <div class="card riwayat-table-card">
            <div class="card-body p-0">
                <div class="riwayat-table-container">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Gudang</th>
                                <th>Nama Barang</th>
                                <th>Jumlah</th>
                                <th>Bagian</th>
                                <th>Bukti</th>
                                <th>Alur Barang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riwayat as $index => $item)
                                <tr>
                                    <td class="fw-semibold">{{ $index + 1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->waktu)->format('H:i') }} WIB</td>
                                    <td class="fw-medium">{{ $item->gudang }}</td>
                                    <td class="fw-medium">{{ $item->nama_barang }}</td>
                                    <td><span>{{ $item->jumlah }}</span></td>
                                    <td>{{ $item->bagian }}</td>
                                    <td>
                                        @if ($item->bukti)
                                            <span class="riwayat-bukti-icon" data-bs-toggle="modal"
                                                data-bs-target="#buktiModal"
                                                data-image="{{ asset('storage/bukti/' . $item->bukti) }}">
                                                <i class="bi bi-eye-fill"></i>
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span
                                            class=" {{ $item->alur_barang == 'Keluar' ? 'btn-keluar btn-sm btn-action' : 'btn-masuk btn-sm btn-action' }}">
                                            {{ $item->alur_barang }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="riwayat-empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p>Tidak ada data ditemukan</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal untuk menampilkan bukti -->
    <div class="modal fade" id="buktiModal" tabindex="-1" aria-labelledby="buktiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="buktiModalLabel">Bukti Foto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="buktiImage" src="" alt="Bukti" class="img-fluid" style="max-height: 70vh;">
                </div>
            </div>
        </div>
    </div>


    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const filterForm = document.getElementById('filterForm');
                const selects = filterForm.querySelectorAll('select');

                selects.forEach(select => {
                    select.addEventListener('change', function() {
                        const tableContainer = document.querySelector('.riwayat-table-container');
                        tableContainer.classList.add('riwayat-loading');

                        setTimeout(() => {
                            filterForm.submit();
                        }, 300);
                    });

                    // Tambahkan kelas 'open' saat dropdown difokus atau dibuka
                    select.addEventListener('focus', function() {
                        this.classList.add('open');
                    });

                    // Hapus kelas 'open' saat dropdown kehilangan fokus
                    select.addEventListener('blur', function() {
                        this.classList.remove('open');
                    });

                    // Untuk browser yang mendukung event 'toggle' pada details (jika menggunakan)
                    select.addEventListener('toggle', function() {
                        this.classList.toggle('open');
                    });
                });

                // Inisialisasi modal bukti
                const buktiModal = document.getElementById('buktiModal');
                if (buktiModal) {
                    buktiModal.addEventListener('show.bs.modal', function(event) {
                        const button = event.relatedTarget;
                        const imageUrl = button.getAttribute('data-image');
                        const modalImage = buktiModal.querySelector('#buktiImage');
                        modalImage.src = imageUrl;
                    });
                }
            });
        </script>
    @endpush
</x-layouts.app>
