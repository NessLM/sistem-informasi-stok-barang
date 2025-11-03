<x-layouts.app title="Data Keseluruhan" :menu="$menu">
    @php
        $pbStokData = $pbStokData ?? collect();
        $kategori = $kategori ?? collect();
        $bagian = $bagian ?? collect();
    @endphp
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/staff/pb/data_keseluruhan_pb.css') }}">
        <style>
            .row-low-stock {
                background-color: #ffcccc !important;
                border-left: 4px solid #dc3545 !important;
            }
        </style>

    @endpush
    <main class="page-wrap container py-4">
        <!-- Toast notification -->
        @if (session('toast'))
            <div id="toast-notif" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
                              z-index: 2000; display: flex; justify-content: center; pointer-events: none;">
                <div class="toast-message" style="background: #fff; border-radius: 12px; padding: 14px 22px;
                                box-shadow: 0 4px 12px rgba(0,0,0,0.15); text-align: center;
                                min-width: 280px; max-width: 360px; transition: opacity .5s ease;">
                    <div style="font-weight: 600; font-size: 16px; margin-bottom: 4px;
                                  color: {{ session('toast.type') === 'success' ? '#28a745' : '#dc3545' }};">
                        {{ session('toast.title') }}
                    </div>
                    <div style="color:#333; font-size: 14px; line-height: 1.4;">
                        {{ session('toast.message') }}
                    </div>
                </div>
            </div>
            <script>
                setTimeout(() => {
                    const toast = document.getElementById('toast-notif');
                    if (toast) toast.style.opacity = '0';
                    setTimeout(() => toast?.remove(), 500);
                }, 3000);
            </script>
        @endif
        <section class="card shadow-sm p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <h4>Data Keseluruhan Stok PB</h4>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFilterBarang">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </div>
            {{-- Search Form dengan Autocomplete --}}
            <div class="position-relative mb-3">
                <form action="{{ route('pb.datakeseluruhan.index') }}" method="GET" class="input-group" id="searchForm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" id="searchInput" class="form-control"
                        placeholder="Telusuri barang (nama atau kode)" value="{{ request('search') }}"
                        autocomplete="off">
                    <button class="btn btn-outline-secondary" type="submit">Cari</button>
                </form>
                <div id="searchSuggestions" class="dropdown-menu w-100 position-absolute"
                    style="z-index: 1050; max-height: 300px; overflow-y: auto; display: none;">
                </div>
            </div>
            {{-- Jika ada filter/search --}}
            @if (
                    request()->filled('search') ||
                    request()->filled('kode') ||
                    request()->filled('stok_min') ||
                    request()->filled('stok_max') ||
                    request()->filled('kategori_id') ||
                    request()->filled('bagian_id') ||
                    request()->filled('harga_min') ||
                    request()->filled('harga_max')
                )
                <h5 class="mt-3">Hasil Pencarian</h5>
                @if ($pbStokData->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered mt-2">
                            <thead class="table-secondary">
                                <tr>
                                    <th>ID</th>
                                    <th>Kode Barang</th>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Bagian</th>
                                    <th>Stok</th>
                                    <th>Harga</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pbStokData as $item)
                                    <tr @if ($item->stok < 10) class="row-low-stock" @endif>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->kode_barang }}</td>
                                        <td>{{ $item->barang->nama_barang ?? '-' }}</td>
                                        <td>{{ $item->barang->kategori->nama ?? '-' }}</td>
                                        <td>{{ $item->bagian->nama ?? '-' }}</td>
                                        <td>{{ $item->stok }}</td>
                                        <td>Rp {{ number_format($item->harga ?? 0, 0, ',', '.') }}</td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#modalKelolaBarang" data-id="{{ $item->id }}"
                                                data-nama="{{ $item->barang->nama_barang ?? '-' }}"
                                                data-kode="{{ $item->kode_barang }}" data-stok="{{ $item->stok }}">
                                                <i class="bi bi-box-seam"></i> Kelola
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">Tidak ada data ditemukan untuk kriteria pencarian Anda</div>
                @endif
            @endif
            {{-- Jika tidak ada filter/search - Tampilkan per kategori --}}
            @if (
                    !request()->filled('search') &&
                    !request()->filled('kode') &&
                    !request()->filled('stok_min') &&
                    !request()->filled('stok_max') &&
                    !request()->filled('kategori_id') &&
                    !request()->filled('bagian_id') &&
                    !request()->filled('harga_min') &&
                    !request()->filled('harga_max')
                )
                <div class="table-responsive mt-3">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>KATEGORI</th>
                                <th style="width:180px" class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kategori as $k)
                                @php
                                    // Ambil semua pb_stok yang barangnya dalam kategori ini
                                    $stokInKategori = \App\Models\PbStok::with(['barang', 'bagian'])
                                        ->whereHas('barang', function ($q) use ($k) {
                                            $q->where('id_kategori', $k->id);
                                        })
                                        ->get();
                                @endphp
                                @if($stokInKategori->count() > 0)
                                    <tr>
                                        <td>{{ $k->nama }}</td>
                                        <td class="text-center">
                                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                                <button class="btn btn-sm btn-success" onclick="toggleDetail({{ $k->id }})">
                                                    <i class="bi bi-eye"></i> Lihat ({{ $stokInKategori->count() }})
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr id="detail-{{ $k->id }}" style="display:none;">
                                        <td colspan="2">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Kode Barang</th>
                                                            <th>Nama Barang</th>
                                                            <th>Bagian</th>
                                                            <th>Stok</th>
                                                            <th>Harga</th>
                                                            <th>Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($stokInKategori as $item)
                                                            <tr @if ($item->stok < 10) class="row-low-stock" @endif>
                                                                <td>{{ $item->id }}</td>
                                                                <td>{{ $item->kode_barang }}</td>
                                                                <td>{{ $item->barang->nama_barang ?? '-' }}</td>
                                                                <td>{{ $item->bagian->nama ?? '-' }}</td>
                                                                <td>{{ $item->stok }}</td>
                                                                <td>Rp {{ number_format($item->harga ?? 0, 0, ',', '.') }}</td>
                                                                <td>
                                                                    <button type="button" class="btn btn-primary btn-sm"
                                                                        data-bs-toggle="modal" data-bs-target="#modalKelolaBarang"
                                                                        data-id="{{ $item->id }}"
                                                                        data-nama="{{ $item->barang->nama_barang ?? '-' }}"
                                                                        data-kode="{{ $item->kode_barang }}"
                                                                        data-stok="{{ $item->stok }}">
                                                                        <i class="bi bi-box-seam"></i> Kelola
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </main>
    <!-- Modal Kelola Barang -->
    <div class="modal fade" id="modalKelolaBarang" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold">Kelola Barang: <span id="kelolaBarangNama"
                            class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <!-- Tab Navigation -->
                    <ul class="nav nav-tabs mb-4" id="kelolaTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-barang-masuk" data-bs-toggle="tab"
                                data-bs-target="#content-barang-masuk" type="button" role="tab">
                                <i class="bi bi-box-arrow-in-down"></i> Barang Masuk
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-distribusi" data-bs-toggle="tab"
                                data-bs-target="#content-distribusi" type="button" role="tab">
                                <i class="bi bi-box-arrow-right"></i> Barang Keluar
                            </button>
                        </li>
                    </ul>
                    <!-- Tab Content -->
                    <div class="tab-content" id="kelolaTabContent">
                        <!-- Content: Barang Masuk -->
                        <div class="tab-pane fade show active" id="content-barang-masuk" role="tabpanel">
                            <form method="POST" id="formBarangMasuk" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="kode_barang" id="barangMasukKode">
                                <input type="hidden" name="pb_stok_id" id="barangMasukPbStokId">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Barang</label>
                                        <input type="text" id="barangMasukNama" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Jumlah Masuk</label>
                                        <input type="number" name="jumlah" class="form-control"
                                            placeholder="Masukkan Jumlah" required min="1">
                                        <small class="text-muted d-block mt-1">
                                            Stok Saat Ini: <span id="stokTersediaMasuk">0</span>
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Masuk <small
                                                class="text-muted">(Opsional)</small></label>
                                        <input type="date" name="tanggal" id="tanggalMasuk" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Keterangan</label>
                                        <input type="text" name="keterangan" class="form-control"
                                            placeholder="Masukkan keterangan">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Bukti Barang Masuk</label>
                                        <div class="border rounded p-4 text-center" style="background-color: #f8f9fa;">
                                            <input type="file" name="bukti" id="buktiBrgMasuk" class="d-none"
                                                accept="image/*,.pdf">
                                            <label for="buktiBrgMasuk" class="d-block" style="cursor: pointer;">
                                                <i class="bi bi-cloud-upload"
                                                    style="font-size: 2rem; color: #6c757d;"></i>
                                                <div class="mt-2" style="color: #6c757d; font-size: 0.875rem;">Klik
                                                    untuk Upload</div>
                                            </label>
                                            <div id="fileNameMasuk" class="mt-2 text-primary small"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="button" class="btn btn-secondary px-4"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-success px-4">
                                        <i class="bi bi-check-circle"></i> Simpan Barang Masuk
                                    </button>
                                </div>
                            </form>
                        </div>
                        <!-- Content: Barang Keluar -->
                        <div class="tab-pane fade" id="content-distribusi" role="tabpanel">
                            <form method="POST" id="formDistribusi" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="kode_barang" id="distribusiBarangKode">
                                <input type="hidden" name="pb_stok_id" id="distribusiPbStokId">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Barang</label>
                                        <input type="text" id="distribusiBarangNama" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Jumlah Keluar</label>
                                        <input type="number" name="jumlah" class="form-control"
                                            placeholder="Masukkan Jumlah" required min="1">
                                        <small class="text-muted d-block mt-1">
                                            Stok tersedia: <span id="stokTersediaKeluar">0</span>
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Keluar <small
                                                class="text-muted">(Opsional)</small></label>
                                        <input type="date" name="tanggal" id="tanggalDistribusi" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Keterangan</label>
                                        <input type="text" name="keterangan" class="form-control"
                                            placeholder="Masukkan keterangan">
                                    </div>
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle"></i>
                                            Barang akan didistribusikan dari stok record ini
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Bukti Barang Keluar</label>
                                        <div class="border rounded p-4 text-center" style="background-color: #f8f9fa;">
                                            <input type="file" name="bukti" id="buktiBrgDistribusi" class="d-none"
                                                accept="image/*,.pdf">
                                            <label for="buktiBrgDistribusi" class="d-block" style="cursor: pointer;">
                                                <i class="bi bi-cloud-upload"
                                                    style="font-size: 2rem; color: #6c757d;"></i>
                                                <div class="mt-2" style="color: #6c757d; font-size: 0.875rem;">Klik
                                                    untuk Upload</div>
                                            </label>
                                            <div id="fileNameDistribusi" class="mt-2 text-primary small"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="button" class="btn btn-secondary px-4"
                                        data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-warning px-4">
                                        <i class="bi bi-send"></i> Simpan Barang Keluar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Modal Filter --}}
    <div class="modal fade" id="modalFilterBarang" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form action="{{ route('pb.datakeseluruhan.index') }}" method="GET" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Stok</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Rentang Harga</label>
                            <div class="d-flex gap-2">
                                <input type="number" name="harga_min" class="form-control" placeholder="Min Harga"
                                    value="{{ request('harga_min') }}" step="0.01" min="0">
                                <input type="number" name="harga_max" class="form-control" placeholder="Max Harga"
                                    value="{{ request('harga_max') }}" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select name="kategori_id" class="form-select">
                                <option value="">-- Semua Kategori --</option>
                                @foreach ($kategori as $k)
                                    <option value="{{ $k->id }}" @if (request('kategori_id') == $k->id) selected @endif>
                                        {{ $k->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bagian</label>
                            <select name="bagian_id" class="form-select">
                                <option value="">-- Semua Bagian --</option>
                                @foreach ($bagian as $b)
                                    <option value="{{ $b->id }}" @if (request('bagian_id') == $b->id) selected @endif>
                                        {{ $b->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stok</label>
                            <div class="d-flex gap-2">
                                <input type="number" name="stok_min" class="form-control" placeholder="Stok Minimum"
                                    value="{{ request('stok_min') }}" min="0">
                                <input type="number" name="stok_max" class="form-control" placeholder="Stok Maksimal"
                                    value="{{ request('stok_max') }}" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('pb.datakeseluruhan.index') }}" class="btn btn-secondary">Reset Filter</a>
                    <button class="btn btn-primary" type="submit">Terapkan Filter</button>
                </div>
            </form>
        </div>
    </div>
    @push('scripts')
        <script>
            // Toggle detail function
            function toggleDetail(id) {
                let el = document.getElementById('detail-' + id);
                if (el.style.display === 'none') {
                    el.style.display = 'table-row';
                } else {
                    el.style.display = 'none';
                }
            }
            // Autocomplete search functionality
            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('searchInput');
                const suggestionsContainer = document.getElementById('searchSuggestions');
                let currentSuggestions = [];
                let activeSuggestionIndex = -1;
                let searchTimeout;
                if (!searchInput || !suggestionsContainer) {
                    return;
                }
                function fetchSuggestions(query) {
                    if (query.length < 2) {
                        hideSuggestions();
                        return;
                    }
                    showLoading();
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        fetch(`/pb/api/search-barang?q=${encodeURIComponent(query)}`)
                            .then(response => response.json())
                            .then(data => {
                                currentSuggestions = data;
                                displaySuggestions(data);
                            })
                            .catch(error => {
                                console.error('Search error:', error);
                                hideSuggestions();
                            });
                    }, 300);
                }
                function showLoading() {
                    suggestionsContainer.innerHTML = '<div class="loading-suggestion">Mencari...</div>';
                    suggestionsContainer.style.display = 'block';
                }
                function displaySuggestions(suggestions) {
                    if (suggestions.length === 0) {
                        suggestionsContainer.innerHTML =
                            '<div class="loading-suggestion">Tidak ada barang ditemukan</div>';
                        return;
                    }
                    let html = '';
                    suggestions.forEach((item, index) => {
                        const stockStatusClass = `stock-${item.stock_status}`;
                        const stockText = item.stock_status === 'empty' ? 'Habis' :
                            item.stock_status === 'low' ? 'Sedikit' : 'Tersedia';
                        html += `
                                <div class="search-suggestion-item" data-index="${index}">
                                    <div class="suggestion-name">${item.nama}</div>
                                    <div class="suggestion-code">ID: ${item.id} | Kode: ${item.kode} | Bagian: ${item.bagian}</div>
                                    <div class="suggestion-meta">
                                        <small>Kategori: ${item.kategori} | Stok: ${item.stok} | ${item.harga} | 
                                        <span class="stock-status ${stockStatusClass}">${stockText}</span></small>
                                    </div>
                                </div>
                            `;
                    });
                    suggestionsContainer.innerHTML = html;
                    suggestionsContainer.style.display = 'block';
                    suggestionsContainer.querySelectorAll('.search-suggestion-item').forEach(item => {
                        item.addEventListener('click', function () {
                            const index = parseInt(this.dataset.index);
                            selectSuggestion(index);
                        });
                    });
                }
                function hideSuggestions() {
                    suggestionsContainer.style.display = 'none';
                    activeSuggestionIndex = -1;
                }
                function selectSuggestion(index) {
                    if (currentSuggestions[index]) {
                        const suggestion = currentSuggestions[index];
                        searchInput.value = suggestion.nama;
                        hideSuggestions();
                        const form = document.getElementById('searchForm');
                        form.submit();
                    }
                }
                function updateActiveSuggestion(suggestions) {
                    suggestions.forEach((item, index) => {
                        if (index === activeSuggestionIndex) {
                            item.classList.add('active');
                        } else {
                            item.classList.remove('active');
                        }
                    });
                }
                // Event listeners
                searchInput.addEventListener('input', function () {
                    fetchSuggestions(this.value.trim());
                });
                searchInput.addEventListener('keydown', function (e) {
                    const suggestions = suggestionsContainer.querySelectorAll('.search-suggestion-item');
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        activeSuggestionIndex = Math.min(activeSuggestionIndex + 1, suggestions.length - 1);
                        updateActiveSuggestion(suggestions);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        activeSuggestionIndex = Math.max(activeSuggestionIndex - 1, -1);
                        updateActiveSuggestion(suggestions);
                    } else if (e.key === 'Enter') {
                        if (activeSuggestionIndex >= 0) {
                            e.preventDefault();
                            selectSuggestion(activeSuggestionIndex);
                        }
                    } else if (e.key === 'Escape') {
                        hideSuggestions();
                    }
                });
                searchInput.addEventListener('focus', function () {
                    if (this.value.trim().length >= 2) {
                        fetchSuggestions(this.value.trim());
                    }
                });
                document.addEventListener('click', function (e) {
                    if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                        hideSuggestions();
                    }
                });
                // Handle Modal Kelola Barang
                const modalKelola = document.getElementById("modalKelolaBarang");
                if (modalKelola) {
                    modalKelola.addEventListener("show.bs.modal", function (event) {
                        const button = event.relatedTarget;
                        const pbStokId = button.getAttribute("data-id");
                        const barangNama = button.getAttribute("data-nama");
                        const barangKode = button.getAttribute("data-kode");
                        const stok = parseInt(button.getAttribute("data-stok") || "0", 10);
                        // Set data untuk kedua form
                        document.getElementById("kelolaBarangNama").textContent = barangNama;
                        document.getElementById("barangMasukPbStokId").value = pbStokId;
                        document.getElementById("barangMasukKode").value = barangKode;
                        document.getElementById("barangMasukNama").value = barangNama;
                        document.getElementById("distribusiPbStokId").value = pbStokId;
                        document.getElementById("distribusiBarangKode").value = barangKode;
                        document.getElementById("distribusiBarangNama").value = barangNama;
                        // tampilkan stok di dua tab 
                        const elMasuk = document.getElementById("stokTersediaMasuk");
                        const elKeluar = document.getElementById("stokTersediaKeluar");
                        if (elMasuk) elMasuk.textContent = isFinite(stok) ? stok : 0;
                        if (elKeluar) elKeluar.textContent = isFinite(stok) ? stok : 0;

                        // batasi jumlah distribusi <= stok
                        const inputJumlahKeluar = document.querySelector('#content-distribusi input[name="jumlah"]');
                        if (inputJumlahKeluar) inputJumlahKeluar.max = isFinite(stok) ? stok : 0;

                        // Reset ke tab pertama
                        const firstTab = document.getElementById('tab-barang-masuk');
                        const firstTabContent = document.getElementById('content-barang-masuk');
                        const secondTab = document.getElementById('tab-distribusi');
                        const secondTabContent = document.getElementById('content-distribusi');

                        firstTab.classList.add('active');
                        firstTabContent.classList.add('show', 'active');
                        secondTab.classList.remove('active');
                        secondTabContent.classList.remove('show', 'active');

                        // Reset forms
                        document.getElementById('formBarangMasuk').reset();
                        document.getElementById('formDistribusi').reset();

                        // Set kembali nilai barang setelah reset
                        document.getElementById("barangMasukPbStokId").value = pbStokId;
                        document.getElementById("barangMasukKode").value = barangKode;
                        document.getElementById("barangMasukNama").value = barangNama;
                        document.getElementById("distribusiPbStokId").value = pbStokId;
                        document.getElementById("distribusiBarangKode").value = barangKode;
                        document.getElementById("distribusiBarangNama").value = barangNama;

                        // Clear file previews
                        document.getElementById('fileNameMasuk').textContent = '';
                        document.getElementById('fileNameDistribusi').textContent = '';
                    });
                }

                // File preview handlers
                const buktiBrgMasuk = document.getElementById('buktiBrgMasuk');
                if (buktiBrgMasuk) {
                    buktiBrgMasuk.addEventListener('change', function () {
                        const fileName = this.files[0]?.name || '';
                        document.getElementById('fileNameMasuk').textContent = fileName ? `File: ${fileName}` : '';
                    });
                }

                const buktiBrgDistribusi = document.getElementById('buktiBrgDistribusi');
                if (buktiBrgDistribusi) {
                    buktiBrgDistribusi.addEventListener('change', function () {
                        const fileName = this.files[0]?.name || '';
                        document.getElementById('fileNameDistribusi').textContent = fileName ? `File: ${fileName}` : '';
                    });
                }

                // Form submit handlers
                const formBarangMasuk = document.getElementById('formBarangMasuk');
                if (formBarangMasuk) {
                    formBarangMasuk.addEventListener('submit', function (e) {
                        e.preventDefault();
                        const pbStokId = document.getElementById('barangMasukPbStokId').value;
                        this.action = `/pb/barang-masuk/${pbStokId}`;
                        this.submit();
                    });
                }

                const formDistribusi = document.getElementById('formDistribusi');
                if (formDistribusi) {
                    formDistribusi.addEventListener('submit', function (e) {
                        e.preventDefault();
                        const pbStokId = document.getElementById('distribusiPbStokId').value;
                        this.action = `/pb/distribusi/${pbStokId}`;
                        this.submit();
                    });
                }
            });
        </script>
    @endpush
</x-layouts.app>