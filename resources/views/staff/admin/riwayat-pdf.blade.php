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
        .filter-info {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN RIWAYAT BARANG</h1>
        <p>Tanggal Cetak: {{ date('d/m/Y H:i') }}</p>
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

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Gudang</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Bagian</th>
                <th>Alur Barang</th>
            </tr>
        </thead>
        <tbody>
            @foreach($riwayat as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($item->waktu)->format('H:i') }}</td>
                <td>{{ $item->gudang }}</td>
                <td>{{ $item->nama_barang }}</td>
                <td class="text-center">{{ $item->jumlah }}</td>
                <td>{{ $item->bagian }}</td>
                <td>{{ $item->alur_barang }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak oleh: Admin</p>
    </div>
</body>
</html>