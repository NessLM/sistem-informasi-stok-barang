<?php

namespace App\Exports;

use App\Models\Riwayat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RiwayatExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $riwayat;
    protected $filter;

    public function __construct($riwayat, $filter)
    {
        $this->riwayat = $riwayat;
        $this->filter = $filter;
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
        static $i = 1;
        return [
            $i++,
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
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }
}