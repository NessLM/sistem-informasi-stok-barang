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
        align-items: center;  /* vertikal rata tengah */
        justify-content: center; /* teks tetap center */
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

    /* biar konsisten pas di-print */
    @media print {
        .kop-surat {
            page-break-inside: avoid;
        }
        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
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
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
        5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
        9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
    ];
    $bulanRomawi = $bulan[now()->month];
    $tahun = now()->year;
    $nomorSurat = "LAPORAN-RIWAYAT/Kab-Bangka/{$bulanRomawi}/{$tahun}";
@endphp

<div style="text-align:center; margin:20px 0;">
    <h2 style="margin:0; font-weight:bold; text-decoration:underline;">
        BERITA ACARA LAPORAN KELUAR MASUK BARANG
    </h2>
    <p style="margin-top:5px; font-size:13px;">
        Nomor Surat : {{ $nomorSurat }}
    </p>
    <p style="margin-top:10px; font-size:13px;">
        Tanggal Cetak : {{ now()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
    </p>
</div>
</body>
</html>
