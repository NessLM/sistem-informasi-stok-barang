<x-layouts.app title="Dashboard Admin" :menu="$menu">
  
  {{-- CSS khusus dashboard --}}
  <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/dashboard.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  
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
              <div class="card-number">{{ $totalJenisBarang }}</div>
              <div class="card-label">Total Jenis Barang</div>
            </div>
          </div>
          
          <div class="summary-card">
            <div class="card-icon">
              <i class="bi bi-box-seam"></i>
            </div>
            <div class="card-content">
              <div class="card-number">{{ $totalBarang }}</div>
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
            <div class="dropdown">
              <button class="filter-btn dropdown-toggle" type="button" id="bagianFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-funnel"></i> <span>Semua</span>
              </button>
              <ul class="dropdown-menu" aria-labelledby="bagianFilterDropdown">
                <li><a class="dropdown-item filter-option" href="#" data-type="bagian" data-value="all">Semua</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="bagian" data-value="week">1 Minggu Terakhir</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="bagian" data-value="month">1 Bulan Terakhir</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="bagian" data-value="year">1 Tahun Terakhir</a></li>
              </ul>
            </div>
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
          {{-- <div class="legend-item">
            <span class="legend-color masuk"></span>
            <span>Masuk</span>
          </div> --}}
        </div>
      </div>
    </div>

    {{-- Row kedua: Pengeluaran per Tahun (X-axis = Tahun) --}}
    <div class="dashboard-row">
      <div class="wide-chart-section">
        <div class="chart-header">
          <h2>Grafik Pengeluaran per Tahun</h2> {{-- [FIX] judul sudah per Tahun --}}
          <div class="chart-controls">
            <div class="dropdown">
              <button class="filter-btn dropdown-toggle" type="button" id="pengeluaranFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-funnel"></i> <span>Semua</span>
              </button>
              <ul class="dropdown-menu" aria-labelledby="pengeluaranFilterDropdown">
                {{-- [FIX] Filter tahun --}}
                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran" data-value="all">Semua</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran" data-value="5y">5 Tahun Terakhir</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran" data-value="7y">7 Tahun Terakhir</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran" data-value="10y">10 Tahun Terakhir</a></li>
              </ul>
            </div>
          </div>
        </div>
        <div class="chart-container">
          <canvas id="pengeluaranChart"></canvas>
        </div>
        {{-- [FIX] Legend sederhana: hanya keterangan dataset, bukan daftar tahun --}}
        <div class="chart-legend yearly">
          <div class="legend-item">
            <span class="legend-color" style="background:#8B5CF6"></span>
            <span>Keluar</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Chart.js --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // ====== Grafik Per Bagian ======
      const bagianData = {
        labels: {!! json_encode($bagianLabels) !!},
        datasets: [
          {
            label: 'Keluar',
            data: {!! json_encode($keluarData) !!},
            backgroundColor: '#EF4444',
            borderRadius: 4
          },
          {
            label: 'Masuk',
            data: {!! json_encode($masukData) !!},
            backgroundColor: '#22C55E',
            borderRadius: 4
          }
        ]
      };

      const bagianConfig = {
        type: 'bar',
        data: bagianData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { color: '#6B7280' }, grid: { color: '#F3F4F6' } },
            x: { ticks: { color: '#6B7280', maxRotation: 45, minRotation: 45 }, grid: { display: false } }
          }
        }
      };

      // ====== Pengeluaran per Tahun (X labels = Tahun, 1 dataset Keluar) ======
      const pengeluaranData = {
        labels: {!! json_encode($pengeluaranLabels) !!},   // TAHUN
        datasets: {!! json_encode($pengeluaranData) !!}     // 1 dataset 'Keluar'
      };

      const pengeluaranConfig = {
        type: 'bar',
        data: pengeluaranData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { color: '#6B7280' }, grid: { color: '#F3F4F6' } },
            x: { ticks: { color: '#6B7280' }, grid: { display: false } }
          }
        }
      };

      // Init charts
      const bagianChart = new Chart(document.getElementById('bagianChart').getContext('2d'), bagianConfig);
      const pengeluaranChart = new Chart(document.getElementById('pengeluaranChart').getContext('2d'), pengeluaranConfig);

      // ====== Dropdown filter ======
      document.querySelectorAll('.filter-option').forEach(item => {
        item.addEventListener('click', function(e) {
          e.preventDefault();
          const type  = this.getAttribute('data-type');
          const value = this.getAttribute('data-value');
          // ganti label tombol
          const btn = this.closest('.dropdown').querySelector('.dropdown-toggle');
          btn.innerHTML = `<i class="bi bi-funnel"></i> <span>${this.textContent}</span>`;
          if (type === 'bagian') filterBagianData(value);
          else filterPengeluaranData(value);
        });
      });

      // --- Filter Per Bagian (week/month/year) ---
      function filterBagianData(filterType) {
        fetch(`/admin/dashboard/filter?type=bagian&filter=${filterType}`)
          .then(r => r.json())
          .then(data => {
            bagianChart.data.datasets[0].data = data.keluar;
            bagianChart.data.datasets[1].data = data.masuk;
            bagianChart.update();
          })
          .catch(console.error);
      }

      // --- Filter Pengeluaran per Tahun (all | 5y | 7y | 10y) ---
      function filterPengeluaranData(filterType) {
        fetch(`/admin/dashboard/filter?type=pengeluaran&filter=${filterType}`)
          .then(r => r.json())
          .then(payload => {
            // [FIX] shape baru: { labels: [...tahun], data: [...] }
            pengeluaranChart.data.labels = payload.labels;
            if (pengeluaranChart.data.datasets.length === 0) {
              pengeluaranChart.data.datasets.push({
                label: 'Keluar',
                data: payload.data,
                backgroundColor: '#8B5CF6',
                borderRadius: 4
              });
            } else {
              pengeluaranChart.data.datasets[0].data = payload.data;
            }
            pengeluaranChart.update();
          })
          .catch(console.error);
      }
    });
  </script>

</x-layouts.app>
