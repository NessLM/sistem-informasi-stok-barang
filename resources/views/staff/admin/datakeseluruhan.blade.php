<x-layouts.app title="Data Keseluruhan" :menu="$menu">

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .title { font-weight: 700; margin-bottom: 20px; }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 2000; }
    </style>
</head>

<main class="page-wrap container py-4">
    <h1 class="title">Data Keseluruhan</h1>

    @php
        if (!isset($barang) || $barang === null) $barang = collect();
        if (!isset($kategori) || $kategori === null) $kategori = collect();
        if (!isset($gudang) || $gudang === null) $gudang = collect();
    @endphp

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

    <section class="card shadow-sm p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold">Data Gudang ATK</h4>
            <div class="btn-group">
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
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditBarang-{{ $b->kode }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.barang.destroy', $b->kode) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus barang ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
            <div class="table-wrapper mt-3">
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
                                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditBarang-{{ $b->kode }}">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <form action="{{ route('admin.barang.destroy', $b->kode) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus barang ini?')">
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

{{-- Modal Tambah Kategori --}}
<div class="modal fade" id="modalTambahKategori" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('admin.kategori.store') }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Tambah Kategori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label>Nama Kategori</label>
            <input type="text" name="nama" class="form-control" placeholder="Nama kategori" required>
        </div>
        <div class="mb-3">
            <label>Pilih Gudang</label>
            <select name="gudang_id" class="form-select" required>
                <option value="">-- Pilih Gudang --</option>
                @foreach($gudang as $g)
                    <option value="{{ $g->id }}">{{ $g->nama }}</option>
                @endforeach
            </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit">Simpan</button>
      </div>
    </form>
  </div>
</div>

{{-- Modal Tambah Barang --}}
<div class="modal fade" id="modalTambahBarang" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form action="{{ route('admin.barang.store') }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header"><h5 class="modal-title">Tambah Barang</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-6"><label>Nama</label><input type="text" name="nama" class="form-control" required></div>
            <div class="col-md-6"><label>Kode</label><input type="text" name="kode" class="form-control" required></div>
            <div class="col-md-6">
                <label>Kategori</label>
                <select name="kategori_id" class="form-select" required>
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($kategori as $k) <option value="{{ $k->id }}">{{ $k->nama }}</option> @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label>Harga / Satuan</label>
                <div class="input-group">
                    <input type="number" step="0.01" name="harga" class="form-control" required>
                    <select name="satuan" class="form-select" required>
                        <option value="Pcs">Pcs</option><option value="Box">Box</option><option value="Pack">Pack</option><option value="Rim">Rim</option><option value="Unit">Unit</option>
                    </select>
                </div>
            </div>
        </div>
        <input type="hidden" name="stok" value="0">
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan</button></div>
    </form>
  </div>
</div>

{{-- Modal Edit Barang --}}
@if($barang->count())
    @foreach($barang as $b)
    <div class="modal fade" id="modalEditBarang-{{ $b->kode }}" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.barang.update', $b->kode) }}" method="POST" class="modal-content">
          @csrf
          @method('PUT')
          <div class="modal-header"><h5 class="modal-title">Edit Barang: {{ $b->nama }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6"><label>Nama</label><input type="text" name="nama" class="form-control" value="{{ $b->nama }}" required></div>
                <div class="col-md-6"><label>Kode</label><input type="text" class="form-control" value="{{ $b->kode }}" disabled></div>
                <div class="col-md-6">
                    <label>Kategori</label>
                    <select name="kategori_id" class="form-select" required>
                        @foreach($kategori as $k) <option value="{{ $k->id }}" @if($b->kategori_id == $k->id) selected @endif>{{ $k->nama }}</option> @endforeach
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
                <div class="col-md-6"><label>Stok</label><input type="number" name="stok" class="form-control" value="{{ $b->stok }}"></div>
            </div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan Perubahan</button></div>
        </form>
      </div>
    </div>
    @endforeach
@endif

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
                <label>Jumlah (stok min / max)</label>
                <div class="d-flex gap-2">
                    <input type="number" name="stok_min" class="form-control" value="{{ request('stok_min') }}">
                    <input type="number" name="stok_max" class="form-control" value="{{ request('stok_max') }}">
                </div>
            </div>

            <div class="col-md-6">
                <label>Harga (min / max)</label>
                <div class="d-flex gap-2">
                    <input type="number" step="0.01" name="harga_min" class="form-control" value="{{ request('harga_min') }}">
                    <input type="number" step="0.01" name="harga_max" class="form-control" value="{{ request('harga_max') }}">
                </div>
            </div>

            <div class="col-md-6">
                <label>Kategori</label>
                <select name="kategori_id" class="form-select">
                    <option value="">-- Semua --</option>
                    @foreach($kategori as $k)
                        <option value="{{ $k->id }}" @if(request('kategori_id')==$k->id) selected @endif>
                            {{ $k->nama }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label>Satuan</label>
                <select name="satuan" class="form-select">
                    <option value="">-- Semua --</option>
                    <option value="Pcs" @if(request('satuan')=='Pcs') selected @endif>Pcs</option>
                    <option value="Box" @if(request('satuan')=='Box') selected @endif>Box</option>
                    <option value="Pack" @if(request('satuan')=='Pack') selected @endif>Pack</option>
                    <option value="Rim" @if(request('satuan')=='Rim') selected @endif>Rim</option>
                    <option value="Unit" @if(request('satuan')=='Unit') selected @endif>Unit</option>
                </select>
            </div>

            <div class="col-md-6">
                <label>Nomor (id)</label>
                <div class="d-flex gap-2">
                    <input type="number" name="nomor_awal" class="form-control" value="{{ request('nomor_awal') }}">
                    <input type="number" name="nomor_akhir" class="form-control" value="{{ request('nomor_akhir') }}">
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="{{ route('admin.datakeseluruhan') }}" class="btn btn-danger">Reset</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleDetail(id) {
    let row = document.getElementById('detail-' + id);
    row.style.display = (row.style.display === 'none') ? '' : 'none';
}
document.addEventListener("DOMContentLoaded", function(){
    let toastEl = document.getElementById('successToast');
    if (toastEl) {
        setTimeout(() => {
            let toast = bootstrap.Toast.getOrCreateInstance(toastEl);
            toast.hide();
        }, 3000);
    }
});
</script>

</x-layouts.app>
