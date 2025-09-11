{{-- resources/views/staff/admin/datakeseluruhan.blade.php --}}

<x-layouts.app title="Dashboard • Admin" :menu="$menu">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Data Keseluruhan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/DataKeseluruhan.css') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
</head>

    <main class="page-wrap">
        <h1 class="title">Data Keseluruhan</h1>

        <section class="panel">
            <div class="panel-header">
                <h2>Data Gudang ATK</h2>
                <div class="btn-group">
                    <button class="btn-primary">+ Tambah Kategori</button>
                    <button class="btn-primary">+ Tambah Jenis Barang</button>
                </div>
            </div>

            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Telusuri barang" />
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>KATEGORI</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($kategori as $k)
                            {{-- Baris kategori --}}
                            <tr>
                                <td style="width: 80%">{{ $k->nama }}</td>
                                <td style="width: 20%">
                                    <button class="btn-detail" onclick="toggleDetail({{ $k->id }})">
                                        Detail
                                    </button>
                                </td>
                            </tr>

                            {{-- Baris detail kategori --}}
                            <tr id="detail-{{ $k->id }}" style="display:none; background:#f9f9f9">
                                <td colspan="2" style="padding: 10px;">
                                    @if($k->jenisBarang->count() > 0)
                                        @foreach($k->jenisBarang as $jenis)
                                            <h4 style="margin:10px 0;">Jenis: {{ $jenis->nama }}</h4>

                                            @if($jenis->barang->count() > 0)
                                                <table style="width:100%; border:1px solid #ddd; margin-bottom:15px; border-collapse: collapse;">
                                                    <thead>
                                                        <tr style="background:#eee;">
                                                            <th style="padding:8px; border:1px solid #ddd;">No</th>
                                                            <th style="padding:8px; border:1px solid #ddd;">Nama Barang</th>
                                                            <th style="padding:8px; border:1px solid #ddd;">Kode Barang</th>
                                                            <th style="padding:8px; border:1px solid #ddd;">Harga</th>
                                                            <th style="padding:8px; border:1px solid #ddd;">Stok</th>
                                                            <th style="padding:8px; border:1px solid #ddd;">Satuan</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($jenis->barang as $i => $b)
                                                            <tr>
                                                                <td style="padding:8px; border:1px solid #ddd;">{{ $i+1 }}</td>
                                                                <td style="padding:8px; border:1px solid #ddd;">{{ $b->nama_barang }}</td>
                                                                <td style="padding:8px; border:1px solid #ddd;">{{ $b->kode ?? '-' }}</td>
                                                                <td style="padding:8px; border:1px solid #ddd;">Rp {{ number_format($b->harga ?? 0,0,',','.') }}</td>
                                                                <td style="padding:8px; border:1px solid #ddd;">{{ $b->stok ?? 0 }}</td>
                                                                <td style="padding:8px; border:1px solid #ddd;">{{ $b->satuan ?? '-' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p style="padding:6px; color:#666;">Belum ada barang pada jenis ini.</p>
                                            @endif
                                        @endforeach
                                    @else
                                        <p style="padding:6px; color:#666;">Belum ada jenis barang di kategori ini.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        function toggleDetail(id) {
            let row = document.getElementById('detail-' + id);
            row.style.display = (row.style.display === 'none') ? '' : 'none';
        }
    </script>

</x-layouts.app>
