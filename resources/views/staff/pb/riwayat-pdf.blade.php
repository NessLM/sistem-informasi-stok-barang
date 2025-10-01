<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Riwayat Barang</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
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

        /* styling tabel data barang */
        table.data {
            border-collapse: collapse;
            font-size: 14px;
            margin: auto;
            /* center tabel */
        }

        table.data th,
        table.data td {
            border: 0.5px solid #000;
            padding: 6px 10px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            word-break: break-word;
        }

        /* biar header tidak diulang */
        table.data thead {
            display: table-row-group;
            /* biar dianggap bagian biasa, tidak diulang */
        }

        table.data tfoot {
            display: table-row-group;
        }

        table.data tr {
            page-break-inside: avoid;
        }

        /* barang keluar harus di halaman baru */
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
            width: 40px;
        }

        .col-tanggal {
            width: 100px;
        }

        .col-gudang {
            width: 90px;
        }

        .col-nama {
            width: 100px;
        }

        .col-jumlah {
            width: 60px;
        }

        .col-bukti {
            width: 200px;
        }

        .col-bukti img {
            max-width: 120px;
            height: auto;
            border: 0.5px solid #ccc;
            border-radius: 4px;
        }

        /* tanda tangan */
        .ttd {
            margin-top: 60px;
            width: 100%;
            font-size: 17px;
            /* samain sama info surat */
        }

        .ttd td {
            text-align: center;
            vertical-align: top;
            padding: 10px;
            border: none;
        }

        /* biar konsisten pas di-print */
        @media print {
            .kop-surat {
                page-break-inside: avoid;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table.data th {
                -webkit-print-color-adjust: exact;
                background-color: #f2f2f2 !important;
            }

            table.data tbody tr:nth-child(even) {
                background-color: #f9f9f9 !important;
            }
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
        $nomorSurat = "LAPORAN-RIWAYAT-KELOLABARANG/SETDA-BANGKA/{$bulanRomawi}/{$tahun}";
    @endphp

    <div style="margin:20px 0;">
        <br>
        <h2 style="margin:0; font-weight:bold; text-decoration:underline; text-align:center;">
            BERITA ACARA LAPORAN RIWAYAT PENGELOLAAN BARANG
        </h2>

        <!-- Info Surat (polosan tanpa border, kiri) -->
        <div style="margin:40px 5px; font-size:16px; text-align:left;">
            <table style="border-collapse:collapse; font-size:17px;">
                <tr>
                    <td style="width:80px;">Dari</td>
                    <td style="width:40px;">:</td>
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
    </div>

    <!-- TABEL BARANG KELUAR -->
    <h3 style="margin-top:40px; text-align:center;">Barang Keluar</h3>
    <table class="data">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-tanggal">Tanggal, Waktu</th>
                <th class="col-gudang">Gudang</th>
                <th class="col-nama">Nama Barang</th>
                <th class="col-jumlah">Jumlah</th>
                <th class="col-bukti">Bukti</th>
            </tr>
        </thead>
        <tbody>
            @php
                $no = 1;
                $totalMasuk = 0;
            @endphp
            @foreach ($riwayat->where('alur_barang', 'Masuk') as $r)
                @php $totalMasuk += $r->jumlah; @endphp
                <tr>
                    <td>{{ $no++ }}</td>
                    <td>
                        {{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}<br>
                        {{ \Carbon\Carbon::parse($r->waktu)->format('H:i') }} WIB
                    </td>
                    <td>{{ $r->gudang }}</td>
                    <td>{{ $r->nama_barang }}</td>
                    <td>{{ $r->jumlah }}</td>
                    <td>
                        @if ($r->bukti)
                            <img src="{{ asset('storage/bukti/' . $r->bukti) }}" alt="Bukti">
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="4" style="text-align:right; font-weight:bold;">Total Barang Keluar</td>
                <td style="font-weight:bold;">{{ $totalMasuk }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

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
