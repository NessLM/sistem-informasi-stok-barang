{{-- resources/views/staff/pj/datakeseluruhan.blade.php --}}

@php
    // Controller sudah mengirim $selectedGudang
    $gudangName = $selectedGudang->nama ?? '-';
    $pageHeading = "Data {$gudangName}";
@endphp

<x-layouts.app title="Data Gudang" :menu="$menu" :heading="$pageHeading">

    @php
        $barang = $barang ?? collect();
        $kategori = $kategori ?? collect();
        $barangHabis = $barangHabis ?? collect();
        $barangMasuk = $barangMasuk ?? collect();
        $lowThreshold = $lowThreshold ?? 10;
        $ringkasanCounts = $ringkasanCounts ?? ['ok' => 0, 'low' => 0, 'empty' => 0];
    @endphp

    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/staff/pj/data_keseluruhan_pj.css') }}">
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

                    <div
                        style="flex-shrink: 0; width: 42px; height: 42px; border-radius: 50%;
                                                                                                           background: {{ $bgColor }}; display: flex; align-items: center;
                                                                                                           justify-content: center; box-shadow: 0 2px 8px {{ $bgColor }}40;">
                        <i class="bi {{ $iconClass }}" style="color: #fff; font-size: 22px;"></i>
                    </div>

                    <div style="flex: 1;">
                        <div
                            style="font-weight: 600; font-size: 16px; margin-bottom: 4px;
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
                <form action="{{ route('pj.datakeseluruhan.index') }}" method="GET" class="input-group"
                    id="searchForm">
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
                    request()->filled('satuan'))
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
                                    <th>Harga</th>
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
                                            <td>
                                                Rp {{ number_format($b->harga ?? 0, 0, ',', '.') }} {{-- ✅ TAMPILIN HARGA --}}
                                            </td>
                                            <td>{{ $b->satuan }}</td>
                                            <td>{{ $b->kategori->nama ?? '-' }}</td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2 ">
                                                    {{-- Tombol Barang Keluar --}}
                                                    <button type="button" class="btn btn-danger btn-sm"
                                                        data-bs-toggle="modal" data-bs-target="#modalBarangKeluar"
                                                        data-id="{{ $b->kode }}"
                                                        data-nama="{{ $b->nama }}"
                                                        data-kode="{{ $b->kode }}"
                                                        data-stok="{{ $stokTersedia }}"
                                                        data-harga="{{ $b->harga ?? 0 }}">
                                                        <i class="bi bi-box-arrow-right"></i> Barang Keluar
                                                    </button>

                                                    {{-- ✅ TOMBOL KEMBALIKAN BARU --}}
                                                    <button type="button"
                                                        class="btn btn-warning btn-sm btn-kembalikan-stok"
                                                        data-bs-toggle="modal" data-bs-target="#modalKembalikanStok"
                                                        data-kode="{{ $b->kode }}"
                                                        data-nama="{{ $b->nama }}"
                                                        data-stok="{{ $stokTersedia }}"
                                                        data-harga="{{ $b->harga ?? 0 }}"
                                                        data-satuan="{{ $b->satuan }}">
                                                        <i class="bi bi-arrow-counterclockwise"></i> Kembalikan
                                                    </button>
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

            {{-- Jika tidak ada filter/search --}}
            @if (
                !request()->filled('search') &&
                    !request()->filled('kode') &&
                    !request()->filled('stok_min') &&
                    !request()->filled('stok_max') &&
                    !request()->filled('kategori_id') &&
                    !request()->filled('satuan'))
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
                                            <button class="btn btn-sm btn-success"
                                                onclick="toggleDetail({{ $k->id }})"><i class="bi bi-eye"></i>
                                                Lihat</button>
                                        </div>
                                    </td>
                                </tr>

                                <tr id="detail-{{ $k->id }}" style="display:none;">
                                    <td colspan="2">
                                        @php
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
                                                            <th>Harga</th>
                                                            <th>Satuan</th>
                                                            <th>Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($barangFiltered as $item)
                                                            @php
                                                                $stokTersedia = $item->stok_tersedia ?? 0;
                                                            @endphp
                                                            <tr
                                                                @if ($stokTersedia < 10) class="row-low-stock" @endif>
                                                                <td>{{ $item->kode }}</td>
                                                                <td>{{ $item->nama }}</td>
                                                                <td>{{ $stokTersedia }}</td>
                                                                <td>Rp
                                                                    {{ number_format($item->harga ?? 0, 0, ',', '.') }}
                                                                </td>
                                                                <td>{{ $item->satuan }}</td>
                                                                <td>
                                                                    <div class="d-flex flex-wrap gap-2">
                                                                        {{-- Tombol Barang Keluar --}}
                                                                        <button type="button"
                                                                            class="btn btn-danger btn-sm"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#modalBarangKeluar"
                                                                            data-id="{{ $item->kode }}"
                                                                            data-nama="{{ $item->nama }}"
                                                                            data-kode="{{ $item->kode }}"
                                                                            data-stok="{{ $stokTersedia }}"
                                                                            data-harga="{{ $item->harga }}">
                                                                            <i class="bi bi-box-arrow-right"></i>
                                                                            Barang Keluar
                                                                        </button>

                                                                        {{-- ✅ TOMBOL KEMBALIKAN BARU --}}
                                                                        <button type="button"
                                                                            class="btn btn-warning btn-sm btn-kembalikan-stok"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#modalKembalikanStok"
                                                                            data-kode="{{ $item->kode }}"
                                                                            data-nama="{{ $item->nama }}"
                                                                            data-stok="{{ $stokTersedia }}"
                                                                            data-harga="{{ $item->harga ?? 0 }}"
                                                                            data-satuan="{{ $item->satuan }}">
                                                                            <i
                                                                                class="bi bi-arrow-counterclockwise"></i>
                                                                            Kembalikan
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-muted">Tidak ada barang pada kategori ini (atau semua barang
                                                telah
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

        {{-- SECTION BARANG HABIS --}}
        @if (($barangHabis ?? collect())->count())
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
                                    <th style="width:140px">Harga</th>
                                    <th style="width:220px">Kategori</th>
                                    <th style="width:120px">Satuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($barangHabis as $item)
                                    @php
                                        // handle mode gudang yang mungkin nggak punya harga
                                        $harga = property_exists($item, 'harga') ? $item->harga : null;
                                    @endphp
                                    <tr class="row-empty">
                                        <td>{{ $item->kode }}</td>
                                        <td>{{ $item->nama }}</td>
                                        <td>
                                            @if (!is_null($harga) && $harga !== '')
                                                Rp {{ number_format($harga, 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
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


        {{-- SECTION BARANG MASUK --}}
        @if (($barangMasuk ?? collect())->count())
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
                                            @if ($status === 'confirmed')
                                                <span class="badge bg-success badge-normal" style="font-size: 14px;">
                                                    <i class="bi bi-check-circle"></i> Dikonfirmasi
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark badge-normal"
                                                    style="font-size: 14px;">
                                                    <i class="bi bi-clock-history"></i> Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            {!! \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') !!}
                                            <br>
                                            {!! \Carbon\Carbon::parse($item->tanggal)->format('H:i') !!} WIB
                                        </td>
                                        <td>
                                            {{ $item->nama_barang }}
                                            <br>
                                            <small class="text-muted">{{ $item->kode_barang }}</small>
                                        </td>
                                        <td class="text-center">{{ $item->jumlah }}</td>
                                        <td>{{ $item->satuan }}</td>
                                        <td>{{ $item->keterangan ?? '-' }}</td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column gap-2">
                                                @if ($item->bukti)
                                                    <a href="{{ asset('storage/' . $item->bukti) }}" target="_blank"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-file-earmark-text"></i> Lihat Bukti
                                                    </a>
                                                @endif

                                                @if ($status === 'pending')
                                                    {{-- Tombol Konfirmasi dengan Modal - TAMBAHKAN data-harga --}}
                                                    <button type="button"
                                                        class="btn btn-sm btn-success w-100 btn-konfirmasi"
                                                        data-id="{{ $item->id }}"
                                                        data-nama="{{ $item->nama_barang }}"
                                                        data-jumlah="{{ $item->jumlah }}"
                                                        data-satuan="{{ $item->satuan }}"
                                                        data-kode="{{ $item->kode_barang }}"
                                                        data-harga="{{ $item->harga ?? 0 }}">
                                                        <i class="bi bi-check-circle"></i> Konfirmasi
                                                    </button>

                                                    {{-- Tombol Kembalikan dengan Modal --}}
                                                    <button type="button"
                                                        class="btn btn-sm btn-return w-100 btn-kembalikan"
                                                        data-id="{{ $item->id }}"
                                                        data-nama="{{ $item->nama_barang }}"
                                                        data-jumlah="{{ $item->jumlah }}"
                                                        data-satuan="{{ $item->satuan }}"
                                                        data-harga="{{ $item->harga ?? 0 }}">   {{-- ✅ kirim harga ke JS --}}
                                                        <i class="bi bi-arrow-left-circle"></i> Kembalikan
                                                    </button>
                                                @else
                                                    <span class="badge bg-success text-wrap badge-normal"
                                                        style="font-size: 12px;">
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

    {{-- ============================================= --}}
    {{-- MODAL KEMBALIKAN STOK (TAMBAHKAN SETELAH MODAL BARANG KELUAR) --}}
    {{-- ============================================= --}}

    <!-- Modal Kembalikan Stok ke PB -->
    <div class="modal fade" id="modalKembalikanStok" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold">
                        <i class="bi bi-arrow-counterclockwise text-warning"></i>
                        Kembalikan Barang ke PB Stok
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-2">
                    {{-- Info Warning --}}
                    <div class="alert alert-warning d-flex align-items-start mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0" style="font-size: 20px;"></i>
                        <div>
                            <strong>Perhatian!</strong><br>
                            Barang akan dikembalikan ke PB Stok dengan batch yang sama (kode barang, bagian, dan harga).
                        </div>
                    </div>

                    <form method="POST" id="formKembalikanStok" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="kode_barang" id="kembalikanKodeBarang">
                        <input type="hidden" name="harga" id="kembalikanHarga">

                        <div class="row g-3">
                            {{-- Info Barang --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Barang</label>
                                <input type="text" id="kembalikanNamaBarang" class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Kode Barang</label>
                                <input type="text" id="kembalikanKode" class="form-control" readonly>
                            </div>

                            {{-- Stok Info --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Stok Tersedia</label>
                                <div class="input-group">
                                    <input type="text" id="kembalikanStokTersedia" class="form-control" readonly>
                                    <span class="input-group-text" id="kembalikanSatuanDisplay"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Harga Satuan</label>
                                <input type="text" id="kembalikanHargaDisplay" class="form-control" readonly>
                            </div>

                            {{-- Input Jumlah --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Jumlah Dikembalikan <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="jumlah" id="jumlahKembalikan" class="form-control"
                                    placeholder="Masukkan Jumlah" required min="1">
                                <small class="text-muted">
                                    Maksimal: <span id="maxKembalikan">0</span> <span id="maxKembalikanSatuan"></span>
                                </small>
                            </div>

                            {{-- Keterangan --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Keterangan <small class="text-muted">(Opsional)</small>
                                </label>
                                <textarea name="keterangan" id="kembalikanKeterangan" class="form-control" rows="2"
                                    placeholder="Alasan pengembalian..."></textarea>
                            </div>

                            {{-- Upload Bukti --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Bukti Pengembalian <small class="text-muted">(Opsional)</small>
                                </label>
                                <div class="border rounded p-4 text-center" style="background-color: #f8f9fa;">
                                    <input type="file" name="bukti" id="buktiKembalikan" class="d-none"
                                        accept="image/*,.pdf">
                                    <label for="buktiKembalikan" class="d-block" style="cursor: pointer;">
                                        <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #6c757d;"></i>
                                        <div class="mt-2" style="color: #6c757d; font-size: 0.875rem;">
                                            Klik untuk Upload atau tarik dan seret
                                        </div>
                                    </label>
                                    <div id="fileNameKembalikan" class="mt-2 text-primary small"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                                Batal
                            </button>
                            <button type="submit" class="btn btn-warning px-4" id="btnSimpanKembalikan">
                                <span id="btnTextKembalikan">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Kembalikan
                                </span>
                                <span id="btnLoaderKembalikan" class="d-none">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"
                                        aria-hidden="true"></span>
                                    Memproses...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Barang Masuk --}}
    <div class="modal fade" id="modalKonfirmasi" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center pt-0">
                    {{-- Icon --}}
                    <div class="mb-3">
                        <div class="modal-confirm-icon mx-auto"
                            style="width: 64px; height: 64px; background: #d4edda; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-check-circle-fill" style="font-size: 32px; color: #28a745;"></i>
                        </div>
                    </div>

                    {{-- Title --}}
                    <h5 class="mb-3" style="font-weight: 600;">Konfirmasi Barang Masuk?</h5>

                    {{-- Message --}}
                    <p class="text-muted mb-4">
                        Setelah dikonfirmasi, barang akan masuk ke stok dan tidak bisa dikembalikan.
                    </p>

                    {{-- Details Box --}}
                    <div class="border rounded p-3 mb-4 text-start" style="background: #f8f9fa;">
                        <div class="d-flex justify-content-between mb-2">
                            <span style="color: #495057; font-weight: 500;">Nama Barang:</span>
                            <span style="color: #212529; font-weight: 600;" id="konfirmasiNama">-</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span style="color: #495057; font-weight: 500;">Kode Barang:</span>
                            <span style="color: #212529; font-weight: 600;" id="konfirmasiKode">-</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span style="color: #495057; font-weight: 500;">Jumlah:</span>
                            <span style="color: #212529; font-weight: 600;" id="konfirmasiJumlah">-</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span style="color: #495057; font-weight: 500;">Harga Satuan:</span>
                            <span style="color: #212529; font-weight: 600;" id="konfirmasiHarga">-</span>
                        </div>
                        <hr class="my-2">
                    </div>

                    {{-- Warning Alert --}}
                    <div class="alert alert-warning d-flex align-items-start text-start mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0" style="font-size: 20px;"></i>
                        <div>
                            <strong>Perhatian!</strong><br>
                            Pastikan barang sudah diterima dengan baik sebelum melakukan konfirmasi.
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    {{-- Action Buttons --}}
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-success px-4" id="btnKonfirmasiOk">
                            <span id="btnTextKonfirmasi">
                                <i class="bi bi-check-circle me-1"></i> Konfirmasi
                            </span>
                            <span id="btnLoaderKonfirmasi" class="d-none">
                                <span class="spinner-border spinner-border-sm me-1" role="status"
                                    aria-hidden="true"></span>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Kembalikan Barang --}}
    <div class="modal fade" id="modalKembalikan" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center pt-0">
                    {{-- Icon --}}
                    <div class="mb-3">
                        <div class="modal-confirm-icon mx-auto"
                            style="width: 64px; height: 64px; background: #fff3cd; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-arrow-left-circle-fill" style="font-size: 32px; color: #ffc107;"></i>
                        </div>
                    </div>

                    {{-- Title --}}
                    <h5 class="mb-3" style="font-weight: 600;">Kembalikan Barang?</h5>

                    {{-- Message --}}
                    <p class="text-muted mb-4">
                        Barang akan dikembalikan ke PB Stok dan akan dihapus dari daftar barang masuk.
                    </p>

                    {{-- Details Box --}}
                    <div class="border rounded p-3 mb-4 text-start" style="background: #f8f9fa;">
                        <div class="d-flex justify-content-between mb-2">
                            <span style="color: #495057; font-weight: 500;">Nama Barang:</span>
                            <span style="color: #212529; font-weight: 600;" id="kembalikanNama">-</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span style="color: #495057; font-weight: 500;">Jumlah:</span>
                            <span style="color: #212529; font-weight: 600;" id="kembalikanJumlah">-</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span style="color: #495057; font-weight: 500;">Harga Satuan:</span>
                            <span style="color: #212529; font-weight: 600;" id="kembalikanHargaBarangMasuk">-</span>
                        </div>
                    </div>

                    {{-- Warning Alert --}}
                    <div class="alert alert-warning d-flex align-items-start text-start mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0" style="font-size: 20px;"></i>
                        <div>
                            <strong>Perhatian!</strong><br>
                            Barang yang dikembalikan akan kembali ke PB Stok untuk didistribusikan ulang.
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-warning px-4" id="btnKembalikanOk">
                            <span id="btnTextKembalikan">
                                <i class="bi bi-arrow-left-circle me-1"></i> Kembalikan
                            </span>
                            <span id="btnLoaderKembalikan" class="d-none">
                                <span class="spinner-border spinner-border-sm me-1" role="status"
                                    aria-hidden="true"></span>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                        <input type="hidden" name="harga_dipilih" id="hargaDipilih"> {{-- ✅ TAMBAHKAN INI --}}

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Barang</label>
                                <input type="text" id="barangKeluarNama" class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Kode Barang</label>
                                <input type="text" id="barangKeluarKode" class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Penerima <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="nama_penerima" class="form-control"
                                    placeholder="Masukkan Nama Penerima" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Jumlah <span
                                        class="text-danger">*</span></label>
                                <input type="number" name="jumlah" id="jumlahKeluar" class="form-control"
                                    placeholder="Masukkan Jumlah" required min="1">
                                <small class="text-muted">Stok tersedia: <span id="stokTersedia">0</span></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tanggal <small
                                        class="text-muted">(Opsional)</small></label>
                                <input type="date" name="tanggal" id="tanggalKeluar" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Keterangan <small
                                        class="text-muted">(Opsional)</small></label>
                                <textarea name="keterangan" class="form-control" rows="1" placeholder="Masukkan keterangan"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Bukti <small
                                        class="text-muted">(Opsional)</small> </label>
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
                            <button type="button" class="btn btn-secondary px-4"
                                data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger px-4" id="btnSimpanBarangKeluar"
                                style="font-size: 16px;">
                                <span id="btnTextKeluar">Simpan</span>
                                <span id="btnLoaderKeluar" class="d-none">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"
                                        aria-hidden="true"></span>
                                    Memproses...
                                </span>
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
                                    <option value="{{ $k->id }}"
                                        @if (request('kategori_id') == $k->id) selected @endif>
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
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log('✅ Script loaded'); // Debug

                // ========================================
                // HELPER FUNCTIONS
                // ========================================

                function formatRupiah(angka) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(angka);
                }

                function setButtonLoading(btnId, textId, loaderId, isLoading) {
                    const btn = document.getElementById(btnId);
                    const text = document.getElementById(textId);
                    const loader = document.getElementById(loaderId);

                    if (btn && text && loader) {
                        btn.disabled = isLoading;
                        if (isLoading) {
                            text.classList.add('d-none');
                            loader.classList.remove('d-none');
                        } else {
                            text.classList.remove('d-none');
                            loader.classList.add('d-none');
                        }
                    }
                }

                // ========================================
                // MODAL KEMBALIKAN STOK
                // ========================================

                const modalKembalikanStok = document.getElementById('modalKembalikanStok');
                if (modalKembalikanStok) {
                    console.log('✅ Modal Kembalikan ditemukan'); // Debug

                    modalKembalikanStok.addEventListener('show.bs.modal', function(event) {
                        console.log('🔵 Modal Kembalikan dibuka'); // Debug

                        const button = event.relatedTarget;
                        console.log('Button:', button); // Debug

                        // Ambil data dari button
                        const kodeBarang = button.getAttribute('data-kode');
                        const namaBarang = button.getAttribute('data-nama');
                        const stokTersedia = parseFloat(button.getAttribute('data-stok'));
                        const harga = parseFloat(button.getAttribute('data-harga') || 0);
                        const satuan = button.getAttribute('data-satuan') || '';

                        console.log('📦 Data dari button:', {
                            kodeBarang,
                            namaBarang,
                            stokTersedia,
                            harga,
                            satuan
                        }); // Debug

                        // Set values ke form
                        document.getElementById('kembalikanKodeBarang').value = kodeBarang;
                        document.getElementById('kembalikanHarga').value = harga;
                        document.getElementById('kembalikanNamaBarang').value = namaBarang;
                        document.getElementById('kembalikanKode').value = kodeBarang;
                        document.getElementById('kembalikanStokTersedia').value = stokTersedia;
                        document.getElementById('kembalikanSatuanDisplay').textContent = satuan;
                        document.getElementById('kembalikanHargaDisplay').value = formatRupiah(harga);
                        document.getElementById('maxKembalikan').textContent = stokTersedia;
                        document.getElementById('maxKembalikanSatuan').textContent = satuan;

                        // Set max pada input jumlah
                        const inputJumlah = document.getElementById('jumlahKembalikan');
                        inputJumlah.max = stokTersedia;
                        inputJumlah.value = '';

                        // Reset form
                        document.getElementById('kembalikanKeterangan').value = '';
                        document.getElementById('fileNameKembalikan').textContent = '';
                        const fileInput = document.getElementById('buktiKembalikan');
                        if (fileInput) fileInput.value = '';

                        // Reset button state
                        setButtonLoading('btnSimpanKembalikan', 'btnTextKembalikan', 'btnLoaderKembalikan',
                            false);

                        console.log('✅ Form berhasil diisi'); // Debug
                    });
                } else {
                    console.error('❌ Modal Kembalikan tidak ditemukan!');
                }

                // File preview untuk Kembalikan
                const buktiKembalikan = document.getElementById('buktiKembalikan');
                if (buktiKembalikan) {
                    buktiKembalikan.addEventListener('change', function() {
                        const fileName = this.files[0]?.name || '';
                        document.getElementById('fileNameKembalikan').textContent =
                            fileName ? `📄 ${fileName}` : '';
                    });
                }

                // Form submit untuk Kembalikan
                const formKembalikanStok = document.getElementById('formKembalikanStok');
                if (formKembalikanStok) {
                    formKembalikanStok.addEventListener('submit', function(e) {
                        e.preventDefault();

                        const kodeBarang = document.getElementById('kembalikanKodeBarang').value;
                        const jumlah = parseInt(document.getElementById('jumlahKembalikan').value);
                        const stokMax = parseInt(document.getElementById('maxKembalikan').textContent);
                        const namaBarang = document.getElementById('kembalikanNamaBarang').value;

                        if (isNaN(jumlah) || jumlah <= 0) {
                            alert('Jumlah harus lebih dari 0');
                            return;
                        }

                        if (jumlah > stokMax) {
                            alert(`Jumlah tidak boleh melebihi stok tersedia (${stokMax})`);
                            return;
                        }

                        if (!confirm(
                                `Apakah Anda yakin ingin mengembalikan ${jumlah} unit "${namaBarang}" ke PB Stok?`
                                )) {
                            return;
                        }

                        setButtonLoading('btnSimpanKembalikan', 'btnTextKembalikan', 'btnLoaderKembalikan',
                            true);
                        const baseKembalikanKePb = "{{ url('pj/kembalikan-ke-pb') }}";
                        this.action = baseKembalikanKePb + '/' + kodeBarang;
                        this.submit();
                    });
                }

                // Validasi input jumlah real-time
                const jumlahKembalikan = document.getElementById('jumlahKembalikan');
                if (jumlahKembalikan) {
                    jumlahKembalikan.addEventListener('input', function() {
                        const max = parseInt(this.max);
                        const value = parseInt(this.value);

                        if (value > max) {
                            this.value = max;
                            alert(`Jumlah maksimal adalah ${max}`);
                        }

                        if (value < 1 && this.value !== '') {
                            this.value = 1;
                        }
                    });
                }

                // ========================================
                // MODAL BARANG KELUAR
                // ========================================

                const modalBarangKeluar = document.getElementById("modalBarangKeluar");
                if (modalBarangKeluar) {
                    modalBarangKeluar.addEventListener("show.bs.modal", function(event) {
                        const button = event.relatedTarget;
                        const barangId = button.getAttribute("data-id");
                        const barangNama = button.getAttribute("data-nama");
                        const barangKode = button.getAttribute("data-kode");
                        const stokTersedia = button.getAttribute("data-stok");
                        const hargaDipilih = button.getAttribute("data-harga");

                        document.getElementById("barangKeluarId").value = barangId;
                        document.getElementById("barangKeluarNama").value = barangNama;
                        document.getElementById("barangKeluarKode").value = barangKode;
                        document.getElementById("stokTersedia").textContent = stokTersedia;
                        document.getElementById("hargaDipilih").value = hargaDipilih;
                        document.getElementById("jumlahKeluar").max = stokTersedia;

                        setButtonLoading('btnSimpanBarangKeluar', 'btnTextKeluar', 'btnLoaderKeluar', false);

                        document.getElementById('formBarangKeluar').reset();
                        document.getElementById("barangKeluarId").value = barangId;
                        document.getElementById("barangKeluarNama").value = barangNama;
                        document.getElementById("barangKeluarKode").value = barangKode;
                        document.getElementById("hargaDipilih").value = hargaDipilih;
                        document.getElementById('fileNameKeluar').textContent = '';
                    });
                }

                const buktiBrgKeluar = document.getElementById('buktiBrgKeluar');
                if (buktiBrgKeluar) {
                    buktiBrgKeluar.addEventListener('change', function() {
                        const fileName = this.files[0]?.name || '';
                        document.getElementById('fileNameKeluar').textContent = fileName ? `File: ${fileName}` :
                            '';
                    });
                }

                const formBarangKeluar = document.getElementById('formBarangKeluar');
                if (formBarangKeluar) {
                    formBarangKeluar.addEventListener('submit', function(e) {
                        e.preventDefault();

                        const barangId = document.getElementById('barangKeluarId').value;
                        const jumlah = parseInt(document.getElementById('jumlahKeluar').value);
                        const stokMax = parseInt(document.getElementById('stokTersedia').textContent);

                        if (jumlah > stokMax) {
                            alert(`Jumlah tidak boleh melebihi stok tersedia (${stokMax})`);
                            return;
                        }

                        setButtonLoading('btnSimpanBarangKeluar', 'btnTextKeluar', 'btnLoaderKeluar', true);
                        const baseBarangKeluar = "{{ url('pj/barang-keluar') }}";
                        this.action = baseBarangKeluar + '/' + barangId;
                        this.submit();
                    });
                }

                // ========================================
                // MODAL KONFIRMASI & KEMBALIKAN BARANG MASUK
                // ========================================

                let currentKonfirmasiId = null;
                let currentKembalikanId = null;
                let modalKonfirmasiBS = null;
                let modalKembalikanBS = null;

                const modalKonfirmasiEl = document.getElementById('modalKonfirmasi');
                if (modalKonfirmasiEl) {
                    modalKonfirmasiBS = new bootstrap.Modal(modalKonfirmasiEl);
                }

                const modalKembalikanEl = document.getElementById('modalKembalikan');
                if (modalKembalikanEl) {
                    modalKembalikanBS = new bootstrap.Modal(modalKembalikanEl);
                }

                document.querySelectorAll('.btn-konfirmasi').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.dataset.id;
                        const nama = this.dataset.nama;
                        const kode = this.dataset.kode;
                        const jumlah = parseFloat(this.dataset.jumlah);
                        const satuan = this.dataset.satuan;
                        const harga = parseFloat(this.dataset.harga || 0);

                        currentKonfirmasiId = id;

                        document.getElementById('konfirmasiNama').textContent = nama;
                        document.getElementById('konfirmasiKode').textContent = kode;
                        document.getElementById('konfirmasiJumlah').textContent = `${jumlah} ${satuan}`;
                        document.getElementById('konfirmasiHarga').textContent = formatRupiah(harga);

                        setButtonLoading('btnKonfirmasiOk', 'btnTextKonfirmasi', 'btnLoaderKonfirmasi',
                            false);

                        if (modalKonfirmasiBS) {
                            modalKonfirmasiBS.show();
                        }
                    });
                });

                document.querySelectorAll('.btn-kembalikan').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id     = this.dataset.id;
                        const nama   = this.dataset.nama;
                        const jumlah = this.dataset.jumlah;
                        const satuan = this.dataset.satuan;
                        const harga  = parseFloat(this.dataset.harga || 0);   // ✅ ambil harga

                        currentKembalikanId = id;

                        document.getElementById('kembalikanNama').textContent   = nama;
                        document.getElementById('kembalikanJumlah').textContent = `${jumlah} ${satuan}`;
                        document.getElementById('kembalikanHargaBarangMasuk').textContent =
                            harga > 0 ? formatRupiah(harga) : '-';             // ✅ tampilkan harga

                        setButtonLoading('btnKembalikanOk', 'btnTextKembalikan', 'btnLoaderKembalikan', false);

                        if (modalKembalikanBS) {
                            modalKembalikanBS.show();
                        }
                    });
                });

                document.getElementById('btnKonfirmasiOk')?.addEventListener('click', function() {
                    if (currentKonfirmasiId) {
                        setButtonLoading('btnKonfirmasiOk', 'btnTextKonfirmasi', 'btnLoaderKonfirmasi', true);

                        const form = document.createElement('form');
                        form.method = 'POST';
                        const baseKonfirmasiMasuk = "{{ url('pj/konfirmasi-barang-masuk') }}";
                        form.action = baseKonfirmasiMasuk + '/' + currentKonfirmasiId;

                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';
                        form.appendChild(csrf);

                        document.body.appendChild(form);
                        form.submit();
                    }
                });

                document.getElementById('btnKembalikanOk')?.addEventListener('click', function() {
                    if (currentKembalikanId) {
                        setButtonLoading('btnKembalikanOk', 'btnTextKembalikan', 'btnLoaderKembalikan', true);

                        const form = document.createElement('form');
                        form.method = 'POST';
                        const baseKembalikanBarangMasuk = "{{ url('pj/kembalikan-barang') }}";
                        form.action = baseKembalikanBarangMasuk + '/' + currentKembalikanId;

                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';
                        form.appendChild(csrf);

                        document.body.appendChild(form);
                        form.submit();
                    }
                });

                // ========================================
                // AUTOCOMPLETE SEARCH
                // ========================================

                const searchInput = document.getElementById('searchInput');
                const suggestionsContainer = document.getElementById('searchSuggestions');
                let currentSuggestions = [];
                let searchTimeout;

                if (searchInput && suggestionsContainer) {
                    function fetchSuggestions(query) {
                        if (query.length < 2) {
                            suggestionsContainer.style.display = 'none';
                            return;
                        }

                        suggestionsContainer.innerHTML = '<div class="loading-suggestion">Mencari...</div>';
                        suggestionsContainer.style.display = 'block';
                        clearTimeout(searchTimeout);

                        searchTimeout = setTimeout(() => {
                            const baseSearchBarangPj = "{{ url('pj/api/search-barang') }}";

                            fetch(`${baseSearchBarangPj}?q=${encodeURIComponent(query)}`)
                                .then(response => response.json())
                                .then(data => {
                                    currentSuggestions = data;
                                    displaySuggestions(data);
                                })
                                .catch(error => {
                                    console.error('Search error:', error);
                                    suggestionsContainer.style.display = 'none';
                                });
                        }, 300);
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
                            <small>Kategori: ${item.kategori} | Stok: ${item.stok} |
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
                                if (currentSuggestions[index]) {
                                    searchInput.value = currentSuggestions[index].nama;
                                    suggestionsContainer.style.display = 'none';
                                    document.getElementById('searchForm').submit();
                                }
                            });
                        });
                    }

                    searchInput.addEventListener('input', function() {
                        fetchSuggestions(this.value.trim());
                    });

                    searchInput.addEventListener('focus', function() {
                        if (this.value.trim().length >= 2) {
                            fetchSuggestions(this.value.trim());
                        }
                    });

                    document.addEventListener('click', function(e) {
                        if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                            suggestionsContainer.style.display = 'none';
                        }
                    });
                }

                // ========================================
                // TOGGLE DETAIL
                // ========================================

                window.toggleDetail = function(id) {
                    let el = document.getElementById('detail-' + id);
                    if (el.style.display === 'none') {
                        el.style.display = 'table-row';
                    } else {
                        el.style.display = 'none';
                    }
                };
            });
        </script>
    @endpush
</x-layouts.app>
