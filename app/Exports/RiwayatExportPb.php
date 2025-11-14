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
            'Bagian', // TAMBAHKAN KOLOM BAGIAN DI SINI
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
        $waktu = isset($riwayat->waktu) ? Carbon::parse($riwayat->waktu)->format('H:i') . ' WIB' : '-';

        return [
            $rowNumber,
            $tanggal,
            $waktu,
            $riwayat->gudang ?? '-',
            $riwayat->bagian_nama ?? '-', // TAMBAHKAN DATA BAGIAN DI SINI
            $riwayat->nama_barang ?? '-',
            $riwayat->jumlah ?? 0,
            $riwayat->satuan ?? '-',
            $riwayat->keterangan ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max(1, $this->riwayat->count() + 1);
        $dataRange = 'A1:I' . $lastRow; // UBAH DARI H MENJADI I KARENA ADA TAMBAHAN KOLOM

        // Apply borders to all cells
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Set semua kolom ke center alignment untuk data
        $sheet->getStyle('A2:I' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:I' . $lastRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // Set Nama Barang (F) dan Keterangan (I) ke left alignment (SESUAIKAN POSISI KOLOM)
        $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        // Set column widths (SESUAIKAN LEBAR KOLOM)
        $sheet->getColumnDimension('A')->setWidth(8);    // No
        $sheet->getColumnDimension('B')->setWidth(15);   // Tanggal
        $sheet->getColumnDimension('C')->setWidth(12);   // Waktu
        $sheet->getColumnDimension('D')->setWidth(20);   // Gudang
        $sheet->getColumnDimension('E')->setWidth(20);   // Bagian (BARU)
        $sheet->getColumnDimension('F')->setWidth(30);   // Nama Barang
        $sheet->getColumnDimension('G')->setWidth(10);   // Jumlah
        $sheet->getColumnDimension('H')->setWidth(10);   // Satuan
        $sheet->getColumnDimension('I')->setWidth(25);   // Keterangan

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E2E2']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ],
        ];
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
            'Bagian Tujuan',
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
        $waktu = isset($riwayat->waktu) ? Carbon::parse($riwayat->waktu)->format('H:i') . ' WIB' : '-';

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

        // Apply borders to all cells
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Set semua kolom ke center alignment untuk data
        $sheet->getStyle('A2:H' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:H' . $lastRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // Set Nama Barang (E) dan Keterangan (H) ke left alignment
        $sheet->getStyle('E2:E' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H2:H' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(25);

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E2E2']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ],
        ];
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
        $lastRow = 5;
        $dataRange = 'A1:D' . $lastRow;

        // Apply borders to all cells
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'TIDAK ADA DATA');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1')->getAlignment()->setVertical('center');
        $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A1')->getFill()->getStartColor()->setARGB('FFE2E2E2');

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'Tidak ada data riwayat barang yang ditemukan untuk filter yang dipilih.');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A2')->getAlignment()->setVertical('center');

        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(20);

        // Set row heights
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(25);

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E2E2']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ],
            2 => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                ]
            ],
        ];
    }


    public function title(): string
    {
        return 'Tidak Ada Data';
    }
}
