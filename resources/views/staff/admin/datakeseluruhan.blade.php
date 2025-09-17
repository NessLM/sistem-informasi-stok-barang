<x-layouts.app title="Data Keseluruhan" :menu="$menu">

@php
    $barang   = $barang   ?? collect();
    $kategori = $kategori ?? collect();
    $gudang   = $gudang   ?? collect();
@endphp

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .title { font-weight: 700; margin-bottom: 20px; }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 2000; }
        @media (max-width: 576px) {
            h1.title { font-size: 1.5rem; }
            .btn { width: 100%; } /* tombol full width di hp */
            .input-group .form-control { min-width: 0; }
        }
    </style>
</head>

<main class="page-wrap container py-4">

    {{-- Toast sukses --}}
    @if(session('success'))
        <div class="toast-container">
            <div class="toast align-items-center text-bg-success border-0 show" id="successToast">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>Berhasil!</strong> {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>
    @endif

    {{-- Pesan error global --}}
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="card shadow-sm p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <h4 class="fw-bold">Data Gudang ATK</h4>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">+ Tambah Kategori</button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahBarang">+ Tambah Barang</button>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFilterBarang">
                    <i class="bi bi-funnel"></i> Filter
                </button>
            </div>
        </div>

        {{-- Search --}}
        <form action="{{ route('admin.datakeseluruhan') }}" method="GET" class="input-group mb-3">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Telusuri barang" value="{{ request('search') }}">
            <button class="btn btn-outline-secondary" type="submit">Cari</button>
        </form>

        {{-- Jika ada filter/search --}}
        @if(
            request()->filled('search') || request()->filled('kode') || 
            request()->filled('stok_min') || request()->filled('stok_max') || 
            request()->filled('kategori_id') || request()->filled('satuan') || 
            request()->filled('nomor_awal') || request()->filled('nomor_akhir') || 
            request()->filled('harga_min') || request()->filled('harga_max')
        )
            <h5 class="mt-3">Hasil</h5>
            @if($barang->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered mt-2">
                        <thead class="table-secondary">
                            <tr>
                                <th>No</th><th>Nama</th><th>Kode</th><th>Harga</th><th>Stok</th><th>Satuan</th><th>Kategori</th><th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($barang as $i => $b)
                                <tr @if($b->stok == 0) class="table-danger" @endif>
                                    <td>{{ $i+1 }}</td>
                                    <td>{{ $b->nama }}</td>
                                    <td>{{ $b->kode }}</td>
                                    <td>Rp {{ number_format($b->harga ?? 0,0,',','.') }}</td>
                                    <td>{{ $b->stok }}</td>
                                    <td>{{ $b->satuan }}</td>
                                    <td>{{ $b->kategori->nama ?? '-' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditBarang-{{ $b->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('admin.barang.destroy', $b->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus barang ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-warning">Tidak ada data ditemukan</div>
            @endif
        @endif

        {{-- Jika tidak ada filter/search --}}
        @if(
            !request()->filled('search') && !request()->filled('kode') && 
            !request()->filled('stok_min') && !request()->filled('stok_max') && 
            !request()->filled('kategori_id') && !request()->filled('satuan') && 
            !request()->filled('nomor_awal') && !request()->filled('nomor_akhir') && 
            !request()->filled('harga_min') && !request()->filled('harga_max')
        )
            <div class="table-responsive mt-3">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr><th>KATEGORI</th><th>GUDANG</th><th style="width:180px">AKSI</th></tr>
                    </thead>
                    <tbody>
                        @foreach($kategori as $k)
                            <tr>
                                <td class="fw-bold">{{ $k->nama }}</td>
                                <td>{{ $k->gudang->nama ?? '-' }}</td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-success" onclick="toggleDetail({{ $k->id }})"><i class="bi bi-eye"></i></button>
                                        <form action="{{ route('admin.kategori.destroy', $k->id) }}" method="POST" onsubmit="return confirm('Hapus kategori ini beserta barang di dalamnya?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <tr id="detail-{{ $k->id }}" style="display:none;">
                                <td colspan="3">
                                    @if($k->barang->count())
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>No</th><th>Nama</th><th>Kode</th><th>Harga</th><th>Stok</th><th>Satuan</th><th>Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($k->barang as $i => $b)
                                                        <tr @if($b->stok == 0) class="table-danger" @endif>
                                                            <td>{{ $i+1 }}</td>
                                                            <td>{{ $b->nama }}</td>
                                                            <td>{{ $b->kode }}</td>
                                                            <td>Rp {{ number_format($b->harga ?? 0,0,',','.') }}</td>
                                                            <td>{{ $b->stok }}</td>
                                                            <td>{{ $b->satuan }}</td>
                                                            <td>
                                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditBarang-{{ $b->id }}">
                                                                    <i class="bi bi-pencil"></i>
                                                                </button>
                                                                <form action="{{ route('admin.barang.destroy', $b->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus barang ini?')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </form>
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
                        <label for="gudang_id" class="form-label">Gudang</label>
                        <select class="form-select" name="gudang_id" id="gudang_id" required>
                            <option value="">-- Pilih Gudang --</option>
                            @forelse($gudang as $g)
                                <option value="{{ $g->id }}">{{ $g->nama }}</option>
                            @empty
                                <option disabled>⚠️ Tidak ada data gudang</option>
                            @endforelse
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
<div class="modal fade" id="modalTambahBarang" tabindex="-1">
  <div class="modal-dialog modal-lg">
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
            <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}" required>
            @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label>Kode</label>
            <input type="text" name="kode" class="form-control @error('kode') is-invalid @enderror" value="{{ old('kode') }}" required>
            @error('kode')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label>Kategori</label>
            <select name="kategori_id" class="form-select @error('kategori_id') is-invalid @enderror" required>
              <option value="">-- Pilih Kategori --</option>
              @foreach($kategori as $k)
                <option value="{{ $k->id }}" @if(old('kategori_id')==$k->id) selected @endif>{{ $k->nama }}</option>
              @endforeach
            </select>
            @error('kategori_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label>Harga / Satuan</label>
            <div class="input-group">
              <input type="number" step="0.01" name="harga" class="form-control @error('harga') is-invalid @enderror" value="{{ old('harga') }}">
              <select name="satuan" class="form-select">
                <option value="Pcs" @if(old('satuan')=='Pcs') selected @endif>Pcs</option>
                <option value="Box" @if(old('satuan')=='Box') selected @endif>Box</option>
                <option value="Pack" @if(old('satuan')=='Pack') selected @endif>Pack</option>
                <option value="Rim" @if(old('satuan')=='Rim') selected @endif>Rim</option>
                <option value="Unit" @if(old('satuan')=='Unit') selected @endif>Unit</option>
              </select>
            </div>
            @error('harga')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          {{-- stok default 0 --}}
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

{{-- Modal Edit Barang untuk SEMUA barang --}}
@foreach($kategori as $k)
    @foreach($k->barang as $b)
        <div class="modal fade" id="modalEditBarang-{{ $b->id }}" tabindex="-1">
          <div class="modal-dialog modal-lg">
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
                        <input type="text" name="nama" class="form-control" value="{{ $b->nama }}" required>
                    </div>
                    <div class="col-md-6">
                        <label>Kode</label>
                        <input type="text" name="kode" class="form-control" value="{{ $b->kode }}" required>
                    </div>
                    <div class="col-md-6">
                        <label>Kategori</label>
                        <select name="kategori_id" class="form-select" required>
                            @foreach($kategori as $kat) 
                                <option value="{{ $kat->id }}" @if($b->kategori_id == $kat->id) selected @endif>
                                    {{ $kat->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Harga / Satuan</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="harga" class="form-control" value="{{ $b->harga }}">
                            <select name="satuan" class="form-select">
                                <option value="Pcs" @if($b->satuan=='Pcs') selected @endif>Pcs</option>
                                <option value="Box" @if($b->satuan=='Box') selected @endif>Box</option>
                                <option value="Pack" @if($b->satuan=='Pack') selected @endif>Pack</option>
                                <option value="Rim" @if($b->satuan=='Rim') selected @endif>Rim</option>
                                <option value="Unit" @if($b->satuan=='Unit') selected @endif>Unit</option>
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
    <form action="{{ route('admin.datakeseluruhan') }}" method="GET" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Filter Barang</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label>Kode</label>
                <input type="text" name="kode" class="form-control" value="{{ request('kode') }}">
            </div>
            <div class="col-md-6">
                <label>Nama</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}">
            </div>
            <div class="col-md-6">
                <label>Stok Minimum</label>
                <input type="number" name="stok_min" class="form-control" value="{{ request('stok_min') }}">
            </div>
            <div class="col-md-6">
                <label>Stok Maksimum</label>
                <input type="number" name="stok_max" class="form-control" value="{{ request('stok_max') }}">
            </div>
            <div class="col-md-6">
                <label>Kategori</label>
                <select name="kategori_id" class="form-select">
                    <option value="">-- Semua --</option>
                    @foreach($kategori as $k)
                        <option value="{{ $k->id }}" @if(request('kategori_id')==$k->id) selected @endif>{{ $k->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label>Satuan</label>
                <input type="text" name="satuan" class="form-control" value="{{ request('satuan') }}">
            </div>
            <div class="col-md-6">
                <label>Nomor Awal</label>
                <input type="number" name="nomor_awal" class="form-control" value="{{ request('nomor_awal') }}">
            </div>
            <div class="col-md-6">
                <label>Nomor Akhir</label>
                <input type="number" name="nomor_akhir" class="form-control" value="{{ request('nomor_akhir') }}">
            </div>
            <div class="col-md-6">
                <label>Harga Minimum</label>
                <input type="number" name="harga_min" class="form-control" value="{{ request('harga_min') }}">
            </div>
            <div class="col-md-6">
                <label>Harga Maksimum</label>
                <input type="number" name="harga_max" class="form-control" value="{{ request('harga_max') }}">
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="{{ route('admin.datakeseluruhan') }}" class="btn btn-secondary">Reset</a>
        <button class="btn btn-primary" type="submit">Terapkan</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleDetail(id) {
    let el = document.getElementById('detail-' + id);
    if(el.style.display === 'none') {
        el.style.display = 'table-row';
    } else {
        el.style.display = 'none';
    }
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</x-layouts.app>
