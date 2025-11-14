{{-- resources/views/staff/pj/datakeseluruhan.blade.php --}}

@php
    // Controller sudah mengirim $selectedGudang
    $gudangName = $selectedGudang->nama ?? '-';
    $pageHeading = "Data {$gudangName}";
@endphp

<x-layouts.app title="Data Gudang" :menu="$menu" :heading="$pageHeading">

    @php
        $barang = $barang ?? collect();     // hasil filter/search (bisa kosong)
        $kategori = $kategori ?? collect();   // kategori + barang (stok > 0)
        // Tambahan dari controller:
        $barangHabis = $barangHabis ?? collect();  // list barang stok = 0
        $barangMasuk = $barangMasuk ?? collect();  // list barang masuk dari transaksi_distribusi
        $lowThreshold = $lowThreshold ?? 10;         // ambang "menipis"
        $ringkasanCounts = $ringkasanCounts ?? ['ok' => 0, 'low' => 0, 'empty' => 0];
    @endphp


    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/staff/pj/data_keseluruhan_pj.css') }}">
        <style>
            .row-low-stock {
                background-color: #ffcccc !important;
                border-left: 4px solid #fd7e14 !important;
            }

            .incoming-card {
                border-left: 4px solid #28a745;
            }

            .incoming-card .card-body {
                background-color: #f8f9fa;
            }

            .btn-return {
                background-color: #ffc107;
                border-color: #ffc107;
                color: #000;
            }

            .btn-return:hover {
                background-color: #e0a800;
                border-color: #d39e00;
                color: #000;
            }

            .badge-normal {
                font-weight: normal !important;
            }
        </style>

    @endpush

    <main class="page-wrap container py-4">

<!-- Toast notification -->
        @if (session('toast'))
            @php
                $isSuccess = session('toast.type') === 'success';
                $bgColor = $isSuccess ? '#28a745' : '#dc3545';
                $iconClass = $isSuccess ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
            @endphp
            
            <div id="toast-notif"
                style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%) translateY(-20px);
                       z-index: 2000; display: flex; justify-content: center; pointer-events: none;
                       animation: slideDown 0.4s ease-out forwards;">

                <div class="toast-message"
                    style="background: #fff; border-radius: 16px; padding: 18px 24px;
                           box-shadow: 0 8px 24px rgba(0,0,0,0.15), 0 2px 8px rgba(0,0,0,0.1);
                           text-align: left; min-width: 320px; max-width: 420px;
                           border-left: 5px solid {{ $bgColor }};
                           transition: all 0.3s ease;
                           display: flex; align-items: center; gap: 14px;">

                    <div style="flex-shrink: 0; width: 42px; height: 42px; border-radius: 50%;
                               background: {{ $bgColor }}; display: flex; align-items: center;
                               justify-content: center; box-shadow: 0 2px 8px {{ $bgColor }}40;">
                        <i class="bi {{ $iconClass }}" style="color: #fff; font-size: 22px;"></i>
                    </div>

                    <div style="flex: 1;">
                        <div style="font-weight: 600; font-size: 16px; margin-bottom: 4px;
                                   color: #1a1a1a; line-height: 1.3;">
                            {{ session('toast.title') }}
                        </div>
                        <div style="color: #666; font-size: 14px; line-height: 1.5;">
                            {{ session('toast.message') }}
                        </div>
                    </div>

                    <button onclick="closeToast()" 
                            style="flex-shrink: 0; background: none; border: none; 
                                   color: #999; cursor: pointer; padding: 4px; 
                                   border-radius: 4px; transition: all 0.2s;
                                   pointer-events: all; width: 24px; height: 24px;
                                   display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-x-lg" style="font-size: 14px;"></i>
                    </button>
                </div>
            </div>

            <style>
                @keyframes slideDown {
                    from {
                        transform: translateX(-50%) translateY(-20px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(-50%) translateY(0);
                        opacity: 1;
                    }
                }

                @keyframes slideUp {
                    from {
                        transform: translateX(-50%) translateY(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(-50%) translateY(-20px);
                        opacity: 0;
                    }
                }

                #toast-notif button:hover {
                    background: #f0f0f0 !important;
                    color: #333 !important;
                }
            </style>

            <script>
                function closeToast() {
                    const toast = document.getElementById('toast-notif');
                    if (toast) {
                        toast.style.animation = 'slideUp 0.3s ease-out forwards';
                        setTimeout(() => toast?.remove(), 300);
                    }
                }

                setTimeout(() => {
                    closeToast();
                }, 4000);
            </script>
        @endif

        <section class="card shadow-sm p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                @php
                    $gudangNama = $selectedGudang->nama ?? 'Gudang';
                    if (str_starts_with($gudangNama, 'Gudang')) {
                        $title = 'Data ' . $gudangNama;
                    } else {
                        $title = 'Data Gudang ' . $gudangNama;
                    }
                @endphp
                <h4 class="mb-0" style="font-weight: 600;">Data Gudang</h4>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFilterBarang">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </div>

            {{-- === RINGKASAN KETERSEDIAAN === --}}
            <div class="summary-badges d-flex flex-wrap gap-2 mb-3">
                <a href="#sec-empty" class="badge badge-empty text-decoration-none">
                    <span class="badge-dot" style="background:#dc3545"></span>
                    Habis: {{ $ringkasanCounts['empty'] ?? 0 }}
                </a>
                <a href="#sec-low" class="badge badge-low text-decoration-none">
                    <span class="badge-dot" style="background:#fd7e14"></span>
                    Menipis (&lt;{{ $lowThreshold }}): {{ $ringkasanCounts['low'] ?? 0 }}
                </a>
                <span class="badge badge-ok">
                    <span class="badge-dot" style="background:#28a745"></span>
                    Tersedia: {{ $ringkasanCounts['ok'] ?? 0 }}
                </span>
            </div>

            {{-- Search Form dengan Autocomplete --}}
            <div class="position-relative mb-3">
                <form action="{{ route('pj.datakeseluruhan.index') }}" method="GET" class="input-group" id="searchForm">
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
                    request()->filled('satuan')
                )
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
                                        $stokTersedia = $b->stok_tersedia ?? 0;
                                    @endphp
                                    @if ($stokTersedia > 0)
                                        <tr @if ($stokTersedia < 10) class="row-low-stock" @endif>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $b->nama }}</td>
                                            <td>{{ $b->kode }}</td>
                                            <td>{{ $stokTersedia }}</td>
                                            <td>{{ $b->satuan }}</td>
                                            <td>{{ $b->kategori->nama ?? '-' }}</td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#modalBarangKeluar" data-id="{{ $b->kode }}"
                                                    data-nama="{{ $b->nama }}" data-kode="{{ $b->kode }}"
                                                    data-stok="{{ $stokTersedia }}">
                                                    <i class="bi bi-box-arrow-right"></i> Barang Keluar
                                                </button>
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

            {{-- Jika tidak ada filter/search --}}
            @if (
                    !request()->filled('search') &&
                    !request()->filled('kode') &&
                    !request()->filled('stok_min') &&
                    !request()->filled('stok_max') &&
                    !request()->filled('kategori_id') &&
                    !request()->filled('satuan')
                )
                {{-- Anchor untuk badge "Menipis" --}}
                <div id="sec-low"></div>

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
                                <tr>
                                    <td>{{ $k->nama }}</td>
                                    <td class="text-center">
                                        <div class="d-flex flex-wrap justify-content-center gap-2">
                                            <button class="btn btn-sm btn-success" onclick="toggleDetail({{ $k->id }})"><i
                                                    class="bi bi-eye"></i></button>
                                        </div>
                                    </td>
                                </tr>

                                <tr id="detail-{{ $k->id }}" style="display:none;">
                                    <td colspan="2">
                                        @php
                                            // barang sudah di-load (stok > 0) di controller
                                            $barangFiltered = $k->barang;
                                        @endphp
                                        @if ($barangFiltered->count())
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Kode</th>
                                                            <th>Nama Barang</th>
                                                            <th>Stok</th>
                                                            <th>Satuan</th>
                                                            <th>Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($barangFiltered as $item)
                                                            @php
                                                                $stokTersedia = $item->stok_tersedia ?? 0;
                                                            @endphp
                                                            <tr @if ($stokTersedia < 10) class="row-low-stock" @endif>
                                                                <td>{{ $item->kode }}</td>
                                                                <td>{{ $item->nama }}</td>
                                                                <td>{{ $stokTersedia }}</td>
                                                                <td>{{ $item->satuan }}</td>
                                                                <td>
                                                                    <button type="button" class="btn btn-danger btn-sm"
                                                                        data-bs-toggle="modal" data-bs-target="#modalBarangKeluar"
                                                                        data-id="{{ $item->kode }}" data-nama="{{ $item->nama }}"
                                                                        data-kode="{{ $item->kode }}" data-stok="{{ $stokTersedia }}">
                                                                        <i class="bi bi-box-arrow-right"></i> Barang Keluar
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-muted">Tidak ada barang pada kategori ini (atau semua barang telah
                                                habis).</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- =========================
        SECTION BARANG HABIS
        ========================= --}}
        @if(($barangHabis ?? collect())->count())
            <section id="sec-empty" class="card empty-card mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="m-0">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Barang Habis
                            <span class="badge bg-danger ms-2">{{ $barangHabis->count() }}</span>
                        </h5>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-danger">
                                <tr>
                                    <th style="width:120px">Kode</th>
                                    <th>Nama Barang</th>
                                    <th style="width:220px">Kategori</th>
                                    <th style="width:120px">Satuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($barangHabis as $item)
                                    <tr class="row-empty">
                                        <td>{{ $item->kode }}</td>
                                        <td>
                                            {{ $item->nama }}

                                        </td>
                                        <td>{{ $item->kategori->nama ?? '-' }}</td>
                                        <td>{{ $item->satuan ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        {{-- =========================
        SECTION BARANG MASUK (UPDATED WITH KONFIRMASI & KEMBALIKAN BUTTON)
        ========================= --}}
        @if(($barangMasuk ?? collect())->count())
            <section id="sec-incoming" class="card incoming-card shadow-sm mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="m-0">
                            <i class="bi bi-box-arrow-in-down-right me-1 text-success"></i>
                            Barang Masuk
                            <span class="badge bg-success ms-2">{{ $barangMasuk->count() }}</span>
                        </h5>
                        <div class="d-flex gap-2">
                            <span class="badge bg-warning text-dark badge-normal" style="font-size: 14px;">
                                <i class="bi bi-clock-history"></i>
                                Pending: {{ $barangMasuk->where('status_konfirmasi', 'pending')->count() }}
                            </span>
                            <span class="badge bg-success badge-normal" style="font-size: 14px;">
                                <i class="bi bi-check-circle"></i>
                                Confirmed: {{ $barangMasuk->where('status_konfirmasi', 'confirmed')->count() }}
                            </span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-success">
                                <tr>
                                    <th style="width:50px">No</th>
                                    <th style="width:120px">Status</th>
                                    <th style="width:140px">Tanggal Waktu</th>
                                    <th>Nama Barang</th>
                                    <th style="width:100px">Jumlah</th>
                                    <th style="width:100px">Satuan</th>
                                    <th style="width:200px">Keterangan</th>
                                    <th style="width:200px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($barangMasuk as $index => $item)
                                    @php
                                        $status = $item->status_konfirmasi ?? 'pending';
                                        $rowClass = $status === 'confirmed' ? 'table-success-light' : '';
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>
                                            @if($status === 'confirmed')
                                                <span class="badge bg-success badge-normal" style="font-size: 14px;">
                                                    <i class="bi bi-check-circle"></i> Dikonfirmasi
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark badge-normal" style="font-size: 14px;">
                                                    <i class="bi bi-clock-history"></i> Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y H:i') }} WIB</td>
                                        <td>
                                            {{ $item->nama_barang }}
                                            <br>
                                            <small class="text-muted">{{ $item->kode_barang }}</small>
                                        </td>
                                        <td class="text-center">
                                            {{ $item->jumlah }}
                                        </td>
                                        <td>{{ $item->satuan }}</td>
                                        <td>{{ $item->keterangan ?? '-' }}</td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column gap-2">
                                                @if($item->bukti)
                                                    <a href="{{ asset('storage/' . $item->bukti) }}" target="_blank"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-file-earmark-text"></i> Lihat Bukti
                                                    </a>
                                                @endif

                                                @if($status === 'pending')
                                                    {{-- Tombol Konfirmasi --}}
                                                    <form action="{{ route('pj.konfirmasi-barang-masuk', $item->id) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Konfirmasi barang masuk?\n\nSetelah dikonfirmasi, barang akan masuk ke stok dan tidak bisa dikembalikan.\n\nBarang: {{ $item->nama_barang }}\nJumlah: {{ $item->jumlah }} {{ $item->satuan }}')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success w-100">
                                                            <i class="bi bi-check-circle"></i> Konfirmasi
                                                        </button>
                                                    </form>

                                                    {{-- Tombol Kembalikan --}}
                                                    <form action="{{ route('pj.kembalikan-barang', $item->id) }}" method="POST"
                                                        onsubmit="return confirm('Yakin ingin mengembalikan barang ini ke PB Stok?\n\nBarang: {{ $item->nama_barang }}\nJumlah: {{ $item->jumlah }} {{ $item->satuan }}')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-return w-100">
                                                            <i class="bi bi-arrow-left-circle"></i> Kembalikan
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="badge bg-success text-wrap badge-normal" style="font-size: 12px;">
                                                        <i class="bi bi-check-all"></i> Sudah Masuk ke Stok
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

    </main>

    <!-- Modal Barang Keluar -->
    <div class="modal fade" id="modalBarangKeluar" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Barang Keluar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <form method="POST" id="formBarangKeluar" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="barang_id" id="barangKeluarId">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Barang</label>
                                <input type="text" id="barangKeluarNama" class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode Barang</label>
                                <input type="text" id="barangKeluarKode" class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Penerima <span class="text-danger">*</span></label>
                                <input type="text" name="nama_penerima" class="form-control"
                                    placeholder="Masukkan Nama Penerima" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                                <input type="number" name="jumlah" id="jumlahKeluar" class="form-control"
                                    placeholder="Masukkan Jumlah" required min="1">
                                <small class="text-muted">Stok tersedia: <span id="stokTersedia">0</span></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal <small class="text-muted">(Opsional)</small></label>
                                <input type="date" name="tanggal" id="tanggalKeluar" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Keterangan <small
                                        class="text-muted">(Opsional)</small></label>
                                <textarea name="keterangan" class="form-control" rows="1"
                                    placeholder="Masukkan keterangan"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Bukti <small class="text-muted">(Opsional)</small> </label>
                                <div class="border rounded p-4 text-center" style="background-color: #f8f9fa;">
                                    <input type="file" name="bukti" id="buktiBrgKeluar" class="d-none"
                                        accept="image/*,.pdf">
                                    <label for="buktiBrgKeluar" class="d-block" style="cursor: pointer;">
                                        <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #6c757d;"></i>
                                        <div class="mt-2" style="color: #6c757d; font-size: 0.875rem;">Klik untuk
                                            Upload
                                            atau tarik dan seret</div>
                                    </label>
                                    <div id="fileNameKeluar" class="mt-2 text-primary small"></div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger px-4" style="font-size: 16px;">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Filter --}}
    <div class="modal fade" id="modalFilterBarang" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form action="{{ route('pj.datakeseluruhan.index') }}" method="GET" class="modal-content">
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
                                    <option value="{{ $k->id }}" @if (request('kategori_id') == $k->id) selected @endif>
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
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('pj.datakeseluruhan.index') }}" class="btn btn-secondary">Reset Filter</a>
                    <button class="btn btn-primary" type="submit">Terapkan Filter</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
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
            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('searchInput');
                const suggestionsContainer = document.getElementById('searchSuggestions');
                let currentSuggestions = [];
                let activeSuggestionIndex = -1;
                let searchTimeout;

                if (!searchInput || !suggestionsContainer) { return; }

                function fetchSuggestions(query) {
                    if (query.length < 2) { hideSuggestions(); return; }
                    showLoading();
                    clearTimeout(searchTimeout);

                    searchTimeout = setTimeout(() => {
                        let searchUrl = `/pj/api/search-barang?q=${encodeURIComponent(query)}`;
                        fetch(searchUrl)
                            .then(response => response.json())
                            .then(data => { currentSuggestions = data; displaySuggestions(data); })
                            .catch(error => { console.error('Search error:', error); hideSuggestions(); });
                    }, 300);
                }

                function showLoading() {
                    suggestionsContainer.innerHTML = '<div class="loading-suggestion">Mencari...</div>';
                    suggestionsContainer.style.display = 'block';
                }

                function displaySuggestions(suggestions) {
                    if (suggestions.length === 0) {
                        suggestionsContainer.innerHTML = '<div class="loading-suggestion">Tidak ada barang ditemukan</div>';
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

                    suggestionsContainer.querySelectorAll('.search-suggestion-item').forEach(item => {
                        item.addEventListener('click', function () {
                            const index = parseInt(this.dataset.index);
                            selectSuggestion(index);
                        });
                    });
                }

                function hideSuggestions() { suggestionsContainer.style.display = 'none'; activeSuggestionIndex = -1; }

                function selectSuggestion(index) {
                    if (currentSuggestions[index]) {
                        const suggestion = currentSuggestions[index];
                        searchInput.value = suggestion.nama;
                        hideSuggestions();
                        document.getElementById('searchForm').submit();
                    }
                }

                function updateActiveSuggestion(suggestions) {
                    suggestions.forEach((item, index) => {
                        if (index === activeSuggestionIndex) item.classList.add('active');
                        else item.classList.remove('active');
                    });
                }

                // Event listeners
                searchInput.addEventListener('input', function () { fetchSuggestions(this.value.trim()); });

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
                    if (this.value.trim().length >= 2) { fetchSuggestions(this.value.trim()); }
                });

                document.addEventListener('click', function (e) {
                    if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) { hideSuggestions(); }
                });

                // Handle Modal Barang Keluar
                const modalBarangKeluar = document.getElementById("modalBarangKeluar");
                if (modalBarangKeluar) {
                    modalBarangKeluar.addEventListener("show.bs.modal", function (event) {
                        const button = event.relatedTarget;
                        const barangId = button.getAttribute("data-id");
                        const barangNama = button.getAttribute("data-nama");
                        const barangKode = button.getAttribute("data-kode") || '';
                        const stokTersedia = button.getAttribute("data-stok") || '0';

                        document.getElementById("barangKeluarId").value = barangId;
                        document.getElementById("barangKeluarNama").value = barangNama;
                        document.getElementById("barangKeluarKode").value = barangKode;
                        document.getElementById("stokTersedia").textContent = stokTersedia;

                        const jumlahInput = document.getElementById("jumlahKeluar");
                        jumlahInput.max = stokTersedia;

                        document.getElementById('formBarangKeluar').reset();
                        document.getElementById("barangKeluarId").value = barangId;
                        document.getElementById("barangKeluarNama").value = barangNama;
                        document.getElementById("barangKeluarKode").value = barangKode;

                        document.getElementById('fileNameKeluar').textContent = '';
                    });
                }

                // File preview handler
                const buktiBrgKeluar = document.getElementById('buktiBrgKeluar');
                if (buktiBrgKeluar) {
                    buktiBrgKeluar.addEventListener('change', function () {
                        const fileName = this.files[0]?.name || '';
                        document.getElementById('fileNameKeluar').textContent = fileName ? `File: ${fileName}` : '';
                    });
                }

                // Form submit handler
                const formBarangKeluar = document.getElementById('formBarangKeluar');
                if (formBarangKeluar) {
                    formBarangKeluar.addEventListener('submit', function (e) {
                        e.preventDefault();
                        const barangId = document.getElementById('barangKeluarId').value;
                        const jumlah = parseInt(document.getElementById('jumlahKeluar').value);
                        const stokMax = parseInt(document.getElementById('stokTersedia').textContent);

                        if (jumlah > stokMax) {
                            alert(`Jumlah tidak boleh melebihi stok tersedia (${stokMax})`);
                            return;
                        }

                        this.action = `/pj/barang-keluar/${barangId}`;
                        this.submit();
                    });
                }
            });
        </script>
    @endpush
</x-layouts.app>