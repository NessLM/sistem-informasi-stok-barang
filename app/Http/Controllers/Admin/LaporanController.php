<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function index()
    {
        $menu = MenuHelper::adminMenu();
        $reports = $this->getAllExistingReports();

        return view('staff.admin.laporan', compact('menu', 'reports'));
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

    /**
     * Buat report data untuk kuartal tertentu
     */
    private function createQuarterlyReport($quarter, $year)
    {
        $quarterData = $this->getQuarterData($quarter, $year);
        $fileName = $this->generateFileName($quarter, $year);

        return [
            'title' => "LAPORAN STOCK OPNAME BULAN {$quarterData['month_range']} {$year}",
            'quarter' => $quarter,
            'year' => $year,
            'file_name' => $fileName,
            'exists' => true // Selalu true karena generate on-demand
        ];
    }

    /**
     * Dapatkan data kuartal berdasarkan nomor kuartal
     */
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

    /**
     * Generate nama file berdasarkan kuartal dan tahun
     */
    private function generateFileName($quarter, $year)
    {
        $quarterNames = [1 => 'Q1', 2 => 'Q2', 3 => 'Q3', 4 => 'Q4'];
        return "stock_opname_{$year}_{$quarterNames[$quarter]}.pdf";
    }

    /**
     * Preview laporan untuk quarter & year tertentu
     */
    public function previewLaporan($quarter, $year)
    {
        // Validasi input
        if (!in_array($quarter, [1, 2, 3, 4]) || $year < 2025 || $year > date('Y')) {
            abort(404, 'Laporan tidak ditemukan');
        }

        // Gunakan LaporanPDFController untuk mengambil data
        $pdfController = new LaporanPDFController();
        $riwayat = $pdfController->getRiwayatData($quarter, $year);
        
        return view('staff.admin.laporan-pdf', compact('quarter', 'year', 'riwayat'));
    }
}