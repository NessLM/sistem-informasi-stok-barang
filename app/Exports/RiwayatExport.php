<?php

namespace App\Exports;

use App\Models\Riwayat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
        
        // Sheet untuk barang masuk
        $masuk = $this->riwayat->where('alur_barang', 'Masuk')->sortByDesc('tanggal')->sortByDesc('waktu');
        $sheets[] = new RiwayatSheet($masuk, 'Barang Masuk');
        
        // Sheet untuk barang keluar
        $keluar = $this->riwayat->where('alur_barang', 'Keluar')->sortByDesc('tanggal')->sortByDesc('waktu');
        $sheets[] = new RiwayatSheet($keluar, 'Barang Keluar');
        
        return $sheets;
    }
}

class RiwayatSheet implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $riwayat;
    protected $sheetTitle;
    protected $counter;

    public function __construct($riwayat, $sheetTitle)
    {
        $this->riwayat = $riwayat;
        $this->sheetTitle = $sheetTitle;
        $this->counter = 0; // Inisialisasi counter untuk setiap sheet
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
            'Bagian',
            'Alur Barang'
        ];
    }

    public function map($riwayat): array
    {
        $this->counter++;
        
        return [
            $this->counter, // Gunakan counter instance, bukan static
            \Carbon\Carbon::parse($riwayat->tanggal)->format('d/m/Y'),
            \Carbon\Carbon::parse($riwayat->waktu)->format('H:i'),
            $riwayat->gudang,
            $riwayat->nama_barang,
            $riwayat->jumlah,
            $riwayat->bagian,
            $riwayat->alur_barang
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set title for the sheet
        $sheet->setTitle(substr($this->sheetTitle, 0, 31)); // Excel sheet title max 31 chars
        
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }
}