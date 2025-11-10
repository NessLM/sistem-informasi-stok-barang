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

class RiwayatExport implements WithMultipleSheets
{
    protected $riwayat;
    protected $filter;

    public function __construct($riwayat, $filter)
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
        
        // Sheet untuk barang masuk
        $masuk = $this->riwayat->where('alur_barang', 'Masuk PB')->sortByDesc('tanggal')->sortByDesc('waktu')->values();
        if ($masuk->count() > 0) {
            $sheets[] = new RiwayatMasukSheet($masuk, 'Barang Masuk');
        }
        
        // Sheet untuk distribusi
        $distribusi = $this->riwayat->where('alur_barang', 'Distribusi PJ')->sortByDesc('tanggal')->sortByDesc('waktu')->values();
        if ($distribusi->count() > 0) {
            $sheets[] = new RiwayatDistribusiSheet($distribusi, 'Distribusi Barang');
        }
        
        // Sheet untuk barang keluar
        $keluar = $this->riwayat->where('alur_barang', 'Keluar PJ')->sortByDesc('tanggal')->sortByDesc('waktu')->values();
        if ($keluar->count() > 0) {
            $sheets[] = new RiwayatKeluarSheet($keluar, 'Barang Keluar');
        }
        
        return $sheets;
    }
}

class RiwayatMasukSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $riwayat;
    protected $sheetTitle;

    public function __construct($riwayat, $sheetTitle)
    {
        $this->riwayat = $riwayat;
        $this->sheetTitle = $sheetTitle;
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
        
        $tanggal = isset($riwayat->tanggal) ? 
                   \Carbon\Carbon::parse($riwayat->tanggal)->format('d/m/Y') : '-';
        $waktu = isset($riwayat->waktu) ? 
                 \Carbon\Carbon::parse($riwayat->waktu)->format('H:i') . ' WIB' : '-';
        $gudang = $riwayat->gudang ?? '-';
        $namaBarang = $riwayat->nama_barang ?? '-';
        $jumlah = $riwayat->jumlah ?? '0';
        $satuan = $riwayat->satuan ?? '-';
        $keterangan = $riwayat->keterangan ?? '-';
        
        return [
            $rowNumber,
            $tanggal,
            $waktu,
            $gudang,
            $namaBarang,
            $jumlah,
            $satuan,
            $keterangan
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

        // Set semua kolom ke center alignment
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
        return 'Barang Masuk';
    }
}

class RiwayatDistribusiSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $riwayat;
    protected $sheetTitle;

    public function __construct($riwayat, $sheetTitle)
    {
        $this->riwayat = $riwayat;
        $this->sheetTitle = $sheetTitle;
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
        
        $tanggal = isset($riwayat->tanggal) ? 
                   \Carbon\Carbon::parse($riwayat->tanggal)->format('d/m/Y') : '-';
        $waktu = isset($riwayat->waktu) ? 
                 \Carbon\Carbon::parse($riwayat->waktu)->format('H:i') . ' WIB' : '-';
        $gudangTujuan = $riwayat->gudang ?? '-';
        $namaBarang = $riwayat->nama_barang ?? '-';
        $jumlah = $riwayat->jumlah ?? '0';
        $satuan = $riwayat->satuan ?? '-';
        $keterangan = $riwayat->keterangan ?? '-';
        
        return [
            $rowNumber,
            $tanggal,
            $waktu,
            $gudangTujuan,
            $namaBarang,
            $jumlah,
            $satuan,
            $keterangan
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

        // Set semua kolom ke center alignment
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

class RiwayatKeluarSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $riwayat;
    protected $sheetTitle;

    public function __construct($riwayat, $sheetTitle)
    {
        $this->riwayat = $riwayat;
        $this->sheetTitle = $sheetTitle;
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
            'Bagian Asal',
            'Nama Barang',
            'Jumlah',
            'Satuan',
            'Penerima',
            'Keterangan'
        ];
    }

    public function map($riwayat): array
    {
        static $rowNumber = 0;
        $rowNumber++;
        
        $tanggal = isset($riwayat->tanggal) ? 
                   \Carbon\Carbon::parse($riwayat->tanggal)->format('d/m/Y') : '-';
        $waktu = isset($riwayat->waktu) ? 
                 \Carbon\Carbon::parse($riwayat->waktu)->format('H:i') . ' WIB' : '-';
        $bagianAsal = $riwayat->gudang ?? '-';
        $namaBarang = $riwayat->nama_barang ?? '-';
        $jumlah = $riwayat->jumlah ?? '0';
        $satuan = $riwayat->satuan ?? '-';
        $penerima = $riwayat->penerima ?? '-';
        $keterangan = $riwayat->keterangan ?? '-';
        
        return [
            $rowNumber,
            $tanggal,
            $waktu,
            $bagianAsal,
            $namaBarang,
            $jumlah,
            $satuan,
            $penerima,
            $keterangan
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max(1, $this->riwayat->count() + 1);
        $dataRange = 'A1:I' . $lastRow;
        
        // Apply borders to all cells
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Set semua kolom ke center alignment
        $sheet->getStyle('A2:I' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:I' . $lastRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        
        // Set Nama Barang (E) dan Keterangan (I) ke left alignment
        $sheet->getStyle('E2:E' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(25);

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
        return 'Barang Keluar';
    }
}