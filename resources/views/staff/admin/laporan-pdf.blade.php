{{-- resources/views/staff/admin/laporan-pdf.blade.php --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Riwayat Barang</title>
    <link rel="stylesheet" href="{{ public_path('assets/css/staff/admin/laporan_pdf.css') }}">


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
        <p> BPPKAD Kab. Bangka</p>
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
        <h3>SURAT PENGANTAR</h3>
        <p class="nomor-surat">Nomor : 045.2/ <span> &nbsp; &nbsp;</span>
            /{{ $bulanSekarang }}/{{ $tahunSekarang }}</p>
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
                            <p>- Penyampaian Laporan Berita Acara Rekonsiliasi Internal Data BMD berupa Aset pada
                                Sekretariat Daerah</p>
                            <p>- Penyampaian Laporan Berita Acara Pemeriksaan Persediaan Barang Pakai Habis Stock Opname
                                per 30 Juni {{ $year ?? $tahunSekarang }} pada Sekretariat Daerah</p>
                        </div>
                    </td>
                    <td class="col-jumlah">1 ( satu )<br>Berkas</td>
                    <td class="col-keterangan">Disampaikan dengan hormat untuk dipergunakan sebagaimana mestinya,
                        terimakasih.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section-ttd">
        <div class="ttd-jabatan">
            Kepala Bagian Perencanaan dan Keuangan<br>
            Setda Kabupaten Bangka,
        </div>
        <div class="ttd-nama">Tati Djumijati, SE, M. Si</div>
        <div class="ttd-nip">NIP 19720512 198803 2 008</div>
    </div>

    {{-- SECTION PENERIMA --}}
    <div class="section-penerima">
        <div class="penerima-info">
            <table>
                <tr>
                    <td class="label">Diterima Tgl</td>
                    <td class="titik-dua">:</td>
                    <td><span>..................................................</span></td>
                </tr>
                <tr>
                    <td class="label">Nama</td>
                    <td class="titik-dua">:</td>
                    <td><span>..................................................</span></td>
                </tr>
                <tr>
                    <td class="label">Tanda Tangan</td>
                    <td class="titik-dua">:</td>
                    <td><span>..................................................</span></td>
                </tr>
            </table>
        </div>
    </div>

</body>

</html>
