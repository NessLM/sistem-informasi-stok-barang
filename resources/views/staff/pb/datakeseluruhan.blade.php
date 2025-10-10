<x-layouts.app title="Data Keseluruhan" :menu="$menu">

    @php
        $barang = $barang ?? collect();
        $kategori = $kategori ?? collect();
        $gudang = $gudang ?? collect();
    @endphp

    <head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        .search-suggestion-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }
        
        .search-suggestion-item:hover,
        .search-suggestion-item.active {
            background-color: #f8f9fa;
        }
        
        .search-suggestion-item:last-child {
            border-bottom: none;
        }
        
        .suggestion-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
        }
        
        .suggestion-code {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 4px;
        }
        
        .suggestion-meta {
            font-size: 0.8rem;
            color: #95a5a6;
        }
        
        .stock-status {
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        
        .stock-available {
            background-color: #d4edda;
            color: #155724;
        }
        
        .stock-low {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .stock-empty {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .loading-suggestion {
            padding: 12px 16px;
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }
        
        #searchSuggestions {
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 0 0 0.375rem 0.375rem;
        }
    </style>
</head>

    <main class="page-wrap container py-4">

      <!-- Toast notification -->
    @if (session('toast'))
        <div id="toast-notif"
            style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
              z-index: 2000; display: flex; justify-content: center; pointer-events: none;">

            <div class="toast-message"
                style="background: #fff; border-radius: 12px; padding: 14px 22px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15); text-align: center;
                min-width: 280px; max-width: 360px; transition: opacity .5s ease;">

                <div
                    style="font-weight: 600; font-size: 16px; margin-bottom: 4px;
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
                @php
                    $title = 'Data Keseluruhan';
                    
                    if($kategori->isNotEmpty()) {
                        $firstGudang = $kategori->first()->gudang->nama ?? null;
                        $allSameGudang = $kategori->every(function($k) use ($firstGudang) {
                            return ($k->gudang->nama ?? null) === $firstGudang;
                        });
                        
                        if($allSameGudang && $firstGudang) {
                            if(str_starts_with($firstGudang, 'Gudang')) {
                                $title = 'Data ' . $firstGudang;
                            } else {
                                $title = 'Data Gudang ' . $firstGudang;
                            }
                        }
                    }
                    
                    if(request()->filled('gudang_id') && isset($selectedGudang)) {
                        $gudangNama = $selectedGudang->nama;
                        if(str_starts_with($gudangNama, 'Gudang')) {
                            $title = 'Data ' . $gudangNama;
                        } else {
                            $title = 'Data Gudang ' . $gudangNama;
                        }
                    }
                    
                    $currentPath = request()->path();
                    if (str_contains($currentPath, '/atk')) {
                        $title = 'Data Gudang ATK';
                    } elseif (str_contains($currentPath, '/listrik')) {
                        $title = 'Data Gudang Listrik';
                    } elseif (str_contains($currentPath, '/kebersihan')) {
                        $title = 'Data Gudang Kebersihan';
                    } elseif (str_contains($currentPath, '/komputer')) {
                        $title = 'Data Gudang Komputer';
                    }
                @endphp
                <h4>{{ $title }}</h4>
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
            @if (request()->filled('search') ||
                    request()->filled('kode') ||
                    request()->filled('stok_min') ||
                    request()->filled('stok_max') ||
                    request()->filled('kategori_id') ||
                    request()->filled('gudang_id') ||
                    request()->filled('satuan') ||
                    request()->filled('nomor_awal') ||
                    request()->filled('nomor_akhir') ||
                    request()->filled('harga_min') ||
                    request()->filled('harga_max'))
                <h5 class="mt-3">Hasil Pencarian</h5>
                @if ($barang->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered mt-2">
                            <thead class="table-secondary">
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Kode</th>
                                    <th>Stok</th>
                                    <th>Satuan</th>
                                    <th>Kategori</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($barang as $i => $b)
    @php
        $stokGudang = \App\Models\StokGudang::where('barang_id', $b->id)
            ->where('gudang_id', $selectedGudang->id)
            ->first();
        $stokTersedia = $stokGudang ? $stokGudang->stok : 0;
    @endphp
    <tr @if ($stokTersedia == 0) class="table-danger" @endif>
        <td>{{ $i + 1 }}</td>
        <td>{{ $b->nama }}</td>
        <td>{{ $b->kode }}</td>
        <td>{{ $stokTersedia }}</td>
        <td>{{ $b->satuan }}</td>
        <td>{{ $b->kategori->nama ?? '-' }}</td>
        <td>...</td>
    </tr>
@endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">Tidak ada data ditemukan untuk kriteria pencarian Anda</div>
                @endif
            @endif

            {{-- Jika tidak ada filter/search --}}
            @if (
                !request()->filled('search') &&
                    !request()->filled('kode') &&
                    !request()->filled('stok_min') &&
                    !request()->filled('stok_max') &&
                    !request()->filled('kategori_id') &&
                    !request()->filled('gudang_id') &&
                    !request()->filled('satuan') &&
                    !request()->filled('nomor_awal') &&
                    !request()->filled('nomor_akhir') &&
                    !request()->filled('harga_min') &&
                    !request()->filled('harga_max'))
                <div class="table-responsive mt-3">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>KATEGORI</th>
                                <th>GUDANG</th>
                                <th style="width:180px" class="text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kategori as $k)
                                <tr>
                                    <td>{{ $k->nama }}</td>
                                    <td>{{ $k->gudang->nama ?? '-' }}</td>
                                    <td class="text-center">
                                        <div class="d-flex flex-wrap justify-content-center gap-2">
                                            <button class="btn btn-sm btn-success"
                                                onclick="toggleDetail({{ $k->id }})"><i
                                                    class="bi bi-eye"></i></button>
                                        </div>
                                    </td>
                                </tr>

                                <tr id="detail-{{ $k->id }}" style="display:none;">
                                    <td colspan="3">
                                        @if ($k->barang->count())
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Kode</th>
                                                            <th>Nama Barang</th>
                                                            <th>Stok</th>
                                                            <th>Satuan</th>
                                                            <th>Kategori</th>
                                                            <th>Gudang</th>
                                                            <th>Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($k->barang as $item)
                                                            @php
                                                                $stokGudang = \App\Models\StokGudang::where('barang_id', $item->id)
                                                                    ->where('gudang_id', $k->gudang_id)
                                                                    ->first();
                                                                $stokTersedia = $stokGudang ? $stokGudang->stok : 0;
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $item->kode }}</td>
                                                                <td>{{ $item->nama }}</td>
                                                                <td>{{ $stokTersedia }}</td>
                                                                <td>{{ $item->satuan }}</td>
                                                                <td>{{ $item->kategori->nama ?? '-' }}</td>
                                                                <td>{{ $item->kategori->gudang->nama ?? '-' }}</td>
                                                                <td>
                                                                    <button type="button"
                                                                        class="btn btn-primary btn-sm"
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#modalKelolaBarang"
                                                                        data-id="{{ $item->id }}"
                                                                        data-nama="{{ $item->nama }}"
                                                                        data-kode="{{ $item->kode }}">
                                                                        <i class="bi bi-box-seam"></i> Kelola
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-muted">Belum ada barang pada kategori ini.</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </main>

<!-- Modal Kelola Barang (menggabungkan Barang Masuk & Distribusi) -->
<div class="modal fade" id="modalKelolaBarang" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold">Kelola Barang: <span id="kelolaBarangNama" class="text-primary"></span></h5>
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
                            <i class="bi bi-box-arrow-right"></i> Distribusi
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="kelolaTabContent">
                    <!-- Content: Barang Masuk -->
                    <div class="tab-pane fade show active" id="content-barang-masuk" role="tabpanel">
                        <form method="POST" id="formBarangMasuk" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="barang_id" id="barangMasukId">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Barang</label>
                                    <input type="text" id="barangMasukNama" class="form-control" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jumlah Masuk</label>
                                    <input type="number" name="jumlah" class="form-control" placeholder="Masukkan Jumlah" required min="1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Masuk <small class="text-muted">(Opsional)</small></label>
                                    <input type="date" name="tanggal" id="tanggalMasuk" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Keterangan</label>
                                    <input type="text" name="keterangan" class="form-control" placeholder="Masukkan keterangan">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Bukti Barang Masuk</label>
                                    <div class="border rounded p-4 text-center" style="background-color: #f8f9fa;">
                                        <input type="file" name="bukti" id="buktiBrgMasuk" class="d-none" accept="image/*,.pdf">
                                        <label for="buktiBrgMasuk" class="d-block" style="cursor: pointer;">
                                            <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #6c757d;"></i>
                                            <div class="mt-2" style="color: #6c757d; font-size: 0.875rem;">Klik untuk Upload</div>
                                        </label>
                                        <div id="fileNameMasuk" class="mt-2 text-primary small"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success px-4">
                                    <i class="bi bi-check-circle"></i> Simpan Barang Masuk
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Content: Distribusi -->
                    <div class="tab-pane fade" id="content-distribusi" role="tabpanel">
                        <form method="POST" id="formDistribusi" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="barang_id" id="distribusiBarangId">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Barang</label>
                                    <input type="text" id="distribusiBarangNama" class="form-control" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jumlah Keluar</label>
                                    <input type="number" name="jumlah" class="form-control" placeholder="Masukkan Jumlah" required min="1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Distribusi <small class="text-muted">(Opsional)</small></label>
                                    <input type="date" name="tanggal" id="tanggalDistribusi" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Gudang Tujuan</label>
                                    <select name="gudang_tujuan_id" id="distribusiGudangTujuan" class="form-select" required>
                                        <option value="">-- Pilih Gudang --</option>
                                        @foreach($gudang as $g)
                                            <option value="{{ $g->id }}">{{ $g->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Kategori Tujuan</label>
                                    <select name="kategori_tujuan_id" id="distribusiKategoriTujuan" class="form-select" required disabled>
                                        <option value="">-- Pilih Gudang Terlebih Dahulu --</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Bukti Distribusi</label>
                                    <div class="border rounded p-4 text-center" style="background-color: #f8f9fa;">
                                        <input type="file" name="bukti" id="buktiBrgDistribusi" class="d-none" accept="image/*,.pdf">
                                        <label for="buktiBrgDistribusi" class="d-block" style="cursor: pointer;">
                                            <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #6c757d;"></i>
                                            <div class="mt-2" style="color: #6c757d; font-size: 0.875rem;">Klik untuk Upload</div>
                                        </label>
                                        <div id="fileNameDistribusi" class="mt-2 text-primary small"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-warning px-4">
                                    <i class="bi bi-send"></i> Simpan Distribusi
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
                    <h5 class="modal-title">Filter Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Satuan</label>
                            <select name="satuan" class="form-select">
                                <option value="">-- Semua Satuan --</option>
                                <option value="Pcs" @if (request('satuan') == 'Pcs') selected @endif>Pcs</option>
                                <option value="Box" @if (request('satuan') == 'Box') selected @endif>Box</option>
                                <option value="Pack" @if (request('satuan') == 'Pack') selected @endif>Pack</option>
                                <option value="Rim" @if (request('satuan') == 'Rim') selected @endif>Rim</option>
                                <option value="Unit" @if (request('satuan') == 'Unit') selected @endif>Unit</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Rentang Harga</label>
                            <div class="d-flex gap-2">
                                <input type="number" name="harga_min" class="form-control"
                                    placeholder="Min Harga" value="{{ request('harga_min') }}" step="0.01" min="0">
                                <input type="number" name="harga_max" class="form-control"
                                    placeholder="Max Harga" value="{{ request('harga_max') }}" step="0.01" min="0">
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
                            <label class="form-label">Stok</label>
                            <div class="d-flex gap-2">
                                <input type="number" name="stok_min" class="form-control"
                                    placeholder="Stok Minimum" value="{{ request('stok_min') }}" min="0">
                                <input type="number" name="stok_max" class="form-control"
                                    placeholder="Stok Maksimal" value="{{ request('stok_max') }}" min="0">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Gudang</label>
                            <select name="gudang_id" class="form-select">
                                <option value="">-- Semua Gudang --</option>
                                @foreach ($gudang as $g)
                                    <option value="{{ $g->id }}" @if (request('gudang_id') == $g->id) selected @endif>
                                        {{ $g->nama }}
                                    </option>
                                @endforeach
                            </select>
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

    {{-- JavaScript --}}
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
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const suggestionsContainer = document.getElementById('searchSuggestions');
            let currentSuggestions = [];
            let activeSuggestionIndex = -1;
            let searchTimeout;

            if (!searchInput || !suggestionsContainer) {
                return;
            }

            function getActiveGudangId() {
                const modalGudangSelect = document.querySelector('#modalFilterBarang select[name="gudang_id"]');
                if (modalGudangSelect && modalGudangSelect.value) {
                    return modalGudangSelect.value;
                }

                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('gudang_id')) {
                    return urlParams.get('gudang_id');
                }

                const currentPath = window.location.pathname;
                if (currentPath.includes('/atk')) {
                    return getGudangIdByName('ATK');
                } else if (currentPath.includes('/listrik')) {
                    return getGudangIdByName('Listrik');
                } else if (currentPath.includes('/kebersihan')) {
                    return getGudangIdByName('Kebersihan');
                } else if (currentPath.includes('/komputer')) {
                    return getGudangIdByName('Komputer');
                }

                return null;
            }

            function getGudangIdByName(namaGudang) {
                const gudangSelect = document.querySelector('select[name="gudang_id"]');
                if (!gudangSelect) return null;

                for (let option of gudangSelect.options) {
                    if (option.text.toLowerCase().includes(namaGudang.toLowerCase())) {
                        return option.value;
                    }
                }
                return null;
            }

            function fetchSuggestions(query) {
                if (query.length < 2) {
                    hideSuggestions();
                    return;
                }

                showLoading();
                clearTimeout(searchTimeout);

                searchTimeout = setTimeout(() => {
                    const activeGudangId = getActiveGudangId();
                    let searchUrl = `/pb/api/search-barang?q=${encodeURIComponent(query)}`;
                    
                    if (activeGudangId) {
                        searchUrl += `&gudang_id=${activeGudangId}`;
                    }

                    fetch(searchUrl)
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
                            <div class="suggestion-code">Kode: ${item.kode}</div>
                            <div class="suggestion-meta">
                                <small>Kategori: ${item.kategori} | Gudang: ${item.gudang} | Stok: ${item.stok} | 
                                <span class="stock-status ${stockStatusClass}">${stockText}</span></small>
                            </div>
                        </div>
                    `;
                });

                suggestionsContainer.innerHTML = html;
                suggestionsContainer.style.display = 'block';

                suggestionsContainer.querySelectorAll('.search-suggestion-item').forEach(item => {
                    item.addEventListener('click', function() {
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
                    const activeGudangId = getActiveGudangId();
                    
                    if (activeGudangId) {
                        let hiddenGudangInput = form.querySelector('input[name="gudang_id"]');
                        if (!hiddenGudangInput) {
                            hiddenGudangInput = document.createElement('input');
                            hiddenGudangInput.type = 'hidden';
                            hiddenGudangInput.name = 'gudang_id';
                            form.appendChild(hiddenGudangInput);
                        }
                        hiddenGudangInput.value = activeGudangId;
                    }
                    
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
            searchInput.addEventListener('input', function() {
                fetchSuggestions(this.value.trim());
            });

            searchInput.addEventListener('keydown', function(e) {
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

            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length >= 2) {
                    fetchSuggestions(this.value.trim());
                }
            });

            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                    hideSuggestions();
                }
            });

            const gudangSelects = document.querySelectorAll('select[name="gudang_id"]');
            gudangSelects.forEach(select => {
                select.addEventListener('change', function() {
                    if (searchInput.value.trim().length >= 2) {
                        fetchSuggestions(searchInput.value.trim());
                    }
                });
            });

            // Handle Modal Kelola Barang
            const modalKelola = document.getElementById("modalKelolaBarang");
            if (modalKelola) {
                modalKelola.addEventListener("show.bs.modal", function (event) {
                    const button = event.relatedTarget;
                    const barangId = button.getAttribute("data-id");
                    const barangNama = button.getAttribute("data-nama");
                    const barangKode = button.getAttribute("data-kode") || '';
                    
                    // Set data untuk kedua form
                    document.getElementById("kelolaBarangNama").textContent = barangNama;
                    document.getElementById("barangMasukId").value = barangId;
                    document.getElementById("barangMasukNama").value = barangNama;
                    document.getElementById("distribusiBarangId").value = barangId;
                    document.getElementById("distribusiBarangNama").value = barangNama;
                    
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
                    document.getElementById("barangMasukId").value = barangId;
                    document.getElementById("barangMasukNama").value = barangNama;
                    document.getElementById("distribusiBarangId").value = barangId;
                    document.getElementById("distribusiBarangNama").value = barangNama;
                    
                    // Reset kategori dropdown
                    const kategoriSelect = document.getElementById('distribusiKategoriTujuan');
                    kategoriSelect.innerHTML = '<option value="">-- Pilih Gudang Terlebih Dahulu --</option>';
                    kategoriSelect.disabled = true;
                    document.getElementById('distribusiGudangTujuan').value = '';
                    
                    // Clear file previews
                    document.getElementById('fileNameMasuk').textContent = '';
                    document.getElementById('fileNameDistribusi').textContent = '';
                });
            }

            // Handle perubahan Gudang Tujuan
            const distribusiGudangTujuan = document.getElementById('distribusiGudangTujuan');
            if (distribusiGudangTujuan) {
                distribusiGudangTujuan.addEventListener('change', function() {
                    const gudangId = this.value;
                    const kategoriSelect = document.getElementById('distribusiKategoriTujuan');
                    
                    if (!gudangId) {
                        kategoriSelect.innerHTML = '<option value="">-- Pilih Gudang Terlebih Dahulu --</option>';
                        kategoriSelect.disabled = true;
                        return;
                    }
                    
                    kategoriSelect.innerHTML = '<option value="">Memuat kategori...</option>';
                    kategoriSelect.disabled = true;
                    
                    fetch(`/pb/api/kategori-by-gudang/${gudangId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.length === 0) {
                                kategoriSelect.innerHTML = '<option value="">Tidak ada kategori</option>';
                                kategoriSelect.disabled = true;
                            } else {
                                let options = '<option value="">-- Pilih Kategori --</option>';
                                data.forEach(kategori => {
                                    options += `<option value="${kategori.id}">${kategori.nama}</option>`;
                                });
                                kategoriSelect.innerHTML = options;
                                kategoriSelect.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            kategoriSelect.innerHTML = '<option value="">Error memuat kategori</option>';
                            kategoriSelect.disabled = true;
                        });
                });
            }

            // File preview handlers
            const buktiBrgMasuk = document.getElementById('buktiBrgMasuk');
            if (buktiBrgMasuk) {
                buktiBrgMasuk.addEventListener('change', function() {
                    const fileName = this.files[0]?.name || '';
                    document.getElementById('fileNameMasuk').textContent = fileName ? `File: ${fileName}` : '';
                });
            }

            const buktiBrgDistribusi = document.getElementById('buktiBrgDistribusi');
            if (buktiBrgDistribusi) {
                buktiBrgDistribusi.addEventListener('change', function() {
                    const fileName = this.files[0]?.name || '';
                    document.getElementById('fileNameDistribusi').textContent = fileName ? `File: ${fileName}` : '';
                });
            }

            // Form submit handlers
            const formBarangMasuk = document.getElementById('formBarangMasuk');
            if (formBarangMasuk) {
                formBarangMasuk.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const barangId = document.getElementById('barangMasukId').value;
                    this.action = `/pb/barang-masuk/${barangId}`;
                    this.submit();
                });
            }

            const formDistribusi = document.getElementById('formDistribusi');
            if (formDistribusi) {
                formDistribusi.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const barangId = document.getElementById('distribusiBarangId').value;
                    this.action = `/pb/distribusi/${barangId}`;
                    this.submit();
                });
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</x-layouts.app>