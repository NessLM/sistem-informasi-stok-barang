<?php
// App\Http\Controllers\Pb\LaporanController.php
namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use App\Http\Controllers\Pb\LaporanPDFController;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function index()
    {
        $menu = MenuHelper::pbMenu();
        $reports = $this->getAllExistingReports();

        return view('staff.pb.laporan', compact('menu', 'reports'));
    }

    public function previewLaporan($quarter, $year)
    {
        // Validasi input
        if (!in_array($quarter, [1, 2, 3, 4]) || $year < 2025 || $year > date('Y')) {
            abort(404, 'Laporan tidak ditemukan');
        }

        // Gunakan LaporanPDFController untuk mengambil data
        $pdfController = new LaporanPDFController();
        $riwayat = $pdfController->getRiwayatData($quarter, $year);
        $rekapKategori = $pdfController->getRekapKategoriTriwulan($quarter, $year);
        $stockOpnameData = $pdfController->getStockOpnameData($quarter, $year);

        return view('staff.pb.laporan-pdf', [
            'quarter' => $quarter,
            'year' => $year,
            'riwayat' => $riwayat,
            'rekapKategori' => $rekapKategori,
            'stockOpnameData' => $stockOpnameData,
            'isDownload' => false,
        ]);
    }

    public function downloadPDF($quarter, $year)
{
    // Validasi input
    if (!in_array($quarter, [1, 2, 3, 4]) || $year < 2025 || $year > date('Y')) {
        abort(404, 'Laporan tidak ditemukan');
    }

    // Gunakan LaporanPDFController untuk mengambil data
    $pdfController = new LaporanPDFController();
    $riwayat = $pdfController->getRiwayatData($quarter, $year);
    $rekapKategori = $pdfController->getRekapKategoriTriwulan($quarter, $year);
    $stockOpnameData = $pdfController->getStockOpnameData($quarter, $year);

    // Data untuk view
    $data = [
        'quarter' => $quarter,
        'year' => $year,
        'riwayat' => $riwayat,
        'rekapKategori' => $rekapKategori,
        'stockOpnameData' => $stockOpnameData,
        'isDownload' => true, // ⬅️ TAMBAHKAN FLAG INI
    ];

    // Generate PDF
    $pdf = Pdf::loadView('staff.pb.laporan-pdf', $data);
    
    // Set paper orientation to landscape for better table display
    $pdf->setPaper('A4', 'portrait');
    
    // Nama file untuk download
    $filename = "Laporan_Stock_Opname_Q{$quarter}_{$year}.pdf";
    
    return $pdf->download($filename);
}

    private function getAllExistingReports()
    {
        $reports = [];

        // Generate laporan untuk kuartal-kuartal yang tersedia
        $currentYear = date('Y');
        $currentMonth = date('n');
        $currentQuarter = ceil($currentMonth / 3);

        $startYear = 2025; // Tahun awal sistem

        for ($year = $startYear; $year <= $currentYear; $year++) {
            $maxQuarter = ($year == $currentYear) ? $currentQuarter : 4;

            for ($quarter = 1; $quarter <= $maxQuarter; $quarter++) {
                $reports[] = $this->createQuarterlyReport($quarter, $year);
            }
        }

        // Urutkan dari yang terbaru ke terlama
        usort($reports, function ($a, $b) {
            if ($a['year'] === $b['year']) {
                return $b['quarter'] - $a['quarter'];
            }
            return $b['year'] - $a['year'];
        });

        return $reports;
    }

    private function createQuarterlyReport($quarter, $year)
    {
        $quarterData = $this->getQuarterData($quarter, $year);

        return [
            'title' => "LAPORAN STOCK OPNAME BULAN {$quarterData['month_range']} {$year}",
            'quarter' => $quarter,
            'year' => $year,
            'exists' => true // Selalu true karena generate on-demand
        ];
    }

    private function getQuarterData($quarter, $year)
    {
        $quarters = [
            1 => ['month_range' => 'JANUARI – MARET', 'start_month' => 1, 'end_month' => 3],
            2 => ['month_range' => 'APRIL – JUNI', 'start_month' => 4, 'end_month' => 6],
            3 => ['month_range' => 'JULI – SEPTEMBER', 'start_month' => 7, 'end_month' => 9],
            4 => ['month_range' => 'OKTOBER – DESEMBER', 'start_month' => 10, 'end_month' => 12]
        ];

        return $quarters[$quarter] ?? $quarters[1];
    }
}