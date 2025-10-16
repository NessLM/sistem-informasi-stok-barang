<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use Carbon\Carbon;

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
        
        // Pisahkan data berdasarkan alur_barang
        $barangMasuk = $this->riwayat->where('alur_barang', 'Masuk')->values();
        $barangKeluar = $this->riwayat->where('alur_barang', 'Keluar')->values();
        
        // Hanya buat sheet jika ada data
        if ($barangMasuk->count() > 0) {
            $sheets[] = new BarangMasukSheet($barangMasuk);
        }
        
        if ($barangKeluar->count() > 0) {
            $sheets[] = new DistribusiBarangSheet($barangKeluar);
        }
        
        // Jika tidak ada data sama sekali, buat sheet kosong dengan pesan
        if (count($sheets) === 0) {
            $sheets[] = new EmptyDataSheet();
        }
        
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
            'Tanggal',
            'Waktu', 
            'Gudang',
            'Nama Barang',
            'Jumlah',
            'Satuan',
            'Keterangan'
        ];
    }

    public function map($riwayat): array
    {
        static $rowNumber = 0;
        $rowNumber++;
        
        // Format tanggal dan waktu sesuai dengan data dari Controller
        $tanggal = isset($riwayat->tanggal) ? Carbon::parse($riwayat->tanggal)->format('d/m/Y') : '-';
        $waktu = isset($riwayat->waktu) ? $riwayat->waktu : '-';
        
        return [
            $rowNumber,
            $tanggal,
            $waktu,
            $riwayat->gudang ?? '-',
            $riwayat->nama_barang ?? '-',
            $riwayat->jumlah ?? 0,
            $riwayat->satuan ?? '-',
            $riwayat->keterangan ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max(1, $this->riwayat->count() + 1);
        $dataRange = 'A1:H' . $lastRow;
        
        // Style untuk header
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE2E2E2']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Style untuk data
        if ($lastRow > 1) {
            $sheet->getStyle('A2:H' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(30);

        // Center align untuk kolom tertentu
        $sheet->getStyle('A:A')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('F:F')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('B:B')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('C:C')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('G:G')->getAlignment()->setHorizontal('center');

        return [];
    }

    public function title(): string
    {
        return 'Barang Masuk';
    }
}

class DistribusiBarangSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
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
            'Tanggal',
            'Waktu',
            'Gudang Tujuan',
            'Nama Barang', 
            'Jumlah',
            'Satuan',
            'Keterangan'
        ];
    }

    public function map($riwayat): array
    {
        static $rowNumber = 0;
        $rowNumber++;
        
        // Format tanggal dan waktu sesuai dengan data dari Controller
        $tanggal = isset($riwayat->tanggal) ? Carbon::parse($riwayat->tanggal)->format('d/m/Y') : '-';
        $waktu = isset($riwayat->waktu) ? $riwayat->waktu : '-';
        
        return [
            $rowNumber,
            $tanggal,
            $waktu,
            $riwayat->gudang_tujuan ?? '-',
            $riwayat->nama_barang ?? '-',
            $riwayat->jumlah ?? 0,
            $riwayat->satuan ?? '-',
            $riwayat->keterangan ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max(1, $this->riwayat->count() + 1);
        $dataRange = 'A1:H' . $lastRow;
        
        // Style untuk header
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE2E2E2']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Style untuk data
        if ($lastRow > 1) {
            $sheet->getStyle('A2:H' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ]);
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(30);

        // Center align untuk kolom tertentu
        $sheet->getStyle('A:A')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('F:F')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('B:B')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('C:C')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('G:G')->getAlignment()->setHorizontal('center');

        return [];
    }

    public function title(): string
    {
        return 'Distribusi Barang';
    }
}

class EmptyDataSheet implements FromCollection, WithStyles, WithTitle
{
    public function collection()
    {
        return collect([['Tidak ada data yang ditemukan untuk periode yang dipilih.']]);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'TIDAK ADA DATA');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'Tidak ada data riwayat barang yang ditemukan untuk filter yang dipilih.');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(30);
        
        return [];
    }

    public function title(): string
    {
        return 'Tidak Ada Data';
    }
}