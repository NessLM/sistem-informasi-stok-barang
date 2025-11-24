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
    protected $stokBagian;
    
    public function __construct($riwayat, $filter = [], $stokBagian = null)
    {
        $this->riwayat = $riwayat;
        $this->filter = $filter;
        $this->stokBagian = $stokBagian;

        // DEBUG: Log data yang masuk
        Log::info('RiwayatExportPj Constructor:', [
            'total' => is_countable($riwayat) ? count($riwayat) : 0,
            'type' => get_class($riwayat),
            'sample' => $riwayat->first(),
            'stokBagian_count' => $stokBagian ? $stokBagian->count() : 0
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
                'all_fields' => array_keys((array) $firstItem)
            ]);
        }

        // PERBAIKAN: Sesuaikan dengan field di PDF (alur_barang: Masuk/Keluar)
        $barangMasuk = $this->riwayat->filter(function ($item) {
            return ($item->alur_barang ?? $item->jenis_transaksi ?? '') === 'Masuk';
        })->values();

        $barangKeluar = $this->riwayat->filter(function ($item) {
            return ($item->alur_barang ?? $item->jenis_transaksi ?? '') === 'Keluar';
        })->values();

        Log::info('Data split:', [
            'masuk' => $barangMasuk->count(),
            'keluar' => $barangKeluar->count()
        ]);

        $sheets[] = new BarangMasukSheet($barangMasuk);
        $sheets[] = new BarangKeluarSheet($barangKeluar);
        
        // Tambahkan sheet STOK TERUPDATE jika ada data stok
        if ($this->stokBagian && $this->stokBagian->count() > 0) {
            $sheets[] = new StokTerupdateSheet($this->stokBagian);
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
        $namaBarang = $riwayat->nama_barang ?? '-';
        $jumlah = $riwayat->jumlah ?? '0';
        $satuan = $riwayat->satuan ?? '-';
        $keterangan = $riwayat->keterangan ?? '-';

        return [
            $rowNumber,
            $tanggal,
            $waktu,
            $namaBarang,
            $jumlah,
            $satuan,
            $keterangan
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max(1, $this->riwayat->count() + 1);
        $dataRange = 'A1:G' . $lastRow;

        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Set semua kolom ke center alignment secara default
        $sheet->getStyle('A2:G' . $lastRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // Set kolom tertentu ke center
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // No
        $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Tanggal
        $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Waktu
        $sheet->getStyle('E2:E' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Jumlah
        $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Satuan

        // PERBAIKAN: Set Nama Barang (kolom D) dan Keterangan (kolom G) ke LEFT alignment
        $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Nama Barang
        $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Keterangan

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(30); // Nama Barang lebih lebar
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(25); // Keterangan lebih lebar

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
            'Nama Barang',
            'Jumlah',
            'Satuan',
            'Nama Penerima',
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
        $namaBarang = $riwayat->nama_barang ?? '-';
        $jumlah = $riwayat->jumlah ?? '0';
        $satuan = $riwayat->satuan ?? '-';
        $penerima = $riwayat->nama_penerima ?? '-';
        $keterangan = $riwayat->keterangan ?? '-';

        return [
            $rowNumber,
            $tanggal,
            $waktu,
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
        $dataRange = 'A1:H' . $lastRow;

        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Set semua kolom ke center alignment secara default
        $sheet->getStyle('A2:H' . $lastRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // Set kolom tertentu ke center
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // No
        $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Tanggal
        $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Waktu
        $sheet->getStyle('E2:E' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Jumlah
        $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Satuan
        $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Nama Penerima

        // PERBAIKAN: Set Nama Barang (kolom D) dan Keterangan (kolom H) ke LEFT alignment
        $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Nama Barang
        $sheet->getStyle('H2:H' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Keterangan

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(30); // Nama Barang lebih lebar
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(25); // Keterangan lebih lebar

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

class StokTerupdateSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $stokBagian;

    public function __construct($stokBagian)
    {
        $this->stokBagian = $stokBagian;
        Log::info('StokTerupdateSheet:', ['count' => $stokBagian->count()]);
    }

    public function collection()
    {
        return $this->stokBagian;
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Barang',
            'Nama Barang',
            'Kategori',
            'Stok',
            'Satuan',
            'Harga Satuan',
            'Total Nilai',
            '', // Kolom I (spacer)
            '', // Kolom J (label keterangan)
            ''  // Kolom K (nilai keterangan)
        ];
    }

    public function map($stok): array
    {
        static $rowNumber = 0;
        $rowNumber++;

        $kodeBarang = $stok->kode_barang ?? '-';
        $namaBarang = $stok->nama_barang ?? '-';
        $kategori = $stok->kategori ?? '-';
        $stokValue = $stok->stok ?? 0;
        $satuan = $stok->satuan ?? '-';
        $harga = $stok->harga ?? 0;
        $totalNilai = $stokValue * $harga;

        // Keterangan kolom J dan K
        $kolomJ = '';
        $kolomK = '';
        
        if ($rowNumber == 1) {
            $kolomJ = "Keterangan:";
        } elseif ($rowNumber == 2) {
            $kolomJ = "Data stok per tanggal:";
            $kolomK = ": " . now()->format('d F Y, H:i') . " WIB";
        } elseif ($rowNumber == 3) {
            $totalItemBarang = $this->stokBagian->count();
            $kolomJ = "Total item barang:";
            $kolomK = ": " . number_format($totalItemBarang, 0, ',', '.') . " jenis";
        } elseif ($rowNumber == 4) {
            $totalKeseluruhanStok = $this->stokBagian->sum('stok');
            $kolomJ = "Total keseluruhan stok:";
            $kolomK = ": " . number_format($totalKeseluruhanStok, 0, ',', '.') . " unit";
        } elseif ($rowNumber == 5) {
            $totalNilaiInventaris = $this->stokBagian->sum(function($item) {
                return ($item->stok ?? 0) * ($item->harga ?? 0);
            });
            $kolomJ = "Total nilai inventaris:";
            $kolomK = ": Rp " . number_format($totalNilaiInventaris, 0, ',', '.');
        }

        return [
            $rowNumber,
            $kodeBarang,
            $namaBarang,
            $kategori,
            $stokValue,
            $satuan,
            $harga,
            $totalNilai,
            '', // Kolom I (kosong sebagai spacer)
            $kolomJ, // Kolom J (label)
            $kolomK  // Kolom K (nilai)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max(1, $this->stokBagian->count() + 1);
        $dataRange = 'A1:H' . $lastRow;

        // Border untuk tabel utama
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Vertical alignment untuk semua data
        $sheet->getStyle('A2:H' . $lastRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // Horizontal alignment untuk tabel utama
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // No
        $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Kode Barang
        $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT); // Nama Barang
        $sheet->getStyle('D2:D' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Kategori
        $sheet->getStyle('E2:E' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Stok
        $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Satuan
        $sheet->getStyle('G2:G' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Harga Satuan
        $sheet->getStyle('H2:H' . $lastRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); // Total Nilai

        // Format number untuk kolom Harga dan Total Nilai
        $sheet->getStyle('G2:H' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);  // No
        $sheet->getColumnDimension('B')->setWidth(15); // Kode Barang
        $sheet->getColumnDimension('C')->setWidth(30); // Nama Barang
        $sheet->getColumnDimension('D')->setWidth(20); // Kategori
        $sheet->getColumnDimension('E')->setWidth(10); // Stok
        $sheet->getColumnDimension('F')->setWidth(10); // Satuan
        $sheet->getColumnDimension('G')->setWidth(18); // Harga Satuan
        $sheet->getColumnDimension('H')->setWidth(20); // Total Nilai
        $sheet->getColumnDimension('I')->setWidth(3);  // Spacer
        $sheet->getColumnDimension('J')->setWidth(30); // Label Keterangan
        $sheet->getColumnDimension('K')->setWidth(30); // Nilai Keterangan

        // Style untuk area keterangan (J2:K6)
        if ($this->stokBagian->count() > 0) {
            // Border untuk box keterangan
            $sheet->getStyle('J2:K6')->applyFromArray([
                'borders' => [
                    'outline' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                    'inside' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF8F9FA']
                ],
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ]
            ]);

            // Bold untuk judul "Keterangan:"
            $sheet->getStyle('J2')->getFont()->setBold(true);
            $sheet->getStyle('J2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Merge cell untuk judul
            $sheet->mergeCells('J2:K2');

            // Alignment untuk label (kolom J)
            $sheet->getStyle('J3:J6')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            
            // Alignment untuk nilai (kolom K)
            $sheet->getStyle('K3:K6')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        }

        // Style untuk header
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
        return 'Stok Terupdate';
    }
}