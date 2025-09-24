    {{-- resources/views/staff/admin/riwayat.blade.php --}}

    <x-layouts.app title="Riwayat" :menu="$menu">

        <div class="container-fluid riwayat-container">

            <!-- Filter Section -->
            <div class="card riwayat-filter-card mb-4">
                <div class="card-body riwayat-filter-body">
                    <h3>Filter Data</h3>
                    <form id="filterForm" class="riwayat-filter-form" method="GET">
                        <!-- Filter Alur Barang -->
                        <div class="riwayat-filter-group riwayat-filter-dropdown">
                            <button class="btn riwayat-btn-filter dropdown-toggle" type="button" id="alurDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <span>{{ request('alur_barang', 'Semua') == 'Semua' ? 'Pilih Alur Barang' : request('alur_barang') }}</span>
                                <i class="bi bi-chevron-right dropdown-arrow"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="alurDropdown">
                                <li><a class="dropdown-item {{ request('alur_barang', 'Semua') == 'Semua' ? 'active' : '' }}"
                                        href="#" data-value="Semua">Semua</a></li>
                                <li><a class="dropdown-item {{ request('alur_barang') == 'Masuk' ? 'active' : '' }}"
                                        href="#" data-value="Masuk">Masuk</a></li>
                                <li><a class="dropdown-item {{ request('alur_barang') == 'Keluar' ? 'active' : '' }}"
                                        href="#" data-value="Keluar">Keluar</a></li>
                            </ul>
                            <input type="hidden" name="alur_barang" value="{{ request('alur_barang', 'Semua') }}">
                        </div>

                        <!-- Filter Gudang -->
                        <div class="riwayat-filter-group riwayat-filter-dropdown">
                            <button class="btn riwayat-btn-filter dropdown-toggle" type="button" id="gudangDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <span>{{ request('gudang', 'Semua') == 'Semua' ? 'Pilih Gudang' : request('gudang') }}</span>
                                <i class="bi bi-chevron-right dropdown-arrow"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="gudangDropdown">
                                <li><a class="dropdown-item {{ request('gudang', 'Semua') == 'Semua' ? 'active' : '' }}"
                                        href="#" data-value="Semua">Semua</a></li>
                                @foreach ($gudangList as $gudang)
                                    <li><a class="dropdown-item {{ request('gudang') == $gudang->gudang ? 'active' : '' }}"
                                            href="#" data-value="{{ $gudang->gudang }}">{{ $gudang->gudang }}</a>
                                    </li>
                                @endforeach
                            </ul>
                            <input type="hidden" name="gudang" value="{{ request('gudang', 'Semua') }}">
                        </div>

                        <!-- Filter Periode -->
                        <div class="riwayat-filter-group riwayat-filter-dropdown">
                            <button class="btn riwayat-btn-filter dropdown-toggle" type="button" id="periodeDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <span id="periodeText">
                                    @if (request('periode') == '1_minggu_terakhir')
                                        1 Minggu Terakhir
                                    @elseif(request('periode') == '1_bulan_terakhir')
                                        1 Bulan Terakhir
                                    @elseif(request('periode') == '1_tahun_terakhir')
                                        1 Tahun Terakhir
                                    @elseif(request('periode') == 'custom' && request('dari_tanggal') && request('sampai_tanggal'))
                                        {{ \Carbon\Carbon::parse(request('dari_tanggal'))->format('d/m/Y') }} -
                                        {{ \Carbon\Carbon::parse(request('sampai_tanggal'))->format('d/m/Y') }}
                                    @else
                                        Pilih Periode
                                    @endif
                                </span>
                                <i class="bi bi-chevron-right dropdown-arrow"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="periodeDropdown">
                                <li><a class="dropdown-item {{ !request('periode') ? 'active' : '' }}" href="#"
                                        data-value="">Pilih Periode</a></li>
                                <li><a class="dropdown-item {{ request('periode') == '1_minggu_terakhir' ? 'active' : '' }}"
                                        href="#" data-value="1_minggu_terakhir">1 Minggu Terakhir</a></li>
                                <li><a class="dropdown-item {{ request('periode') == '1_bulan_terakhir' ? 'active' : '' }}"
                                        href="#" data-value="1_bulan_terakhir">1 Bulan Terakhir</a></li>
                                <li><a class="dropdown-item {{ request('periode') == '1_tahun_terakhir' ? 'active' : '' }}"
                                        href="#" data-value="1_tahun_terakhir">1 Tahun Terakhir</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item {{ request('periode') == 'custom' ? 'active' : '' }} custom-period-item"
                                        href="#" data-bs-toggle="modal" data-bs-target="#customPeriodModal">
                                        <i class="bi bi-calendar-range me-2"></i>Custom Periode
                                    </a></li>
                            </ul>
                            <input type="hidden" name="periode" id="periodeInput" value="{{ request('periode') }}">
                            <input type="hidden" name="dari_tanggal" id="dariTanggalInput"
                                value="{{ request('dari_tanggal') }}">
                            <input type="hidden" name="sampai_tanggal" id="sampaiTanggalInput"
                                value="{{ request('sampai_tanggal') }}">
                        </div>

                        <!-- Tombol Reset -->
                        <div class="riwayat-filter-group-reset">
                            <a href="{{ route('admin.riwayat.index') }}" class="btn riwayat-btn-reset">
                                <i class="bi bi-arrow-clockwise me-2"></i>Reset
                            </a>
                        </div>

                        <!-- Tombol Unduh -->
                        <div class="riwayat-action-buttons">
                            <div class="dropdown riwayat-download-dropdown">
                                <button class="btn riwayat-btn-download dropdown-toggle" type="button"
                                    id="downloadDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-download me-2"></i>Unduh
                                    <i class="bi bi-chevron-right dropdown-arrow ms-2"></i>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="downloadDropdown">
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="downloadReport('excel')">
                                            <i class="bi bi-file-earmark-excel me-2"></i>Excel
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="downloadReport('pdf')">
                                            <i class="bi bi-file-earmark-pdf me-2"></i>PDF
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table Section -->
            @php
                // Pisahkan data berdasarkan alur barang
                $riwayatMasuk = $riwayat->where('alur_barang', 'Masuk');
                $riwayatKeluar = $riwayat->where('alur_barang', 'Keluar');
                $alurFilter = request('alur_barang', 'Semua');

                // Konfigurasi pagination
                $itemsPerPage = 10;
                $currentPageMasuk = request('page_masuk', 1);
                $currentPageKeluar = request('page_keluar', 1);

                // Paginasi untuk data masuk
                $masukPaginated = $riwayatMasuk->slice(($currentPageMasuk - 1) * $itemsPerPage, $itemsPerPage);
                $totalPagesMasuk = ceil($riwayatMasuk->count() / $itemsPerPage);

                // Paginasi untuk data keluar
                $keluarPaginated = $riwayatKeluar->slice(($currentPageKeluar - 1) * $itemsPerPage, $itemsPerPage);
                $totalPagesKeluar = ceil($riwayatKeluar->count() / $itemsPerPage);
            @endphp

            @if ($alurFilter == 'Semua' || $alurFilter == 'Masuk')
                <!-- Tabel Barang Masuk -->
                <div class="card riwayat-table-card mb-4">
                    <div class="card-header riwayat-header-masuk">
                        <h5 class="mb-0">Barang Masuk</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Waktu</th>
                                        <th>Gudang</th>
                                        <th>Nama Barang</th>
                                        <th>Jumlah</th>
                                        <th>Bukti</th>
                                        <th>Alur Barang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($masukPaginated as $item)
                                        <tr>
                                            <td class="fw-semibold">
                                                {{ ($currentPageMasuk - 1) * $itemsPerPage + $loop->iteration }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item->waktu)->format('H:i') }} WIB</td>
                                            <td class="fw-medium">{{ $item->gudang }}</td>
                                            <td class="fw-medium">{{ $item->nama_barang }}</td>
                                            <td><span>{{ $item->jumlah }}</span></td>
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
                                                <span class="btn-masuk btn-sm btn-action">
                                                    {{ $item->alur_barang }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        @if ($alurFilter == 'Masuk')
                                            <tr>
                                                <td colspan="8" class="riwayat-empty-state text-center py-4">
                                                    <i class="bi bi-inbox"></i>
                                                    <p>Tidak ada data barang masuk ditemukan</p>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination untuk Barang Masuk -->
                        @if ($totalPagesMasuk > 1)
                            <div class="card-footer d-flex justify-content-center align-items-center">
                                <div class="pagination-controls">
                                    <button class="btn btn-sm btn-outline-primary pagination-btn pagination-prev"
                                        onclick="changePage('masuk', {{ max(1, $currentPageMasuk - 1) }})"
                                        {{ $currentPageMasuk <= 1 ? 'disabled' : '' }}>
                                        <i class="bi bi-chevron-left"></i>
                                        <span class="pagination-text">Sebelumnya</span>
                                    </button>
                                    <span class="mx-2 pagination-info">Halaman {{ $currentPageMasuk }} dari
                                        {{ $totalPagesMasuk }}</span>
                                    <button class="btn btn-sm btn-outline-primary pagination-btn pagination-next"
                                        onclick="changePage('masuk', {{ min($totalPagesMasuk, $currentPageMasuk + 1) }})"
                                        {{ $currentPageMasuk >= $totalPagesMasuk ? 'disabled' : '' }}>
                                        <span class="pagination-text">Selanjutnya</span>
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($alurFilter == 'Semua' || $alurFilter == 'Keluar')
                <!-- Tabel Barang Keluar -->
                <div class="card riwayat-table-card">
                    <div class="card-header riwayat-header-keluar">
                        <h5 class="mb-0">Barang Keluar</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
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
                                    @forelse($keluarPaginated as $item)
                                        <tr>
                                            <td class="fw-semibold">
                                                {{ ($currentPageKeluar - 1) * $itemsPerPage + $loop->iteration }}</td>
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
                                                <span class="btn-keluar btn-sm btn-action">
                                                    {{ $item->alur_barang }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        @if ($alurFilter == 'Keluar')
                                            <tr>
                                                <td colspan="9" class="riwayat-empty-state text-center py-4">
                                                    <i class="bi bi-inbox"></i>
                                                    <p>Tidak ada data barang keluar ditemukan</p>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination untuk Barang Keluar -->
                        @if ($totalPagesKeluar > 1)
                            <div class="card-footer d-flex justify-content-center align-items-center">
                                <div class="pagination-controls">
                                    <button class="btn btn-sm btn-outline-primary pagination-btn pagination-prev"
                                        onclick="changePage('keluar', {{ max(1, $currentPageKeluar - 1) }})"
                                        {{ $currentPageKeluar <= 1 ? 'disabled' : '' }}>
                                        <i class="bi bi-chevron-left"></i>
                                        <span class="pagination-text">Sebelumnya</span>
                                    </button>
                                    <span class="mx-2 pagination-info">Halaman {{ $currentPageKeluar }} dari
                                        {{ $totalPagesKeluar }}</span>
                                    <button class="btn btn-sm btn-outline-primary pagination-btn pagination-next"
                                        onclick="changePage('keluar', {{ min($totalPagesKeluar, $currentPageKeluar + 1) }})"
                                        {{ $currentPageKeluar >= $totalPagesKeluar ? 'disabled' : '' }}>
                                        <span class="pagination-text">Selanjutnya</span>
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>


        <!-- Modal untuk Custom Periode -->
        <div class="modal fade" id="customPeriodModal" tabindex="-1" aria-labelledby="customPeriodModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="customPeriodModalLabel">Pilih Periode Custom</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="customPeriodForm">
                            <div class="mb-3">
                                <label for="dariTanggal" class="form-label">Dari Tanggal</label>
                                <input type="date" class="form-control" id="dariTanggal"
                                    value="{{ request('dari_tanggal') }}" max="{{ date('Y-m-d') }}">
                            </div>
                            <div class="mb-3">
                                <label for="sampaiTanggal" class="form-label">Sampai Tanggal</label>
                                <input type="date" class="form-control" id="sampaiTanggal"
                                    value="{{ request('sampai_tanggal') }}" max="{{ date('Y-m-d') }}">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" id="applyCustomPeriod">Terapkan</button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modal untuk menampilkan bukti -->
        <div class="modal fade" id="buktiModal" tabindex="-1" aria-labelledby="buktiModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="buktiModalLabel">Bukti Foto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img id="buktiImage" src="" alt="Bukti" class="img-fluid"
                            style="max-height: 70vh;">
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    initEventListeners();

                    function initEventListeners() {
                        // Filter untuk alur barang dan gudang
                        document.querySelectorAll('.riwayat-filter-dropdown .dropdown-item').forEach(item => {
                            // Hapus event listener lama dan tambahkan yang baru
                            item.removeEventListener('click', handleFilterClick);
                            item.addEventListener('click', handleFilterClick);
                        });

                        // Handle custom period modal - khusus untuk item custom period
                        const customPeriodItem = document.querySelector('.custom-period-item');
                        if (customPeriodItem) {
                            customPeriodItem.removeEventListener('click', handleCustomPeriodClick);
                            customPeriodItem.addEventListener('click', handleCustomPeriodClick);
                        }

                        // Handle apply button di modal custom period
                        const applyCustomPeriodBtn = document.getElementById('applyCustomPeriod');
                        if (applyCustomPeriodBtn) {
                            applyCustomPeriodBtn.removeEventListener('click', applyCustomPeriod);
                            applyCustomPeriodBtn.addEventListener('click', applyCustomPeriod);
                        }

                        // Inisialisasi modal bukti
                        document.querySelectorAll('.riwayat-bukti-icon').forEach(icon => {
                            icon.removeEventListener('click', handleBuktiClick);
                            icon.addEventListener('click', handleBuktiClick);
                        });

                        // Inisialisasi pagination buttons
                        document.querySelectorAll('.pagination-btn').forEach(btn => {
                            btn.removeEventListener('click', handlePaginationClick);
                            btn.addEventListener('click', handlePaginationClick);
                        });
                    }

                    // Fungsi khusus untuk menangani klik pada custom period item
                    function handleCustomPeriodClick(e) {
                        e.preventDefault();
                        // Hanya buka modal, jangan submit form
                        // Biarkan modal Bootstrap menangani pembukaan modal
                    }

                    // Fungsi untuk menangani klik filter regular (bukan custom period)
                    function handleFilterClick(e) {
                        e.preventDefault();

                        // Jika ini adalah custom period item, biarkan handleCustomPeriodClick yang menanganinya
                        if (this.classList.contains('custom-period-item')) {
                            return;
                        }

                        const value = this.getAttribute('data-value');
                        const dropdown = this.closest('.riwayat-filter-dropdown');
                        const button = dropdown.querySelector('.riwayat-btn-filter');
                        const hiddenInput = dropdown.querySelector('input[type="hidden"]');

                        // Perbarui teks tombol
                        button.querySelector('span').textContent = this.textContent;

                        // Perbarui nilai input tersembunyi
                        if (hiddenInput) {
                            hiddenInput.value = value;

                            // Jika ini dropdown periode dan bukan custom, reset tanggal custom
                            if (dropdown.id === 'periodeDropdown' && value !== 'custom') {
                                document.getElementById('dariTanggalInput').value = '';
                                document.getElementById('sampaiTanggalInput').value = '';
                            }
                        }

                        // Hapus kelas active dari semua item dalam dropdown yang sama
                        dropdown.querySelectorAll('.dropdown-item').forEach(i => {
                            i.classList.remove('active');
                        });

                        // Tambahkan kelas active ke item yang dipilih
                        this.classList.add('active');

                        // Submit form
                        submitFilterForm();
                    }

                    // Fungsi untuk menerapkan periode custom
                    function applyCustomPeriod() {
                        const dariTanggal = document.getElementById('dariTanggal').value;
                        const sampaiTanggal = document.getElementById('sampaiTanggal').value;

                        if (!dariTanggal || !sampaiTanggal) {
                            alert('Harap pilih kedua tanggal!');
                            return;
                        }

                        if (new Date(dariTanggal) > new Date(sampaiTanggal)) {
                            alert('Tanggal "Dari" tidak boleh lebih besar dari tanggal "Sampai"!');
                            return;
                        }

                        // Update hidden inputs
                        document.getElementById('periodeInput').value = 'custom';
                        document.getElementById('dariTanggalInput').value = dariTanggal;
                        document.getElementById('sampaiTanggalInput').value = sampaiTanggal;

                        // Update button text
                        const dariFormatted = formatDate(dariTanggal);
                        const sampaiFormatted = formatDate(sampaiTanggal);
                        document.getElementById('periodeText').textContent = `${dariFormatted} - ${sampaiFormatted}`;

                        // Update active class di dropdown periode
                        document.querySelectorAll('#periodeDropdown + .dropdown-menu .dropdown-item').forEach(i => {
                            i.classList.remove('active');
                        });
                        document.querySelector('#periodeDropdown + .dropdown-menu .custom-period-item').classList.add(
                            'active');

                        // Close modal dengan getOrCreateInstance
                        const modalEl = document.getElementById('customPeriodModal');
                        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.hide();

                        // Submit form
                        submitFilterForm();
                    }


                    // Fungsi untuk menangani klik bukti
                    function handleBuktiClick() {
                        const imageUrl = this.getAttribute('data-image');
                        const modalImage = document.querySelector('#buktiImage');
                        if (modalImage) {
                            modalImage.src = imageUrl;
                        }
                    }

                    // Fungsi untuk menangani klik pagination
                    function handlePaginationClick() {
                        const onclickAttr = this.getAttribute('onclick');
                        if (onclickAttr) {
                            const match = onclickAttr.match(/changePage\('(\w+)',\s*(\d+)\)/);
                            if (match) {
                                const type = match[1];
                                const page = parseInt(match[2]);
                                changePage(type, page);
                            }
                        }
                    }

                    // Fungsi untuk submit form filter
                    function submitFilterForm() {
                        // Reset pagination saat filter berubah
                        resetPagination();

                        // Submit form
                        document.getElementById('filterForm').submit();
                    }

                    // Fungsi untuk reset pagination
                    function resetPagination() {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('page_masuk');
                        url.searchParams.delete('page_keluar');
                        window.history.replaceState({}, '', url);
                    }

                    // Fungsi untuk mengubah halaman
                    function changePage(type, page) {
                        const url = new URL(window.location.href);
                        url.searchParams.set(`page_${type}`, page);

                        // Scroll ke bagian atas tabel
                        const tableElement = document.querySelector(`.riwayat-header-${type}`)?.closest('.card');
                        if (tableElement) {
                            tableElement.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }

                        // AJAX request
                        fetch(url, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'text/html'
                                }
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.text();
                            })
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newTable = doc.querySelector(`.riwayat-header-${type}`)?.closest('.card');

                                if (newTable) {
                                    const oldTable = document.querySelector(`.riwayat-header-${type}`)?.closest(
                                    '.card');
                                    if (oldTable) {
                                        oldTable.replaceWith(newTable);
                                    }

                                    window.history.pushState({}, '', url);
                                    initEventListeners();
                                } else {
                                    window.location.href = url;
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                window.location.href = url;
                            });
                    }

                    // Fungsi utilitas untuk memformat tanggal
                    function formatDate(dateString) {
                        const date = new Date(dateString);
                        return date.toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });
                    }

                    // Set max date for date inputs to today
                    const today = new Date().toISOString().split('T')[0];
                    const dariTanggalInput = document.getElementById('dariTanggal');
                    const sampaiTanggalInput = document.getElementById('sampaiTanggal');

                    if (dariTanggalInput) dariTanggalInput.max = today;
                    if (sampaiTanggalInput) sampaiTanggalInput.max = today;
                });

                function downloadReport(format) {
                    const form = document.getElementById('filterForm');
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'download';
                    input.value = format;
                    form.appendChild(input);
                    form.submit();
                }
            </script>
        @endpush

        @push('styles')
        @endpush
    </x-layouts.app>
