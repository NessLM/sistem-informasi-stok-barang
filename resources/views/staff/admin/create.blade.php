{{-- resources/views/staff/admin/create.blade.php --}}

<x-layouts.app title="Dashboard • Admin" :menu="$menu">
    <main class="page-wrap">
        <h1 class="title mb-4">Tambah Barang</h1>

        {{-- Alert success --}}
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Form Tambah Barang --}}
        <form action="{{ route('barang.store') }}" method="POST" class="card p-4 shadow-sm">
            @csrf
            <div class="mb-3">
                <label class="form-label">Nama Barang</label>
                <input type="text" name="nama" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Kode Barang</label>
                <input type="text" name="kode" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Harga</label>
                <input type="number" name="harga" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Stok</label>
                <input type="number" name="stok" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Satuan</label>
                <input type="text" name="satuan" class="form-control" required>
            </div>

            <div class="mb-3">
    <label class="form-label">Kategori Barang</label>
    <select name="kategori_id" class="form-select" required>
        <option value="">-- Pilih Kategori Barang --</option>
        @foreach($kategori as $kat)
            <option value="{{ $kat->id }}">{{ $kat->nama }}</option>
        @endforeach
    </select>
</div>


            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('staff.admin.datakeseluruhan') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </main>
</x-layouts.app>
