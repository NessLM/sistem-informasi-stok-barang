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
                            <li><a class="dropdown-item {{ request('alur_barang') == 'Keluar' ? 'active' : '' }}"
                                    href="#" data-value="Keluar">Keluar</a></li>
                            <li><a class="dropdown-item {{ request('alur_barang') == 'Masuk' ? 'active' : '' }}"
                                    href="#" data-value="Masuk">Masuk</a></li>
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
                                        href="#" data-value="{{ $gudang->gudang }}">{{ $gudang->gudang }}</a></li>
                            @endforeach
                        </ul>
                        <input type="hidden" name="gudang" value="{{ request('gudang', 'Semua') }}">
                    </div>

                    <!-- Filter Periode -->
                    <div class="riwayat-filter-group riwayat-filter-dropdown">
                        <button class="btn riwayat-btn-filter dropdown-toggle" type="button" id="periodeDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span>
                                @if (request('periode') == '1_minggu_terakhir')
                                    1 Minggu Terakhir
                                @elseif(request('periode') == '1_bulan_terakhir')
                                    1 Bulan Terakhir
                                @elseif(request('periode') == '1_tahun_terakhir')
                                    1 Tahun Terakhir
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
                        </ul>
                        <input type="hidden" name="periode" value="{{ request('periode') }}">
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
        @endphp

        @if($alurFilter == 'Semua' || $alurFilter == 'Masuk')
            <!-- Tabel Barang Masuk -->
            <div class="card riwayat-table-card mb-4">
                <div class="card-header riwayat-header-masuk">
                    <h5 class="mb-0">Barang Masuk</h5>
                </div>
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
                                @forelse($riwayatMasuk as $item)
                                    <tr>
                                        <td class="fw-semibold">{{ $loop->iteration }}</td>
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
                                            <span class="btn-masuk btn-sm btn-action">
                                                {{ $item->alur_barang }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    @if($alurFilter == 'Masuk')
                                        <tr>
                                            <td colspan="9" class="riwayat-empty-state">
                                                <i class="bi bi-inbox"></i>
                                                <p>Tidak ada data barang masuk ditemukan</p>
                                            </td>
                                        </tr>
                                    @endif
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @if($alurFilter == 'Semua' || $alurFilter == 'Keluar')
            <!-- Tabel Barang Keluar -->
            <div class="card riwayat-table-card">
                <div class="card-header riwayat-header-keluar">
                    <h5 class="mb-0">Barang Keluar</h5>
                </div>
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
                                @forelse($riwayatKeluar as $item)
                                    <tr>
                                        <td class="fw-semibold">{{ $loop->iteration }}</td>
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
                                    @if($alurFilter == 'Keluar')
                                        <tr>
                                            <td colspan="9" class="riwayat-empty-state">
                                                <i class="bi bi-inbox"></i>
                                                <p>Tidak ada data barang keluar ditemukan</p>
                                            </td>
                                        </tr>
                                    @endif
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
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
                // Tangani pemilihan filter dropdown
                document.querySelectorAll('.riwayat-filter-dropdown .dropdown-item').forEach(item => {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        const value = this.getAttribute('data-value');
                        const dropdown = this.closest('.riwayat-filter-dropdown');
                        const button = dropdown.querySelector('.riwayat-btn-filter');
                        const hiddenInput = dropdown.querySelector('input[type="hidden"]');

                        // Perbarui teks tombol
                        button.querySelector('span').textContent = this.textContent;

                        // Perbarui nilai input tersembunyi
                        hiddenInput.value = value;

                        // Hapus kelas active dari semua item
                        dropdown.querySelectorAll('.dropdown-item').forEach(i => {
                            i.classList.remove('active');
                        });

                        // Tambahkan kelas active ke item yang dipilih
                        this.classList.add('active');

                        // Submit form
                        const tableContainer = document.querySelector('.riwayat-table-container');
                        tableContainer.classList.add('riwayat-loading');

                        setTimeout(() => {
                            document.getElementById('filterForm').submit();
                        }, 300);
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

            function downloadReport(format) {
                const form = document.getElementById('filterForm');
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'download';
                input.value = format;
                form.appendChild(input);
                form.submit();
                form.removeChild(input);
            }
        </script>
    @endpush
</x-layouts.app>