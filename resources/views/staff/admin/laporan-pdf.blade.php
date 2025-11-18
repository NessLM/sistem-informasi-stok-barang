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
    {{-- HALAMAN 1 --}}

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
        <div class="ttd-spacer-halaman3"></div>
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



    {{-- HALAMAN 2 --}}
    <div class="page-break"></div>

    <div class="kop-surat">
        <div class="kop-logo-copy">
            <img src="{{ asset('assets/banner/logo_bupati_copy.png') }}" alt="Logo Bupati">
        </div>
        <div class="kop-text">
            <h1>PEMERINTAH KABUPATEN BANGKA</h1>
            <h2>SEKRETARIAT DAERAH</h2>
            <p>Jl. A. Yani (Jalur Dua) Sungailiat 33215 Telp. (0717) 92536, Faksimile (0717) 92534</p>
        </div>
    </div>

    <div class="judul-dokumen">
        <h3>BERITA ACARA PEMERIKSAAN PERSEDIAAN BARANG PAKAI HABIS</h3>
        <h3>STOCK OPNAME PER 30 JUNI {{ $year ?? $tahunSekarang }}</h3>
        <h3>PADA SEKRETARIAT DAERAH KABUPATEN BANGKA</h3>
    </div>

    <div class="pembukaan-berita-acara">
        <p>Pada hari ini Senin tanggal Satu Bulan Juli tahun Dua Ribu Dua Puluh Lima (01-07-2025), bertempat di
            Sungailiat, yang bertanda tangan di bawah ini :</p>
    </div>

    <div class="identitas-petugas">
        <table>
            <tr>
                <td class="label">Nama</td>
                <td class="titik-dua">:</td>
                <td>Redha Efrida, A.Md</td>
            </tr>
            <tr>
                <td class="label">NIP</td>
                <td class="titik-dua">:</td>
                <td>19820816 201101 2 002</td>
            </tr>
            <tr>
                <td class="label">Jabatan</td>
                <td class="titik-dua">:</td>
                <td>Pengurus Barang Pengguna pada Sekretariat Daerah</td>
            </tr>
        </table>
    </div>

    <div class="isi-berita-acara">
        <p>Telah melaksanakan pemeriksaan persediaan barang (Stock Opname) barang pakai habis berdasarkan kartu
            persediaan barang dan dicocokkan dengan fisik barang yang tersedia. Dengan perincian sebagaimana terlampir
            dalam Berita Acara Stock Opname ini.</p>

        <p>Demikianlah Berita Acara ini dibuat dalam rangkap 2 (Dua) untuk dapat dipergunakan sebagaimana mestinya.</p>
    </div>

    <div class="lokasi-tanggal">
        Sungailiat, 01 Juli 2025
    </div>

    <div class="ttd-berita-acara">
        <div class="ttd-kiri">
            <div class="ttd-jabatan">Pejabat Penatausahaan Pengguna Barang</div>
            <div class="ttd-spacer"></div>
            <div class="ttd-nama">Hiskawati, S.AP</div>
            <div class="ttd-nip">NIP. 198109202006042007</div>
        </div>

        <div class="ttd-kanan">
            <div class="ttd-jabatan">Penanggung Jawab LBP <br>
                Pengurus Barang Pengguna,</div>
            <div class="ttd-spacer"></div>
            <div class="ttd-nama">Redha Efrida, A.Md</div>
            <div class="ttd-nip">NIP. 198206192008042002</div>
        </div>
    </div>

    <div class="mengesahkan-berita-acara">
        <div class="ttd-mengesahkan">
            <div class="ttd-jabatan">Mengetahui</div>
            <div class="ttd-jabatan">Pengguna Barang,</div>
            <div class="ttd-spacer"></div>
            <div class="ttd-nama">Thony Marza, AP
            </div>
            <div class="ttd-nip">NIP. 19750306199311101</div>
        </div>
    </div>




    {{-- HALAMAN 3 --}}
    <div class="page-break"></div>

    <div class="kop-surat">
        <div class="kop-logo-copy">
            <img src="{{ asset('assets/banner/logo_bupati_copy.png') }}" alt="Logo Bupati">
        </div>
        <div class="kop-text">
            <h1>PEMERINTAH KABUPATEN BANGKA</h1>
            <h2>SEKRETARIAT DAERAH</h2>
            <p>Jl. A. Yani (Jalur Dua) Sungailiat 33215 Telp. (0717) 92536, Faksimile (0717) 92534</p>
        </div>
    </div>


    <div class="judul-dokumen">
        <h3>BERITA ACARA REKONSILIASI INTERNAL DATA BARANG MILIK DAERAH BERUPA ASET LANCAR/PERSEDIAAN</h3>
        <h3>PADA SEKRETARIAT DAERAH KABUPATEN BANGKA</h3>
        <h3>NOMOR : BA / &nbsp;&nbsp; / SETDA/ 2025</h3>
    </div>

    <div class="pembukaan-berita-acara-halaman3">
        <p>Pada hari ini Senin tanggal satu Bulan Juli Tahun Dua Ribu Dua Puluh Lima (01 - 07 - 2025) Bertempat di
            Sungailiat, yang bertanda tangan
            dibawah ini :</p>
    </div>

    <div class="identitas-pihak-halaman3">
        <div class="pihak-section">
            <table class="identitas-table">
                <tr>
                    <td>
                        <p class="pihak-title">I. &nbsp;</p>
                    </td>
                    <td class="label">Nama</td>
                    <td class="titik-dua">:</td>
                    <td class="label-wide">Redha Efrida, A.Md</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="label">NIP</td>
                    <td class="titik-dua">:</td>
                    <td>19820816 201101 2 002</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="label">Jabatan</td>
                    <td class="titik-dua">:</td>
                    <td>Pengurus Barang Pengguna pada Sekretariat Daerah</td>
                </tr>
                <tr>
                    <td colspan="4" class="keterangan-pihak">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;dalam hal ini
                        bertindak untuk dan atas nama penanggung jawab penyusunan laporan barang pengguna selanjutnya
                        disebut sebagai Pihak Pertama.</td>
                </tr>
            </table>
        </div>

        <div class="pihak-section">
            <table class="identitas-table">
                <tr>
                    <td>
                        <p class="pihak-title">II.</p>
                    </td>
                    <td class="label">Nama</td>
                    <td class="titik-dua">:</td>
                    <td class="label-wide">Tri Medlowaty, A. Md</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="label">NIP</td>
                    <td class="titik-dua">:</td>
                    <td>19810522 201101 2 004</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="label">Jabatan</td>
                    <td class="titik-dua">:</td>
                    <td>Bendahara Pengeluaran pada Sekretariat Daerah</td>
                </tr>
                <tr>
                    <td colspan="4" class="keterangan-pihak">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;dalam hal ini bertindak
                        untuk dan atas nama penanggung
                        jawab penyusunan laporan keuangan selanjutnya disebut sebagai Pihak Kedua.</td>
                </tr>
            </table>
        </div>
    </div>

    <p class="keterangan-m">Menyatakan bahwa telah melakukan rekon data Barang Milik Daerah (BMD) berupa persediaan
        pada lingkup internal
        Sekretariat Daerah
        dengan cara membandingkan data BMD berupa persediaan pada Laporan Barang Pengguna (LBP) yang disusun oleh
        Pengurus Barang
        dengan Laporan Keuangan (LK) yang disusun oleh Pejabat Penatausahaan Keuangan SKPD untuk periode bulan April
        s.d. Juni 2025
        dengan hasil sebagai berikut :
    </p>

    <div class="tabel-aset-halaman3">
        <table class="tabel-utama">
            <thead>
                <tr>
                    <th class="col-no" rowspan ="3">No</th>
                    <th class="col-uraian" rowspan="3">Uraian</th>
                    <th colspan="4">Nilai Aset Lancar/Persediaan Periode Per 30 Juni 2025</th>
                </tr>
                <tr>
                    <th class="col-angka" rowspan="2">Stock Opname <br>30 Juni 2024</th>
                    <th class="col-angka" colspan="2">Perubahan</th>
                    <th class="col-angka" rowspan="2">Stock Opname <br>Monday, 30 June 2025</th>
                </tr>
                <tr>
                    <th class="col-angka">Bertambah</th>
                    <th class="col-angka">Berkurang</th>
                </tr>
                <tr>
                    <th>1</th>
                    <th>2</th>
                    <th>3</th>
                    <th>4</th>
                    <th>5</th>
                    <th>6</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center"><strong>A.</strong></td>
                    <td class="text-left"><strong>Barang Pakai Habis</strong></td>
                    <td class="text-right"><strong>11.657.697,62</strong></td>
                    <td class="text-right"><strong>390.946.310,00</strong></td>
                    <td class="text-right"><strong>382.421.310,00</strong></td>
                    <td class="text-right"><strong>20.182.697,62</strong></td>
                </tr>
                <tr>
                    <td class="text-center"><strong>A.1</strong></td>
                    <td class="text-left"><strong>Bahan</strong></td>
                    <td class="text-right"><strong>9.323.997,62</strong></td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right"><strong>9.323.997,62</strong></td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Bahan Bangunan Dan Konstruksi</td>
                    <td class="text-right">9.323.997,62</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">9.323.997,62</td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Bahan Kimia</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                </tr>
                <tr>
                    <td class="text-center"><strong>A.2</strong></td>
                    <td class="text-left"><strong>Alat/Bahan Untuk Kegiatan Kantor</strong></td>
                    <td class="text-right"><strong>2.383.700,00</strong></td>
                    <td class="text-right"><strong>207.096.310,00</strong></td>
                    <td class="text-right"><strong>198.571.310,00</strong></td>
                    <td class="text-right"><strong>10.858.700,00</strong></td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Alat Tulis Kantor</td>
                    <td class="text-right">581.700,00</td>
                    <td class="text-right">53.313.850,00</td>
                    <td class="text-right">49.240.580,00</td>
                    <td class="text-right">4.651.970,00</td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Bendera Pramuka</td>
                    <td class="text-right">1.752.000,00</td>
                    <td class="text-right">0.010.000,00</td>
                    <td class="text-right">18.401.100,00</td>
                    <td class="text-right">3.369.900,00</td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Persediaan Dokumen / Administrasi Tender</td>
                    <td class="text-right">-</td>
                    <td class="text-right">400.000,00</td>
                    <td class="text-right">400.000,00</td>
                    <td class="text-right">-</td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Bahan Komputer</td>
                    <td class="text-right">-</td>
                    <td class="text-right">+297.000,00</td>
                    <td class="text-right">4.663.830,00</td>
                    <td class="text-right">2.833.830,00</td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Bendel Kantor</td>
                    <td class="text-right">-</td>
                    <td class="text-right">53.802.800,00</td>
                    <td class="text-right">53.802.800,00</td>
                    <td class="text-right">-</td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Alat Laundry</td>
                    <td class="text-right">-</td>
                    <td class="text-right">59.513.000,00</td>
                    <td class="text-right">59.513.000,00</td>
                    <td class="text-right">-</td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Karpet dan Perlengkapan Ruang</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Piala</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Souvenir/Cenderamata</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Pakaian Dinas</td>
                    <td class="text-right">-</td>
                    <td class="text-right">8.950.000,00</td>
                    <td class="text-right">8.950.000,00</td>
                    <td class="text-right">-</td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Alat/Bahan Untuk Kegiatan Kantor Lainnya</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                </tr>
                <tr>
                    <td class="text-center"><strong>A.3</strong></td>
                    <td class="text-left"><strong>Obat-Obatan</strong></td>
                    <td class="text-right"><strong>-</strong></td>
                    <td class="text-right"><strong>-</strong></td>
                    <td class="text-right"><strong>-</strong></td>
                    <td class="text-right"><strong>-</strong></td>
                </tr>
                <tr>
                    <td class="text-center"><strong>A.4</strong></td>
                    <td class="text-left"><strong>Persediaan Untuk Dijual/Diserahkan</strong></td>
                    <td class="text-right"><strong>-</strong></td>
                    <td class="text-right"><strong>183.850.000,00</strong></td>
                    <td class="text-right"><strong>183.850.000,00</strong></td>
                    <td class="text-right"><strong>-</strong></td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Persediaan untuk Dijual/Diserahkan kepada Masyarakat</td>
                    <td class="text-right">-</td>
                    <td class="text-right">183.850.000,00</td>
                    <td class="text-right">183.850.000,00</td>
                    <td class="text-right">-</td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Persediaan Untuk Dijual/Diserahkan Lainnya</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                </tr>
                <tr class="sub-item">
                    <td></td>
                    <td class="text-left sub-item-text">Persediaan untuk Dijual/Diserahkan Lainnya</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                </tr>

                <tr class="total-row">
                    <td class="text-center" colspan="2"><strong>TOTAL (A+B+C)</strong></td>
                    <td class="text-right"><strong>11.657.697,62</strong></td>
                    <td class="text-right"><strong>390.946.310,00</strong></td>
                    <td class="text-right"><strong>382.421.310,00</strong></td>
                    <td class="text-right"><strong>20.182.697,62</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="keterangan-lampiran-halaman3">
        <p style="font-style: italic;">Rincian Terlampir</p>
        <br>
        <p>II &nbsp; Hal hal penting lainnya mengenai data BMD berupa Aset Lancar/Persediaan terkait penyusunan LBP/KP
            dan LK disajikan dalam Lampiran Berita Acara ini, yang merupakan bagian yang tidak terpisahkan dari Berita
            Acara ini.</p>
    </div>

    <div class="penutup-berita-acara-halaman3">
        <p>Demikian Berita Acara ini dibuat untuk bahan penyusunan laporan barang untuk daerah dan
            laporan keuangan periode 01 April s.d. 30 Juni 2025 dan apabila dikemudian hari terdapat kekeliruan akan
            dilakukan perbaikan sebagaimana mestinya.</p>
    </div>

    <div class="ttd-berita-acara-halaman3">
        <div class="ttd-kiri-halaman3">
            <div class="ttd-jabatan-halaman3">Penanggung Jawab LK</div>
            <div class="ttd-jabatan-halaman3">Bendahara Pengeluaran,</div>
            <div class="ttd-spacer-halaman3"></div>
            <div class="ttd-nama-halaman3">Tri Mediawaty, A. Md</div>
            <div class="ttd-nip-halaman3">NIP. 198105222011012004</div>
        </div>

        <div class="ttd-kanan-halaman3">
            <div class="ttd-jabatan-halaman3">Penanggung Jawab LBP</div>
            <div class="ttd-jabatan-halaman3">Pengurus Barang Pengguna,</div>
            <div class="ttd-spacer-halaman3"></div>
            <div class="ttd-nama-halaman3">Redha Efrida, A.Md</div>
            <div class="ttd-nip-halaman3">NIP. 198208162011012002</div>
        </div>
    </div>

    <div class="pejabat-penatausahaan-halaman3">
        <div class="ttd-jabatan-halaman3">Mengetahui</div>
    </div>

    <div class="ttd-berita-acara-halaman3">
        <div class="ttd-kiri-halaman3">
            <div class="ttd-jabatan-halaman3">Pengguna Barang,</div>
            <div class="ttd-spacer-halaman3"></div>
            <div class="ttd-nama-halaman3">Thony Marza, AP</div>
            <div class="ttd-nip-halaman3">NIP. 197503061993111001</div>
        </div>


        <div class="ttd-kanan-halaman3">
            <div class="ttd-jabatan-halaman3">Pejabat Penatausahaan Pengguna Barang.</div>
            <div class="ttd-spacer-halaman3"></div>
            <div class="ttd-nama-halaman3">Hiskawati, S. AP</div>
            <div class="ttd-nip-halaman3">NIP. 19810922006042007</div>
        </div>
    </div>
</body>

</html>
