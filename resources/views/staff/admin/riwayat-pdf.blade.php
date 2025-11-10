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
            top: 10px;
        }

        .kop-logo img {
            width: 80px;
            height: 100px;
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

        /* Container untuk tabel */
        .table-container {
            width: 100%;
            overflow: hidden;
            margin: 10px 0;
        }

        /* styling tabel data barang */
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

        /* biar header tidak diulang */
        table.data thead {
            display: table-row-group;
        }

        table.data tfoot {
            display: table-row-group;
        }

        table.data tr {
            page-break-inside: avoid;
        }

        /* distribusi dan keluar harus di halaman baru - hanya untuk h3 */
        h3.distribusi,
        h3.barang-keluar {
            page-break-before: always;
        }

        /* Pastikan judul dan tabel tidak terpisah */
        h3 {
            page-break-after: avoid;
        }

        .table-container {
            page-break-before: avoid;
        }

        table.data th {
            background: #f2f2f2;
        }

        table.data tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        /* Perbaikan lebar kolom - TABEL BARANG MASUK */
        table.data.masuk .col-no {
            width: 4%;
        }

        table.data.masuk .col-tanggal {
            width: 13%;
        }

        table.data.masuk .col-gudang {
            width: 13%;
        }

        table.data.masuk .col-nama {
            width: 22%;
        }

        table.data.masuk .col-jumlah {
            width: 8%;
        }

        table.data.masuk .col-satuan {
            width: 8%;
        }

        table.data.masuk .col-keterangan {
            width: 32%;
        }

        /* Perbaikan lebar kolom - TABEL DISTRIBUSI */
        table.data.distribusi .col-no {
            width: 4%;
        }

        table.data.distribusi .col-tanggal {
            width: 13%;
        }

        table.data.distribusi .col-gudang {
            width: 13%;
        }

        table.data.distribusi .col-nama {
            width: 22%;
        }

        table.data.distribusi .col-jumlah {
            width: 8%;
        }

        table.data.distribusi .col-satuan {
            width: 8%;
        }

        table.data.distribusi .col-keterangan {
            width: 32%;
        }

        /* Perbaikan lebar kolom - TABEL BARANG KELUAR */
        table.data.keluar .col-no {
            width: 4%;
        }

        table.data.keluar .col-tanggal {
            width: 11%;
        }

        table.data.keluar .col-gudang {
            width: 11%;
        }

        table.data.keluar .col-nama {
            width: 15%;
        }

        table.data.keluar .col-jumlah {
            width: 7%;
        }

        table.data.keluar .col-satuan {
            width: 7%;
        }

        table.data.keluar .col-bagian {
            width: 14%;
        }

        table.data.keluar .col-penerima {
            width: 13%;
        }

        table.data.keluar .col-keterangan {
            width: 18%;
        }

        .col-bukti {
            width: 10%;
        }

        .col-bukti img {
            max-width: 80px;
            max-height: 60px;
            height: auto;
            border: 0.5px solid #ccc;
            border-radius: 2px;
        }

        /* tanda tangan */
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

        /* styling untuk judul dan info */
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

        /* biar konsisten pas di-print */
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

        /* Untuk landscape jika perlu */
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
            <h2>BAGIAN PERENCANAAN DAN KEUANGAN</h2>
            <p>Jalan Ahmad Yani (Jalur Dua) Sungailiat - Bangka 33211, Telp. (0717) 92536</p>
        </div>
    </div>

    <!-- JUDUL LAPORAN -->
    @php
        $bulan = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];
        $bulanRomawi = $bulan[now()->month];
        $tahun = now()->year;

        // Hitung jumlah data untuk setiap jenis
        $jumlahMasuk = $riwayat->where('alur_barang', 'Masuk PB')->count();
        $jumlahDistribusi = $riwayat->where('alur_barang', 'Distribusi PJ')->count();
        $jumlahKeluar = $riwayat->where('alur_barang', 'Keluar PJ')->count();
    @endphp

    <div class="judul-laporan">
        <h2>BERITA ACARA LAPORAN RIWAYAT PENGELOLAAN BARANG</h2>
    </div>

    <!-- Info Surat -->
    <div class="info-surat">
        <table>
            <tr>
                <td style="width:60px;">Dari</td>
                <td style="width:20px;">:</td>
                <td>Plt. Kepala Bagian Umum dan Rumah Tangga</td>
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

    <!-- TABEL BARANG MASUK -->
    @if ($jumlahMasuk > 0)
        <h3 style="margin-top:30px; text-align:center; font-size:14px;">Barang Masuk</h3>
        <div class="table-container">
            <table class="data masuk">
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th class="col-tanggal">Tanggal, Waktu</th>
                        <th class="col-gudang">Gudang</th>
                        <th class="col-nama">Nama Barang</th>
                        <th class="col-jumlah">Jumlah</th>
                        <th class="col-satuan">Satuan</th>
                        <th class="col-keterangan">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $no = 1;
                        $totalMasuk = 0;
                    @endphp
                    @foreach ($riwayat->where('alur_barang', 'Masuk PB') as $r)
                        @php $totalMasuk += $r->jumlah; @endphp
                        <tr>
                            <td>{{ $no++ }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}<br>
                                {{ \Carbon\Carbon::parse($r->waktu)->format('H:i') }} WIB
                            </td>
                            <td>
                                {{ $r->gudang }}
                                @if (isset($r->bagian_nama) && $r->bagian_nama && $r->bagian_nama != '-')
                                    <br>
                                    <span class="bagian-nama">{{ $r->bagian_nama }}</span>
                                @endif
                            </td>
                            <td>{{ $r->nama_barang }}</td>
                            <td>{{ $r->jumlah }}</td>
                            <td>{{ $r->satuan }}</td>
                            <td>{{ $r->keterangan ?? '-' }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="4" style="text-align:center; font-weight:bold;">Total Barang Masuk</td>
                        <td style="font-weight:bold; text-align:center;">{{ $totalMasuk }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <!-- TABEL DISTRIBUSI BARANG -->
    @if ($jumlahDistribusi > 0)
        <h3 style="margin-top:40px; text-align:center; font-size:14px;" class="distribusi">Distribusi Barang</h3>
        <div class="table-container">
            <table class="data distribusi">
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th class="col-tanggal">Tanggal, Waktu</th>
                        <th class="col-gudang">Gudang Tujuan</th>
                        <th class="col-nama">Nama Barang</th>
                        <th class="col-jumlah">Jumlah</th>
                        <th class="col-satuan">Satuan</th>
                        <th class="col-keterangan">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $no = 1;
                        $totalDistribusi = 0;
                    @endphp
                    @foreach ($riwayat->where('alur_barang', 'Distribusi PJ') as $r)
                        @php $totalDistribusi += $r->jumlah; @endphp
                        <tr>
                            <td>{{ $no++ }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}<br>
                                {{ \Carbon\Carbon::parse($r->waktu)->format('H:i') }} WIB
                            </td>
                            <td>{{ $r->gudang }}</td>
                            <td>{{ $r->nama_barang }}</td>
                            <td>{{ $r->jumlah }}</td>
                            <td>{{ $r->satuan }}</td>
                            <td>{{ $r->keterangan ?? '-' }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="4" style="text-align:center; font-weight:bold;">Total Distribusi Barang</td>
                        <td style="font-weight:bold; text-align:center;">{{ $totalDistribusi }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    <!-- TABEL BARANG KELUAR -->
    @if ($jumlahKeluar > 0)
        <h3 style="margin-top:40px; text-align:center; font-size:14px;" class="barang-keluar">Barang Keluar</h3>
        <div class="table-container">
            <table class="data keluar">
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th class="col-tanggal">Tanggal, Waktu</th>
                        <th class="col-gudang">Gudang Asal</th>
                        <th class="col-nama">Nama Barang</th>
                        <th class="col-jumlah">Jumlah</th>
                        <th class="col-satuan">Satuan</th>
                        <th class="col-bagian">Bagian</th>
                        <th class="col-penerima">Penerima</th>
                        <th class="col-keterangan">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $no = 1;
                        $totalKeluar = 0;
                    @endphp
                    @foreach ($riwayat->where('alur_barang', 'Keluar PJ') as $r)
                        @php $totalKeluar += $r->jumlah; @endphp
                        <tr>
                            <td>{{ $no++ }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}<br>
                                {{ \Carbon\Carbon::parse($r->waktu)->format('H:i') }} WIB
                            </td>
                            <td>{{ $r->gudang }}</td>
                            <td>{{ $r->nama_barang }}</td>
                            <td>{{ $r->jumlah }}</td>
                            <td>{{ $r->satuan }}</td>
                            <td>{{ $r->bagian }}</td>
                            <td>{{ $r->penerima }}</td>
                            <td>{{ $r->keterangan ?? '-' }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="4" style="text-align:center; font-weight:bold;">Total Barang Keluar</td>
                        <td style="font-weight:bold; text-align:center;">{{ $totalKeluar }}</td>
                        <td colspan="4"></td>
                    </tr>
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
                Plt. Kepala Bagian Umum dan Rumah Tangga <br><br><br><br><br>
                <span style="font-weight:bold; text-decoration:underline;">Nama Pejabat</span><br>
                NIP. 1975xxxxxxxxx
            </td>
        </tr>
    </table>

</body>

</html>
