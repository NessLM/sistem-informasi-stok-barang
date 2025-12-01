{{-- resources/views/staff/admin/laporan-pdf.blade.php --}}
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Laporan Riwayat Barang</title>
    <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/laporan_pdf.css') }}">
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

        // TAMBAHKAN LOGIKA INI (ganti yang lama)
        $tanggalSekarang = Carbon::now();

        // Tentukan bulan terakhir dari triwulan berdasarkan $quarter
        $endMonthMap = [
            1 => 3, // Q1: Maret
            2 => 6, // Q2: Juni
            3 => 9, // Q3: September
            4 => 12, // Q4: Desember
        ];

        $q = isset($quarter) ? (int) $quarter : 2;
        $q = $q >= 1 && $q <= 4 ? $q : 2;
        $endMonth = $endMonthMap[$q] ?? 6;

        $yearForQuarter = isset($year) ? (int) $year : (int) $tanggalSekarang->year;

        // Jika bulan sekarang <= bulan akhir triwulan, gunakan tanggal hari ini
        // Jika tidak, gunakan akhir bulan triwulan
        if ($tanggalSekarang->year == $yearForQuarter && $tanggalSekarang->month <= $endMonth) {
            $tanggalSurat = $tanggalSekarang;
        } else {
            $tanggalSurat = Carbon::create($yearForQuarter, $endMonth, 1)->endOfMonth();
        }

        $tanggalFormatted = $tanggalSurat->locale('id')->isoFormat('D MMMM YYYY');

        function terbilang($angka)
        {
            $angka = (int) $angka;
            $bilangan = [
                '',
                'Satu',
                'Dua',
                'Tiga',
                'Empat',
                'Lima',
                'Enam',
                'Tujuh',
                'Delapan',
                'Sembilan',
                'Sepuluh',
                'Sebelas',
            ];

            if ($angka < 12) {
                return $bilangan[$angka];
            } elseif ($angka < 20) {
                return terbilang($angka - 10) . ' Belas';
            } elseif ($angka < 100) {
                return terbilang(intval($angka / 10)) . ' Puluh ' . terbilang($angka % 10);
            } elseif ($angka < 200) {
                return 'Seratus ' . terbilang($angka - 100);
            } elseif ($angka < 1000) {
                return terbilang(intval($angka / 100)) . ' Ratus ' . terbilang($angka % 100);
            } elseif ($angka < 2000) {
                return 'Seribu ' . terbilang($angka - 1000);
            } elseif ($angka < 1000000) {
                return terbilang(intval($angka / 1000)) . ' Ribu ' . terbilang($angka % 1000);
            }
        }
        // Tentukan bulan terakhir dari triwulan berdasarkan $quarter
        $endMonthMap = [
            1 => 3, // Q1: Maret
            2 => 6, // Q2: Juni
            3 => 9, // Q3: September
            4 => 12, // Q4: Desember
        ];

        // Paksa quarter jadi int & pastikan di range 1–4
        $q = isset($quarter) ? (int) $quarter : 2; // default ke Q2 (Juni) kalau kosong
        $q = $q >= 1 && $q <= 4 ? $q : 2;

        $endMonth = $endMonthMap[$q] ?? 6;

        $endMonthName = [
            3 => 'Maret',
            6 => 'Juni',
            9 => 'September',
            12 => 'Desember',
        ][$endMonth];

        // Tanggal terakhir bulan tersebut (30 atau 31)
        $yearForQuarter = isset($year) ? (int) $year : (int) $tanggalSekarang->year;
        $lastDayOfMonth = Carbon::create($yearForQuarter, $endMonth, 1)->endOfMonth()->day;

        $tanggal = $tanggalSurat->locale('id');

        $hari = $tanggal->isoFormat('dddd'); // Senin
        $tglAngka = $tanggal->format('d-m-Y'); // 01-07-2025
        $tglHuruf = terbilang($tanggal->day); // Satu
        $bulanHuruf = $tanggal->isoFormat('MMMM'); // Juli
        $tahunHuruf = trim(terbilang($tanggal->year)); // Dua Ribu Dua Puluh Lima

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
        $bulanSekarang = $bulanRomawi[$tanggalSurat->month];
        $tahunSekarang = $tanggalSurat->year;
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
                            <p>- Penyampaian Laporan Berita Acara Pemeriksaan Persediaan Barang Pakai Habis Stock
                                Opname
                                per {{ $lastDayOfMonth }} {{ $endMonthName }} {{ $year ?? $tahunSekarang }} pada
                                Sekretariat Daerah</p>
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
        <div class="ttd-spacer-halaman1"></div>
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
        <h3>STOCK OPNAME PER {{ $tanggalSurat->format('d') }} {{ $tanggalSurat->locale('id')->isoFormat('MMMM') }}
            {{ $tanggalSurat->year }}</h3>
        <h3>PADA SEKRETARIAT DAERAH KABUPATEN BANGKA</h3>
    </div>

    <div class="pembukaan-berita-acara">
        <p>
            Pada hari ini {{ $hari }} tanggal {{ $tglHuruf }} Bulan {{ $bulanHuruf }}
            Tahun {{ $tahunHuruf }} ({{ $tglAngka }}), bertempat di
            Sungailiat, yang bertanda tangan di bawah ini :
        </p>
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
            persediaan barang dan dicocokkan dengan fisik barang yang tersedia. Dengan perincian sebagaimana
            terlampir
            dalam Berita Acara Stock Opname ini.</p>

        <p>Demikianlah Berita Acara ini dibuat dalam rangkap 2 (Dua) untuk dapat dipergunakan sebagaimana mestinya.
        </p>
    </div>

    <div class="lokasi-tanggal">
        Sungailiat, {{ $tanggalSurat->locale('id')->isoFormat('D MMMM YYYY') }}
    </div>

    <!-- Bagian TTD Kiri dan Kanan -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <tr>
            <!-- TTD KIRI -->
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <div class="ttd-jabatan">Pejabat Penatausahaan Pengguna Barang</div>
                <br><br><br> <!-- Spacer Tanda Tangan -->
                <div class="ttd-nama">Hiskawati, S.AP</div>
                <div class="ttd-nip">NIP. 198109202006042007</div>
            </td>

            <!-- TTD KANAN -->
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <div class="ttd-jabatan">Penanggung Jawab LBP <br> Pengurus Barang Pengguna,</div>
                <br><br> <!-- Spacer Tanda Tangan -->
                <div class="ttd-nama">Redha Efrida, A.Md</div>
                <div class="ttd-nip">NIP. 198206192008042002</div>
            </td>
        </tr>
    </table>

    <!-- Bagian Mengesahkan -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 30px;">
        <tr>
            <td style="text-align: center;">

                <div class="ttd-jabatan">Mengetahui</div>
                <div class="ttd-jabatan">Pengguna Barang,</div>
                <br><br> <!-- Spacer Tanda Tangan -->
                <div class="ttd-nama">Thony Marza, AP</div>
                <div class="ttd-nip">NIP. 19750306199311101</div>

            </td>
        </tr>
    </table>




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
        <h3>NOMOR : BA / &nbsp;&nbsp; / SETDA /{{ $bulanSekarang }}/{{ $tahunSekarang }}</h3>
    </div>

    <div class="pembukaan-berita-acara-halaman3">
        <p>Pada hari ini {{ $hari }} tanggal {{ $tglHuruf }} Bulan {{ $bulanHuruf }}
            Tahun {{ $tahunHuruf }} ({{ $tglAngka }}), bertempat di
            Sungailiat, yang bertanda tangan di bawah ini :</p>
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
                        bertindak untuk dan atas nama penanggung jawab penyusunan laporan barang pengguna
                        selanjutnya
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
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;dalam hal ini
                        bertindak
                        untuk dan atas nama penanggung
                        jawab penyusunan laporan keuangan selanjutnya disebut sebagai Pihak Kedua.</td>
                </tr>
            </table>
        </div>
    </div>

    @php
        // Tentukan bulan awal triwulan
        $startMonthMap = [
            1 => 1, // Q1: Januari
            2 => 4, // Q2: April
            3 => 7, // Q3: Juli
            4 => 10, // Q4: Oktober
        ];

        $bulanMulai = $startMonthMap[$q] ?? 1;

        // Nama bulan awal dan akhir triwulan
        $bulanAwalTriwulan = Carbon::create($yearForQuarter, $bulanMulai, 1)->locale('id')->isoFormat('MMMM');
        $bulanAkhirTriwulan = $tanggalSurat->locale('id')->isoFormat('MMMM');
        $tahunTriwulan = $tanggalSurat->year;
    @endphp

    <p class="keterangan-m">Menyatakan bahwa telah melakukan rekon data Barang Milik Daerah (BMD) berupa persediaan
        pada lingkup internal
        Sekretariat Daerah
        dengan cara membandingkan data BMD berupa persediaan pada Laporan Barang Pengguna (LBP) yang disusun oleh
        Pengurus Barang
        dengan Laporan Keuangan (LK) yang disusun oleh Pejabat Penatausahaan Keuangan SKPD untuk periode bulan
        {{ $bulanAwalTriwulan }}
        s.d. {{ $bulanAkhirTriwulan }} {{ $tahunTriwulan }}
        dengan hasil sebagai berikut :
    </p>

    @php
        // Bulan awal tiap triwulan
        $startMonthMap = [
            1 => 1, // Q1: Jan
            2 => 4, // Q2: Apr
            3 => 7, // Q3: Jul
            4 => 10, // Q4: Okt
        ];

        // Pastikan quarter int & dalam range 1–4
        $q = isset($quarter) ? (int) $quarter : 1;
        $q = $q >= 1 && $q <= 4 ? $q : 1;

        $startMonth = $startMonthMap[$q];

        // Tahun yang dipakai untuk triwulan
        $yearForQuarter = isset($year) ? (int) $year : (isset($tahunSekarang) ? (int) $tahunSekarang : (int) date('Y'));

        // Nama 3 bulan dalam triwulan (Jan, Feb, Mar / Apr, Mei, Jun, dst.)
        $bulanQuarter = [];
        for ($i = 0; $i < 3; $i++) {
            $bulanQuarter[] = Carbon::create($yearForQuarter, $startMonth + $i, 1)
                ->locale('id')
                ->isoFormat('MMM'); // Jan, Feb, Mar
        }
    @endphp

    <div class="tabel-aset-halaman3">
        <table class="tabel-utama">
            <thead>
                <tr>
                    <th class="col-no" rowspan="3">No</th>
                    <th class="col-uraian" rowspan="3">Kategori</th>
                    <th colspan="6">
                        Nilai Harga Persediaan Per {{ $tanggalSurat->format('d') }}
                        {{ $tanggalSurat->locale('id')->isoFormat('MMMM') }}
                    </th>
                    <th class="col-opname" rowspan="3">
                        Stock<br>Opname Ter<br>Update Per<br>{{ $tanggalSurat->format('d') }}
                        {{ $tanggalSurat->locale('id')->isoFormat('MMMM') }}
                    </th>
                </tr>
                <tr>
                    {{-- Row 2: Pemasukan & Pengeluaran --}}
                    <th colspan="3">
                        Pemasukan Tri Wulan {{ $quarter }} Per {{ $tanggalSurat->format('d') }}
                        {{ $tanggalSurat->locale('id')->isoFormat('MMMM') }}
                    </th>
                    <th colspan="3">
                        Pengeluaran Tri Wulan {{ $quarter }} Per {{ $tanggalSurat->format('d') }}
                        {{ $tanggalSurat->locale('id')->isoFormat('MMMM') }}
                    </th>
                </tr>
                <tr>
                    {{-- Nama bulan: Jan / Feb / Mar (atau Apr / Mei / Jun, dst) --}}
                    <th class="col-angka">{{ $bulanQuarter[0] ?? '' }}</th>
                    <th class="col-angka">{{ $bulanQuarter[1] ?? '' }}</th>
                    <th class="col-angka">{{ $bulanQuarter[2] ?? '' }}</th>

                    <th class="col-angka">{{ $bulanQuarter[0] ?? '' }}</th>
                    <th class="col-angka">{{ $bulanQuarter[1] ?? '' }}</th>
                    <th class="col-angka">{{ $bulanQuarter[2] ?? '' }}</th>
                </tr>

            </thead>
            <tbody>
                @php
                    // Helper format rupiah dengan koma + titik
                    $fmt = function ($value) {
                        $value = $value ?? 0;
                        return $value === 0 ? '-' : number_format($value, 2, ',', '.');
                    };
                @endphp

                @forelse ($rekapKategori ?? [] as $idx => $row)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="text-left">{{ $row['kategori'] ?? '-' }}</td>

                        {{-- Pemasukan triwulan (m1, m2, m3) --}}
                        <td class="text-center">Rp. {{ $fmt($row['pemasukan']['m1'] ?? 0) }}</td>
                        <td class="text-center">Rp. {{ $fmt($row['pemasukan']['m2'] ?? 0) }}</td>
                        <td class="text-center">Rp. {{ $fmt($row['pemasukan']['m3'] ?? 0) }}</td>

                        {{-- Pengeluaran triwulan (m1, m2, m3) --}}
                        <td class="text-center">Rp. {{ $fmt($row['pengeluaran']['m1'] ?? 0) }}</td>
                        <td class="text-center">Rp. {{ $fmt($row['pengeluaran']['m2'] ?? 0) }}</td>
                        <td class="text-center">Rp. {{ $fmt($row['pengeluaran']['m3'] ?? 0) }}</td>

                        {{-- Stock opname terupdate --}}
                        <td class="text-right">Rp. {{ $fmt($row['stock_opname'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">
                            Belum ada data transaksi untuk triwulan ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="keterangan-lampiran-halaman3">
        <p style="font-style: italic;">Rincian Terlampir</p>
        <br>
        <p>II &nbsp; Hal hal penting lainnya mengenai data BMD berupa Aset Lancar/Persediaan terkait penyusunan
            LBP/KP
            dan LK disajikan dalam Lampiran Berita Acara ini, yang merupakan bagian yang tidak terpisahkan dari
            Berita
            Acara ini.</p>
    </div>

    <div class="penutup-berita-acara-halaman3">
        @php
            $tglAwalPeriode = Carbon::create($yearForQuarter, $startMonth, 1)->format('d');
            $bulanAwalPeriode = Carbon::create($yearForQuarter, $startMonth, 1)->locale('id')->isoFormat('MMMM');
            $tglAkhirPeriode = $tanggalSurat->format('d');
            $bulanAkhirPeriode = $tanggalSurat->locale('id')->isoFormat('MMMM');
            $tahunPeriode = $tanggalSurat->year;
        @endphp

        <p>Demikian Berita Acara ini dibuat untuk bahan penyusunan laporan barang untuk daerah dan
            laporan keuangan periode {{ $tglAwalPeriode }} {{ $bulanAwalPeriode }} s.d. {{ $tglAkhirPeriode }}
            {{ $bulanAkhirPeriode }} {{ $tahunPeriode }} dan apabila dikemudian hari terdapat kekeliruan akan
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






    {{-- HALAMAN 4 --}}
    <div class="page-break"></div>

    <div class="header-halaman4">
        <div class="header-top-halaman4">
            <div class="judul-laporan-halaman4">
                <p>LAPORAN PENGECEKAN FISIK (STOCK OPNAME)</p>
                <p>PERSEDIAAN BARANG HABIS PAKAI</p>
                <p>PER {{ $tanggalSurat->format('d') }} {{ $tanggalSurat->locale('id')->isoFormat('MMMM') }}
                    {{ $tanggalSurat->year }}</p>
                <p>NOMOR : BA/ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; /SETDA/{{ $tahunSekarang }}</p>
            </div>

            <div class="lampiran-halaman4">
                <p>Lampiran : Berita Acara Stock Opname</p>
            </div>
        </div>

        <div class="kop-info-halaman4">
            <table>
                <tr>
                    <td class="label-halaman4">OPD/ UNIT KERJA</td>
                    <td class="titik-dua-halaman4">:</td>
                    <td>SEKRETARIAT DAERAH</td>
                </tr>
                <tr>
                    <td class="label-halaman4">KABUPATEN</td>
                    <td class="titik-dua-halaman4">:</td>
                    <td>BANGKA</td>
                </tr>
                <tr>
                    <td class="label-halaman4">PROVINSI</td>
                    <td class="titik-dua-halaman4">:</td>
                    <td>KEPULAUAN BANGKA BELITUNG</td>
                </tr>
            </table>
        </div>
    </div>

    @php
        // Ambil data stock opname
        $stockOpnameData = app(App\Http\Controllers\Admin\LaporanPDFController::class)->getStockOpnameData(
            $quarter ?? 2,
            $year ?? $tahunSekarang,
        );

        // Helper format rupiah
        $formatRupiah = function ($value) {
            return $value > 0 ? number_format($value, 2, ',', '.') : '-';
        };

        // PERBAIKAN: Sesuaikan dengan tinggi halaman dan tinggi baris
        // Halaman pertama: ada header dokumen + tabel header (lebih sedikit baris)
        // Halaman lanjutan: full table tanpa header dokumen (lebih banyak baris)
        $rowsPerFirstPage = 35; // Halaman pertama dengan header dokumen
        $rowsPerPage = 43; // Halaman lanjutan tanpa header (lebih banyak)
        $currentRow = 0;
        $totalRows = $stockOpnameData->count();
    @endphp

    <div class="tabel-riwayat-halaman4">
        <table class="tabel-utama-halaman4">
            <thead>
                <tr>
                    <th class="col-kode-h4">KODE</th>
                    <th class="col-uraian-h4">URAIAN</th>
                    <th class="col-volume-h4">VOLUME</th>
                    <th class="col-satuan-h4">SATUAN</th>
                    <th class="col-harga-h4">HARGA</th>
                    <th class="col-jumlah-h4">JUMLAH HARGA</th>
                    <th class="col-keterangan-h4">KET</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stockOpnameData as $index => $item)
                    @php
                        $currentRow++;
                        // Hitung kapan perlu page break
                        if ($currentRow <= $rowsPerFirstPage) {
                            $needPageBreak = $currentRow == $rowsPerFirstPage && $currentRow < $totalRows;
                        } else {
                            $adjustedRow = $currentRow - $rowsPerFirstPage;
                            $needPageBreak = $adjustedRow % $rowsPerPage == 0 && $currentRow < $totalRows;
                        }
                    @endphp

                    <tr @if ($item->is_header) style="font-weight: bold;" @endif>
                        {{-- Kode Barang --}}
                        <td class="text-left isi-kode">{{ $item->kode_barang }}</td>

                        {{-- Uraian --}}
                        <td class="text-left isi-uraian">{{ $item->uraian }}</td>

                        @if (!$item->is_header)
                            {{-- Volume --}}
                            <td class="text-center isi-volume">{{ $item->volume }}</td>

                            {{-- Satuan --}}
                            <td class="text-center isi-satuan">{{ $item->satuan }}</td>

                            {{-- Harga --}}
                            <td class="text-right isi-harga">
                                {{ $item->harga ? 'Rp. ' . $formatRupiah($item->harga) : '' }}
                            </td>

                            {{-- Jumlah Harga --}}
                            <td class="text-right isi-jumlah">
                                Rp. {{ $formatRupiah($item->jumlah_harga) }}
                            </td>

                            {{-- Keterangan --}}
                            <td class="text-center isi-keterangan"></td>
                        @else
                            {{-- Untuk header kategori --}}
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                            <td class="text-right"></td>
                            <td class="text-right">
                                <strong>Rp. {{ $formatRupiah($item->jumlah_harga) }}</strong>
                            </td>
                            <td class="text-center"></td>
                        @endif
                    </tr>

                    {{-- Page break jika sudah mencapai batas baris --}}
                    @if ($needPageBreak)
            </tbody>
        </table>
    </div>

    {{-- Halaman baru --}}
    <div class="page-break"></div>

    {{-- Tabel lanjutan tanpa header --}}
    <div class="tabel-riwayat-halaman4 tabel-lanjutan">
        <table class="tabel-utama-halaman4">
            <tbody>
                @endif

            @empty
                <tr>
                    <td colspan="7" class="text-center">
                        Tidak ada data stock opname
                    </td>
                </tr>
                @endforelse

                {{-- Hitung total --}}
                @php
                    $totalVolume = 0;
                    $totalJumlahHarga = 0;

                    foreach ($stockOpnameData as $item) {
                        if (!$item->is_header) {
                            $totalVolume += $item->volume;
                            $totalJumlahHarga += $item->jumlah_harga;
                        }
                    }
                @endphp

                {{-- Baris Total --}}
                <tr class="uraian-total">
                    <td class="text-left isi-kode"></td>
                    <td class="text-left isi-uraian">JUMLAH</td>
                    <td class="text-center isi-volume">{{ $totalVolume }}</td>
                    <td class="text-center isi-satuan"></td>
                    <td class="text-right isi-harga"></td>
                    <td class="text-right isi-jumlah">Rp. {{ $formatRupiah($totalJumlahHarga) }}</td>
                    <td class="text-center isi-keterangan"></td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Footer halaman terakhir --}}
    <div class="footer-lampiran-halaman4">
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <tr>
                <td style="width: 50%; text-align: center; vertical-align: top;">
                    <div class="ttd-jabatan">Pejabat Penatausahaan Pengguna Barang,</div>
                    <br>
                    <div class="ttd-nama">Hiskawati, S.AP</div>
                    <div class="ttd-nip">NIP. 19810920 200604 2 007</div>
                </td>
                <td style="width: 50%; text-align: center; vertical-align: top;">
                    <div class="ttd-jabatan">Pengurus Barang Pengguna,</div>
                    <br>
                    <div class="ttd-nama">Redha Efrida, A.Md</div>
                    <div class="ttd-nip">NIP. 19820816 201101 2 002</div>
                </td>
            </tr>
        </table>
        <table style="width: 100%; border-collapse: collapse; margin-top: 30px;">
            <tr>
                <td style="text-align: center;">
                    <div class="ttd-jabatan">Mengetahui :</div>
                    <div class="ttd-jabatan">Pengguna Barang,</div>
                    <br>
                    <div class="ttd-nama">Thony Marza, AP</div>
                    <div class="ttd-nip">NIP. 19750306199311101</div>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>
