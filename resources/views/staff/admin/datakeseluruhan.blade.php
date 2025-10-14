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
            .row-low-stock {
                background-color: #ffcccc !important;
                border-left: 4px solid #dc3545 !important;
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

                {{-- Judul (Hijau kalau success, Merah kalau error) --}}
                <div
                    style="font-weight: 600; font-size: 16px; margin-bottom: 4px;
                  color: {{ session('toast.type') === 'success' ? '#28a745' : '#dc3545' }};">
                    {{ session('toast.title') }}
                </div>

                {{-- Pesan kecil --}}
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
                    $title = 'Data Keseluruhan'; // default
                    
                    // Jika ada kategori dan semua kategori dari gudang yang sama
                    if($kategori->isNotEmpty()) {
                        $firstGudang = $kategori->first()->gudang->nama ?? null;
                        $allSameGudang = $kategori->every(function($k) use ($firstGudang) {
                            return ($k->gudang->nama ?? null) === $firstGudang;
                        });
                        
                        if($allSameGudang && $firstGudang) {
                            // Hindari duplikasi kata "Gudang"
                            if(str_starts_with($firstGudang, 'Gudang')) {
                                $title = 'Data ' . $firstGudang;
                            } else {
                                $title = 'Data Gudang ' . $firstGudang;
                            }
                        }
                    }
                    
                    // Override berdasarkan filter gudang jika ada
                    if(request()->filled('gudang_id') && isset($selectedGudang)) {
                        $gudangNama = $selectedGudang->nama;
                        if(str_starts_with($gudangNama, 'Gudang')) {
                            $title = 'Data ' . $gudangNama;
                        } else {
                            $title = 'Data Gudang ' . $gudangNama;
                        }
                    }
                    
                    // Override berdasarkan URL path
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
                    <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
                        <div class="btn-text">+ Tambah Kategori</div>
                    </button>
                    <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#modalTambahBarang">
                        <div class="btn-text">+ Tambah Barang</div>
                    </button>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFilterBarang">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </div>

            {{-- Search Form dengan Autocomplete --}}
            <div class="position-relative mb-3">
                <form action="{{ route('admin.datakeseluruhan.index') }}" method="GET" class="input-group" id="searchForm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" id="searchInput" class="form-control"
                        placeholder="Telusuri barang (nama atau kode)" value="{{ request('search') }}"
                        autocomplete="off">
                    <button class="btn btn-outline-secondary" type="submit">Cari</button>
                </form>

                {{-- Dropdown Suggestions --}}
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
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th>Satuan</th>
                                    <th>Kategori</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($barang as $i => $b)
                                    @php
                                        // Ambil stok berdasarkan gudang yang dipilih atau total
                                        $stokDisplay = 0;
                                        if (request()->filled('gudang_id') && isset($selectedGudang)) {
                                            $pjStok = $b->pjStok()->where('id_gudang', $selectedGudang->id)->first();
                                            $stokDisplay = $pjStok ? $pjStok->stok : 0;
                                        } elseif ($b->kategori && $b->kategori->gudang_id) {
                                            // Stok di gudang kategori
                                            $pjStok = $b->pjStok()->where('id_gudang', $b->kategori->gudang_id)->first();
                                            $stokDisplay = $pjStok ? $pjStok->stok : 0;
                                        } else {
                                            // Total stok dari semua gudang PJ
                                            $stokDisplay = $b->pjStok()->sum('stok');
                                        }
                                    @endphp
                                    <tr @if ($stokDisplay < 10) class="row-low-stock" @endif>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $b->nama_barang }}</td>
                                        <td>{{ $b->kode_barang }}</td>
                                        <td>Rp {{ number_format($b->harga_barang ?? 0, 0, ',', '.') }}</td>
                                        <td>{{ $stokDisplay }}</td>
                                        <td>{{ $b->satuan }}</td>
                                        <td>{{ $b->kategori->nama ?? '-' }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                data-bs-target="#modalEditBarang-{{ $b->kode_barang }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                             <button type="button" class="btn btn-sm btn-danger"
                                                onclick="confirmDelete('{{ route('admin.barang.destroy', $b->kode_barang) }}', 'Barang {{ $b->nama_barang }}')">
                                                <i class="bi bi-trash"></i>
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

            {{-- Jika tidak ada filter/search --}}
            @if (
                !request()->filled('search') &&
                    !request()->filled('kode') &&
                    !request()->filled('stok_min') &&
                    !request()->filled('stok_max') &&
                    !request()->filled('kategori_id') &&
                    !request()->filled('gudang_id') &&
                    !request()->filled('satuan') &&
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
                                            <button type="button" class="btn btn-sm btn-danger"
                                                onclick="confirmDelete('{{ route('admin.kategori.destroy', $k->id) }}', 'Kategori {{ $k->nama }}')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr id="detail-{{ $k->id }}" style="display:none;">
                                    <td colspan="3">
                                        @if ($k->barang->count())
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>No</th>
                                                            <th>Nama</th>
                                                            <th>Kode</th>
                                                            <th>Harga</th>
                                                            <th>Stok</th>
                                                            <th>Satuan</th>
                                                            <th>Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($k->barang as $i => $b)
                                                            @php
                                                                // Ambil stok dari gudang yang sedang dipilih
                                                                $stokDisplay = 0;
                                                                if (isset($selectedGudang)) {
                                                                    $pjStok = $b->pjStok()->where('id_gudang', $selectedGudang->id)->first();
                                                                    $stokDisplay = $pjStok ? $pjStok->stok : 0;
                                                                } else {
                                                                    // Jika tidak ada filter gudang, ambil stok dari gudang kategori
                                                                    if ($k->gudang_id) {
                                                                        $pjStok = $b->pjStok()->where('id_gudang', $k->gudang_id)->first();
                                                                        $stokDisplay = $pjStok ? $pjStok->stok : 0;
                                                                    }
                                                                }
                                                            @endphp
                                                            <tr @if ($stokDisplay < 10) class="row-low-stock" @endif>
                                                                <td>{{ $i + 1 }}</td>
                                                                <td>{{ $b->nama_barang }}</td>
                                                                <td>{{ $b->kode_barang }}</td>
                                                                <td>Rp {{ number_format($b->harga_barang ?? 0, 0, ',', '.') }}</td>
                                                                <td>{{ $stokDisplay }}</td>
                                                                <td>{{ $b->satuan }}</td>
                                                                <td class="d-flex gap-2">
                                                                    <button class="btn btn-sm btn-warning"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#modalEditBarang-{{ $b->kode_barang }}">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-danger"
                                                                        onclick="confirmDelete('{{ route('admin.barang.destroy', $b->kode_barang) }}', 'Barang {{ $b->nama_barang }}')">
                                                                        <i class="bi bi-trash"></i>
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

    <!-- Modal Tambah Kategori -->
    <div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.kategori.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" name="nama" id="nama" required>
                        </div>
                        <div class="mb-3">
                            <label for="gudang_id" class="form-label">Pilih Gudang</label>
                            <select name="gudang_id" id="gudang_id" class="form-select" required>
                                <option value="">-- Pilih Gudang --</option>
                                @foreach ($gudang as $item)
                                    <option value="{{ $item->id }}">{{ $item->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div> 

    @php
    // Kita cek: ini konteks "satu gudang" atau "multi gudang"?
    $isSingleGudang = false;

    if ($kategori->isNotEmpty()) {
        $firstGudang = optional($kategori->first()->gudang)->nama;
        $isSingleGudang = $firstGudang && $kategori->every(fn($k) => optional($k->gudang)->nama === $firstGudang);
    }

    // kalau ada filter gudang/halaman khusus gudang → paksa single
    if (isset($selectedGudang) && $selectedGudang) {
        $isSingleGudang = true;
    }
    $path = request()->path();
    if (str_contains($path, '/atk') || str_contains($path, '/listrik')
        || str_contains($path, '/kebersihan') || str_contains($path, '/komputer')) {
        $isSingleGudang = true;
    }
    @endphp

    {{-- Modal Tambah Barang --}}
    <div class="modal fade" id="modalTambahBarang" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form action="{{ route('admin.barang.store') }}" method="POST" class="modal-content" id="formTambahBarang">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Nama Barang</label>
                            <input type="text" name="nama_barang"
                                class="form-control @error('nama_barang') is-invalid @enderror" value="{{ old('nama_barang') }}"
                                required>
                            @error('nama_barang')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label>Kode Barang</label>
                            <input type="text" name="kode_barang"
                                class="form-control @error('kode_barang') is-invalid @enderror" value="{{ old('kode_barang') }}"
                                required>
                            @error('kode_barang')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label>Kategori</label>
                            <select name="id_kategori" class="form-select @error('id_kategori') is-invalid @enderror" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($kategori as $k)
                                    @if($k->gudang && str_contains(strtolower($k->gudang->nama), 'utama'))
                                        <option value="{{ $k->id }}" @selected(old('id_kategori') == $k->id)>
                                            {{ $k->nama }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('id_kategori')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label>Harga / Satuan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="harga_display" id="hargaTambah"
                                    class="form-control @error('harga_barang') is-invalid @enderror"
                                    value="{{ old('harga_barang') }}" placeholder="Harga">
                                <input type="hidden" name="harga_barang" id="hargaTambahHidden">
                                <select name="satuan" class="form-select">
                                    <option value="Pcs" @if (old('satuan') == 'Pcs') selected @endif>Pcs</option>
                                    <option value="Box" @if (old('satuan') == 'Box') selected @endif>Box</option>
                                    <option value="Pack" @if (old('satuan') == 'Pack') selected @endif>Pack</option>
                                    <option value="Rim" @if (old('satuan') == 'Rim') selected @endif>Rim</option>
                                    <option value="Unit" @if (old('satuan') == 'Unit') selected @endif>Unit</option>
                                </select>
                            </div>
                            @error('harga_barang')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Barang --}}
    @foreach ($kategori as $k)
        @foreach ($k->barang as $b)
            <div class="modal fade" id="modalEditBarang-{{ $b->kode_barang }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <form action="{{ route('admin.barang.update', $b->kode_barang) }}" method="POST" class="modal-content" id="formEditBarang-{{ $b->kode_barang }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Barang: {{ $b->nama_barang }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label>Nama Barang</label>
                                    <input type="text" name="nama_barang" class="form-control"
                                        value="{{ $b->nama_barang }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Kode Barang</label>
                                    <input type="text" class="form-control"
                                        value="{{ $b->kode_barang }}" disabled>
                                    <small class="text-muted">Kode barang tidak dapat diubah</small>
                                </div>
                                <div class="col-md-6">
                                    <label>Kategori</label>
                                    <select name="id_kategori" class="form-select" required>
                                        @foreach ($kategori as $kat)
                                            @if($kat->gudang && str_contains(strtolower($kat->gudang->nama), 'utama'))
                                                <option value="{{ $kat->id }}"
                                                    @if ($b->id_kategori == $kat->id) selected @endif>
                                                    {{ $kat->nama }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Harga / Satuan</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" name="harga_display" id="hargaEdit-{{ $b->kode_barang }}"
                                            class="form-control" value="{{ number_format($b->harga_barang ?? 0, 0, ',', '.') }}">
                                        <input type="hidden" name="harga_barang" id="hargaEditHidden-{{ $b->kode_barang }}" value="{{ $b->harga_barang }}">
                                        <select name="satuan" class="form-select">
                                            <option value="Pcs" @if ($b->satuan == 'Pcs') selected @endif>Pcs</option>
                                            <option value="Box" @if ($b->satuan == 'Box') selected @endif>Box</option>
                                            <option value="Pack" @if ($b->satuan == 'Pack') selected @endif>Pack</option>
                                            <option value="Rim" @if ($b->satuan == 'Rim') selected @endif>Rim</option>
                                            <option value="Unit" @if ($b->satuan == 'Unit') selected @endif>Unit</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
                            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    @endforeach

    {{-- Modal Filter --}}
    <div class="modal fade" id="modalFilterBarang" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form action="{{ route('admin.datakeseluruhan.index') }}" method="GET" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Satuan -->
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

                        <!-- Rentang Harga -->
                        <div class="col-md-6">
                            <label class="form-label">Rentang Harga</label>
                            <div class="d-flex gap-2">
                                <input type="number" name="harga_min" class="form-control"
                                    placeholder="Min Harga" value="{{ request('harga_min') }}" step="0.01" min="0">
                                <input type="number" name="harga_max" class="form-control"
                                    placeholder="Max Harga" value="{{ request('harga_max') }}" step="0.01" min="0">
                            </div>
                        </div>

                        <!-- Kategori -->
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

                        <!-- Stok -->
                        <div class="col-md-6">
                            <label class="form-label">Stok</label>
                            <div class="d-flex gap-2">
                                <input type="number" name="stok_min" class="form-control"
                                    placeholder="Stok Minimum" value="{{ request('stok_min') }}" min="0">
                                <input type="number" name="stok_max" class="form-control"
                                    placeholder="Stok Maksimal" value="{{ request('stok_max') }}" min="0">
                            </div>
                        </div>

                        <!-- Gudang -->
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
                    <a href="{{ route('admin.datakeseluruhan.index') }}" class="btn btn-secondary">Reset Filter</a>
                    <button class="btn btn-primary" type="submit">Terapkan Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="deleteForm" class="modal-content">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteMessage">Apakah Anda yakin ingin menghapus data ini?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
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

        // Confirm delete function
        function confirmDelete(actionUrl, itemName) {
            document.getElementById('deleteForm').setAttribute('action', actionUrl);
            document.getElementById('deleteMessage').innerText =
                "Apakah Anda yakin ingin menghapus " + itemName + "?";
            let modal = new bootstrap.Modal(document.getElementById('modalConfirmDelete'));
            modal.show();
        }

        // Format Rupiah function
        function formatRupiah(angka) {
            return angka.replace(/\D/g, "")
                .replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function unformatRupiah(formatted) {
            return formatted.replace(/\./g, "");
        }

        // Handle Tambah Barang Price Format
        const hargaTambahInput = document.getElementById('hargaTambah');
        const hargaTambahHidden = document.getElementById('hargaTambahHidden');
        
        if (hargaTambahInput && hargaTambahHidden) {
            hargaTambahInput.addEventListener('input', function() {
                const formatted = formatRupiah(this.value);
                this.value = formatted;
                hargaTambahHidden.value = unformatRupiah(formatted);
            });

            // Set initial value if exists
            if (hargaTambahInput.value) {
                const initialValue = unformatRupiah(hargaTambahInput.value);
                hargaTambahHidden.value = initialValue;
                hargaTambahInput.value = formatRupiah(initialValue);
            }
        }

        // Handle Edit Barang Price Format
        document.querySelectorAll('[id^="hargaEdit-"]').forEach(input => {
            const kodeBarang = input.id.replace('hargaEdit-', '');
            const hiddenInput = document.getElementById('hargaEditHidden-' + kodeBarang);
            
            if (hiddenInput) {
                input.addEventListener('input', function() {
                    const formatted = formatRupiah(this.value);
                    this.value = formatted;
                    hiddenInput.value = unformatRupiah(formatted);
                });

                // Set initial formatted value
                if (hiddenInput.value) {
                    input.value = formatRupiah(hiddenInput.value.toString());
                }
            }
        });

        // Auto-clean form before submit (backup solution)
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                // Clean any remaining display price inputs
                const displayInputs = form.querySelectorAll('input[name="harga_display"]');
                displayInputs.forEach(input => {
                    const hiddenInput = form.querySelector('input[name="harga_barang"]');
                    if (hiddenInput) {
                        hiddenInput.value = unformatRupiah(input.value);
                    }
                });
            });
        });

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
                    let searchUrl = `{{ route('admin.api.search.barang') }}?q=${encodeURIComponent(query)}`;
                    
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
                suggestionsContainer.innerHTML = '<div class="dropdown-item">Mencari...</div>';
                suggestionsContainer.style.display = 'block';
            }

            function displaySuggestions(suggestions) {
                if (suggestions.length === 0) {
                    suggestionsContainer.innerHTML = 
                        '<div class="dropdown-item">Tidak ada barang ditemukan</div>';
                    return;
                }

                let html = '';
                suggestions.forEach((item, index) => {
                    const stockStatusClass = item.stock_status === 'empty' ? 'text-danger' :
                        item.stock_status === 'low' ? 'text-warning' : 'text-success';
                    const stockText = item.stock_status === 'empty' ? 'Habis' :
                        item.stock_status === 'low' ? 'Sedikit' : 'Tersedia';

                    html += `
                        <div class="dropdown-item cursor-pointer" data-index="${index}" style="cursor: pointer;">
                            <div class="fw-bold">${item.nama}</div>
                            <small class="text-muted">Kode: ${item.kode} | Kategori: ${item.kategori} | Gudang: ${item.gudang}</small><br>
                            <small>Stok: <span class="${stockStatusClass}">${item.stok} - ${stockText}</span></small>
                        </div>
                    `;
                });

                suggestionsContainer.innerHTML = html;
                suggestionsContainer.style.display = 'block';

                suggestionsContainer.querySelectorAll('.dropdown-item').forEach(item => {
                    if (item.dataset.index) {
                        item.addEventListener('click', function() {
                            const index = parseInt(this.dataset.index);
                            selectSuggestion(index);
                        });
                        
                        item.addEventListener('mouseenter', function() {
                            activeSuggestionIndex = parseInt(this.dataset.index);
                            updateActiveSuggestion();
                        });
                    }
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

            function updateActiveSuggestion() {
                const suggestions = suggestionsContainer.querySelectorAll('.dropdown-item[data-index]');
                suggestions.forEach((item, index) => {
                    if (parseInt(item.dataset.index) === activeSuggestionIndex) {
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
                const suggestions = suggestionsContainer.querySelectorAll('.dropdown-item[data-index]');

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (suggestions.length > 0) {
                        activeSuggestionIndex = activeSuggestionIndex < suggestions.length - 1 
                            ? activeSuggestionIndex + 1 
                            : 0;
                        updateActiveSuggestion();
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (suggestions.length > 0) {
                        activeSuggestionIndex = activeSuggestionIndex > 0 
                            ? activeSuggestionIndex - 1 
                            : suggestions.length - 1;
                        updateActiveSuggestion();
                    }
                } else if (e.key === 'Enter') {
                    if (activeSuggestionIndex >= 0 && suggestions.length > 0) {
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

            // Listen to main gudang filter changes only
            const mainGudangSelect = document.querySelector('#modalFilterBarang select[name="gudang_id"]');
            if (mainGudangSelect) {
                mainGudangSelect.addEventListener('change', function() {
                    if (searchInput.value.trim().length >= 2) {
                        fetchSuggestions(searchInput.value.trim());
                    }
                });
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</x-layouts.app>