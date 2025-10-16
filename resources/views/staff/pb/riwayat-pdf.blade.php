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
            padding: 0;
        }

        .kop-surat {
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 3px double #000;
            padding: 10px 0;
            margin-bottom: 20px;
            position: relative;
            page-break-inside: avoid;
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
            font-size: 12px;
            width: 100%;
            margin-bottom: 20px;
        }

        table.data th,
        table.data td {
            border: 0.5px solid #000;
            padding: 6px 8px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        table.data th {
            background: #f2f2f2;
            font-weight: bold;
        }

        table.data tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .col-no {
            width: 30px;
        }

        .col-tanggal {
            width: 80px;
        }

        .col-gudang {
            width: 100px;
        }

        .col-nama {
            width: 120px;
        }

        .col-jumlah {
            width: 50px;
        }

        /* barang keluar harus di halaman baru */
        .barang-keluar {
            page-break-before: always;
            margin-top: 30px;
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

        .judul-laporan {
            text-align: center;
            margin: 20px 0;
        }

        .judul-laporan h2 {
            margin: 0;
            font-weight: bold;
            text-decoration: underline;
        }

        .info-surat {
            margin: 20px 0;
            font-size: 14px;
        }

        .info-surat table {
            border-collapse: collapse;
        }

        .info-surat td {
            padding: 2px 5px;
            vertical-align: top;
        }

        .total-row {
            font-weight: bold;
            background-color: #e8e8e8 !important;
        }

        /* biar konsisten pas di-print */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin: 0;
                padding: 0;
            }

            .kop-surat {
                page-break-inside: avoid;
            }

            table.data th {
                background-color: #f2f2f2 !important;
            }

            table.data tbody tr:nth-child(even) {
                background-color: #f9f9f9 !important;
            }

            .total-row {
                background-color: #e8e8e8 !important;
            }

            .barang-keluar {
                page-break-before: always;
                margin-top: 0;
            }
        }

        @page {
            size: A4 portrait;
            margin: 20mm;
        }
    </style>
</head>

<body>
    <div class="kop-surat">
        <div class="kop-logo">
            <!-- Logo akan ditampilkan jika ada, jika tidak tetap berjalan -->
            @if (file_exists(public_path('assets/banner/logo_bupati.png')))
                <img src="{{ public_path('assets/banner/logo_bupati.png') }}" alt="Logo Bupati">
            @else
                <div style="width: 80px; height: 100px; border: 1px solid #000; text-align: center; line-height: 100px;">
                    LOGO
                </div>
            @endif
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
        $bulanRomawi = $bulan[now()->month] ?? 'XII';
        $tahun = now()->year;
    @endphp

    <div class="judul-laporan">
        <h2>BERITA ACARA LAPORAN RIWAYAT PENGELOLAAN BARANG</h2>
    </div>

    <!-- Info Surat -->
    <div class="info-surat">
        <table>
            <tr>
                <td style="width: 80px;">Dari</td>
                <td style="width: 10px;">:</td>
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
            @if (isset($filter) && ($filter['periode'] || $filter['alur_barang']))
                <tr>
                    <td>Filter</td>
                    <td>:</td>
                    <td>
                        @if ($filter['alur_barang'] && $filter['alur_barang'] !== 'Semua')
                            Alur: {{ $filter['alur_barang'] }}
                        @endif
                        @if ($filter['periode'])
                            @if ($filter['alur_barang'] && $filter['alur_barang'] !== 'Semua')
                                ,
                            @endif
                            @if ($filter['periode'] == 'custom' && $filter['dari_tanggal'] && $filter['sampai_tanggal'])
                                Periode: {{ \Carbon\Carbon::parse($filter['dari_tanggal'])->format('d/m/Y') }} -
                                {{ \Carbon\Carbon::parse($filter['sampai_tanggal'])->format('d/m/Y') }}
                            @else
                                Periode: {{ str_replace('_', ' ', $filter['periode']) }}
                            @endif
                        @endif
                    </td>
                </tr>
            @endif
        </table>
    </div>

    <!-- TABEL BARANG MASUK -->
    @php
        $riwayatMasuk = $riwayat->where('alur_barang', 'Masuk');
    @endphp

    @if ($riwayatMasuk->count() > 0)
        <h3 style="margin-top:20px; text-align:center;">Barang Masuk</h3>
        <table class="data">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-tanggal">Tanggal, Waktu</th>
                    <th class="col-gudang">Gudang</th>
                    <th class="col-nama">Nama Barang</th>
                    <th class="col-jumlah">Jumlah</th>
                    <th class="col-keterangan">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $no = 1;
                    $totalMasuk = 0;
                @endphp
                @foreach ($riwayatMasuk as $r)
                    @php $totalMasuk += $r->jumlah; @endphp
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}<br>
                            {{ $r->waktu }} WIB
                        </td>
                        <td>{{ $r->gudang }}</td>
                        <td>{{ $r->nama_barang }}</td>
                        <td>{{ $r->jumlah }}</td>
                        <td>{{ $r->keterangan }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="5" style="text-align:right;">Total Barang Masuk</td>
                    <td>{{ $totalMasuk }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <!-- TABEL BARANG KELUAR -->
    @php
        $riwayatKeluar = $riwayat->where('alur_barang', 'Keluar');
    @endphp

    @if ($riwayatKeluar->count() > 0)
        @if ($riwayatMasuk->count() > 0)
            <div class="barang-keluar">
        @endif
        <h3 style="text-align:center;">Barang Keluar</h3>
        <table class="data">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-tanggal">Tanggal, Waktu</th>
                    <th class="col-gudang">Gudang Tujuan</th>
                    <th class="col-nama">Nama Barang</th>
                    <th class="col-jumlah">Jumlah</th>
                    <th class="col-keterangan">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $no = 1;
                    $totalKeluar = 0;
                @endphp
                @foreach ($riwayatKeluar as $r)
                    @php $totalKeluar += $r->jumlah; @endphp
                    <tr>
                        <td>{{ $no++ }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}<br>
                            {{ $r->waktu }} WIB
                        </td>
                        <td>
                            {{ $r->gudang_tujuan }}
                            @if (isset($r->kategori_tujuan) && $r->kategori_tujuan)
                            @endif
                        </td>
                        <td>{{ $r->nama_barang }}</td>
                        <td>{{ $r->jumlah }}</td>
                        <td>{{ $r->keterangan }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4" style="text-align:right;">Total Barang Keluar</td>
                    <td>{{ $totalKeluar }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        @if ($riwayatMasuk->count() > 0)
            </div>
        @endif
    @endif

    <!-- Kondisi jika tidak ada data sama sekali -->
    @if ($riwayatMasuk->count() == 0 && $riwayatKeluar->count() == 0)
        <div style="text-align: center; margin: 50px 0;">
            <h3>Tidak Ada Data Riwayat Barang</h3>
            <p>Tidak ditemukan data riwayat barang untuk periode yang dipilih.</p>
        </div>
    @endif

    <!-- TANDA TANGAN -->
    <!-- Hanya tampilkan tanda tangan jika ada data -->
    @if ($riwayatMasuk->count() > 0 || $riwayatKeluar->count() > 0)
        <table class="ttd">
            <tr>
                <td style="width:60%;"></td>
                <td style="width:40%;">
                    Sungailiat, {{ now()->format('d F Y') }} <br>
                    Plt. Kepala Bagian Umum dan Rumah Tangga <br><br><br><br><br>
                    <span style="font-weight:bold; text-decoration:underline;">Nama Pejabat</span><br>
                    NIP. 1975xxxxxxxxx
                </td>
            </tr>
        </table>
    @endif

</body>

</html>
