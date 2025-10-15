<?php

namespace App\Exports;

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
        $masuk = $this->riwayat->where('alur_barang', 'Masuk PB')->sortByDesc('tanggal')->sortByDesc('waktu');
        if ($masuk->count() > 0) {
            $sheets[] = new RiwayatMasukSheet($masuk, 'Barang Masuk');
        }
        
        // Sheet untuk distribusi
        $distribusi = $this->riwayat->where('alur_barang', 'Distribusi PJ')->sortByDesc('tanggal')->sortByDesc('waktu');
        if ($distribusi->count() > 0) {
            $sheets[] = new RiwayatDistribusiSheet($distribusi, 'Distribusi Barang');
        }
        
        // Sheet untuk barang keluar
        $keluar = $this->riwayat->where('alur_barang', 'Keluar PJ')->sortByDesc('tanggal')->sortByDesc('waktu');
        if ($keluar->count() > 0) {
            $sheets[] = new RiwayatKeluarSheet($keluar, 'Barang Keluar');
        }
        
        return $sheets;
    }
}

class RiwayatMasukSheet implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $riwayat;
    protected $sheetTitle;
    protected $counter;

    public function __construct($riwayat, $sheetTitle)
    {
        $this->riwayat = $riwayat;
        $this->sheetTitle = $sheetTitle;
        $this->counter = 0;
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
            'Keterangan'
        ];
    }

    public function map($riwayat): array
    {
        $this->counter++;
        
        return [
            $this->counter,
            \Carbon\Carbon::parse($riwayat->tanggal)->format('d/m/Y'),
            \Carbon\Carbon::parse($riwayat->waktu)->format('H:i'),
            $riwayat->gudang,
            $riwayat->nama_barang,
            $riwayat->jumlah,
            $riwayat->keterangan ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setTitle(substr($this->sheetTitle, 0, 31));
        
        // Auto size columns
        foreach(range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

class RiwayatDistribusiSheet implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $riwayat;
    protected $sheetTitle;
    protected $counter;

    public function __construct($riwayat, $sheetTitle)
    {
        $this->riwayat = $riwayat;
        $this->sheetTitle = $sheetTitle;
        $this->counter = 0;
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
            'Keterangan'
        ];
    }

    public function map($riwayat): array
    {
        $this->counter++;
        
        return [
            $this->counter,
            \Carbon\Carbon::parse($riwayat->tanggal)->format('d/m/Y'),
            \Carbon\Carbon::parse($riwayat->waktu)->format('H:i'),
            $riwayat->gudang,
            $riwayat->nama_barang,
            $riwayat->jumlah,
            $riwayat->keterangan ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setTitle(substr($this->sheetTitle, 0, 31));
        
        // Auto size columns
        foreach(range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

class RiwayatKeluarSheet implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $riwayat;
    protected $sheetTitle;
    protected $counter;

    public function __construct($riwayat, $sheetTitle)
    {
        $this->riwayat = $riwayat;
        $this->sheetTitle = $sheetTitle;
        $this->counter = 0;
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
            'Gudang Asal',
            'Nama Barang',
            'Jumlah',
            'Bagian',
            'Penerima',
            'Keterangan'
        ];
    }

    public function map($riwayat): array
    {
        $this->counter++;
        
        return [
            $this->counter,
            \Carbon\Carbon::parse($riwayat->tanggal)->format('d/m/Y'),
            \Carbon\Carbon::parse($riwayat->waktu)->format('H:i'),
            $riwayat->gudang,
            $riwayat->nama_barang,
            $riwayat->jumlah,
            $riwayat->bagian,
            $riwayat->penerima,
            $riwayat->keterangan ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setTitle(substr($this->sheetTitle, 0, 31));
        
        // Auto size columns
        foreach(range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}