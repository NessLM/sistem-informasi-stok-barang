{{-- resources/views/staff/pb/riwayat.blade.php --}}
<x-layouts.app title="Riwayat" :menu="$menu">

    <div class="container-fluid riwayat-container riwayat-page">

        <!-- Filter Section -->
        <div class="card riwayat-filter-card mb-4">
            <div class="card-body riwayat-filter-body">
                <h3>Filter Data</h3>
                <form id="filterForm" class="riwayat-filter-form" method="GET">
                    <!-- Filter Alur Barang -->
                    <div class="riwayat-filter-group riwayat-filter-dropdown">
                        <button class="btn riwayat-btn-filter dropdown-toggle" type="button" id="alurDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span>{{ request('alur_barang', 'Semua') == 'Semua' ? 'Pilih Alur' : request('alur_barang') }}</span>
                            <i class="bi bi-chevron-right dropdown-arrow"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="alurDropdown">
                            <li><a class="dropdown-item {{ request('alur_barang', 'Semua') == 'Semua' ? 'active' : '' }}"
                                    href="#" data-value="Semua">Semua</a></li>
                            <li><a class="dropdown-item {{ request('alur_barang') == 'Masuk' ? 'active' : '' }}"
                                    href="#" data-value="Masuk">Barang Masuk</a></li>
                            <li><a class="dropdown-item {{ request('alur_barang') == 'Keluar' ? 'active' : '' }}"
                                    href="#" data-value="Keluar">Barang Keluar</a></li>
                        </ul>
                        <input type="hidden" name="alur_barang" value="{{ request('alur_barang', 'Semua') }}">
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
                        <a href="{{ route('pb.riwayat.index') }}" class="btn riwayat-btn-reset">
                            <i class="bi bi-arrow-clockwise me-2"></i>Reset
                        </a>
                    </div>

                    <!-- Tombol Unduh -->
                    <div class="riwayat-action-buttons">
                        <div class="dropdown riwayat-download-dropdown">
                            <button class="btn riwayat-btn-download dropdown-toggle" type="button" id="downloadDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-download me-2"></i>Unduh
                                <i class="bi bi-chevron-right dropdown-arrow ms-2"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="downloadDropdown">
                                <li>
                                    <a class="dropdown-item" href="#"
                                        onclick="event.preventDefault(); downloadReport('excel')">
                                        <i class="bi bi-file-earmark-excel me-2"></i>Excel
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#"
                                        onclick="event.preventDefault(); downloadReport('pdf')">
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
                    <div class="table-responsive-vertical"> <!-- Ganti class untuk scroll vertikal -->
                        <table class="table table-bordered mb-0 riwayat-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal <br> Waktu </th>
                                    <th>Gudang</th>
                                    <th>Nama Barang</th>
                                    <th>Jumlah</th>
                                    <th>Satuan</th>
                                    <th>Keterangan</th> <!-- Lebar lebih untuk keterangan -->
                                    <th>Bukti</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($masukPaginated as $item)
                                    <tr>
                                        <td class="fw-semibold" data-label="No">
                                            {{ ($currentPageMasuk - 1) * $itemsPerPage + $loop->iteration }}
                                        </td>
                                        <td data-label="Tanggal/Waktu">
                                            {{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }} <br>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($item->waktu)->format('H:i') }}
                                                WIB</small>
                                        </td>
                                        <td class="fw-medium" data-label="Gudang">{{ $item->gudang }}</td>
                                        <td class="fw-medium" data-label="Nama Barang">{{ $item->nama_barang }}</td>
                                        <td data-label="Jumlah"><span class="fw-medium">{{ $item->jumlah }}</span></td>
                                        <td class="fw-medium" data-label="Satuan">{{ $item->satuan }}</td>
                                        <td data-label="Keterangan"
                                            style="white-space: normal !important; word-wrap: break-word !important; word-break: break-word !important; max-width: 200px; vertical-align: top;">
                                            @if ($item->keterangan && strlen($item->keterangan) > 55)
                                                <div class="keterangan-wrapper">
                                                    <span class="keterangan-text collapsed"
                                                        data-full-text="{{ $item->keterangan }}">
                                                        {{ Str::limit($item->keterangan, 55, '') }}
                                                    </span>
                                                    <span class="keterangan-dots keterangan-toggle">...</span>
                                                </div>
                                            @else
                                                {{ $item->keterangan ?? '-' }}
                                            @endif
                                        </td>
                                        <td class="text-center" data-label="Bukti">
                                            @if ($item->bukti_path)
                                                <span class="riwayat-bukti-icon" style="cursor: pointer;" data-bs-toggle="modal"
                                                    data-bs-target="#buktiModal" data-image="{{ $item->bukti_path }}">
                                                    <i class="bi bi-eye-fill"></i>
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="riwayat-empty-state text-center py-4">
                                            <i class="bi bi-inbox"></i>
                                            <p>Tidak ada data barang masuk ditemukan</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination untuk Barang Masuk -->
                    @if ($totalPagesMasuk > 1)
                        <div class="card-footer d-flex justify-content-center align-items-center">
                            <div class="pagination-controls">
                                <button class="btn btn-sm btn-outline-primary pagination-btn pagination-prev"
                                    onclick="changePage('masuk', {{ max(1, $currentPageMasuk - 1) }})" {{ $currentPageMasuk <= 1 ? 'disabled' : '' }}>
                                    <i class="bi bi-chevron-left"></i>
                                    <span class="pagination-text">Sebelumnya</span>
                                </button>
                                <span class="mx-2 pagination-info">Halaman {{ $currentPageMasuk }} dari
                                    {{ $totalPagesMasuk }}</span>
                                <button class="btn btn-sm btn-outline-primary pagination-btn pagination-next"
                                    onclick="changePage('masuk', {{ min($totalPagesMasuk, $currentPageMasuk + 1) }})" {{ $currentPageMasuk >= $totalPagesMasuk ? 'disabled' : '' }}>
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
            <div class="card riwayat-table-card mb-4">
                <div class="card-header riwayat-header-keluar">
                    <h5 class="mb-0">Barang Keluar</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive-vertical"> <!-- Ganti class untuk scroll vertikal -->
                        <table class="table table-bordered mb-0 riwayat-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal <br> Waktu </th>
                                    <th>Gudang Tujuan</th>
                                    <th>Nama Barang</th>
                                    <th>Jumlah</th>
                                    <th>Satuan</th>
                                    <th>Keterangan</th> <!-- Lebar lebih untuk keterangan -->
                                    <th>Bukti</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($keluarPaginated as $item)
                                    <tr>
                                        <td class="fw-semibold" data-label="No">
                                            {{ ($currentPageKeluar - 1) * $itemsPerPage + $loop->iteration }}
                                        </td>
                                        <td data-label="Tanggal/Waktu">
                                            {{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }} <br>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($item->waktu)->format('H:i') }}
                                                WIB</small>
                                        </td>
                                        <td class="fw-medium" data-label="Gudang Tujuan">
                                            {{ $item->gudang_tujuan }}<br>
                                        </td>
                                        <td class="fw-medium" data-label="Nama Barang">{{ $item->nama_barang }}</td>
                                        <td data-label="Jumlah"><span class="fw-medium">{{ $item->jumlah }}</span></td>
                                        <td class="fw-medium" data-label="Satuan">{{ $item->satuan }}</td>
                                        <td data-label="Keterangan"
                                            style="white-space: normal !important; word-wrap: break-word !important; word-break: break-word !important; max-width: 200px; vertical-align: top;">
                                            @if ($item->keterangan && strlen($item->keterangan) > 55)
                                                <div class="keterangan-wrapper">
                                                    <span class="keterangan-text collapsed"
                                                        data-full-text="{{ $item->keterangan }}">
                                                        {{ Str::limit($item->keterangan, 55, '') }}
                                                    </span>
                                                    <span class="keterangan-dots keterangan-toggle">...</span>
                                                </div>
                                            @else
                                                {{ $item->keterangan ?? '-' }}
                                            @endif
                                        </td>
                                        <td class="text-center" data-label="Bukti">
                                            @if ($item->bukti_path)
                                                <span class="riwayat-bukti-icon" style="cursor: pointer;" data-bs-toggle="modal"
                                                    data-bs-target="#buktiModal" data-image="{{ $item->bukti_path }}">
                                                    <i class="bi bi-eye-fill"></i>
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="riwayat-empty-state text-center py-4">
                                            <i class="bi bi-inbox"></i>
                                            <p>Tidak ada data barang keluar ditemukan</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination untuk Barang Keluar -->
                    @if ($totalPagesKeluar > 1)
                        <div class="card-footer d-flex justify-content-center align-items-center">
                            <div class="pagination-controls">
                                <button class="btn btn-sm btn-outline-primary pagination-btn pagination-prev"
                                    onclick="changePage('keluar', {{ max(1, $currentPageKeluar - 1) }})" {{ $currentPageKeluar <= 1 ? 'disabled' : '' }}>
                                    <i class="bi bi-chevron-left"></i>
                                    <span class="pagination-text">Sebelumnya</span>
                                </button>
                                <span class="mx-2 pagination-info">Halaman {{ $currentPageKeluar }} dari
                                    {{ $totalPagesKeluar }}</span>
                                <button class="btn btn-sm btn-outline-primary pagination-btn pagination-next"
                                    onclick="changePage('keluar', {{ min($totalPagesKeluar, $currentPageKeluar + 1) }})" {{ $currentPageKeluar >= $totalPagesKeluar ? 'disabled' : '' }}>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
    <div class="modal fade" id="buktiModal" tabindex="-1" aria-labelledby="buktiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
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
            document.addEventListener('DOMContentLoaded', function () {
                initEventListeners();
                initKeteranganToggle();

                function initEventListeners() {
                    // Filter untuk alur barang dan gudang
                    document.querySelectorAll('.riwayat-filter-dropdown .dropdown-item').forEach(item => {
                        item.addEventListener('click', handleFilterClick);
                    });

                    // Handle custom period modal
                    const customPeriodItem = document.querySelector('.custom-period-item');
                    if (customPeriodItem) {
                        customPeriodItem.addEventListener('click', handleCustomPeriodClick);
                    }

                    // Handle apply button di modal custom period
                    const applyCustomPeriodBtn = document.getElementById('applyCustomPeriod');
                    if (applyCustomPeriodBtn) {
                        applyCustomPeriodBtn.addEventListener('click', applyCustomPeriod);
                    }

                    // Inisialisasi modal bukti
                    document.querySelectorAll('.riwayat-bukti-icon').forEach(icon => {
                        icon.addEventListener('click', handleBuktiClick);
                    });
                }

                // Inisialisasi
                function initKeteranganToggle() {
                    document.querySelectorAll('.keterangan-toggle').forEach(toggle => {
                        toggle.addEventListener('click', handleKeteranganToggle);
                    });
                }

                // Handler ketika diklik
                function handleKeteranganToggle(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const toggle = this;
                    const wrapper = toggle.closest('.keterangan-wrapper');
                    const textSpan = wrapper.querySelector('.keterangan-text');

                    if (textSpan.classList.contains('collapsed')) {
                        // EXPAND - tampilkan full text
                        const fullText = textSpan.getAttribute('data-full-text');
                        textSpan.textContent = fullText;
                        textSpan.classList.remove('collapsed');
                        textSpan.classList.add('expanded');
                        toggle.textContent = 'tutup';
                    } else {
                        // COLLAPSE - kembalikan ke text pendek
                        const fullText = textSpan.getAttribute('data-full-text');
                        const limitedText = fullText.substring(0, 55); // sesuai limit di blade
                        textSpan.textContent = limitedText;
                        textSpan.classList.remove('expanded');
                        textSpan.classList.add('collapsed');
                        toggle.textContent = '...';
                    }
                }

                function handleCustomPeriodClick(e) {
                    e.preventDefault();
                }

                function handleFilterClick(e) {
                    e.preventDefault();

                    if (this.classList.contains('custom-period-item')) {
                        return;
                    }

                    const value = this.getAttribute('data-value');
                    const dropdown = this.closest('.riwayat-filter-dropdown');
                    const button = dropdown.querySelector('.riwayat-btn-filter');
                    const hiddenInput = dropdown.querySelector('input[type="hidden"]');

                    button.querySelector('span').textContent = this.textContent;

                    if (hiddenInput) {
                        hiddenInput.value = value;

                        if (hiddenInput.name === 'periode' && value !== 'custom') {
                            document.getElementById('dariTanggalInput').value = '';
                            document.getElementById('sampaiTanggalInput').value = '';
                        }
                    }

                    dropdown.querySelectorAll('.dropdown-item').forEach(i => {
                        i.classList.remove('active');
                    });

                    this.classList.add('active');

                    submitFilterForm();
                }

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

                    document.getElementById('periodeInput').value = 'custom';
                    document.getElementById('dariTanggalInput').value = dariTanggal;
                    document.getElementById('sampaiTanggalInput').value = sampaiTanggal;

                    const dariFormatted = formatDate(dariTanggal);
                    const sampaiFormatted = formatDate(sampaiTanggal);
                    document.getElementById('periodeText').textContent = `${dariFormatted} - ${sampaiFormatted}`;

                    document.querySelectorAll('#periodeDropdown + .dropdown-menu .dropdown-item').forEach(i => {
                        i.classList.remove('active');
                    });
                    document.querySelector('#periodeDropdown + .dropdown-menu .custom-period-item').classList.add(
                        'active');

                    const modalEl = document.getElementById('customPeriodModal');
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.hide();

                    submitFilterForm();
                }

                function handleBuktiClick() {
                    const imageUrl = this.getAttribute('data-image');
                    const modalImage = document.querySelector('#buktiImage');
                    if (modalImage) {
                        modalImage.src = imageUrl;
                    }
                }

                function submitFilterForm() {
                    resetPagination();
                    document.getElementById('filterForm').submit();
                }

                function resetPagination() {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('page_masuk');
                    url.searchParams.delete('page_keluar');
                    window.history.replaceState({}, '', url);
                }

                function formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                }

                const today = new Date().toISOString().split('T')[0];
                const dariTanggalInput = document.getElementById('dariTanggal');
                const sampaiTanggalInput = document.getElementById('sampaiTanggal');

                if (dariTanggalInput) dariTanggalInput.max = today;
                if (sampaiTanggalInput) sampaiTanggalInput.max = today;
            });

            function changePage(type, page) {
                const url = new URL(window.location.href);
                url.searchParams.set(`page_${type}`, page);

                // Scroll ke tabel yang bersangkutan
                const tableElement = document.querySelector(`.riwayat-header-${type}`)?.closest('.card');
                if (tableElement) {
                    tableElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }

                // Gunakan fetch untuk AJAX pagination
                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newTable = doc.querySelector(`.riwayat-header-${type}`)?.closest('.card');

                        if (newTable) {
                            const oldTable = document.querySelector(`.riwayat-header-${type}`)?.closest('.card');
                            if (oldTable) {
                                oldTable.replaceWith(newTable);
                            }

                            window.history.pushState({}, '', url);

                            // Re-init semua event listeners
                            setTimeout(() => {
                                initKeteranganToggle();
                            }, 100);
                        } else {
                            window.location.href = url.toString();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        window.location.href = url.toString();
                    });
            }

            function downloadReport(format) {
                const form = document.getElementById('filterForm');
                const formData = new FormData(form);

                // Buat URL dengan semua parameter filter
                const params = new URLSearchParams();
                for (let [key, value] of formData.entries()) {
                    if (value && value !== 'Semua' && value !== '') {
                        params.append(key, value);
                    }
                }

                // Tambahkan parameter download
                params.append('download', format);

                // Redirect untuk download
                window.location.href = `{{ route('pb.riwayat.index') }}?${params.toString()}`;
            }

            // Export function untuk bisa dipanggil dari luar
            window.initKeteranganToggle = initKeteranganToggle;
        </script>
    @endpush


    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/staff/pb/riwayat.pb.css') }}">
        <style>
            /* Force override untuk keterangan */
            .riwayat-page .table td:nth-child(7) {
                white-space: normal !important;
                word-break: break-word !important;
                max-width: 200px !important;
            }
        </style>
    @endpush

</x-layouts.app>