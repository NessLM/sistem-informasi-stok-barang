<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Riwayat Barang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header h2 {
            margin: 10px 0 5px 0;
            font-size: 16px;
            background-color: #f8f9fa;
            padding: 5px;
            border-radius: 3px;
        }
        .filter-info {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: center;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }
        .text-center {
            text-align: center;
        }
        .table-title {
            background-color: #28a745;
            color: white;
            padding: 5px;
            margin-top: 20px;
            margin-bottom: 10px;
            border-radius: 3px;
        }
        .table-title-keluar {
            background-color: #dc3545;
            color: white;
            padding: 5px;
            margin-top: 20px;
            margin-bottom: 10px;
            border-radius: 3px;
        }
        .no-data {
            text-align: center;
            padding: 10px;
            font-style: italic;
            color: #6c757d;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN RIWAYAT BARANG</h1>
        <p>Tanggal Cetak : {{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
    </div>

    @if($filter['alur_barang'] || $filter['gudang'] || $filter['periode'])
    <div class="filter-info">
        <strong>Filter yang diterapkan:</strong>
        @if($filter['alur_barang'] && $filter['alur_barang'] !== 'Semua')
            | Alur Barang: {{ $filter['alur_barang'] }}
        @endif
        @if($filter['gudang'] && $filter['gudang'] !== 'Semua')
            | Gudang: {{ $filter['gudang'] }}
        @endif
        @if($filter['periode'])
            | Periode: 
            @if($filter['periode'] == '1_minggu_terakhir') 1 Minggu Terakhir
            @elseif($filter['periode'] == '1_bulan_terakhir') 1 Bulan Terakhir
            @elseif($filter['periode'] == '1_tahun_terakhir') 1 Tahun Terakhir
            @endif
        @endif
    </div>
    @endif

    @php
        // Pisahkan data berdasarkan alur barang
        $riwayatMasuk = $riwayat->where('alur_barang', 'Masuk');
        $riwayatKeluar = $riwayat->where('alur_barang', 'Keluar');
        $alurFilter = $filter['alur_barang'] ?? 'Semua';
        
        // Set timezone ke WIB (Asia/Jakarta)
        date_default_timezone_set('Asia/Jakarta');
    @endphp

    @if($alurFilter == 'Semua' || $alurFilter == 'Masuk')
        <!-- Tabel Barang Masuk -->
        <div class="table-title">BARANG MASUK</div>
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="10%">Tanggal</th>
                    <th width="10%">Waktu</th>
                    <th width="12%">Gudang</th>
                    <th width="20%">Nama Barang</th>
                    <th width="8%">Jumlah</th>
                    <th width="15%">Bagian</th>
                    <th width="12%">Alur Barang</th>
                </tr>
            </thead>
            <tbody>
                @if($riwayatMasuk->count() > 0)
                    @foreach($riwayatMasuk as $index => $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->tanggal)->timezone('Asia/Jakarta')->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->waktu)->timezone('Asia/Jakarta')->format('H:i') }} WIB</td>
                        <td>{{ $item->gudang }}</td>
                        <td>{{ $item->nama_barang }}</td>
                        <td class="text-center">{{ $item->jumlah }}</td>
                        <td>{{ $item->bagian }}</td>
                        <td>{{ $item->alur_barang }}</td>
                    </tr>
                    @endforeach
                @else
                <tr>
                    <td colspan="8" class="no-data">Tidak ada data barang masuk</td>
                </tr>
                @endif
            </tbody>
        </table>
    @endif

    @if($alurFilter == 'Semua' || $alurFilter == 'Keluar')
        <!-- Tabel Barang Keluar -->
        <div class="table-title-keluar">BARANG KELUAR</div>
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="10%">Tanggal</th>
                    <th width="10%">Waktu</th>
                    <th width="12%">Gudang</th>
                    <th width="20%">Nama Barang</th>
                    <th width="8%">Jumlah</th>
                    <th width="15%">Bagian</th>
                    <th width="12%">Alur Barang</th>
                </tr>
            </thead>
            <tbody>
                @if($riwayatKeluar->count() > 0)
                    @foreach($riwayatKeluar as $index => $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->tanggal)->timezone('Asia/Jakarta')->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($item->waktu)->timezone('Asia/Jakarta')->format('H:i') }} WIB</td>
                        <td>{{ $item->gudang }}</td>
                        <td>{{ $item->nama_barang }}</td>
                        <td class="text-center">{{ $item->jumlah }}</td>
                        <td>{{ $item->bagian }}</td>
                        <td>{{ $item->alur_barang }}</td>
                    </tr>
                    @endforeach
                @else
                <tr>
                    <td colspan="8" class="no-data">Tidak ada data barang keluar</td>
                </tr>
                @endif
            </tbody>
        </table>
    @endif

    <div class="footer">
        <p>Dicetak oleh : Admin</p>
        {{-- <p>Waktu Cetak: {{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p> --}}
    </div>
</body>
</html>