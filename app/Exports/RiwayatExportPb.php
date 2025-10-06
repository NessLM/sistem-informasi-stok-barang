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
        
        // Debug detail
        Log::info('=== DEBUG RIWAYAT EXPORT ===');
        Log::info('Tipe data riwayat: ' . gettype($this->riwayat));
        Log::info('Is collection? ' . ($this->riwayat instanceof Collection ? 'Ya' : 'Tidak'));
        Log::info('Jumlah total data: ' . (is_countable($this->riwayat) ? count($this->riwayat) : 'N/A'));
        
        // Pastikan $this->riwayat adalah collection
        if (!$this->riwayat instanceof Collection) {
            $this->riwayat = collect($this->riwayat);
        }
        
        // Debug data pertama untuk melihat struktur
        if ($this->riwayat->count() > 0) {
            $firstItem = $this->riwayat->first();
            Log::info('Struktur data pertama:', is_array($firstItem) ? $firstItem : $firstItem->toArray());
            Log::info('Jenis transaksi pertama: ' . ($firstItem->jenis_transaksi ?? 'Tidak ada'));
        }
        
        // Pisahkan data berdasarkan jenis transaksi
        $barangMasuk = $this->riwayat->filter(function($item) {
            $jenis = $item->jenis_transaksi ?? null;
            Log::info("Filter masuk - jenis: " . $jenis);
            return $jenis === 'masuk';
        })->values();
        
        $barangKeluar = $this->riwayat->filter(function($item) {
            $jenis = $item->jenis_transaksi ?? null;
            Log::info("Filter keluar - jenis: " . $jenis);
            return $jenis === 'keluar';
        })->values();
        
        Log::info('Jumlah barang masuk: ' . $barangMasuk->count());
        Log::info('Jumlah barang keluar: ' . $barangKeluar->count());
        
        // Jika tidak ada data keluar, buat sheet kosong untuk debugging
        if ($barangKeluar->count() === 0) {
            Log::warning('TIDAK ADA DATA BARANG KELUAR YANG DITEMUKAN!');
            
            // Tambahkan data dummy untuk testing
            $barangKeluar = collect([
                (object)[
                    'created_at' => now(),
                    'tanggal' => now()->format('d/m/Y'),
                    'barang' => (object)[
                        'nama' => 'DATA TEST - Periksa Filter',
                        'kategori' => (object)[
                            'gudang' => (object)['nama' => 'Gudang Test']
                        ]
                    ],
                    'jumlah' => 0,
                    'gudangTujuan' => (object)['nama' => 'Tujuan Test'],
                    'jenis_transaksi' => 'keluar'
                ]
            ]);
        }
        
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
        Log::info('BarangMasukSheet Constructor - Jumlah data: ' . $riwayat->count());
    }

    public function collection()
    {
        Log::info('Barang Masuk Sheet - collection() - Data Count: ' . $this->riwayat->count());
        
        if ($this->riwayat->count() > 0) {
            Log::info('Sample data barang masuk:', [$this->riwayat->first()]);
        }
        
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
        
        Log::info("Mapping barang masuk row {$rowNumber}", ['data' => $riwayat]);
        
        // Format tanggal dan waktu sesuai gambar
        $tanggalWaktu = '';
        if (isset($riwayat->created_at)) {
            $tanggalWaktu = $riwayat->created_at->format('d/m/Y, H:i:s');
        } elseif (isset($riwayat->tanggal)) {
            $tanggalWaktu = $riwayat->tanggal;
        } else {
            $tanggalWaktu = '-';
        }
        
        $mappedData = [
            $rowNumber,
            $tanggalWaktu,
            optional(optional($riwayat->barang)->kategori->gudang)->nama ?? 'Tidak ada gudang',
            optional($riwayat->barang)->nama ?? 'Tidak ada nama barang',
            $riwayat->jumlah ?? '0'
        ];
        
        Log::info("Mapped data barang masuk: ", $mappedData);
        
        return $mappedData;
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
            'B' => ['width' => 20],
            'C' => ['width' => 20],
            'D' => ['width' => 25],
            'E' => ['width' => 12, 'alignment' => ['horizontal' => 'center']],
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
        Log::info('BarangKeluarSheet Constructor - Jumlah data: ' . $riwayat->count());
    }

    public function collection()
    {
        Log::info('Barang Keluar Sheet - collection() - Data Count: ' . $this->riwayat->count());
        
        if ($this->riwayat->count() > 0) {
            Log::info('Sample data barang keluar:', [$this->riwayat->first()]);
        } else {
            Log::warning('TIDAK ADA DATA DI BARANG KELUAR SHEET!');
        }
        
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
        
        Log::info("Mapping barang keluar row {$rowNumber}", ['data' => $riwayat]);
        
        // Format tanggal dan waktu sesuai gambar
        $tanggalWaktu = '';
        if (isset($riwayat->created_at)) {
            $tanggalWaktu = $riwayat->created_at->format('d/m/Y, H:i:s');
        } elseif (isset($riwayat->tanggal)) {
            $tanggalWaktu = $riwayat->tanggal;
        } else {
            $tanggalWaktu = '-';
        }
        
        $mappedData = [
            $rowNumber,
            $tanggalWaktu,
            optional(optional($riwayat->barang)->kategori->gudang)->nama ?? 'Tidak ada gudang asal',
            optional($riwayat->barang)->nama ?? 'Tidak ada nama barang',
            $riwayat->jumlah ?? '0',
            optional($riwayat->gudangTujuan)->nama ?? 'Tidak ada gudang tujuan'
        ];
        
        Log::info("Mapped data barang keluar: ", $mappedData);
        
        return $mappedData;
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
            'B' => ['width' => 20],
            'C' => ['width' => 20],
            'D' => ['width' => 25],
            'E' => ['width' => 12, 'alignment' => ['horizontal' => 'center']],
            'F' => ['width' => 20],
        ];
    }

    public function title(): string
    {
        return 'Barang Keluar (Distribusi)';
    }
}