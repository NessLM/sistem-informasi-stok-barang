<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Riwayat Barang</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            margin: 0;
            padding: 0 10px;
        }

        .kop-surat {
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 3px double #000;
            padding: 10px 0;
            margin-top: -30px;
            margin-bottom: 20px;
            position: relative;
        }

        .kop-logo {
            position: absolute;
            left: 20px;
        }

        .kop-logo img {
            width: 60px;
            height: 75px;
        }

        .kop-text {
            width: 100%;
            text-align: center;
        }

        .kop-text h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }

        .kop-text h2 {
            margin: 2px 0;
            font-size: 20px;
            font-weight: bold;
        }

        .kop-text p {
            margin: 2px 0;
            font-size: 14px;
        }

        .table-container {
            width: 100%;
            overflow: hidden;
            margin: 10px 0;
        }

        table.data {
            border-collapse: collapse;
            font-size: 12px;
            width: 100%;
            table-layout: fixed;
        }

        table.data th,
        table.data td {
            border: 0.5px solid #000;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
        }

        table.data thead {
            display: table-row-group;
        }

        table.data tfoot {
            display: table-row-group;
        }

        table.data tr {
            page-break-inside: avoid;
        }

        .barang-keluar {
            page-break-before: always;
        }

        table.data th {
            background: #f2f2f2;
        }

        table.data tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .col-no {
            width: 5%;
        }

        .col-tanggal {
            width: 15%;
        }

        .col-nama {
            width: 20%;
        }

        .col-jumlah {
            width: 8%;
        }

        .col-keterangan {
            width: 20%;
        }

        .ttd {
            margin-top: 60px;
            width: 100%;
            font-size: 14px;
        }

        .ttd td {
            text-align: center;
            vertical-align: top;
            padding: 10px;
            border: none;
        }

        .judul-laporan {
            margin: 20px 0;
        }

        .judul-laporan h2 {
            margin: 0;
            font-weight: bold;
            text-decoration: underline;
            text-align: center;
            font-size: 16px;
        }

        .info-surat {
            margin: 20px 0;
            font-size: 13px;
            text-align: left;
        }

        .info-surat table {
            border-collapse: collapse;
            font-size: 13px;
        }

        .info-surat td {
            padding: 2px 5px;
            vertical-align: top;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin: 0;
                padding: 0 15px;
            }

            .kop-surat {
                page-break-inside: avoid;
            }

            table.data th {
                -webkit-print-color-adjust: exact;
                background-color: #f2f2f2 !important;
            }

            table.data tbody tr:nth-child(even) {
                background-color: #f9f9f9 !important;
            }

            .table-container {
                width: 100%;
            }

            table.data {
                width: 100%;
            }
        }

        @page {
            size: portrait;
            margin: 15mm;
        }
    </style>
</head>

<body>
    <div class="kop-surat">
        <div class="kop-logo">
            <img src="assets/banner/logo_bupati.png" alt="Logo Bupati">
        </div>
        <div class="kop-text">
            <h1>PEMERINTAH KABUPATEN BANGKA</h1>
            <h2>SEKRETARIAT DAERAH</h2>
            <p>Jalan Ahmad Yani (Jalur Dua) Sungailiat - Bangka 33211, Telp. (0717) 92536</p>
        </div>
    </div>

    <div class="judul-laporan">
        <h2>BERITA ACARA LAPORAN RIWAYAT PENGELOLAAN BARANG</h2>
    </div>

    <!-- Info Surat -->
    <div class="info-surat">
        <table>
            <tr>
                <td style="width:60px;">Dari</td>
                <td style="width:20px;">:</td>
                <td>Kepala {{ $filter['gudang'] }}</td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td>:</td>
                <td>{{ now()->format('d F Y') }}</td>
            </tr>
            <tr>
                <td>Sifat</td>
                <td>:</td>
                <td>Laporan</td>
            </tr>
            <tr>
                <td>Hal</td>
                <td>:</td>
                <td>Laporan Riwayat Pengelolaan Barang</td>
            </tr>
        </table>
    </div>

    @php
        $riwayatMasuk = $riwayat->where('alur_barang', 'Masuk');
        $riwayatKeluar = $riwayat->where('alur_barang', 'Keluar');
    @endphp

    <!-- TABEL BARANG MASUK -->
    @if($riwayatMasuk->count() > 0 || $filter['alur_barang'] == 'Masuk' || !isset($filter['alur_barang']) || $filter['alur_barang'] == 'Semua')
    <h3 style="margin-top:30px; text-align:center; font-size:14px;">Barang Masuk</h3>
    <div class="table-container">
        <table class="data">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-tanggal">Tanggal, Waktu</th>
                    <th class="col-nama">Nama Barang</th>
                    <th class="col-jumlah">Jumlah</th>
                    <th class="col-nama">Satuan</th>
                    <th class="col-keterangan">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $no = 1;
                    $totalMasuk = 0;
                @endphp
                @forelse($riwayatMasuk as $r)
                    @php $totalMasuk += $r->jumlah; @endphp
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}<br>
                            {{ \Carbon\Carbon::parse($r->waktu)->format('H:i') }} WIB
                        </td>
                        <td>{{ $r->nama_barang }}</td>
                        <td>{{ $r->jumlah }}</td>
                        <td>{{ $r->satuan }}</td>
                        <td>{{ $r->keterangan ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;">Tidak ada data barang masuk</td>
                    </tr>
                @endforelse
                @if($riwayatMasuk->count() > 0)
                <tr>
                    <td colspan="3" style="text-align:center; font-weight:bold;">Total Barang Masuk</td>
                    <td style="font-weight:bold;">{{ $totalMasuk }}</td>
                    <td colspan="2"></td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif

    <!-- TABEL BARANG KELUAR -->
    @if($riwayatKeluar->count() > 0 || $filter['alur_barang'] == 'Keluar' || !isset($filter['alur_barang']) || $filter['alur_barang'] == 'Semua')
    <h3 style="margin-top:40px; text-align:center; font-size:14px;" class="@if($riwayatMasuk->count() > 0) barang-keluar @endif">Barang Keluar</h3>
    <div class="table-container">
        <table class="data">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-tanggal">Tanggal, Waktu</th>
                    <th class="col-nama">Nama Barang</th>
                    <th class="col-jumlah">Jumlah</th>
                    <th class="col-nama">Satuan</th>
                    <th class="col-nama">Nama Penerima</th>
                    <th class="col-keterangan">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $no = 1;
                    $totalKeluar = 0;
                @endphp
                @forelse($riwayatKeluar as $r)
                    @php $totalKeluar += $r->jumlah; @endphp
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}<br>
                            {{ \Carbon\Carbon::parse($r->waktu)->format('H:i') }} WIB
                        </td>
                        <td>{{ $r->nama_barang }}</td>
                        <td>{{ $r->jumlah }}</td>
                        <td>{{ $r->satuan }}</td>
                        <td>{{ $r->nama_penerima ?? '-' }}</td>
                        <td>{{ $r->keterangan ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;">Tidak ada data barang keluar</td>
                    </tr>
                @endforelse
                @if($riwayatKeluar->count() > 0)
                <tr>
                    <td colspan="3" style="text-align:center; font-weight:bold;">Total Barang Keluar</td>
                    <td style="font-weight:bold;">{{ $totalKeluar }}</td>
                    <td colspan="3"></td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif

    <!-- TANDA TANGAN -->
    <table class="ttd">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%;">
                Sungailiat, {{ now()->format('d F Y') }} <br>
                Kepala {{ $filter['gudang'] }} <br><br><br><br><br>
                <span style="font-weight:bold; text-decoration:underline;">.................................</span><br>
                NIP. .............................
            </td>
        </tr>
    </table>

</body>

</html>