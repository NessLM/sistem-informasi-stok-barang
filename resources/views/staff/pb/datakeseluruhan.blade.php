<x-layouts.app title="Data Keseluruhan" :menu="$menu">
    @php
        // Pastikan selalu collection, biar nggak kejadian "foreach string given"
        $barangData      = $barangData ?? collect();      // hasil search PbStok (Gudang Utama)
        $stokBagianData  = $stokBagianData ?? collect();  // hasil search StokBagian
        $barangMasukData = $barangMasukData ?? collect(); // hasil search Kelola Barang Masuk

        $kategori = (isset($kategori) && $kategori instanceof \Illuminate\Support\Collection)
            ? $kategori
            : collect();

        $bagian = (isset($bagian) && $bagian instanceof \Illuminate\Support\Collection)
            ? $bagian
            : collect();

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
        @if (session('toast'))
            <div id="toast-notif"
                style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
                       z-index: 9999; display: flex; justify-content: center; pointer-events: none;">
                <div class="toast-message"
                    style="background: #fff; border-radius: 12px; padding: 14px 22px;
                           box-shadow: 0 8px 24px rgba(0,0,0,0.25); text-align: center;
                           min-width: 280px; max-width: 360px; transition: opacity .5s ease;
                           pointer-events: auto;">
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
            <!-- Tab Navigation - CENTERED -->
            <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'data-keseluruhan' ? 'active' : '' }}"
                        href="{{ route('pb.datakeseluruhan.index', ['tab' => 'data-keseluruhan']) }}">
                        <i class="bi bi-grid-3x3-gap"></i> Data Keseluruhan dan Distribusi Barang
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'distribusi' ? 'active' : '' }}"
                        href="{{ route('pb.datakeseluruhan.index', ['tab' => 'distribusi']) }}">
                        <i class="bi bi-box-arrow-right"></i> Kelola Barang Masuk
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
                        <button class="btn btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#modalFilterBarang">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>

                    {{-- Search Form dengan Autocomplete --}}
                    <div class="position-relative mb-3">
                        <form action="{{ route('pb.datakeseluruhan.index') }}" method="GET" class="input-group"
                            id="searchForm">
                            <input type="hidden" name="tab" value="data-keseluruhan">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="searchInput" class="form-control"
                                placeholder="Cari barang (nama, kode, kategori)" value="{{ request('search') }}"
                                autocomplete="off">
                            <button class="btn btn-outline-secondary" type="submit">Cari</button>
                        </form>
                        <div id="searchSuggestions" class="dropdown-menu w-100 position-absolute"
                            style="z-index: 1050; max-height: 300px; overflow-y: auto; display: none;">
                        </div>
                    </div>

                    {{-- Jika ada filter/search --}}
                    @if ($barangData->count() > 0 || $stokBagianData->count() > 0)

                    {{-- HASIL PENCARIAN: DATA BARANG GUDANG UTAMA (PbStok) --}}
                    @if ($barangData->count() > 0)
                        <h6 class="mt-3">Hasil Pencarian - Data Barang Gudang Utama</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered mt-2">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>No</th>
                                        <th>Kode Barang</th>
                                        <th>Nama Barang</th>
                                        <th>Kategori</th>
                                        <th>Satuan</th>
                                        <th>Bagian</th>
                                        <th>Stok (PB)</th>
                                        <th>Harga</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($barangData as $i => $item)
                                        @php
                                            $namaBarang = $item->barang->nama_barang ?? '-';
                                            $kategoriNm = $item->barang->kategori->nama ?? '-';
                                            $satuan     = $item->barang->satuan ?? '-';
                                            $bagianNm   = $item->bagian->nama ?? '-';
                                            $stok       = $item->stok ?? 0;
                                            $harga      = $item->harga ?? 0;
                                        @endphp
                                        <tr @if ($stok < 10) class="row-low-stock" @endif>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $item->kode_barang }}</td>
                                            <td>{{ $namaBarang }}</td>
                                            <td>{{ $kategoriNm }}</td>
                                            <td>{{ $satuan }}</td>
                                            <td>{{ $bagianNm }}</td>
                                            <td>{{ $stok }}</td>
                                            <td>Rp {{ number_format($harga, 0, ',', '.') }}</td>
                                            <td>
                                                {{-- Tombol Edit --}}
                                                <button type="button" class="btn btn-info btn-sm"
                                                        data-bs-toggle="modal" data-bs-target="#modalEditPbStok"
                                                        data-id="{{ $item->id }}"
                                                        data-kode="{{ $item->kode_barang }}"
                                                        data-nama="{{ $namaBarang }}"
                                                        data-harga="{{ $harga }}"
                                                        data-bagian-id="{{ $item->bagian_id ?? '' }}"
                                                        data-bagian-nama="{{ $bagianNm }}">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </button>
                                                {{-- Tombol Distribusi --}}
                                                <button type="button" class="btn btn-warning btn-sm"
                                                        data-bs-toggle="modal" data-bs-target="#modalDistribusi"
                                                        data-id="{{ $item->id }}"
                                                        data-kode="{{ $item->kode_barang }}"
                                                        data-nama="{{ $namaBarang }}"
                                                        data-stok="{{ $stok }}"
                                                        data-harga="{{ $harga }}"
                                                        data-bagian-id="{{ $item->bagian_id ?? '' }}"
                                                        data-bagian-nama="{{ $bagianNm }}">
                                                    <i class="bi bi-send"></i> Distribusi
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- HASIL PENCARIAN: STOK PER BAGIAN (StokBagian) --}}
                    @if ($stokBagianData->count() > 0)
                        <h6 class="mt-4"><strong>Hasil Pencarian - Stok Per Bagian</strong></h6>
                        <div class="table-responsive">
                            <table class="table table-bordered mt-2">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>No</th>
                                        <th>Bagian</th>
                                        <th>Kode Barang</th>
                                        <th>Nama Barang</th>
                                        <th>Kategori</th>
                                        <th>Satuan</th>
                                        <th>Stok</th>
                                        <th>Harga</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($stokBagianData as $i => $sb)
                                        @php
                                            $namaBarang = $sb->barang->nama_barang ?? '-';
                                            $kategoriNm = $sb->barang->kategori->nama ?? '-';
                                            $satuan     = $sb->barang->satuan ?? '-';
                                            $bagianNm   = $sb->bagian->nama ?? '-';
                                            $stok       = $sb->stok ?? 0;
                                            $harga      = $sb->harga ?? 0;
                                        @endphp
                                        <tr @if ($stok < 10) class="row-low-stock" @endif>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $bagianNm }}</td>
                                            <td>{{ $sb->kode_barang }}</td>
                                            <td>{{ $namaBarang }}</td>
                                            <td>{{ $kategoriNm }}</td>
                                            <td>{{ $satuan }}</td>
                                            <td>{{ $stok }}</td>
                                            <td>Rp {{ number_format($harga, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @elseif(
                    request()->filled('search') ||
                    request()->filled('kategori_id') ||
                    request()->filled('satuan') ||
                    request()->filled('stok_min') ||
                    request()->filled('stok_max') ||
                    request()->filled('harga_min') ||
                    request()->filled('harga_max')
                    )
                    <div class="alert alert-warning">
                        Tidak ada data ditemukan untuk kriteria pencarian Anda
                    </div>
                    @else
                    {{-- Tampilan Default - Data Barang Gudang Utama per Kategori --}}
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
                                                <button class="btn btn-sm btn-success"
                                                    onclick="toggleDetail('barang', {{ $k->id }})">
                                                    <i class="bi bi-eye"></i> Lihat ({{ $stokInKategori->count() }})
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
                                                                <th>Kode Barang</th>
                                                                <th>Nama Barang</th>
                                                                <th>Bagian</th>
                                                                <th>Stok</th>
                                                                <th>Harga</th>
                                                                <th>Aksi</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($stokInKategori as $idx => $item)
                                                                <tr @if ($item->stok < 10) class="row-low-stock" @endif>
                                                                    <td>{{ $idx + 1 }}</td>
                                                                    <td>{{ $item->kode_barang }}</td>
                                                                    <td>{{ $item->barang->nama_barang ?? '-' }}</td>
                                                                    <td>{{ $item->bagian->nama ?? '-' }}</td>
                                                                    <td>{{ $item->stok }}</td>
                                                                    <td>Rp {{ number_format($item->harga ?? 0, 0, ',', '.') }}</td>
                                                                    <td>
                                                                        {{-- Tombol Edit --}}
                                                                        <button type="button" class="btn btn-info btn-sm"
                                                                            data-bs-toggle="modal" data-bs-target="#modalEditPbStok"
                                                                            data-id="{{ $item->id }}"
                                                                            data-kode="{{ $item->kode_barang }}"
                                                                            data-nama="{{ $item->barang->nama_barang ?? '-' }}"
                                                                            data-harga="{{ $item->harga ?? 0 }}"
                                                                            data-bagian-id="{{ $item->bagian_id ?? '' }}"
                                                                            data-bagian-nama="{{ $item->bagian->nama ?? 'Tidak ada bagian' }}">
                                                                            <i class="bi bi-pencil-square"></i> Edit
                                                                        </button>
                                                                        {{-- Tombol Distribusi --}}
                                                                        <button type="button" class="btn btn-warning btn-sm"
                                                                            data-bs-toggle="modal" data-bs-target="#modalDistribusi"
                                                                            data-id="{{ $item->id }}"
                                                                            data-kode="{{ $item->kode_barang }}"
                                                                            data-nama="{{ $item->barang->nama_barang ?? '-' }}"
                                                                            data-stok="{{ $item->stok }}"
                                                                            data-harga="{{ $item->harga ?? 0 }}"
                                                                            data-bagian-id="{{ $item->bagian_id ?? '' }}"
                                                                            data-bagian-nama="{{ $item->bagian->nama ?? 'Tidak ada bagian' }}">
                                                                            <i class="bi bi-send"></i> Distribusi
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
                    <h6 class="mt-4"> <strong> Stok Per Bagian - View Only</strong></h6>
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
                                            ->where('stok_bagian.bagian_id', $bg->id)
                                            ->orderBy('kode_barang')
                                            ->orderBy('batch_number')
                                            ->get();
                                    @endphp
                                    @if($stokDiBagian->count() > 0)
                                        <tr class="table-secondary">
                                            <td>{{ $bg->nama }}</td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-primary"
                                                    onclick="toggleDetail('bagian', {{ $bg->id }})">
                                                    <i class="bi bi-chevron-down"></i> Expand ({{ $stokDiBagian->count() }})
                                                </button>
                                            </td>
                                        </tr>
                                        <tr id="detail-bagian-{{ $bg->id }}" style="display:none;">
                                            <td colspan="2">
                                                @php
                                                    $grouped = $stokDiBagian->groupBy(function ($item) {
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
                                                                        <th style="width:200px">Batch Number</th>
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
                                                                            <td>
                                                                                @if($sb->batch_number)
                                                                                    <code class="text-primary" style="font-size: 11px;">
                                                                                        {{ $sb->batch_number }}
                                                                                    </code>
                                                                                @else
                                                                                    Legacy Data
                                                                                @endif
                                                                            </td>
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

                <!-- TAB 2: KELOLA BARANG MASUK -->
                <div class="tab-pane fade {{ $activeTab === 'distribusi' ? 'show active' : '' }}" id="distribusi"
                    role="tabpanel">

                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <h5 class="mb-0">Kelola Barang Masuk</h5>
                    </div>

                    {{-- Search untuk Kelola Barang Masuk --}}
                    <div class="position-relative mb-3">
                        <form action="{{ route('pb.datakeseluruhan.index') }}" method="GET" class="input-group"
                            id="searchFormDistribusi">
                            <input type="hidden" name="tab" value="distribusi">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="searchInputDistribusi" class="form-control"
                                placeholder="Cari barang untuk dicatat masuk" value="{{ request('search') }}"
                                autocomplete="off">
                            <button class="btn btn-outline-secondary" type="submit">Cari</button>
                        </form>
                        <div id="searchSuggestionsDistribusi" class="dropdown-menu w-100 position-absolute"
                            style="z-index: 1050; max-height: 300px; overflow-y: auto; display: none;">
                        </div>
                    </div>

                    @if ($activeTab === 'distribusi' && $barangMasukData->count() > 0)
                    <h6 class="mt-3">Hasil Pencarian Barang</h6>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>KATEGORI</th>
                                    <th style="width:180px" class="text-center">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $groupedByKategori = $barangMasukData->groupBy('id_kategori');
                                @endphp

                                @foreach ($groupedByKategori as $kategoriId => $items)
                                    @php
                                        $kategoriNama = optional($items->first()->kategori)->nama ?? 'Tanpa Kategori';
                                    @endphp

                                    <tr>
                                        <td>{{ $kategoriNama }}</td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-primary"
                                                    onclick="toggleKelolaBarangMasuk({{ $kategoriId }})">
                                                <i class="bi bi-chevron-up"></i> Lihat ({{ $items->count() }})
                                            </button>
                                        </td>
                                    </tr>

                                    <tr id="kelola-masuk-{{ $kategoriId }}" style="display: table-row;">
                                        <td colspan="2" class="p-0">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-sm mb-0">
                                                    <thead class="table-secondary">
                                                        <tr>
                                                            <th style="width: 50px;">No</th>
                                                            <th>Nama Barang</th>
                                                            <th>Kode Barang</th>
                                                            <th>Satuan</th>
                                                            <th style="width: 200px;" class="text-center">Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($items as $idx => $item)
                                                            <tr>
                                                                <td>{{ $idx + 1 }}</td>
                                                                <td>{{ $item->nama_barang }}</td>
                                                                <td>{{ $item->kode_barang }}</td>
                                                                <td>{{ $item->satuan }}</td>
                                                                <td class="text-center">
                                                                    <button type="button"
                                                                            class="btn btn-success btn-sm"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#modalBarangMasuk"
                                                                            data-kode="{{ $item->kode_barang }}"
                                                                            data-nama="{{ $item->nama_barang }}"
                                                                            data-satuan="{{ $item->satuan }}">
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
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @elseif($activeTab === 'distribusi' && request()->filled('search'))
                    <div class="alert alert-warning mt-3">
                        Tidak ada data ditemukan untuk kata kunci "<strong>{{ request('search') }}</strong>".
                    </div>

                    @else
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
                                            $barangKategori = \App\Models\Barang::with(['pbStok.bagian', 'kategori'])
                                                ->where('id_kategori', $k->id)
                                                ->get();
                                        @endphp
                                        @if($barangKategori->count() > 0)
                                            <tr>
                                                <td>{{ $k->nama }}</td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-primary"
                                                            onclick="toggleKelolaBarangMasuk({{ $k->id }})">
                                                        <i class="bi bi-chevron-down"></i> Lihat
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr id="kelola-masuk-{{ $k->id }}" style="display:none;">
                                                <td colspan="2" class="p-0">
                                                    <div class="table-responsive">
                                                        <table class="table table-striped table-sm mb-0">
                                                            <thead class="table-secondary">
                                                                <tr>
                                                                    <th style="width: 50px;">No</th>
                                                                    <th>Nama Barang</th>
                                                                    <th>Kode Barang</th>
                                                                    <th>Satuan</th>
                                                                    <th style="width: 200px;" class="text-center">Aksi</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($barangKategori as $idx => $item)
                                                                    <tr>
                                                                        <td>{{ $idx + 1 }}</td>
                                                                        <td>{{ $item->nama_barang }}</td>
                                                                        <td>{{ $item->kode_barang }}</td>
                                                                        <td>{{ $item->satuan }}</td>
                                                                        <td class="text-center">
                                                                            <button type="button"
                                                                                    class="btn btn-success btn-sm"
                                                                                    data-bs-toggle="modal"
                                                                                    data-bs-target="#modalBarangMasuk"
                                                                                    data-kode="{{ $item->kode_barang }}"
                                                                                    data-nama="{{ $item->nama_barang }}"
                                                                                    data-satuan="{{ $item->satuan }}">
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
                    @endif

                </div>
            </div>
        </section>
    </main>

    <!-- Modal Edit PB Stok -->
    <div class="modal fade" id="modalEditPbStok" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i> Edit Data Barang
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEditPbStok">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="pb_stok_id" id="editPbStokId">
                    
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Edit harga dan bagian untuk barang di gudang utama.
                        </div>

                        <div class="row g-3">
                            <!-- Nama Barang (Read-only) -->
                            <div class="col-12">
                                <label class="form-label fw-bold">Nama Barang</label>
                                <input type="text" id="editNamaBarang" class="form-control bg-light" readonly>
                            </div>

                            <!-- Kode Barang (Read-only) -->
                            <div class="col-12">
                                <label class="form-label fw-bold">Kode Barang</label>
                                <input type="text" id="editKodeBarang" class="form-control bg-light" readonly>
                            </div>

                            <!-- Harga Barang (Editable) -->
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    Harga Satuan <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="harga" id="editHarga" class="form-control"
                                        placeholder="0" required min="0" step="0.01">
                                </div>
                                <small class="text-muted">Masukkan harga baru per satuan barang</small>
                            </div>

                            <!-- Bagian (Editable) -->
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    Bagian <span class="text-danger">*</span>
                                </label>
                                <select name="bagian_id" id="editBagian" class="form-select" required>
                                    <option value="">-- Pilih Bagian --</option>
                                    @foreach($bagian as $bg)
                                        <option value="{{ $bg->id }}">{{ $bg->nama }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Pilih bagian baru untuk barang ini</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-info text-white">
                            <i class="bi bi-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Barang Masuk -->
    <div class="modal fade" id="modalBarangMasuk" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        Form Barang Masuk
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formBarangMasuk" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="kode_barang" id="barangMasukKode">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Input barang masuk akan menambah stok pada gudang utama.
                        </div>

                        <div class="row g-3">
                            <!-- Nama Barang (Read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Nama Barang
                                </label>
                                <input type="text" id="barangMasukNama" class="form-control" readonly>
                            </div>

                            <div class="col-md-6">
                                <!-- Kode Barang (Read-only) -->
                                <label class="form-label fw-bold">
                                    Kode Barang
                                </label>
                                <input type="text" id="barangMasukKodeDisplay" class="form-control" readonly>
                            </div>

                            <div class="col-md-6">
                                <!-- Harga Barang -->
                                <label class="form-label fw-bold">
                                    Harga Satuan <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="harga" id="hargaBarangMasuk" class="form-control"
                                        placeholder="0" required min="0" step="0.01">
                                </div>
                                <small class="text-muted">Masukkan harga per satuan barang</small>
                            </div>

                            <!-- Bagian Tujuan -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Bagian <span class="text-danger">*</span>
                                </label>
                                <select name="bagian_id" id="bagianTujuanMasuk" class="form-select" required>
                                    <option value="">-- Pilih Bagian --</option>
                                    @foreach($bagian as $bg)
                                        <option value="{{ $bg->id }}">{{ $bg->nama }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Pilih bagian yang akan menerima barang</small>
                            </div>

                            <!-- Jumlah Masuk -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Stok Masuk <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" name="jumlah" class="form-control" placeholder="0" required
                                        min="1">
                                    <span class="input-group-text" id="satuanDisplay">Unit</span>
                                </div>
                                <small class="text-muted">Jumlah barang yang masuk</small>
                            </div>

                            <!-- Tanggal Masuk -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Tanggal Masuk
                                </label>
                                <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}">
                                <small class="text-muted">Kosongkan untuk menggunakan tanggal hari ini</small>
                            </div>

                            <!-- Keterangan -->
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    Keterangan
                                </label>
                                <textarea name="keterangan" class="form-control" rows="2"
                                    placeholder="Contoh: Pembelian dari supplier PT. XYZ"></textarea>
                            </div>

                            <!-- Bukti -->
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    Bukti Barang Masuk
                                </label>
                                <input type="file" name="bukti" class="form-control" accept="image/*,.pdf">
                                <small class="text-muted">
                                    Format: JPG, PNG, PDF (Maks. 2MB) - Nota pembelian, bukti penerimaan, dll
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-success">
                            Simpan Barang Masuk
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
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-send"></i> Form Distribusi Barang
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formDistribusi" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="pb_stok_id" id="distribusiPbStokId">
                    <input type="hidden" name="kode_barang" id="distribusiKode">
                    <input type="hidden" name="harga" id="distribusiHarga">
                    <input type="hidden" name="bagian_id" id="distribusiBagianId">

                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Distribusi akan mengurangi stok dari gudang utama (PB) dan menambahkan ke bagian tujuan.
                        </div>

                        <!-- Nama Barang (Read-only) -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Nama Barang
                                </label>
                                <input type="text" id="distribusiNama" class="form-control bg-light" readonly>
                            </div>

                            <!-- Kode Barang (Read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Kode Barang
                                </label>
                                <input type="text" id="distribusiKodeDisplay" class="form-control bg-light" readonly>
                            </div>

                            <!-- Bagian Tujuan (Auto Fill - Read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Bagian Tujuan
                                </label>
                                <input type="text" id="distribusiBagianNama" class="form-control bg-light" readonly>
                                <small class="text-muted">Barang akan didistribusikan ke bagian ini</small>
                            </div>

                            <!-- Harga (Auto Fill - Read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Harga Satuan
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" id="distribusiHargaDisplay" class="form-control bg-light"
                                        readonly>
                                </div>
                                <small class="text-muted">Harga per satuan barang</small>
                            </div>

                            <!-- Stok Tersedia (Read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Stok Tersedia di Gudang Utama
                                </label>
                                <input type="text" id="stokTersedia" class="form-control bg-light" readonly>
                                <small class="text-muted">Stok yang tersedia untuk didistribusikan</small>
                            </div>

                            <!-- Jumlah Distribusi -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    Jumlah Distribusi <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="jumlah" id="jumlahDistribusi" class="form-control"
                                    placeholder="Masukkan jumlah" required min="1">
                                <small class="text-muted">Jumlah barang yang akan didistribusikan</small>
                            </div>

                            <!-- Tanggal Distribusi -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">
                                    Tanggal Distribusi
                                </label>
                                <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}">
                                <small class="text-muted">Kosongkan untuk menggunakan tanggal hari ini</small>
                            </div>

                            <!-- Keterangan -->
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    Keterangan
                                </label>
                                <textarea name="keterangan" class="form-control" rows="3"
                                    placeholder="Contoh: Distribusi untuk kebutuhan operasional bagian"></textarea>
                            </div>

                            <!-- Bukti Distribusi -->
                            <div class="col-12">
                                <label class="form-label fw-bold">
                                    Bukti Distribusi
                                </label>
                                <input type="file" name="bukti" class="form-control" accept="image/*,.pdf">
                                <small class="text-muted">
                                    Format: JPG, PNG, PDF (Maks. 2MB) - Form permintaan, surat jalan, dll
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-warning text-white" style="font-size: 14px;">
                            Proses Distribusi
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
                                <option value="Pcs" @if (request('satuan') == 'Pcs') selected @endif>
                                    Pcs</option>
                                <option value="Unit" @if (request('satuan') == 'Unit') selected @endif>Unit</option>
                                <option value="Box" @if (request('satuan') == 'Box') selected @endif>
                                    Box</option>
                                <option value="Kg" @if (request('satuan') == 'Kg') selected @endif>Kg
                                </option>
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
                    <a href="{{ route('pb.datakeseluruhan.index', ['tab' => 'data-keseluruhan']) }}"
                        class="btn btn-secondary">Reset Filter</a>
                    <button class="btn btn-primary" type="submit">Terapkan Filter</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            // Toggle untuk kelola barang masuk per kategori
            function toggleKelolaBarangMasuk(id) {
                const el = document.getElementById('kelola-masuk-' + id);
                const btn = event.currentTarget;
                const icon = btn.querySelector('i');

                if (el.style.display === 'none' || el.style.display === '') {
                    el.style.display = 'table-row';
                    icon.classList.remove('bi-chevron-down');
                    icon.classList.add('bi-chevron-up');
                } else {
                    el.style.display = 'none';
                    icon.classList.remove('bi-chevron-up');
                    icon.classList.add('bi-chevron-down');
                }
            }

            // Toggle detail kategori / bagian
            function toggleDetail(type, id) {
                const el = document.getElementById('detail-' + type + '-' + id);
                if (!el) return;
                el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'table-row' : 'none';
            }

            function toggleDistribusi(id) {
                const el = document.getElementById('distribusi-' + id);
                if (!el) return;
                el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'table-row' : 'none';
            }

            // Helper function to disable the submit button and show loading text
            function disableSubmitButton(form) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...`;
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                const today = "{{ date('Y-m-d') }}";

                // Autocomplete
                setupAutocomplete('searchInput', 'searchSuggestions', 'searchForm', 'data-keseluruhan');
                setupAutocomplete('searchInputDistribusi', 'searchSuggestionsDistribusi', 'searchFormDistribusi', 'distribusi');

                /**
                 * =======================
                 * MODAL EDIT PB STOK
                 * =======================
                 */
                const modalEditPbStok = document.getElementById('modalEditPbStok');
                const formEditPbStok = document.getElementById('formEditPbStok');

                if (modalEditPbStok && formEditPbStok) {
                    modalEditPbStok.addEventListener('show.bs.modal', function (event) {
                        const button = event.relatedTarget;
                        
                        const pbStokId = button.getAttribute('data-id');
                        const barangKode = button.getAttribute('data-kode');
                        const barangNama = button.getAttribute('data-nama');
                        const harga = button.getAttribute('data-harga');
                        const bagianId = button.getAttribute('data-bagian-id');

                        // Reset tombol submit
                        const submitBtn = formEditPbStok.querySelector('button[type="submit"]');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = `<i class="bi bi-save"></i> Simpan Perubahan`;
                        }

                        // Set nilai ke form
                        document.getElementById('editPbStokId').value = pbStokId;
                        document.getElementById('editNamaBarang').value = barangNama;
                        document.getElementById('editKodeBarang').value = barangKode;
                        document.getElementById('editHarga').value = harga;
                        document.getElementById('editBagian').value = bagianId;
                    });

                    formEditPbStok.addEventListener('submit', function (e) {
                        e.preventDefault();

                        const harga = document.getElementById('editHarga').value;
                        if (!harga || parseFloat(harga) <= 0) {
                            alert('Silakan masukkan harga yang valid!');
                            return;
                        }

                        const bagianId = document.getElementById('editBagian').value;
                        if (!bagianId) {
                            alert('Silakan pilih bagian!');
                            return;
                        }

                        disableSubmitButton(this);

                        const pbStokId = document.getElementById('editPbStokId').value;
                        this.action = `/pb/edit-stok/${pbStokId}`;
                        this.submit();
                    });
                }

                /**
                 * =======================
                 * MODAL BARANG MASUK
                 * =======================
                 */
                const modalBarangMasuk = document.getElementById('modalBarangMasuk');
                const formBarangMasuk = document.getElementById('formBarangMasuk');

                if (modalBarangMasuk && formBarangMasuk) {
                    modalBarangMasuk.addEventListener('show.bs.modal', function (event) {
                        const button = event.relatedTarget;
                        const barangKode = button.getAttribute('data-kode');
                        const barangNama = button.getAttribute('data-nama');
                        const barangSatuan = button.getAttribute('data-satuan') || 'Unit';

                        const submitBtn = formBarangMasuk.querySelector('button[type="submit"]');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = `Simpan Barang Masuk`;
                        }

                        formBarangMasuk.reset();

                        document.getElementById('barangMasukNama').value = barangNama;
                        document.getElementById('barangMasukKode').value = barangKode;
                        document.getElementById('barangMasukKodeDisplay').value = barangKode;
                        document.getElementById('satuanDisplay').textContent = barangSatuan;

                        const tgl = formBarangMasuk.querySelector('input[name="tanggal"]');
                        if (tgl) tgl.value = today;
                    });

                    formBarangMasuk.addEventListener('submit', function (e) {
                        e.preventDefault();

                        const bagianId = document.getElementById('bagianTujuanMasuk').value;
                        if (!bagianId) {
                            alert('Silakan pilih bagian tujuan terlebih dahulu!');
                            return;
                        }

                        const harga = document.getElementById('hargaBarangMasuk').value;
                        if (!harga || parseFloat(harga) <= 0) {
                            alert('Silakan masukkan harga yang valid!');
                            return;
                        }

                        const jumlah = this.querySelector('input[name="jumlah"]').value;
                        if (!jumlah || parseInt(jumlah) <= 0) {
                            alert('Silakan masukkan jumlah stok yang valid!');
                            return;
                        }

                        disableSubmitButton(this);

                        const kodeBarang = document.getElementById('barangMasukKode').value;
                        this.action = `/pb/barang-masuk/${kodeBarang}`;
                        this.submit();
                    });
                }

                /**
                 * =======================
                 * MODAL DISTRIBUSI BARANG
                 * =======================
                 */
                const modalDistribusi = document.getElementById('modalDistribusi');
                const formDistribusi = document.getElementById('formDistribusi');

                if (modalDistribusi && formDistribusi) {
                    modalDistribusi.addEventListener('show.bs.modal', function (event) {
                        const button = event.relatedTarget;

                        const submitBtn = formDistribusi.querySelector('button[type="submit"]');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = `Proses Distribusi`;
                        }

                        formDistribusi.reset();

                        const pbStokId = button.getAttribute('data-id') || '';
                        const barangKode = button.getAttribute('data-kode') || '';
                        const barangNama = button.getAttribute('data-nama') || '';
                        const stok = parseInt(button.getAttribute('data-stok') || '0', 10);
                        const harga = parseFloat(button.getAttribute('data-harga') || '0');
                        const bagianId = button.getAttribute('data-bagian-id') || '';
                        const bagianNama = button.getAttribute('data-bagian-nama') || '';

                        document.getElementById('distribusiPbStokId').value = pbStokId;
                        document.getElementById('distribusiKode').value = barangKode;
                        document.getElementById('distribusiHarga').value = harga;
                        document.getElementById('distribusiBagianId').value = bagianId;

                        document.getElementById('distribusiNama').value = barangNama;
                        document.getElementById('distribusiKodeDisplay').value = barangKode;
                        document.getElementById('distribusiBagianNama').value = bagianNama;
                        document.getElementById('distribusiHargaDisplay').value =
                            new Intl.NumberFormat('id-ID').format(harga);
                        document.getElementById('stokTersedia').value =
                            (isFinite(stok) ? stok : 0) + ' Unit';

                        const inputJumlah = document.getElementById('jumlahDistribusi');
                        if (inputJumlah) {
                            inputJumlah.max = isFinite(stok) ? stok : 0;
                            inputJumlah.value = '';
                        }

                        const tgl = formDistribusi.querySelector('input[name="tanggal"]');
                        if (tgl) tgl.value = today;
                    });

                    formDistribusi.addEventListener('submit', function (e) {
                        e.preventDefault();

                        const jumlahInput = document.getElementById('jumlahDistribusi');
                        const jumlah = parseInt(jumlahInput.value || '0', 10);
                        const maxStok = parseInt(jumlahInput.max || '0', 10);

                        if (!jumlah || jumlah <= 0) {
                            alert('Jumlah distribusi harus lebih dari 0!');
                            return;
                        }

                        if (jumlah > maxStok) {
                            alert(`Jumlah distribusi tidak boleh melebihi stok tersedia (${maxStok})!`);
                            return;
                        }

                        disableSubmitButton(this);

                        const pbStokId = document.getElementById('distribusiPbStokId').value;
                        this.action = `/pb/distribusi/${pbStokId}`;
                        this.submit();
                    });
                }
            });

            // =======================
            // AUTOCOMPLETE
            // =======================
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
                            .catch(() => hideSuggestions());
                    }, 300);
                }

                function showLoading() {
                    suggestionsContainer.innerHTML =
                        '<div style="padding:12px;text-align:center;color:#6b7280;">Mencari...</div>';
                    suggestionsContainer.style.display = 'block';
                }

                function displaySuggestions(suggestions) {
                    if (!suggestions.length) {
                        suggestionsContainer.innerHTML =
                            '<div style="padding:12px;text-align:center;color:#6b7280;">Tidak ada barang ditemukan</div>';
                        return;
                    }

                    let html = '';
                    suggestions.forEach((item, index) => {
                        const stockStatusClass = `stock-${item.stock_status}`;
                        const stockText =
                            item.stock_status === 'empty' ? 'Habis' :
                                item.stock_status === 'low' ? 'Sedikit' : 'Tersedia';

                        html += `
                            <div class="search-suggestion-item" data-index="${index}">
                                <div class="suggestion-name">${item.nama}</div>
                                <div class="suggestion-code">
                                    Kode: ${item.kode} |
                                    ${tabName === 'data-keseluruhan'
                                ? 'Kategori: ' + item.kategori
                                : 'Bagian: ' + item.bagian}
                                </div>
                                <div class="suggestion-meta">
                                    <small>
                                        Stok: ${item.stok} | ${item.harga} |
                                        <span class="stock-status ${stockStatusClass}">${stockText}</span>
                                    </small>
                                </div>
                            </div>`;
                    });

                    suggestionsContainer.innerHTML = html;
                    suggestionsContainer.style.display = 'block';

                    suggestionsContainer.querySelectorAll('.search-suggestion-item').forEach(item => {
                        item.addEventListener('click', function () {
                            const idx = parseInt(this.dataset.index);
                            selectSuggestion(idx);
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
                        document.getElementById(formId).submit();
                    }
                }

                function updateActiveSuggestion(suggestions) {
                    suggestions.forEach((item, index) => {
                        item.classList.toggle('active', index === activeSuggestionIndex);
                    });
                }

                searchInput.addEventListener('input', function () {
                    fetchSuggestions(this.value.trim());
                });

                searchInput.addEventListener('keydown', function (e) {
                    const suggestions = suggestionsContainer.querySelectorAll('.search-suggestion-item');
                    if (!suggestions.length) return;

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        activeSuggestionIndex = Math.min(activeSuggestionIndex + 1, suggestions.length - 1);
                        updateActiveSuggestion(suggestions);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        activeSuggestionIndex = Math.max(activeSuggestionIndex - 1, 0);
                        updateActiveSuggestion(suggestions);
                    } else if (e.key === 'Enter' && activeSuggestionIndex >= 0) {
                        e.preventDefault();
                        selectSuggestion(activeSuggestionIndex);
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