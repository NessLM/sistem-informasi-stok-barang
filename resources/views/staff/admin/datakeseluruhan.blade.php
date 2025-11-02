<x-layouts.app title="Data Keseluruhan" :menu="$menu">

    @php
        $kategori = $kategori ?? collect();
        $bagian = $bagian ?? collect();
        $hasilCari = $hasilCari ?? collect();
    @endphp

    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/data_keseluruhan.css') }}">
    @endpush

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
                <h4 class="d-flex align-items-center gap-2">Data Keseluruhan</h4>

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

            {{-- Search Form --}}
            <div class="position-relative mb-3">
                <form action="{{ route('admin.datakeseluruhan.index') }}" method="GET" class="input-group"
                    id="searchForm">
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
            @if (
                    request()->filled('search') ||
                    request()->filled('kode') ||
                    request()->filled('stok_min') ||
                    request()->filled('stok_max') ||
                    request()->filled('kategori_id') ||
                    request()->filled('bagian_id') ||
                    request()->filled('satuan') ||
                    request()->filled('harga_min') ||
                    request()->filled('harga_max')
                )
                <h5 class="mt-3">Hasil Pencarian</h5>

                {{-- UNTUK HASIL PENCARIAN - Ganti bagian table dengan: --}}
                @if ($hasilCari->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered mt-2">
                            <thead class="table-secondary">
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Kode</th>
                                    <th>Bagian</th>
                                    <th>Lokasi</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th>Satuan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hasilCari as $i => $row)
                                    <tr @if ($row->stok < 10) class="row-low-stock" @endif>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $row->b->nama_barang }}</td>
                                        <td>{{ $row->b->kode_barang }}</td>
                                        <td>{{ $row->bagian }}</td>
                                        <td><span
                                                class="badge bg-{{ $row->lokasi == 'Gudang Utama' ? 'primary' : 'success' }}">{{ $row->lokasi }}</span>
                                        </td>
                                        <td>{{ $row->kategori }}</td>
                                        <td>Rp {{ number_format($row->harga, 0, ',', '.') }}</td>
                                        <td>{{ $row->stok }}</td>
                                        <td>{{ $row->b->satuan }}</td>
                                        <td class="d-flex gap-2">
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                data-bs-target="#modalEditBarang-{{ $row->b->kode_barang }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                onclick="confirmDelete('{{ route('admin.barang.destroy', $row->b->kode_barang) }}', 'Barang {{ $row->b->nama_barang }}')">
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
            {{-- Jika tidak ada filter/search - Tampilan Nested --}}
                 
                    @if (
                                !request()->filled('search') &&
                                !request()->filled('kode') &&
                                !request()->filled('stok_min') &&
                                !request()->filled('stok_max') &&
                                !request()->filled('kategori_id') &&
                                !request()->filled('bagian_id') &&
                                !request()->filled('satuan') &&
                                !request()->filled('harga_min') &&
                                !request()->filled('harga_max')
                            )
                        <div class="table-responsive mt-3">
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>STRUKTUR DATA</th>
                                        <th style="width:180px" class="text-center">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- GUDANG UTAMA --}}
                                    <tr class="table-secondary">
                                        <td>Gudang Utama</td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-primary" onclick="toggleGudangUtama()">
                                                <i class="bi bi-chevron-down" id="icon-gudang-utama"></i>
                                                Expand
                                            </button>
                                        </td>
                                    </tr>

                                    <tr id="gudang-utama" style="display:none;">
                                        <td colspan="2" class="p-0">
                                            <table class="table table-sm mb-0">
                                                <tbody>
                                                    @foreach ($kategori as $k)
                                                        <tr class="table-light">
                                                            <td style="padding-left: 30px;">{{ $k->nama }}</td>
                                                            <td style="width:180px" class="text-center">
                                                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                                                    <button class="btn btn-sm btn-success"
                                                                        onclick="toggleKategori({{ $k->id }})">
                                                                        <i class="bi bi-eye" id="icon-kategori-{{ $k->id }}"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-danger"
                                                                        onclick="confirmDelete('{{ route('admin.kategori.destroy', $k->id) }}', 'Kategori {{ $k->nama }}')">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>

                                                        {{-- Barang di Kategori Gudang Utama --}}
                                                        <tr id="kategori-{{ $k->id }}" style="display:none;">
                                                            <td colspan="2" style="padding-left: 60px; background-color: #f8f9fa;">
                                                                @if ($k->barang->count())
                                                                    <div class="table-responsive">
                                                                        <table class="table table-striped table-sm">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>No</th>
                                                                                    <th>Nama</th>
                                                                                    <th>Kode</th>
                                                                                    <th>Bagian</th>
                                                                                    <th>Stok</th>
                                                                                    <th>Harga</th>
                                                                                    <th>Satuan</th>
                                                                                    <th>Aksi</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @php
                                                                                    $hasAnyStock = false;
                                                                                @endphp
                                                                                @foreach ($k->barang as $i => $b)
                                                                                    @if ($b->pbStok && $b->pbStok->count() > 0)
                                                                                        @foreach ($b->pbStok as $pb)
                                                                                            @php
                                                                                                $hasAnyStock = true;
                                                                                                $stokDisplay = $pb->stok ?? 0;
                                                                                            @endphp
                                                                                            <tr @if ($stokDisplay < 10) class="row-low-stock" @endif>
                                                                                               <td>{{ $loop->iteration }}</td>

                                                                                                <td>{{ $b->nama_barang }}</td>
                                                                                                <td>{{ $b->kode_barang }}</td>
                                                                                                <td>

                                                                                                        {{ $pb->bagian->nama ?? '-' }}

                                                                                                </td>
                                                                                                <td>
                                                                                                    @if ($stokDisplay == 0)
                                                                                                        Habis
                                                                                                    @elseif ($stokDisplay < 10)
                                                                                                           {{ $stokDisplay }}
                                                                                                    @else
                                                                                                        {{ $stokDisplay }}
                                                                                                    @endif
                                                                                                </td>
                                                                                                <td>Rp {{ number_format($pb->harga ?? 0, 0, ',', '.') }}
                                                                                                </td>
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
                                                                                    @endif
                                                                                @endforeach
                                                                                @if (!$hasAnyStock)
                                                                                    <tr>
                                                                                        <td colspan="8" class="text-center text-muted py-3">
                                                                                            <i class="bi bi-inbox"></i> Belum ada stok barang di
                                                                                            Gudang Utama untuk kategori ini.
                                                                                        </td>
                                                                                    </tr>
                                                                                @endif
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                @else
                                                                    <div class="alert alert-info mb-0" role="alert">
                                                                        <i class="bi bi-info-circle"></i> Belum ada barang pada kategori
                                                                        ini. Silakan tambahkan barang terlebih dahulu.
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>

                                    {{-- 10 BAGIAN --}}
@foreach ($bagian as $bg)
    <tr class="table-secondary">
        <td> {{ $bg->nama }} </td>
        <td class="text-center">
            <button class="btn btn-sm btn-primary" onclick="toggleBagian({{ $bg->id }})">
                <i class="bi bi-chevron-down" id="icon-bagian-{{ $bg->id }}"></i>
                Expand
            </button>
        </td>
    </tr>

    <tr id="bagian-{{ $bg->id }}" style="display:none;">
        <td colspan="2" class="p-0">
            <table class="table table-sm mb-0">
                <tbody>
                    @php
                        $adaBarang = false;
                    @endphp

                    @foreach ($kategori as $k)
                        @php
                            // Filter barang yang punya stok di bagian ini
                            $barangBagian = $k->barang->filter(function ($b) use ($bg) {
                                if (!$b->stokBagian) {
                                    return false;
                                }
                                return $b->stokBagian->where('bagian_id', $bg->id)->isNotEmpty();
                            });

                            if ($barangBagian->count() > 0) {
                                $adaBarang = true;
                            }
                        @endphp

                        {{-- TAMPILKAN SEMUA KATEGORI, baik ada barang atau tidak --}}
                        <tr class="table-light">
                            <td style="padding-left: 30px;">
                                {{ $k->nama }}
                            </td>
                            <td style="width:180px" class="text-center">
                                <button class="btn btn-sm btn-success"
    onclick="toggleKategoriBagian({{ $k->id }}, {{ $bg->id }})">
    <i class="bi bi-eye" id="icon-kategori-{{ $k->id }}-{{ $bg->id }}"></i>
</button>

                            </td>
                        </tr>

                        {{-- DETAIL BARANG atau PESAN KOSONG --}}
                        <tr id="kategori-{{ $k->id }}-bagian-{{ $bg->id }}" style="display:none;">
                            <td colspan="2" style="padding-left: 60px; background-color: #f8f9fa;">
                                @if ($barangBagian->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped table-sm">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Nama</th>
                                                    <th>Kode</th>
                                                    <th>Harga</th>
                                                    <th>Stok</th>
                                                    <th>Satuan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($barangBagian as $i => $b)
                                                    @php
                                                        // Ambil stok dari stok_bagian
                                                        $sb = $b->stokBagian->where('bagian_id', $bg->id)->first();
                                                        $stokDisplay = $sb ? $sb->stok : 0;
                                                        
                                                        // Ambil harga dari stok_bagian (bukan dari pb_stok)
                                                        $harga = $sb ? $sb->harga : 0;
                                                    @endphp
                                                    <tr @if ($stokDisplay < 10) class="row-low-stock" @endif>
                                                        <td>{{ $i + 1 }}</td>
                                                        <td>{{ $b->nama_barang }}</td>
                                                        <td>{{ $b->kode_barang }}</td>
                                                        <td>Rp {{ number_format($harga, 0, ',', '.') }}</td>
                                                        <td>{{ $stokDisplay }}</td>
                                                        <td>{{ $b->satuan }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    {{-- Pesan jika kategori tidak punya barang --}}
                                    <div class="alert alert-info mb-0" role="alert">
                                        <i class="bi bi-info-circle"></i> Belum ada barang pada kategori
                                       {{ $k->nama }} di bagian {{ $bg->nama }}.
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    {{-- Jika bagian ini tidak punya barang sama sekali --}}
                    @if (!$adaBarang)
                        <tr>
                            <td colspan="2" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                <p class="mt-2 mb-0">Belum ada barang di bagian ini</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
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
                                class="form-control @error('nama_barang') is-invalid @enderror"
                                value="{{ old('nama_barang') }}" required>
                            @error('nama_barang')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label>Kode Barang</label>
                            <input type="text" name="kode_barang" id="kodeBarangTambah"
                                class="form-control @error('kode_barang') is-invalid @enderror"
                                value="{{ old('kode_barang') }}" required>
                            <div id="kodeValidationTambah" class="form-text"></div>
                            @error('kode_barang')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label>Kategori</label>
                            <select name="id_kategori" class="form-select @error('id_kategori') is-invalid @enderror"
                                required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach ($kategori as $k)
                                    <option value="{{ $k->id }}" @selected(old('id_kategori') == $k->id)>
                                        {{ $k->nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_kategori')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label>Satuan</label>
                            <select name="satuan" class="form-select" required>
                                <option value="">-- Pilih Satuan --</option>
                                <option value="Pcs" @if (old('satuan') == 'Pcs') selected @endif>Pcs</option>
                                <option value="Box" @if (old('satuan') == 'Box') selected @endif>Box</option>
                                <option value="Pack" @if (old('satuan') == 'Pack') selected @endif>Pack</option>
                                <option value="Rim" @if (old('satuan') == 'Rim') selected @endif>Rim</option>
                                <option value="Unit" @if (old('satuan') == 'Unit') selected @endif>Unit</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" type="submit" id="btnSimpanTambah">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Edit Barang --}}
    @foreach ($kategori as $k)
        @foreach ($k->barang as $b)
            <div class="modal fade" id="modalEditBarang-{{ $b->kode_barang }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <form action="{{ route('admin.barang.update', $b->kode_barang) }}" method="POST" class="modal-content">
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
                                    <input type="text" name="nama_barang" class="form-control" value="{{ $b->nama_barang }}"
                                        required>
                                </div>

                                <div class="col-md-6">
                                    <label>
                                        Kode Barang
                                        <i class="bi bi-info-circle text-primary" data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="Hati-hati mengubah kode barang, akan mempengaruhi riwayat transaksi"
                                            style="cursor: help;"></i>
                                    </label>
                                    <input type="text" name="kode_barang" id="kodeBarangEdit-{{ $b->kode_barang }}"
                                        class="form-control" value="{{ $b->kode_barang }}"
                                        data-original-kode="{{ $b->kode_barang }}" required>
                                    <div id="kodeValidationEdit-{{ $b->kode_barang }}" class="form-text"></div>
                                </div>

                                <div class="col-md-6">
                                    <label>Kategori</label>
                                    <select name="id_kategori" class="form-select" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        @foreach ($kategori as $kat)
                                            <option value="{{ $kat->id }}" @selected($b->id_kategori == $kat->id)>
                                                {{ $kat->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label>Satuan</label>
                                    <select name="satuan" class="form-select" required>
                                        <option value="Pcs" @selected($b->satuan == 'Pcs')>Pcs</option>
                                        <option value="Box" @selected($b->satuan == 'Box')>Box</option>
                                        <option value="Pack" @selected($b->satuan == 'Pack')>Pack</option>
                                        <option value="Rim" @selected($b->satuan == 'Rim')>Rim</option>
                                        <option value="Unit" @selected($b->satuan == 'Unit')>Unit</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
                            <button class="btn btn-primary" type="submit" id="btnSimpanEdit-{{ $b->kode_barang }}">
                                Simpan Perubahan
                            </button>
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
                            <label class="form-label">Kategori</label>
                            <select name="kategori_id" class="form-select">
                                <option value="">-- Semua Kategori --</option>
                                @foreach ($kategori as $k)
                                    <option value="{{ $k->id }}" @selected(request('kategori_id') == $k->id)>
                                        {{ $k->nama }}
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

                        <div class="col-md-6">
                            <label class="form-label">Bagian</label>
                            <select name="bagian_id" class="form-select">
                                <option value="">-- Gudang Utama --</option>
                                @foreach ($bagian as $bg)
                                    <option value="{{ $bg->id }}" @selected(request('bagian_id') == $bg->id)>
                                        {{ $bg->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Rentang Harga</label>
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
    @push('scripts')
        <script>
            // Toggle Gudang Utama
            function toggleGudangUtama() {
                const container = document.getElementById('gudang-utama');
                const icon = document.getElementById('icon-gudang-utama');

                if (container.style.display === 'none') {
                    container.style.display = 'table-row';
                    icon.classList.remove('bi-chevron-down');
                    icon.classList.add('bi-chevron-up');
                } else {
                    container.style.display = 'none';
                    icon.classList.remove('bi-chevron-up');
                    icon.classList.add('bi-chevron-down');

                    // Close all categories when closing warehouse
                    const categories = container.querySelectorAll('[id^="kategori-"]');
                    categories.forEach(cat => {
                        if (!cat.id.includes('-bagian-')) {
                            cat.style.display = 'none';
                            const catId = cat.id.replace('kategori-', '');
                            const catIcon = document.getElementById('icon-kategori-' + catId);
                            if (catIcon) {
                                catIcon.classList.remove('bi-eye-slash');
                                catIcon.classList.add('bi-eye');
                            }
                        }
                    });
                }
            }

            // Toggle Kategori di Gudang Utama
            function toggleKategori(id) {
                let el = document.getElementById('kategori-' + id);
                let icon = document.getElementById('icon-kategori-' + id);

                if (el.style.display === 'none') {
                    el.style.display = 'table-row';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    el.style.display = 'none';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }

            // Toggle Bagian
            function toggleBagian(id) {
                const container = document.getElementById('bagian-' + id);
                const icon = document.getElementById('icon-bagian-' + id);

                if (container.style.display === 'none') {
                    container.style.display = 'table-row';
                    icon.classList.remove('bi-chevron-down');
                    icon.classList.add('bi-chevron-up');
                } else {
                    container.style.display = 'none';
                    icon.classList.remove('bi-chevron-up');
                    icon.classList.add('bi-chevron-down');

                    // Close all categories in this bagian when closing
                    const categories = container.querySelectorAll('[id^="kategori-"][id*="-bagian-' + id + '"]');
                    categories.forEach(cat => {
                        cat.style.display = 'none';
                        const ids = cat.id.match(/kategori-(\d+)-bagian-(\d+)/);
                        if (ids) {
                            const catIcon = document.getElementById('icon-kategori-' + ids[1] + '-' + ids[2]);
                            if (catIcon) {
                                catIcon.classList.remove('bi-eye-slash');
                                catIcon.classList.add('bi-eye');
                            }
                        }
                    });
                }
            }

            // Toggle Kategori di Bagian
            function toggleKategoriBagian(kategoriId, bagianId) {
                const el = document.getElementById('kategori-' + kategoriId + '-bagian-' + bagianId);
                const icon = document.getElementById('icon-kategori-' + kategoriId + '-' + bagianId);

                if (el.style.display === 'none') {
                    el.style.display = 'table-row';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    el.style.display = 'none';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }

            // Confirm delete function
            function confirmDelete(actionUrl, itemName) {
                document.getElementById('deleteForm').setAttribute('action', actionUrl);
                document.getElementById('deleteMessage').innerText =
                    "Apakah Anda yakin ingin menghapus " + itemName +
                    "? Tindakan ini juga akan menghapus seluruh data yang terkait di dalamnya.";
                let modal = new bootstrap.Modal(document.getElementById('modalConfirmDelete'));
                modal.show();
            }

            // Initialize tooltips
            document.addEventListener('DOMContentLoaded', function () {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });

            // ============ VALIDASI REAL-TIME KODE BARANG ============
            document.addEventListener('DOMContentLoaded', function () {
                let kodeCheckTimeout;

                // Validasi untuk Modal Tambah Barang
                const kodeInputTambah = document.getElementById('kodeBarangTambah');
                const validationMsgTambah = document.getElementById('kodeValidationTambah');
                const btnSimpanTambah = document.getElementById('btnSimpanTambah');

                if (kodeInputTambah) {
                    kodeInputTambah.addEventListener('input', function () {
                        const kode = this.value.trim();

                        if (!kode) {
                            validationMsgTambah.textContent = '';
                            validationMsgTambah.className = 'form-text';
                            btnSimpanTambah.disabled = true;
                            return;
                        }

                        // Tampilkan loading
                        validationMsgTambah.textContent = 'Mengecek ketersediaan kode...';
                        validationMsgTambah.className = 'form-text text-muted';
                        btnSimpanTambah.disabled = true;

                        clearTimeout(kodeCheckTimeout);
                        kodeCheckTimeout = setTimeout(() => {
                            fetch(`{{ route('admin.api.check.kode') }}?kode=${encodeURIComponent(kode)}`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.available) {
                                        validationMsgTambah.innerHTML = '<i class="bi bi-check-circle"></i> ' +
                                            data.message;
                                        validationMsgTambah.className = 'form-text text-success';
                                        btnSimpanTambah.disabled = false;
                                        kodeInputTambah.classList.remove('is-invalid');
                                        kodeInputTambah.classList.add('is-valid');
                                    } else {
                                        validationMsgTambah.innerHTML = '<i class="bi bi-x-circle"></i> ' + data
                                            .message;
                                        validationMsgTambah.className = 'form-text text-danger';
                                        btnSimpanTambah.disabled = true;
                                        kodeInputTambah.classList.remove('is-valid');
                                        kodeInputTambah.classList.add('is-invalid');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error checking kode:', error);
                                    validationMsgTambah.textContent = 'Gagal mengecek kode';
                                    validationMsgTambah.className = 'form-text text-danger';
                                    btnSimpanTambah.disabled = true;
                                });
                        }, 500);
                    });
                }

                // Validasi untuk Modal Edit Barang (semua barang)
                document.querySelectorAll('[id^="kodeBarangEdit-"]').forEach(input => {
                    const kodeBarang = input.id.replace('kodeBarangEdit-', '');
                    const validationMsg = document.getElementById('kodeValidationEdit-' + kodeBarang);
                    const btnSimpan = document.getElementById('btnSimpanEdit-' + kodeBarang);
                    const originalKode = input.dataset.originalKode;

                    input.addEventListener('input', function () {
                        const kode = this.value.trim();

                        if (!kode) {
                            validationMsg.textContent = '';
                            validationMsg.className = 'form-text';
                            btnSimpan.disabled = true;
                            return;
                        }

                        // Jika kode sama dengan kode asli, langsung enable
                        if (kode === originalKode) {
                            validationMsg.innerHTML = '<i class="bi bi-info-circle"></i> Kode tidak berubah';
                            validationMsg.className = 'form-text text-muted';
                            btnSimpan.disabled = false;
                            input.classList.remove('is-invalid', 'is-valid');
                            return;
                        }

                        // Tampilkan loading
                        validationMsg.textContent = 'Mengecek ketersediaan kode...';
                        validationMsg.className = 'form-text text-muted';
                        btnSimpan.disabled = true;

                        clearTimeout(kodeCheckTimeout);
                        kodeCheckTimeout = setTimeout(() => {
                            fetch(
                                `{{ route('admin.api.check.kode') }}?kode=${encodeURIComponent(kode)}&current_kode=${encodeURIComponent(originalKode)}`
                            )
                                .then(response => response.json())
                                .then(data => {
                                    if (data.available) {
                                        validationMsg.innerHTML = '<i class="bi bi-check-circle"></i> ' +
                                            data.message;
                                        validationMsg.className = 'form-text text-success';
                                        btnSimpan.disabled = false;
                                        input.classList.remove('is-invalid');
                                        input.classList.add('is-valid');
                                    } else {
                                        validationMsg.innerHTML = '<i class="bi bi-x-circle"></i> ' + data
                                            .message;
                                        validationMsg.className = 'form-text text-danger';
                                        btnSimpan.disabled = true;
                                        input.classList.remove('is-valid');
                                        input.classList.add('is-invalid');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error checking kode:', error);
                                    validationMsg.textContent = 'Gagal mengecek kode';
                                    validationMsg.className = 'form-text text-danger';
                                    btnSimpan.disabled = true;
                                });
                        }, 500);
                    });
                });

                // Reset validasi saat modal ditutup
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.addEventListener('hidden.bs.modal', function () {
                        // Reset untuk modal tambah
                        if (kodeInputTambah) {
                            kodeInputTambah.value = '';
                            kodeInputTambah.classList.remove('is-valid', 'is-invalid');
                            validationMsgTambah.textContent = '';
                            btnSimpanTambah.disabled = false;
                        }

                        // Reset untuk modal edit
                        const editInput = this.querySelector('[id^="kodeBarangEdit-"]');
                        if (editInput) {
                            editInput.classList.remove('is-valid', 'is-invalid');
                            const kodeBarang = editInput.id.replace('kodeBarangEdit-', '');
                            const validationMsg = document.getElementById('kodeValidationEdit-' +
                                kodeBarang);
                            if (validationMsg) {
                                validationMsg.textContent = '';
                            }
                        }
                    });
                });
            });

            // ============ AUTOCOMPLETE SEARCH ============
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
                        fetch(`{{ route('admin.api.search.barang') }}?q=${encodeURIComponent(query)}`)
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
                                                    <small class="text-muted">Kode: ${item.kode} | Kategori: ${item.kategori}</small><br>
                                                    <small>Stok: <span class="${stockStatusClass}">${item.stok} - ${stockText}</span></small>
                                                </div>
                                            `;
                    });

                    suggestionsContainer.innerHTML = html;
                    suggestionsContainer.style.display = 'block';

                    suggestionsContainer.querySelectorAll('.dropdown-item').forEach(item => {
                        if (item.dataset.index) {
                            item.addEventListener('click', function () {
                                const index = parseInt(this.dataset.index);
                                selectSuggestion(index);
                            });

                            item.addEventListener('mouseenter', function () {
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
                        document.getElementById('searchForm').submit();
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
                searchInput.addEventListener('input', function () {
                    fetchSuggestions(this.value.trim());
                });

                searchInput.addEventListener('keydown', function (e) {
                    const suggestions = suggestionsContainer.querySelectorAll('.dropdown-item[data-index]');

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        if (suggestions.length > 0) {
                            activeSuggestionIndex = activeSuggestionIndex < suggestions.length - 1 ?
                                activeSuggestionIndex + 1 : 0;
                            updateActiveSuggestion();
                        }
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        if (suggestions.length > 0) {
                            activeSuggestionIndex = activeSuggestionIndex > 0 ?
                                activeSuggestionIndex - 1 : suggestions.length - 1;
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
            });
        </script>
    @endpush

</x-layouts.app>