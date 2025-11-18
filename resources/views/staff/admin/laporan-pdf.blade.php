{{-- resources/views/staff/admin/laporan-pdf.blade.php --}}
{{-- 
  STRUKTUR HALAMAN LAPORAN:
  
  HALAMAN 1: Header + Info Surat + Barang Masuk
  - Kop Surat dengan Logo
  
  HALAMAN 2: Distribusi Barang
  - Header (diulang untuk konsistensi)
  
  HALAMAN 3: Barang Keluar
  - Header (diulang untuk konsistensi)
 
--}}

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        /* CLASS UNTUK PENANDA HALAMAN BARU */
        /* Distribusi dan Keluar akan menjadi halaman terpisah */
        h3.distribusi,
        h3.barang-keluar {
            page-break-before: always;
        }

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

        /* TABEL BARANG MASUK */
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

        /* TABEL DISTRIBUSI */
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

        /* TABEL BARANG KELUAR */
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
    {{-- ========================================
         HALAMAN 1: KOP + INFO + BARANG MASUK
         ======================================== --}}

    {{-- KOP SURAT --}}
    <div class="kop-surat">
        <div class="kop-logo">
            <img src="{{ asset('assets/banner/logo_bupati.png') }}" alt="Logo Bupati">

        </div>
        <div class="kop-text">
            <h1>PEMERINTAH KABUPATEN BANGKA</h1>
            <h2>SEKRETARIAT DAERAH</h2>
            <h2>BAGIAN PERENCANAAN DAN KEUANGAN</h2>
            <p>Jalan Ahmad Yani (Jalur Dua) Sungailiat - Bangka 33211, Telp. (0717) 92536</p>
        </div>
    </div>

    {{-- JUDUL LAPORAN --}}
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
        
    @endphp

    {{-- TABEL BARANG MASUK (HALAMAN 1) --}}


    {{-- ========================================
         HALAMAN 2
         ======================================== --}}


    {{-- ========================================
         HALAMAN 3
         ======================================== --}}



</body>

</html>
