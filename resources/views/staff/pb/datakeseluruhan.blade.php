<x-layouts.app title="Data Keseluruhan" :menu="$menu">

@php
    $barang   = $barang   ?? collect();
    $kategori = $kategori ?? collect();
    $gudang   = $gudang   ?? collect();
    $role     = auth()->check() ? auth()->user()->role->nama : null;
@endphp

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<div class="container mt-4">
    <h3>Data Barang</h3>

    <!-- Filter Gudang -->
    <form method="GET" class="mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <select name="gudang_id" class="form-select">
                    <option value="">-- Semua Gudang --</option>
                    @foreach($gudang as $gd)
                        <option value="{{ $gd->id }}" {{ request('gudang_id') == $gd->id ? 'selected' : '' }}>
                            {{ $gd->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                       placeholder="Cari barang...">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <!-- Hanya admin bisa tambah kategori & barang -->
    @if($role === 'admin')
        <div class="mb-3">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tambahKategori">Tambah Kategori</button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahBarang">Tambah Barang</button>
        </div>
    @endif

    <!-- Tabel Data Barang -->
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Gudang</th>
                    <th>Stok</th>
                    <th>Satuan</th>
                    <th>Harga</th>
                    <th width="240">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($barang as $item)
                    <tr>
                        <td>{{ $item->kode }}</td>
                        <td>{{ $item->nama }}</td>
                        <td>{{ $item->kategori->nama ?? '-' }}</td>
                        <td>{{ $item->kategori->gudang->nama ?? '-' }}</td>
                        <td>{{ $item->stok }}</td>
                        <td>{{ $item->satuan }}</td>
                        <td>Rp {{ number_format($item->harga,0,',','.') }}</td>
                        <td>
                            @if($role === 'admin')
                                <!-- Admin bisa edit & hapus -->
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#editBarang{{ $item->id }}">Edit</button>
                                <form action="{{ route('admin.barang.destroy', $item->kode) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Yakin hapus barang ini?')">Hapus</button>
                                </form>
                            @elseif($role === 'pb')
                                <!-- PB bisa barang masuk & distribusi -->
                                <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#barangMasuk{{ $item->id }}">Barang Masuk</button>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#distribusiBarang{{ $item->id }}">Distribusi</button>
                            @endif
                        </td>
                    </tr>

                    <!-- Modal Barang Masuk (PB) -->
                    <div class="modal fade" id="barangMasuk{{ $item->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form action="{{ route('pb.barangmasuk.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="barang_id" value="{{ $item->id }}">
                                <div class="modal-content">
                                    <div class="modal-header"><h5>Barang Masuk - {{ $item->nama }}</h5></div>
                                    <div class="modal-body">
                                        <label>Jumlah Masuk</label>
                                        <input type="number" class="form-control" name="jumlah" min="1" required>
                                        <label class="mt-2">Keterangan</label>
                                        <textarea class="form-control" name="keterangan"></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-success">Simpan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal Distribusi (PB) -->
                    <div class="modal fade" id="distribusiBarang{{ $item->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form action="{{ route('pb.distribusi.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="barang_id" value="{{ $item->id }}">
                                <div class="modal-content">
                                    <div class="modal-header"><h5>Distribusi - {{ $item->nama }}</h5></div>
                                    <div class="modal-body">
                                        <label>Tujuan Gudang</label>
                                        <select name="tujuan_gudang_id" class="form-control" required>
                                            @foreach($gudang as $gd)
                                                @if($gd->id !== $item->kategori->gudang_id)
                                                    <option value="{{ $gd->id }}">{{ $gd->nama }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <label class="mt-2">Jumlah Distribusi</label>
                                        <input type="number" class="form-control" name="jumlah" min="1" required>
                                        <label class="mt-2">Keterangan</label>
                                        <textarea class="form-control" name="keterangan"></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Distribusi</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Modal Edit Barang (Admin) -->
                    <div class="modal fade" id="editBarang{{ $item->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form action="{{ route('admin.barang.update', $item->kode) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-content">
                                    <div class="modal-header"><h5>Edit Barang</h5></div>
                                    <div class="modal-body">
                                        <label>Nama Barang</label>
                                        <input type="text" name="nama" value="{{ $item->nama }}" class="form-control" required>
                                        <label class="mt-2">Harga</label>
                                        <input type="number" name="harga" value="{{ $item->harga }}" class="form-control">
                                        <label class="mt-2">Satuan</label>
                                        <input type="text" name="satuan" value="{{ $item->satuan }}" class="form-control">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-warning">Update</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="8" class="text-center">Tidak ada barang ditemukan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</x-layouts.app>
