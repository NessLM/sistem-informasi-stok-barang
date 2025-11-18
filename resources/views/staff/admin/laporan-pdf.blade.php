<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Riwayat Barang</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            line-height: 1.4;
        }

        .kop-surat {
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 3px double #000;
            padding: 15px 0;
            margin-bottom: 10px;
            position: relative;
        }

        .kop-logo {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
        }

        .kop-logo img {
            width: 75px;
            height: 90px;
            object-fit: contain;
        }

        .kop-text {
            width: 100%;
            text-align: center;
            padding: 0 100px;
        }

        .kop-text h1 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .kop-text h2 {
            margin: 2px 0;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .kop-text p {
            margin: 3px 0 0 0;
            font-size: 11px;
        }

        /* SECTION TANGGAL DAN TUJUAN */
        .tanggal-surat {
            text-align: right;
            margin: 30px 0 30px 0;
            font-size: 12px;
        }

        .tujuan-surat {
            margin: 30px 0;
            font-size: 12px;
            line-height: 1.5;
        }

        .tujuan-surat p {
            margin: 2px 0;
        }

        /* JUDUL SURAT PENGANTAR */
        .judul-surat {
            text-align: center;
            margin: 30px 0 20px 0;
        }

        .judul-surat h3 {
            margin: 0 0 5px 0;
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
            letter-spacing: 1px;
        }

        .judul-surat .nomor-surat {
            font-size: 12px;
            margin: 8px 0 0 0;
        }

        /* TABEL URAIAN */
        .table-uraian {
            margin: 20px 0;
            width: 100%;
        }

        .table-uraian table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .table-uraian th,
        .table-uraian td {
            border: 1px solid #000;
            padding: 8px 6px;
            vertical-align: top;
        }

        .table-uraian th {
            text-align: center;
            font-weight: bold;
            background-color: transparent;
            font-size: 11px;
        }

        .table-uraian .col-no {
            width: 6%;
            text-align: center;
        }

        .table-uraian .col-uraian {
            width: 54%;
            text-align: left;
        }

        .table-uraian .col-jumlah {
            width: 15%;
            text-align: center;
        }

        .table-uraian .col-keterangan {
            width: 25%;
            text-align: left;
        }

        .table-uraian td {
            line-height: 1.6;
        }

        .table-uraian .uraian-text {
            text-align: left;
            line-height: 1.7;
        }

        .table-uraian .uraian-text p {
            margin: 0 0 8px 0;
            text-indent: -10px;
            padding-left: 10px;
        }

        .table-uraian .uraian-text p:last-child {
            margin-bottom: 0;
        }

        /* SECTION TTD */
        .section-ttd {
            margin-top: 60px;
            width: 100%;
            text-align: center;
        }

        .ttd-jabatan {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 60px;
            line-height: 1.5;
        }

        .ttd-nama {
            font-size: 12px;
            font-weight: bold;
            text-decoration: underline;
            margin: 5px 0;
        }

        .ttd-nip {
            font-size: 11px;
            margin: 3px 0;
        }

        /* SECTION PENERIMA */
        .section-penerima {
            margin-top: 60px;
            padding-top: 0;
        }

        .penerima-info {
            font-size: 11px;
            line-height: 2;
        }

        .penerima-info table {
            border: none;
            border-collapse: collapse;
        }

        .penerima-info td {
            border: none;
            padding: 2px 0;
        }

        .penerima-info .label {
            width: 110px;
            vertical-align: top;
        }

        .penerima-info .titik-dua {
            width: 15px;
            text-align: center;
        }

        .penerima-info .garis-bawah {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 200px;
            text-align: center;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin: 0;
                padding: 15px;
            }

            .kop-surat {
                page-break-inside: avoid;
            }
        }

        @page {
            size: A4 portrait;
            margin: 20mm;
        }


        
    </style>
</head>

<body>
    {{-- KOP SURAT --}}
    <div class="kop-surat">
        <div class="kop-logo">
            <img src="{{ asset('assets/banner/logo_bupati.png') }}" alt="Logo Bupati">
        </div>
        <div class="kop-text">
            <h1>PEMERINTAH KABUPATEN BANGKA</h1>
            <h2>SEKRETARIAT DAERAH</h2>
            <h2>BAGIAN PERENCANAAN DAN KEUANGAN</h2>
            <p>Jalan Ahmad Yani ( Jalur Dua) Sungailiat - Bangka 33211, Telp. ( 0717 ) 92536</p>
        </div>
    </div>

    {{-- TANGGAL SURAT --}}
    @php
        use Carbon\Carbon;
        $tanggalSekarang = Carbon::now();
        $tanggalFormatted = $tanggalSekarang->locale('id')->isoFormat('D MMMM YYYY');
    @endphp
    
    <div class="tanggal-surat">
        Sungailiat, {{ $tanggalFormatted }}
    </div>

    {{-- TUJUAN SURAT --}}
    <div class="tujuan-surat">
        <p>Kepada Yth.</p>
        <p>>BPPKAD Kab. Bangka</p>
        <p>c.q. Bagian Aset</p>
        <p>di -</p>
        <p><strong>SUNGAILIAT</strong></p>
    </div>

    {{-- JUDUL SURAT --}}
    @php
        $bulanRomawi = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $bulanSekarang = $bulanRomawi[$tanggalSekarang->month];
        $tahunSekarang = $tanggalSekarang->year;
    @endphp

    <div class="judul-surat">
        <h3><strong>SURAT PENGANTAR</h3>
        <p class="nomor-surat">Nomor : 045.2/ <span style="text-decoration: underline;">&nbsp;&nbsp;O8L&nbsp;&nbsp;</span> /{{ $bulanSekarang }}/{{ $tahunSekarang }}</p>
    </div>

    {{-- TABEL URAIAN --}}
    <div class="table-uraian">
        <table>
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-uraian">Uraian</th>
                    <th class="col-jumlah">Jumlah</th>
                    <th class="col-keterangan">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="col-no"></td>
                    <td class="col-uraian">
                        <div class="uraian-text">
                            <p>- Penyampaian Laporan Berita Acara Rekonsiliasi Internal Data BMD berupa Aset pada Sekretariat Daerah</p>
                            <p>- Penyampaian Laporan Berita Acara Pemeriksaan Persediaan Barang Pakai Habis Stock Opname per 30 Juni {{ $year ?? $tahunSekarang }} pada Sekretariat Daerah</p>
                        </div>
                    </td>
                    <td class="col-jumlah">1 ( satu )<br>Berkas</td>
                    <td class="col-keterangan">Disampaikan dengan hormat untuk dipergunakan sebagaimana mestinya, terimakasih.</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- SECTION TTD --}}
    <div class="section-ttd">
        <div class="ttd-jabatan">
            Kepala Bagian Perencanaan dan Keuangan<br>
            Setda Kabupaten Bangka,
        </div>
        <div class="ttd-nama">Tati Djumiyati, SE, M. Si</div>
        <div class="ttd-nip">NIP 19720512 198803 2 008</div>
    </div>

    {{-- SECTION PENERIMA --}}
    <div class="section-penerima">
        <div class="penerima-info">
            <table>
                <tr>
                    <td class="label">Diterima Tgl</td>
                    <td class="titik-dua">:</td>
                    <td><span class="garis-bawah"></span></td>
                </tr>
                <tr>
                    <td class="label">Nama</td>
                    <td class="titik-dua">:</td>
                    <td><span class="garis-bawah"></span></td>
                </tr>
                <tr>
                    <td class="label">Tanda Tangan</td>
                    <td class="titik-dua">:</td>
                    <td><span class="garis-bawah"></span></td>
                </tr>
            </table>
        </div>
    </div>

</body>

</html>