<?php

namespace App\Exports;

use App\Models\RiwayatBarang;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RiwayatExportPb implements WithMultipleSheets
{
    protected $riwayat;
    protected $filter;

    public function __construct($riwayat, $filter = [])
    {
        $this->riwayat = $riwayat;
        $this->filter = $filter;
    }

    public function sheets(): array
    {
        $sheets = [];
        
        // Pastikan $this->riwayat adalah collection
        if (!$this->riwayat instanceof Collection) {
            $this->riwayat = collect($this->riwayat);
        }
        
        // Pisahkan data berdasarkan jenis transaksi
        // PERBAIKAN: Gunakan 'distribusi' bukan 'keluar'
        $barangMasuk = $this->riwayat->where('jenis_transaksi', 'masuk')->values();
        $barangKeluar = $this->riwayat->where('jenis_transaksi', 'distribusi')->values();
        
        $sheets[] = new BarangMasukSheet($barangMasuk);
        $sheets[] = new BarangKeluarSheet($barangKeluar);
        
        return $sheets;
    }
}

class BarangMasukSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $riwayat;

    public function __construct($riwayat)
    {
        $this->riwayat = $riwayat;
    }

    public function collection()
    {
        return $this->riwayat;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal, Waktu',
            'Gudang',
            'Nama Barang',
            'Jumlah'
        ];
    }

    public function map($riwayat): array
    {
        static $rowNumber = 0;
        $rowNumber++;
        
        // Format tanggal dan waktu
        $tanggalWaktu = '';
        if (isset($riwayat->created_at)) {
            $tanggalWaktu = $riwayat->created_at->format('d/m/Y, H:i:s');
        } elseif (isset($riwayat->tanggal)) {
            $tanggalWaktu = $riwayat->tanggal;
        } else {
            $tanggalWaktu = '-';
        }
        
        // Ambil data gudang dan barang
        $gudangAsal = optional(optional($riwayat->barang)->kategori->gudang)->nama ?? '-';
        $namaBarang = optional($riwayat->barang)->nama ?? '-';
        $jumlah = $riwayat->jumlah ?? '0';
        
        // Hapus karakter pipe jika ada
        $gudangAsal = str_replace('|', '', $gudangAsal);
        $namaBarang = str_replace('|', '', $namaBarang);
        
        return [
            $rowNumber,
            $tanggalWaktu,
            $gudangAsal,
            $namaBarang,
            $jumlah
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max(1, $this->riwayat->count() + 1);
        $dataRange = 'A1:E' . $lastRow;
        
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E2E2']
                ]
            ],
            'A' => ['width' => 8, 'alignment' => ['horizontal' => 'center']],
            'B' => ['width' => 25],
            'C' => ['width' => 20],
            'D' => ['width' => 30],
            'E' => ['width' => 15, 'alignment' => ['horizontal' => 'center']],
        ];
    }

    public function title(): string
    {
        return 'Barang Masuk';
    }
}

class BarangKeluarSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $riwayat;

    public function __construct($riwayat)
    {
        $this->riwayat = $riwayat;
    }

    public function collection()
    {
        return $this->riwayat;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal, Waktu',
            'Gudang Asal',
            'Nama Barang', 
            'Jumlah',
            'Gudang Tujuan'
        ];
    }

    public function map($riwayat): array
    {
        static $rowNumber = 0;
        $rowNumber++;
        
        // Format tanggal dan waktu
        $tanggalWaktu = '';
        if (isset($riwayat->created_at)) {
            $tanggalWaktu = $riwayat->created_at->format('d/m/Y, H:i:s');
        } elseif (isset($riwayat->tanggal)) {
            $tanggalWaktu = $riwayat->tanggal;
        } else {
            $tanggalWaktu = '-';
        }
        
        // Ambil data dengan berbagai fallback
        $gudangAsal = optional(optional($riwayat->barang)->kategori->gudang)->nama ?? '-';
        $namaBarang = optional($riwayat->barang)->nama ?? '-';
        $jumlah = $riwayat->jumlah ?? '0';
        $gudangTujuan = optional($riwayat->gudangTujuan)->nama ?? '-';
        
        // Hapus karakter pipe jika ada
        $gudangAsal = str_replace('|', '', $gudangAsal);
        $namaBarang = str_replace('|', '', $namaBarang);
        $gudangTujuan = str_replace('|', '', $gudangTujuan);
        
        return [
            $rowNumber,
            $tanggalWaktu,
            $gudangAsal,
            $namaBarang,
            $jumlah,
            $gudangTujuan
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max(1, $this->riwayat->count() + 1);
        $dataRange = 'A1:F' . $lastRow;
        
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E2E2']
                ]
            ],
            'A' => ['width' => 8, 'alignment' => ['horizontal' => 'center']],
            'B' => ['width' => 25],
            'C' => ['width' => 20],
            'D' => ['width' => 30],
            'E' => ['width' => 15, 'alignment' => ['horizontal' => 'center']],
            'F' => ['width' => 20],
        ];
    }

    public function title(): string
    {
        return 'Barang Keluar (Distribusi)';
    }
}