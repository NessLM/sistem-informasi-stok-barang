<x-layouts.app title="Dashboard Admin" :menu="$menu">
  
  {{-- CSS khusus dashboard --}}
  <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/dashboard.css') }}">
  
  <div class="dashboard-container">

    {{-- Row pertama: Ringkasan dan Grafik Per Bagian --}}
    <div class="dashboard-row">
      {{-- Kolom Ringkasan --}}
      <div class="summary-section">
        <h2>Ringkasan</h2>
        <div class="summary-cards">
          <div class="summary-card">
            <div class="card-icon">
              <i class="bi bi-archive"></i>
            </div>
            <div class="card-content">
              <div class="card-number">110</div>
              <div class="card-label">Total Jenis Barang</div>
            </div>
          </div>
          
          <div class="summary-card">
            <div class="card-icon">
              <i class="bi bi-box-seam"></i>
            </div>
            <div class="card-content">
              <div class="card-number">880</div>
              <div class="card-label">Total Barang</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Kolom Grafik Per Bagian --}}
      <div class="chart-section">
        <div class="chart-header">
          <h2>Grafik Per Bagian</h2>
          <div class="chart-filter">
            <button class="filter-btn">
              <i class="bi bi-funnel"></i>
            </button>
          </div>
        </div>
        <div class="chart-container">
          <canvas id="bagianChart"></canvas>
        </div>
        <div class="chart-legend">
          <div class="legend-item">
            <span class="legend-color keluar"></span>
            <span>Keluar</span>
          </div>
          <div class="legend-item">
            <span class="legend-color masuk"></span>
            <span>Masuk</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Row kedua: Grafik Pengeluaran per Waktu --}}
    <div class="dashboard-row">
      <div class="wide-chart-section">
        <div class="chart-header">
          <h2>Grafik Pengeluaran per Waktu</h2>
          <div class="chart-controls">
            <select class="sort-select">
              <option value="default">Sortir</option>
              <option value="asc">Ascending</option>
              <option value="desc">Descending</option>
            </select>
          </div>
        </div>
        <div class="chart-container">
          <canvas id="pengeluaranChart"></canvas>
        </div>
        <div class="chart-legend yearly">
          <div class="legend-item">
            <span class="legend-color year-2020"></span>
            <span>2020</span>
          </div>
          <div class="legend-item">
            <span class="legend-color year-2021"></span>
            <span>2021</span>
          </div>
          <div class="legend-item">
            <span class="legend-color year-2022"></span>
            <span>2022</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Chart.js Library --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Data untuk grafik per bagian
      const bagianData = {
        labels: ['Tata Pemerintahan', 'Kesra & Kemasy', 'Hukum & HAM', 'ADM Pembangunan', 'Perekonomian', 'Pengadaan Barang', 'Protokol'],
        datasets: [
          {
            label: 'Keluar',
            data: [95, 200, 75, 85, 90, 50, 85],
            backgroundColor: '#EF4444',
            borderRadius: 4
          },
          {
            label: 'Masuk',
            data: [80, 110, 95, 100, 85, 95, 75],
            backgroundColor: '#22C55E',
            borderRadius: 4
          }
        ]
      };

      // Konfigurasi grafik per bagian
      const bagianConfig = {
        type: 'bar',
        data: bagianData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              max: 200,
              ticks: {
                stepSize: 40,
                color: '#6B7280'
              },
              grid: {
                color: '#F3F4F6'
              }
            },
            x: {
              ticks: {
                color: '#6B7280',
                maxRotation: 45,
                minRotation: 45
              },
              grid: {
                display: false
              }
            }
          }
        }
      };

      // Data untuk grafik pengeluaran per waktu
      const pengeluaranData = {
        labels: ['Tata Pem', 'Kesra', 'Hukum', 'ADM Pemb', 'Perekono', 'Pengadaan', 'Protokol', 'Umum', 'Keuangan', 'Kepegaw', 'Humas'],
        datasets: [
          {
            label: '2020',
            data: [70, 55, 87, 31, 20, 95, 15, 48, 77, 97, 61],
            backgroundColor: '#8B5CF6'
          },
          {
            label: '2021',
            data: [35, 65, 75, 30, 16, 12, 19, 43, 82, 78, 31],
            backgroundColor: '#F87171'
          },
          {
            label: '2022',
            data: [65, 35, 68, 36, 34, 85, 49, 58, 52, 64, 72],
            backgroundColor: '#06B6D4'
          }
        ]
      };

      // Konfigurasi grafik pengeluaran per waktu
      const pengeluaranConfig = {
        type: 'bar',
        data: pengeluaranData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              max: 100,
              ticks: {
                stepSize: 20,
                color: '#6B7280'
              },
              grid: {
                color: '#F3F4F6'
              }
            },
            x: {
              ticks: {
                color: '#6B7280'
              },
              grid: {
                display: false
              }
            }
          }
        }
      };

      // Inisialisasi charts
      const bagianCtx = document.getElementById('bagianChart').getContext('2d');
      new Chart(bagianCtx, bagianConfig);

      const pengeluaranCtx = document.getElementById('pengeluaranChart').getContext('2d');
      new Chart(pengeluaranCtx, pengeluaranConfig);
    });
  </script>

</x-layouts.app>