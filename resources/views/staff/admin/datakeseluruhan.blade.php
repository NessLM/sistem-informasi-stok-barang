<x-layouts.app title="Data Keseluruhan" :menu="$menu">

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .title { font-weight: 700; margin-bottom: 20px; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; }
        .table-wrapper { margin-top: 20px; }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 2000; }
    </style>
</head>

<main class="page-wrap container py-4">
    <h1 class="title">Data Keseluruhan</h1>

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

    <section class="panel card shadow-sm p-3">
        <div class="panel-header">
            <h4 class="fw-bold">Data Gudang ATK</h4>
            <div class="btn-group">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
                    + Tambah Kategori
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahBarang">
                    + Tambah Barang
                </button>
            </div>
        </div>

        {{-- Search box --}}
        <form action="{{ route('admin.barang.index') }}" method="GET" class="input-group mt-3 mb-3">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" name="search" class="form-control" placeholder="Telusuri barang"
                   value="{{ request('search') }}">
            <button class="btn btn-outline-secondary" type="submit">Cari</button>
        </form>

        {{-- Hasil pencarian --}}
        @if(request('search'))
            <h5 class="mt-4">Hasil pencarian untuk: <b>{{ request('search') }}</b></h5>
            @if($kategori->pluck('barang')->flatten()->count() > 0)
                <table class="table table-bordered mt-3">
                    <thead class="table-secondary">
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Kode Barang</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Satuan</th>
                            <th>Kategori</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($kategori as $k)
                            @foreach($k->barang as $i => $b)
                                <tr @if($b->stok == 0) class="table-danger" @endif>
                                    <td>{{ $loop->parent->iteration }}.{{ $i+1 }}</td>
                                    <td>{{ $b->nama }}</td>
                                    <td>{{ $b->kode }}</td>
                                    <td>Rp {{ number_format($b->harga,0,',','.') }}</td>
                                    <td>{{ $b->stok }}</td>
                                    <td>{{ $b->satuan }}</td>
                                    <td>{{ $k->nama }}</td>
                                    <td>
                                        <form action="{{ route('barang.destroy', $b->kode) }}"
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Hapus barang ini?')">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-warning mt-3">
                    Tidak ada barang ditemukan untuk pencarian: <b>{{ request('search') }}</b>
                </div>
            @endif
        @endif

        {{-- Tabel Kategori & Barang (default tampilan) --}}
        @if(!request('search'))
        <div class="table-wrapper">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>KATEGORI</th>
                        <th style="width:150px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kategori as $k)
                        {{-- Baris kategori --}}
                        <tr>
                            <td class="fw-bold">{{ $k->nama }}</td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="toggleDetail({{ $k->id }})">
                                    Detail
                                </button>
                            </td>
                        </tr>

                        {{-- Detail barang --}}
                        <tr id="detail-{{ $k->id }}" style="display:none;">
                            <td colspan="2">
                                @if($k->barang->count() > 0)
                                    <table class="table table-striped table-bordered">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Barang</th>
                                                <th>Kode Barang</th>
                                                <th>Harga</th>
                                                <th>Stok</th>
                                                <th>Satuan</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($k->barang as $i => $b)
                                                <tr @if($b->stok == 0) class="table-danger" @endif>
                                                    <td>{{ $i+1 }}</td>
                                                    <td>{{ $b->nama }}</td>
                                                    <td>{{ $b->kode }}</td>
                                                    <td>Rp {{ number_format($b->harga,0,',','.') }}</td>
                                                    <td>{{ $b->stok }}</td>
                                                    <td>{{ $b->satuan }}</td>
                                                    <td>
                                                        <form action="{{ route('barang.destroy', $b->kode) }}"
                                                              method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                    onclick="return confirm('Hapus barang ini?')">
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="text-muted">Belum ada barang di kategori ini.</p>
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
<div class="modal fade" id="modalTambahKategori" tabindex="-1">
  <div class="modal-dialog">
    <form action="{{ route('admin.kategori.store') }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Form Tambah Kategori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" name="nama" class="form-control" placeholder="Masukkan nama kategori..." required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Tambah Barang -->
<div class="modal fade" id="modalTambahBarang" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form action="{{ route('admin.barang.store') }}" method="POST" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Form Tambah Barang</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nama Barang</label>
            <input type="text" name="nama" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Kode Barang</label>
            <input type="text" name="kode" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Kategori</label>
            <select name="kategori_id" class="form-select" required>
              <option value="">-- Pilih Kategori --</option>
              @foreach($kategori as $k)
                <option value="{{ $k->id }}">{{ $k->nama }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Harga / Satuan</label>
            <div class="input-group">
              <input type="number" name="harga" class="form-control" required>
              <select name="satuan" class="form-select" required>
                <option value="Pcs">Pcs</option>
                <option value="Box">Box</option>
                <option value="Pack">Pack</option>
                <option value="Rim">Rim</option>
                <option value="Unit">Unit</option>
              </select>
            </div>
          </div>
        </div>
        <input type="hidden" name="stok" value="0">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
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

// Auto-hide toast setelah 3 detik
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
