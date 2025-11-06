<x-layouts.app title="Data Keseluruhan" :menu="$menu">
    @php
        $barangData = $barangData ?? collect();
        $kategori = $kategori ?? collect();
        $bagian = $bagian ?? collect();
        $activeTab = $activeTab ?? 'data-keseluruhan';
    @endphp
    
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/staff/pb/data_keseluruhan_pb.css') }}">
        <style>
            .row-low-stock {
                background-color: #ffcccc !important;
                border-left: 4px solid #dc3545 !important;
            }

            /* Tab Navigation Styles - CENTERED */
            .nav-tabs-custom {
                border-bottom: 2px solid #dee2e6;
                margin-bottom: 1.5rem;
                display: flex;
                justify-content: center;
            }
            
            .nav-tabs-custom .nav-link {
                border: none;
                color: #6c757d;
                font-weight: 500;
                padding: 0.75rem 1.5rem;
                margin-right: 0.5rem;
                border-bottom: 3px solid transparent;
                transition: all 0.3s ease;
            }
            
            .nav-tabs-custom .nav-link:hover {
                color: #0d6efd;
                border-bottom-color: #0d6efd;
                background: transparent;
            }
            
            .nav-tabs-custom .nav-link.active {
                color: #0d6efd;
                border-bottom-color: #0d6efd;
                background: transparent;
            }

            /* Autocomplete Styles */
            .search-suggestion-item {
                padding: 12px;
                cursor: pointer;
                border-bottom: 1px solid #e5e7eb;
                transition: all 0.2s ease;
            }

            .search-suggestion-item:hover,
            .search-suggestion-item.active {
                background-color: #f3f4f6;
            }

            .suggestion-name {
                font-weight: 600;
                color: #111827;
                margin-bottom: 4px;
            }

            .suggestion-code {
                font-size: 13px;
                color: #6b7280;
                margin-bottom: 4px;
            }

            .suggestion-meta {
                font-size: 12px;
                color: #9ca3af;
            }

            .stock-status {
                padding: 2px 8px;
                border-radius: 4px;
                font-weight: 500;
            }

            .stock-available {
                background-color: #d1fae5;
                color: #065f46;
            }

            .stock-low {
                background-color: #fef3c7;
                color: #92400e;
            }

            .stock-empty {
                background-color: #fee2e2;
                color: #991b1b;
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
            <!-- Tab Navigation - CENTERED -->
            <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'data-keseluruhan' ? 'active' : '' }}" 
                       href="{{ route('pb.datakeseluruhan.index', ['tab' => 'data-keseluruhan']) }}">
                        <i class="bi bi-grid-3x3-gap"></i> Data Keseluruhan
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'distribusi' ? 'active' : '' }}" 
                       href="{{ route('pb.datakeseluruhan.index', ['tab' => 'distribusi']) }}">
                        <i class="bi bi-box-arrow-right"></i> Distribusi
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- TAB 1: DATA KESELURUHAN -->
                <div class="tab-pane fade {{ $activeTab === 'data-keseluruhan' ? 'show active' : '' }}" 
                     id="data-keseluruhan" role="tabpanel">
                    
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <h5 class="mb-0">Data Keseluruhan Barang</h5>
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFilterBarang">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>

                    {{-- Search Form dengan Autocomplete --}}
                    <div class="position-relative mb-3">
                        <form action="{{ route('pb.datakeseluruhan.index') }}" method="GET" class="input-group" id="searchForm">
                            <input type="hidden" name="tab" value="data-keseluruhan">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="searchInput" class="form-control"
                                placeholder="Cari barang (nama, kode, kategori)"
                                value="{{ request('search') }}" autocomplete="off">
                            <button class="btn btn-outline-secondary" type="submit">Cari</button>
                        </form>
                        <div id="searchSuggestions" class="dropdown-menu w-100 position-absolute"
                            style="z-index: 1050; max-height: 300px; overflow-y: auto; display: none;">
                        </div>
                    </div>

                    {{-- Jika ada filter/search --}}
                    @if ($barangData && $barangData->count() > 0)
                        <h6 class="mt-3">Hasil Pencarian - Data Barang</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered mt-2">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>No</th>
                                        <th>Kode Barang</th>
                                        <th>Nama Barang</th>
                                        <th>Kategori</th>
                                        <th>Satuan</th>
                                        <th>Harga Barang</th>
                                        <th>Total Stok (PB)</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($barangData as $i => $item)
                                        @php
                                            $totalStok = $item->pbStok->sum('stok');
                                        @endphp
                                        <tr @if ($totalStok < 10) class="row-low-stock" @endif>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $item->kode_barang }}</td>
                                            <td>{{ $item->nama_barang }}</td>
                                            <td>{{ $item->kategori->nama ?? '-' }}</td>
                                            <td>{{ $item->satuan }}</td>
                                            <td>Rp {{ number_format($item->harga_barang ?? 0, 0, ',', '.') }}</td>
                                            <td>{{ $totalStok }}</td>
                                            <td>
                                                <button type="button" class="btn btn-success btn-sm" 
                                                    data-bs-toggle="modal" data-bs-target="#modalBarangMasuk"
                                                    data-kode="{{ $item->kode_barang }}"
                                                    data-nama="{{ $item->nama_barang }}"
                                                    data-total-stok="{{ $totalStok }}">
                                                    <i class="bi bi-box-arrow-in-down"></i> Barang Masuk
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif(request()->filled('search') || request()->filled('kategori_id') || request()->filled('satuan'))
                        <div class="alert alert-warning">Tidak ada data ditemukan untuk kriteria pencarian Anda</div>
                    @else
                        {{-- Tampilan Nested Default - Data Barang per Kategori --}}
                        <h6 class="mt-3">Data Barang Gudang Utama</h6>
                        <div class="table-responsive">
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
                                            $barangInKategori = \App\Models\Barang::with(['pbStok', 'kategori'])
                                                ->where('id_kategori', $k->id)
                                                ->get();
                                        @endphp
                                        @if($barangInKategori->count() > 0)
                                            <tr>
                                                <td>{{ $k->nama }}</td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-success" onclick="toggleDetail('barang', {{ $k->id }})">
                                                        <i class="bi bi-eye"></i> Lihat ({{ $barangInKategori->count() }})
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr id="detail-barang-{{ $k->id }}" style="display:none;">
                                                <td colspan="2">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>No</th>
                                                                    <th>Kode</th>
                                                                    <th>Nama Barang</th>
                                                                    <th>Satuan</th>
                                                                    <th>Harga Barang</th>
                                                                    <th>Total Stok (PB)</th>
                                                                    <th>Detail Stok per Bagian</th>
                                                                    <th>Aksi</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($barangInKategori as $idx => $item)
                                                                    @php
                                                                        $totalStok = $item->pbStok->sum('stok');
                                                                    @endphp
                                                                    <tr @if ($totalStok < 10) class="row-low-stock" @endif>
                                                                        <td>{{ $idx + 1 }}</td>
                                                                        <td>{{ $item->kode_barang }}</td>
                                                                        <td>{{ $item->nama_barang }}</td>
                                                                        <td>{{ $item->satuan }}</td>
                                                                        <td>Rp {{ number_format($item->harga_barang ?? 0, 0, ',', '.') }}</td>
                                                                        <td><strong>{{ $totalStok }}</strong></td>
                                                                        <td>
                                                                            @if($item->pbStok->count() > 0)
                                                                                <small>
                                                                                    @foreach($item->pbStok as $pb)
                                                                                        <span class="badge bg-info">{{ $pb->bagian->nama ?? '-' }}: {{ $pb->stok }}</span>
                                                                                    @endforeach
                                                                                </small>
                                                                            @else
                                                                                <small class="text-muted">Belum ada stok</small>
                                                                            @endif
                                                                        </td>
                                                                        <td>
                                                                            <button type="button" class="btn btn-success btn-sm"
                                                                                data-bs-toggle="modal" data-bs-target="#modalBarangMasuk"
                                                                                data-kode="{{ $item->kode_barang }}"
                                                                                data-nama="{{ $item->nama_barang }}"
                                                                                data-total-stok="{{ $totalStok }}">
                                                                                <i class="bi bi-box-arrow-in-down"></i> Barang Masuk
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

                        {{-- Data per Bagian (Stok Bagian) - View Only --}}
                        <h6 class="mt-4">Data Per Bagian (Stok_Bagian) - View Only</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>BAGIAN</th>
                                        <th style="width:180px" class="text-center">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bagian as $bg)
                                        @php
                                            $stokDiBagian = \App\Models\StokBagian::with(['barang.kategori', 'bagian'])
                                                ->where('bagian_id', $bg->id)
                                                ->get();
                                        @endphp
                                        @if($stokDiBagian->count() > 0)
                                            <tr class="table-secondary">
                                                <td>{{ $bg->nama }}</td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-primary" onclick="toggleDetail('bagian', {{ $bg->id }})">
                                                        <i class="bi bi-chevron-down"></i> Expand ({{ $stokDiBagian->count() }})
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr id="detail-bagian-{{ $bg->id }}" style="display:none;">
                                                <td colspan="2">
                                                    {{-- Group by kategori --}}
                                                    @php
                                                        $grouped = $stokDiBagian->groupBy(function($item) {
                                                            return $item->barang->kategori->nama ?? 'Tanpa Kategori';
                                                        });
                                                    @endphp
                                                    @foreach($grouped as $katNama => $items)
                                                        <div class="mb-3">
                                                            <h6 class="text-primary mt-2">Kategori: {{ $katNama }}</h6>
                                                            <div class="table-responsive">
                                                                <table class="table table-striped table-sm">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>No</th>
                                                                            <th>Kode</th>
                                                                            <th>Nama Barang</th>
                                                                            <th>Stok</th>
                                                                            <th>Harga</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($items as $idx => $sb)
                                                                            <tr @if ($sb->stok < 10) class="row-low-stock" @endif>
                                                                                <td>{{ $idx + 1 }}</td>
                                                                                <td>{{ $sb->kode_barang }}</td>
                                                                                <td>{{ $sb->barang->nama_barang ?? '-' }}</td>
                                                                                <td>{{ $sb->stok }}</td>
                                                                                <td>Rp {{ number_format($sb->harga ?? 0, 0, ',', '.') }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- TAB 2: DISTRIBUSI -->
                <div class="tab-pane fade {{ $activeTab === 'distribusi' ? 'show active' : '' }}" 
                     id="distribusi" role="tabpanel">
                    
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <h5 class="mb-0">Distribusi Barang ke Bagian</h5>
                    </div>

                    {{-- Search untuk Distribusi --}}
                    <div class="position-relative mb-3">
                        <form action="{{ route('pb.datakeseluruhan.index') }}" method="GET" class="input-group" id="searchFormDistribusi">
                            <input type="hidden" name="tab" value="distribusi">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="searchInputDistribusi" class="form-control"
                                placeholder="Cari barang untuk distribusi"
                                value="{{ request('search') }}" autocomplete="off">
                            <button class="btn btn-outline-secondary" type="submit">Cari</button>
                        </form>
                        <div id="searchSuggestionsDistribusi" class="dropdown-menu w-100 position-absolute"
                            style="z-index: 1050; max-height: 300px; overflow-y: auto; display: none;">
                        </div>
                    </div>

                    {{-- Tampilan per Kategori --}}
                    <div class="table-responsive">
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
                                        $pbStokKategori = \App\Models\PbStok::with(['barang', 'bagian'])
                                            ->whereHas('barang', function ($q) use ($k) {
                                                $q->where('id_kategori', $k->id);
                                            })
                                            ->get();
                                    @endphp
                                    @if($pbStokKategori->count() > 0)
                                        <tr>
                                            <td>{{ $k->nama }}</td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-warning" onclick="toggleDistribusi({{ $k->id }})">
                                                    <i class="bi bi-box-seam"></i> Lihat ({{ $pbStokKategori->count() }})
                                                </button>
                                            </td>
                                        </tr>
                                        <tr id="distribusi-{{ $k->id }}" style="display:none;">
                                            <td colspan="2">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>No</th>
                                                                <th>Kode</th>
                                                                <th>Nama Barang</th>
                                                                <th>Bagian (PB)</th>
                                                                <th>Stok Tersedia</th>
                                                                <th>Harga</th>
                                                                <th>Aksi</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($pbStokKategori as $idx => $pb)
                                                                <tr @if ($pb->stok < 10) class="row-low-stock" @endif>
                                                                    <td>{{ $idx + 1 }}</td>
                                                                    <td>{{ $pb->kode_barang }}</td>
                                                                    <td>{{ $pb->barang->nama_barang ?? '-' }}</td>
                                                                    <td>{{ $pb->bagian->nama ?? '-' }}</td>
                                                                    <td>{{ $pb->stok }}</td>
                                                                    <td>Rp {{ number_format($pb->harga ?? 0, 0, ',', '.') }}</td>
                                                                    <td>
                                                                        <button type="button" class="btn btn-warning btn-sm"
                                                                            data-bs-toggle="modal" data-bs-target="#modalDistribusi"
                                                                            data-id="{{ $pb->id }}"
                                                                            data-nama="{{ $pb->barang->nama_barang ?? '-' }}"
                                                                            data-kode="{{ $pb->kode_barang }}"
                                                                            data-stok="{{ $pb->stok }}"
                                                                            data-harga="{{ $pb->harga }}">
                                                                            <i class="bi bi-box-arrow-right"></i> Distribusi
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
                                                    <i class="bi bi-eye"></i> Lihat
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
            </div>
        </section>
    </main>

    <!-- Modal Barang Masuk -->
    <div class="modal fade" id="modalBarangMasuk" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Barang Masuk: <span id="barangMasukNama" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formBarangMasuk" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="kode_barang" id="barangMasukKode">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Bagian Tujuan</label>
                                <select name="bagian_id" id="bagianTujuan" class="form-select" required>
                                    <option value="">-- Pilih Bagian --</option>
                                    @foreach($bagian as $bg)
                                        <option value="{{ $bg->id }}">{{ $bg->nama }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Pilih bagian yang akan menerima barang masuk</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Masuk</label>
                                <input type="number" name="jumlah" class="form-control" placeholder="Masukkan Jumlah" required min="1">
                                <small class="text-muted d-block mt-1">
                                    Total Stok Saat Ini: <span id="stokSekarang">0</span>
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Harga Satuan</label>
                                <input type="number" name="harga" class="form-control" placeholder="Masukkan Harga" required min="0" step="0.01">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Masuk <small class="text-muted">(Opsional)</small></label>
                                <input type="date" name="tanggal" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Keterangan</label>
                                <input type="text" name="keterangan" class="form-control" placeholder="Masukkan keterangan">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Bukti Barang Masuk</label>
                                <input type="file" name="bukti" class="form-control" accept="image/*,.pdf">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Distribusi -->
    <div class="modal fade" id="modalDistribusi" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Distribusi Barang: <span id="distribusiNama" class="text-warning"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formDistribusi" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="kode_barang" id="distribusiKode">
                    <input type="hidden" name="pb_stok_id" id="distribusiPbStokId">
                    <input type="hidden" name="harga" id="distribusiHarga">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tujuan Bagian</label>
                                <select name="bagian_id" class="form-select" required>
                                    <option value="">-- Pilih Bagian --</option>
                                    @foreach($bagian as $bg)
                                        <option value="{{ $bg->id }}">{{ $bg->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Distribusi</label>
                                <input type="number" name="jumlah" id="jumlahDistribusi" class="form-control" 
                                       placeholder="Masukkan Jumlah" required min="1">
                                <small class="text-muted d-block mt-1">
                                    Stok Tersedia: <span id="stokTersedia">0</span>
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Distribusi <small class="text-muted">(Opsional)</small></label>
                                <input type="date" name="tanggal" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Keterangan</label>
                                <input type="text" name="keterangan" class="form-control" placeholder="Masukkan keterangan">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Bukti Distribusi</label>
                                <input type="file" name="bukti" class="form-control" accept="image/*,.pdf">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-send"></i> Distribusi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Filter --}}
    <div class="modal fade" id="modalFilterBarang" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form action="{{ route('pb.datakeseluruhan.index') }}" method="GET" class="modal-content">
                <input type="hidden" name="tab" value="data-keseluruhan">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
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
                            <label class="form-label">Satuan</label>
                            <select name="satuan" class="form-select">
                                <option value="">-- Semua Satuan --</option>
                                <option value="Pcs" @if (request('satuan') == 'Pcs') selected @endif>Pcs</option>
                                <option value="Unit" @if (request('satuan') == 'Unit') selected @endif>Unit</option>
                                <option value="Box" @if (request('satuan') == 'Box') selected @endif>Box</option>
                                <option value="Kg" @if (request('satuan') == 'Kg') selected @endif>Kg</option>
                                <option value="Liter" @if (request('satuan') == 'Liter') selected @endif>Liter</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stok Total (PB)</label>
                            <div class="d-flex gap-2">
                                <input type="number" name="stok_min" class="form-control" placeholder="Stok Minimum"
                                    value="{{ request('stok_min') }}" min="0">
                                <input type="number" name="stok_max" class="form-control" placeholder="Stok Maksimal"
                                    value="{{ request('stok_max') }}" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rentang Harga Barang</label>
                            <div class="d-flex gap-2">
                                <input type="number" name="harga_min" class="form-control" placeholder="Min Harga"
                                    value="{{ request('harga_min') }}" step="0.01" min="0">
                                <input type="number" name="harga_max" class="form-control" placeholder="Max Harga"
                                    value="{{ request('harga_max') }}" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('pb.datakeseluruhan.index', ['tab' => 'data-keseluruhan']) }}" class="btn btn-secondary">Reset Filter</a>
                    <button class="btn btn-primary" type="submit">Terapkan Filter</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            // Toggle Functions
            function toggleDetail(type, id) {
                let el = document.getElementById('detail-' + type + '-' + id);
                if (el.style.display === 'none') {
                    el.style.display = 'table-row';
                } else {
                    el.style.display = 'none';
                }
            }

            function toggleDistribusi(id) {
                let el = document.getElementById('distribusi-' + id);
                if (el.style.display === 'none') {
                    el.style.display = 'table-row';
                } else {
                    el.style.display = 'none';
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                // Autocomplete untuk Tab Data Keseluruhan
                setupAutocomplete('searchInput', 'searchSuggestions', 'searchForm', 'data-keseluruhan');
                
                // Autocomplete untuk Tab Distribusi
                setupAutocomplete('searchInputDistribusi', 'searchSuggestionsDistribusi', 'searchFormDistribusi', 'distribusi');

                // Handle Modal Barang Masuk
                const modalBarangMasuk = document.getElementById("modalBarangMasuk");
                if (modalBarangMasuk) {
                    modalBarangMasuk.addEventListener("show.bs.modal", function (event) {
                        const button = event.relatedTarget;
                        const barangKode = button.getAttribute("data-kode");
                        const barangNama = button.getAttribute("data-nama");
                        const totalStok = parseInt(button.getAttribute("data-total-stok") || "0", 10);

                        document.getElementById("barangMasukNama").textContent = barangNama;
                        document.getElementById("barangMasukKode").value = barangKode;
                        document.getElementById("stokSekarang").textContent = isFinite(totalStok) ? totalStok : 0;

                        // Reset form
                        document.getElementById('formBarangMasuk').reset();
                        document.getElementById("barangMasukKode").value = barangKode;
                    });
                }

                // Handle Modal Distribusi
                const modalDistribusi = document.getElementById("modalDistribusi");
                if (modalDistribusi) {
                    modalDistribusi.addEventListener("show.bs.modal", function (event) {
                        const button = event.relatedTarget;
                        const pbStokId = button.getAttribute("data-id");
                        const barangNama = button.getAttribute("data-nama");
                        const barangKode = button.getAttribute("data-kode");
                        const stok = parseInt(button.getAttribute("data-stok") || "0", 10);
                        const harga = button.getAttribute("data-harga");

                        document.getElementById("distribusiNama").textContent = barangNama;
                        document.getElementById("distribusiPbStokId").value = pbStokId;
                        document.getElementById("distribusiKode").value = barangKode;
                        document.getElementById("distribusiHarga").value = harga;
                        document.getElementById("stokTersedia").textContent = isFinite(stok) ? stok : 0;

                        // Set max untuk jumlah distribusi
                        const inputJumlah = document.getElementById('jumlahDistribusi');
                        if (inputJumlah) inputJumlah.max = isFinite(stok) ? stok : 0;

                        // Reset form
                        document.getElementById('formDistribusi').reset();
                        document.getElementById("distribusiPbStokId").value = pbStokId;
                        document.getElementById("distribusiKode").value = barangKode;
                        document.getElementById("distribusiHarga").value = harga;
                    });
                }

                // Form submit handlers
                const formBarangMasuk = document.getElementById('formBarangMasuk');
                if (formBarangMasuk) {
                    formBarangMasuk.addEventListener('submit', function (e) {
                        e.preventDefault();
                        
                        // Validasi bagian_id harus dipilih
                        const bagianId = document.getElementById('bagianTujuan').value;
                        if (!bagianId) {
                            alert('Silakan pilih bagian tujuan terlebih dahulu!');
                            return false;
                        }
                        
                        // Validasi harga harus diisi
                        const harga = this.querySelector('input[name="harga"]').value;
                        if (!harga || parseFloat(harga) <= 0) {
                            alert('Silakan masukkan harga yang valid!');
                            return false;
                        }
                        
                        const kodeBarang = document.getElementById('barangMasukKode').value;
                        this.action = `/pb/barang-masuk/${kodeBarang}`;
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

            // Setup Autocomplete Function
            function setupAutocomplete(inputId, suggestionsId, formId, tabName) {
                const searchInput = document.getElementById(inputId);
                const suggestionsContainer = document.getElementById(suggestionsId);
                
                if (!searchInput || !suggestionsContainer) return;

                let currentSuggestions = [];
                let activeSuggestionIndex = -1;
                let searchTimeout;

                function fetchSuggestions(query) {
                    if (query.length < 2) {
                        hideSuggestions();
                        return;
                    }

                    showLoading();
                    clearTimeout(searchTimeout);
                    
                    searchTimeout = setTimeout(() => {
                        fetch(`/pb/api/search-barang?q=${encodeURIComponent(query)}&tab=${tabName}`)
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
                    suggestionsContainer.innerHTML = '<div class="loading-suggestion" style="padding:12px;text-align:center;color:#6b7280;">Mencari...</div>';
                    suggestionsContainer.style.display = 'block';
                }

                function displaySuggestions(suggestions) {
                    if (suggestions.length === 0) {
                        suggestionsContainer.innerHTML = '<div class="loading-suggestion" style="padding:12px;text-align:center;color:#6b7280;">Tidak ada barang ditemukan</div>';
                        return;
                    }

                    let html = '';
                    suggestions.forEach((item, index) => {
                        const stockStatusClass = `stock-${item.stock_status}`;
                        const stockText = item.stock_status === 'empty' ? 'Habis' :
                            item.stock_status === 'low' ? 'Sedikit' : 'Tersedia';

                        html += `
<<<<<<< HEAD
                            <div class="search-suggestion-item" data-index="${index}">
                                <div class="suggestion-name">${item.nama}</div>
                                <div class="suggestion-code">Kode: ${item.kode} | ${tabName === 'data-keseluruhan' ? 'Kategori: ' + item.kategori : 'Bagian: ' + item.bagian}</div>
                                <div class="suggestion-meta">
                                    <small>Stok: ${item.stok} | ${item.harga} | 
                                    <span class="stock-status ${stockStatusClass}">${stockText}</span></small>
                                </div>
                            </div>
                        `;
=======
                                    <div class="search-suggestion-item" data-index="${index}">
                                        <div class="suggestion-name">${item.nama} ${matchBadge}</div>
                                        <div class="suggestion-code">ID: ${item.id} | Kode: ${item.kode} | Bagian: <strong>${item.bagian}</strong></div>
                                        <div class="suggestion-meta">
                                            <small>Kategori: ${item.kategori} | Stok: ${item.stok} | ${item.harga} | 
                                            <span class="stock-status ${stockStatusClass}">${stockText}</span></small>
                                        </div>
                                    </div>
                                `;
>>>>>>> 7ed13d992a1891b817f6cd74826fb62e56cb4387
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
                        const form = document.getElementById(formId);
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
            }
        </script>
    @endpush
</x-layouts.app>