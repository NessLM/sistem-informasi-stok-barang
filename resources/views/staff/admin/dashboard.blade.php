<x-layouts.app title="Dashboard Admin" :menu="$menu">
  {{-- CSS --}}
  <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/dashboard.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

  <div class="dashboard-container">

    {{-- ===== Row 1: Per Bagian ===== --}}
    <div class="dashboard-row">
      <div class="summary-section">
        <h2>Ringkasan</h2>
        <div class="summary-cards">
          <div class="summary-card">
            <div class="card-icon"><i class="bi bi-archive"></i></div>
            <div class="card-content">
              <div class="card-number">{{ $totalJenisBarang }}</div>
              <div class="card-label">Total Jenis Barang</div>
            </div>
          </div>
          <div class="summary-card">
            <div class="card-icon"><i class="bi bi-box-seam"></i></div>
            <div class="card-content">
              <div class="card-number">{{ $totalBarang }}</div>
              <div class="card-label">Total Barang</div>
            </div>
          </div>
        </div>
      </div>

      <div class="chart-section">
        <div class="chart-header">
          <h2>Grafik Per Bagian</h2>
          <div class="chart-filter">
            <div class="dropdown">
              <button class="filter-btn dropdown-toggle" type="button" id="bagianFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-funnel"></i> Semua
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

        {{-- [FIX] legend: tampilkan Masuk & Keluar --}}
        <div class="chart-legend">
          <div class="legend-item"><span class="legend-color keluar"></span><span>Keluar</span></div>
          <div class="legend-item"><span class="legend-color masuk"></span><span>Masuk</span></div>
        </div>
      </div>
    </div>

    {{-- ===== Row 2: Pengeluaran per Tahun ===== --}}
    <div class="dashboard-row">
      <div class="wide-chart-section">
        <div class="chart-header">
          <h2>Grafik Pengeluaran per Tahun</h2>
          <div class="chart-controls">
            <div class="dropdown">
              <button class="filter-btn dropdown-toggle" type="button" id="pengeluaranFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-funnel"></i> Semua
              </button>
              <ul class="dropdown-menu" aria-labelledby="pengeluaranFilterDropdown">
                {{-- [FIX] opsi filter sesuai tahun --}}
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

        {{-- [FIX] legend tahun dinamis (bukan "Keluar") --}}
        <div class="chart-legend yearly" id="legendYears"></div>
      </div>
    </div>
  </div>

  {{-- Chart.js --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      /* ===== Grafik Per Bagian ===== */
      const bagianData = {
        labels: {!! json_encode($bagianLabels) !!},
        datasets: [
          { label: 'Keluar', data: {!! json_encode($keluarData) !!}, backgroundColor: '#EF4444', borderRadius: 4 },
          { label: 'Masuk',  data: {!! json_encode($masukData) !!},  backgroundColor: '#22C55E', borderRadius: 4 }
        ]
      };
      const bagianChart = new Chart(document.getElementById('bagianChart').getContext('2d'), {
        type: 'bar',
        data: bagianData,
        options: {
          responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { color: '#6B7280' }, grid: { color: '#F3F4F6' } },
            x: { ticks: { color: '#6B7280', maxRotation: 45, minRotation: 45 }, grid: { display: false } }
          }
        }
      });

      /* ===== Pengeluaran per Tahun ===== */
      const initialYears  = {!! json_encode($years) !!};
      const initialColors = {!! json_encode($colorsForYears) !!};
      const pengeluaranData = {
        labels: {!! json_encode($pengeluaranLabels) !!}, // tahun
        datasets: {!! json_encode($pengeluaranData) !!}   // 1 dataset, warna per-bar
      };
      const pengeluaranChart = new Chart(document.getElementById('pengeluaranChart').getContext('2d'), {
        type: 'bar',
        data: pengeluaranData,
        options: {
          responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { color: '#6B7280' }, grid: { color: '#F3F4F6' } },
            x: { ticks: { color: '#6B7280' }, grid: { display: false } }
          }
        }
      });

      // [FIX] render legend tahun (chip warna per tahun)
      function renderYearLegend(years, colorsMap){
        const box = document.getElementById('legendYears');
        box.innerHTML = '';
        years.forEach(y => {
          const item  = document.createElement('div');
          item.className = 'legend-item';
          const color = document.createElement('span');
          color.className = 'legend-color';
          color.style.backgroundColor = colorsMap[y] || '#8B5CF6';
          const text  = document.createElement('span');
          text.textContent = y;
          item.appendChild(color); item.appendChild(text);
          box.appendChild(item);
        });
      }
      renderYearLegend(initialYears, initialColors);

      // ===== Dropdown filter handling =====
      document.querySelectorAll('.filter-option').forEach(el => {
        el.addEventListener('click', function(e) {
          e.preventDefault();
          const type  = this.getAttribute('data-type');
          const value = this.getAttribute('data-value');
          // update label tombol
          this.closest('.dropdown').querySelector('.dropdown-toggle').innerHTML =
            `<i class="bi bi-funnel"></i> ${this.textContent}`;
          if (type === 'bagian') filterBagian(value);
          else filterPengeluaran(value);
        });
      });

      // [FIX] filter per bagian
      function filterBagian(filterType){
        fetch(`/admin/dashboard/filter?type=bagian&filter=${filterType}`)
          .then(r => r.json())
          .then(d => {
            bagianChart.data.datasets[0].data = d.keluar;
            bagianChart.data.datasets[1].data = d.masuk;
            bagianChart.update();
          })
          .catch(console.error);
      }

      // [FIX] filter per tahun
      function filterPengeluaran(filterType){
        fetch(`/admin/dashboard/filter?type=pengeluaran&filter=${filterType}`)
          .then(r => r.json())
          .then(payload => {
            // payload: { labels: [...tahun], data: [...], colors: {tahun: warna} }
            pengeluaranChart.data.labels = payload.labels;
            if (pengeluaranChart.data.datasets.length === 0) {
              pengeluaranChart.data.datasets.push({
                label: 'Keluar', data: payload.data,
                backgroundColor: payload.labels.map(y => payload.colors[y] || '#8B5CF6'),
                borderRadius: 4
              });
            } else {
              pengeluaranChart.data.datasets[0].data = payload.data;
              pengeluaranChart.data.datasets[0].backgroundColor =
                payload.labels.map(y => payload.colors[y] || '#8B5CF6');
            }
            pengeluaranChart.update();
            renderYearLegend(payload.labels, payload.colors);
          })
          .catch(console.error);
      }
    });
  </script>
</x-layouts.app>
