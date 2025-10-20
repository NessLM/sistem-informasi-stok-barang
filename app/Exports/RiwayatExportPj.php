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

class RiwayatExportPj implements WithMultipleSheets
{
    protected $riwayat;
    protected $filter;

    public function __construct($riwayat, $filter = [])
    {
        $this->riwayat = $riwayat;
        $this->filter = $filter;
        
        // DEBUG: Log data yang masuk
        Log::info('RiwayatExportPj Constructor:', [
            'total' => is_countable($riwayat) ? count($riwayat) : 0,
            'type' => get_class($riwayat),
            'sample' => $riwayat->first()
        ]);
    }

    public function sheets(): array
    {
        $sheets = [];
        
        // Pastikan $this->riwayat adalah collection
        if (!$this->riwayat instanceof Collection) {
            $this->riwayat = collect($this->riwayat);
        }
        
        // DEBUG: Cek field 'alur_barang' atau 'jenis_transaksi'
        $firstItem = $this->riwayat->first();
        if ($firstItem) {
            Log::info('First item fields:', [
                'alur_barang' => $firstItem->alur_barang ?? 'NOT EXISTS',
                'jenis_transaksi' => $firstItem->jenis_transaksi ?? 'NOT EXISTS',
                'all_fields' => array_keys((array)$firstItem)
            ]);
        }
        
        // PERBAIKAN: Sesuaikan dengan field di PDF (alur_barang: Masuk/Keluar)
        $barangMasuk = $this->riwayat->filter(function($item) {
            return ($item->alur_barang ?? $item->jenis_transaksi ?? '') === 'Masuk';
        })->values();
        
        $barangKeluar = $this->riwayat->filter(function($item) {
            return ($item->alur_barang ?? $item->jenis_transaksi ?? '') === 'Keluar';
        })->values();
        
        Log::info('Data split:', [
            'masuk' => $barangMasuk->count(),
            'keluar' => $barangKeluar->count()
        ]);
        
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
        Log::info('BarangMasukSheet:', ['count' => $riwayat->count()]);
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
        
        // DEBUG: Log setiap baris
        Log::info("Mapping row $rowNumber:", [
            'tanggal' => $riwayat->tanggal ?? 'N/A',
            'nama_barang' => $riwayat->nama_barang ?? 'N/A',
            'jumlah' => $riwayat->jumlah ?? 'N/A'
        ]);
        
        // Ambil langsung dari atribut seperti di PDF
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

class BarangKeluarSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $riwayat;

    public function __construct($riwayat)
    {
        $this->riwayat = $riwayat;
        Log::info('BarangKeluarSheet:', ['count' => $riwayat->count()]);
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
            'Bagian',
            'Keterangan'
        ];
    }

    public function map($riwayat): array
    {
        static $rowNumber = 0;
        $rowNumber++;
        
        // DEBUG: Log setiap baris
        Log::info("Mapping row $rowNumber:", [
            'tanggal' => $riwayat->tanggal ?? 'N/A',
            'nama_barang' => $riwayat->nama_barang ?? 'N/A',
            'jumlah' => $riwayat->jumlah ?? 'N/A'
        ]);
        
        // Ambil langsung dari atribut seperti di PDF
        $tanggal = isset($riwayat->tanggal) ? 
                   \Carbon\Carbon::parse($riwayat->tanggal)->format('d/m/Y') : '-';
        $waktu = isset($riwayat->waktu) ? 
                 \Carbon\Carbon::parse($riwayat->waktu)->format('H:i') . ' WIB' : '-';
        $gudang = $riwayat->gudang ?? '-';
        $namaBarang = $riwayat->nama_barang ?? '-';
        $jumlah = $riwayat->jumlah ?? '0';
        $satuan = $riwayat->satuan ?? '-';
        $bagian = $riwayat->bagian ?? '-';
        $keterangan = $riwayat->keterangan ?? '-';
        
        return [
            $rowNumber,
            $tanggal,
            $waktu,
            $gudang,
            $namaBarang,
            $jumlah,
            $satuan,
            $bagian,
            $keterangan
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max(1, $this->riwayat->count() + 1);
        $dataRange = 'A1:I' . $lastRow;
        
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
        $sheet->getColumnDimension('D')->setWidth(20);
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