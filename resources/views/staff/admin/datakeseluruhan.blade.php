<x-layouts.app title="Data Keseluruhan" :menu="$menu">

    @php
        $barang = $barang ?? collect();
        $kategori = $kategori ?? collect();
        $gudang = $gudang ?? collect();
    @endphp

    <head>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    </head>

    <main class="page-wrap container py-4">

        {{-- Toast sukses --}}
        @if (session('success'))
            <div class="toast-container">
                <div class="toast align-items-center text-bg-success border-0 show" id="successToast">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>Berhasil!</strong> {{ session('success') }}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast"></button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Pesan error global --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="card shadow-sm p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                @php
                    $title = 'Data Gudang ATK'; // default
                    
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
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th>Satuan</th>
                                    <th>Kategori</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($barang as $i => $b)
                                    <tr @if ($b->stok == 0) class="table-danger" @endif>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $b->nama }}</td>
                                        <td>{{ $b->kode }}</td>
                                        <td>Rp {{ number_format($b->harga ?? 0, 0, ',', '.') }}</td>
                                        <td>{{ $b->stok }}</td>
                                        <td>{{ $b->satuan }}</td>
                                        <td>{{ $b->kategori->nama ?? '-' }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                data-bs-target="#modalEditBarang-{{ $b->id }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form action="{{ route('admin.barang.destroy', $b->id) }}" method="POST"
                                                class="d-inline" onsubmit="return confirm('Hapus barang ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger"><i
                                                        class="bi bi-trash"></i></button>
                                            </form>
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
                                                            <tr @if ($b->stok == 0) class="table-danger" @endif>
                                                                <td>{{ $i + 1 }}</td>
                                                                <td>{{ $b->nama }}</td>
                                                                <td>{{ $b->kode }}</td>
                                                                <td>Rp {{ number_format($b->harga ?? 0, 0, ',', '.') }}</td>
                                                                <td>{{ $b->stok }}</td>
                                                                <td>{{ $b->satuan }}</td>
                                                                <td class="d-flex gap-2">
                                                                    <button class="btn btn-sm btn-warning"
                                                                        data-bs-toggle="modal"
                                                                        data-bs-target="#modalEditBarang-{{ $b->id }}">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-danger"
                                                                        onclick="confirmDelete('{{ route('admin.barang.destroy', $b->id) }}', 'Barang {{ $b->nama }}')">
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

    {{-- Modal Tambah Barang --}}
    <div class="modal fade" id="modalTambahBarang" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form action="{{ route('admin.barang.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Nama</label>
                            <input type="text" name="nama"
                                class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}"
                                required>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label>Kode</label>
                            <input type="text" name="kode"
                                class="form-control @error('kode') is-invalid @enderror" value="{{ old('kode') }}"
                                required>
                            @error('kode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label>Kategori</label>
                            <select name="kategori_id" class="form-select @error('kategori_id') is-invalid @enderror"
                                required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($kategori as $k)
                                    <option value="{{ $k->id }}"
                                        @if (old('kategori_id') == $k->id) selected @endif>{{ $k->nama }}</option>
                                @endforeach
                            </select>
                            @error('kategori_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                       <div class="col-md-6">
    <label>Harga / Satuan</label>
    <div class="input-group">
        <span class="input-group-text">Rp</span>
        <input type="text" name="harga" id="hargaTambah"
            class="form-control @error('harga') is-invalid @enderror"
            value="{{ old('harga') }}">
        <select name="satuan" class="form-select">
            <option value="Pcs" @if (old('satuan') == 'Pcs') selected @endif>Pcs</option>
            <option value="Box" @if (old('satuan') == 'Box') selected @endif>Box</option>
            <option value="Pack" @if (old('satuan') == 'Pack') selected @endif>Pack</option>
            <option value="Rim" @if (old('satuan') == 'Rim') selected @endif>Rim</option>
            <option value="Unit" @if (old('satuan') == 'Unit') selected @endif>Unit</option>
        </select>
    </div>
    @error('harga')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

                        <input type="hidden" name="stok" value="0">
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
            <div class="modal fade" id="modalEditBarang-{{ $b->id }}" tabindex="-1"aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <form action="{{ route('admin.barang.update', $b->id) }}" method="POST" class="modal-content">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Barang: {{ $b->nama }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label>Nama</label>
                                    <input type="text" name="nama" class="form-control"
                                        value="{{ $b->nama }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Kode</label>
                                    <input type="text" name="kode" class="form-control"
                                        value="{{ $b->kode }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Kategori</label>
                                    <select name="kategori_id" class="form-select" required>
                                        @foreach ($kategori as $kat)
                                            <option value="{{ $kat->id }}"
                                                @if ($b->kategori_id == $kat->id) selected @endif>
                                                {{ $kat->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                               <div class="col-md-6">
    <label>Harga / Satuan</label>
    <div class="input-group">
        <span class="input-group-text">Rp</span>
        <input type="text" name="harga" id="hargaEdit-{{ $b->id }}"
            class="form-control" value="{{ $b->harga }}">
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
    <div class="modal fade" id="modalFilterBarang" tabindex="-1">
        <div class="modal-dialog modal-lg">
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

            // Check if elements exist
            if (!searchInput || !suggestionsContainer) {
                console.log('Search elements not found');
                return;
            }

            // Function untuk fetch suggestions
            function fetchSuggestions(query) {
                if (query.length < 2) {
                    hideSuggestions();
                    return;
                }

                // Show loading
                showLoading();

                // Clear previous timeout
                clearTimeout(searchTimeout);

                // Debounce search request
                searchTimeout = setTimeout(() => {
                    const searchUrl =
                        `{{ route('admin.api.search.barang') }}?q=${encodeURIComponent(query)}`;
                    console.log('Fetching:', searchUrl);

                    fetch(searchUrl)
                        .then(response => {
                            console.log('Response status:', response.status);
                            return response.json();
                        })
                        .then(data => {
                            console.log('Search results:', data);
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

            // Function untuk display suggestions
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
                        <small>Kategori: ${item.kategori} | Stok: ${item.stok} | 
                        <span class="stock-status ${stockStatusClass}">${stockText}</span></small>
                    </div>
                </div>
            `;
                });

                suggestionsContainer.innerHTML = html;
                suggestionsContainer.style.display = 'block';

                // Add click event listeners
                suggestionsContainer.querySelectorAll('.search-suggestion-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const index = parseInt(this.dataset.index);
                        selectSuggestion(index);
                    });
                });
            }

            // Function untuk hide suggestions
            function hideSuggestions() {
                suggestionsContainer.style.display = 'none';
                activeSuggestionIndex = -1;
            }

            // Function untuk select suggestion
            function selectSuggestion(index) {
                if (currentSuggestions[index]) {
                    const suggestion = currentSuggestions[index];

                    // Gunakan kode karena lebih spesifik dan exact match
                    searchInput.value = suggestion.nama;
                    hideSuggestions();

                    // Auto submit form
                    document.getElementById('searchForm').submit();
                }
            }

            // Event listener untuk input
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                console.log('Input changed:', query);
                fetchSuggestions(query);
            });

            // Event listener untuk keyboard navigation
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

            // Function untuk update active suggestion
            function updateActiveSuggestion(suggestions) {
                suggestions.forEach((item, index) => {
                    if (index === activeSuggestionIndex) {
                        item.classList.add('active');
                    } else {
                        item.classList.remove('active');
                    }
                });
            }

            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                    hideSuggestions();
                }
            });

            // Focus event
            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length >= 2) {
                    fetchSuggestions(this.value.trim());
                }
            });
        });
    </script>

    <script>
    function confirmDelete(actionUrl, itemName) {
        // Set action form hapus
        document.getElementById('deleteForm').setAttribute('action', actionUrl);

        // Update pesan modal
        document.getElementById('deleteMessage').innerText =
            "Apakah Anda yakin ingin menghapus " + itemName + "?";

        // Tampilkan modal
        let modal = new bootstrap.Modal(document.getElementById('modalConfirmDelete'));
        modal.show();
    }
</script>

<script>
function formatRupiah(angka) {
    return angka.replace(/\D/g, "")
        .replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Tambah Barang
document.getElementById('hargaTambah')?.addEventListener('input', function() {
    this.value = formatRupiah(this.value);
});

// Edit Barang (loop semua harga edit)
document.querySelectorAll('[id^="hargaEdit-"]').forEach(input => {
    input.addEventListener('input', function() {
        this.value = formatRupiah(this.value);
    });
});
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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



</x-layouts.app>