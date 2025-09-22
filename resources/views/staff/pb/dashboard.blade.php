<x-layouts.app title="Dashboard Pengelola Barang" :menu="$menu">
  
  {{-- CSS khusus dashboard PB --}}
  <link rel="stylesheet" href="{{ asset('assets/css/staff/pb/dashboard.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  
  <div class="dashboard-container">

    {{-- Row pertama: Ringkasan dan Grafik Barang Keluar --}}
    <div class="dashboard-row">
      {{-- ========================= RINGKASAN (SAMA DENGAN ADMIN) ========================= --}}
      <div class="summary-section">
        <h2>Ringkasan</h2>

        {{-- KLASIK: ikon bulat di kiri, angka & label di kanan (match CSS admin) --}}
        <div class="summary-cards summary-cards--classic">
          {{-- Card: Total Jenis Barang --}}
          <div class="summary-card summary-card--classic">
            <div class="summary-card__icon-circle">
              <i class="bi bi-box"></i>
            </div>
            <div class="summary-card__body">
              <div class="summary-card__number">{{ $totalJenisBarang }}</div>
              <div class="summary-card__label">Total Jenis Barang</div>
            </div>
          </div>

          {{-- Card: Total Barang --}}
          <div class="summary-card summary-card--classic">
            <div class="summary-card__icon-circle">
              <i class="bi bi-box-seam"></i>
            </div>
            <div class="summary-card__body">
              <div class="summary-card__number">{{ $totalBarang }}</div>
              <div class="summary-card__label">Total Barang</div>
            </div>
          </div>
        </div>
      </div>

      {{-- ======================= GRAFIK BARANG KELUAR ======================= --}}
      <div class="chart-section">
        {{-- Header dengan badge tanggal --}}
        <div class="chart-header chart-header--wrap">
          <div class="chart-header-left">
            <h2>Barang Keluar</h2>
            {{-- Badge keterangan rentang (diisi via JS) --}}
            <span id="rangeHintKategori" class="range-hint" title="Semua Data">Semua Data</span>
          </div>

          <div class="chart-filter">
            {{-- Filter waktu --}}
            <div class="dropdown">
              <button class="filter-btn dropdown-toggle" type="button" id="kategoriFilterDropdown" aria-expanded="false">
                <i class="bi bi-funnel"></i> Semua
              </button>
              <ul class="dropdown-menu" aria-labelledby="kategoriFilterDropdown">
                <li><a class="dropdown-item filter-option" href="#" data-type="kategori" data-value="all">Semua</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="kategori" data-value="week">1 Minggu Terakhir</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="kategori" data-value="month">1 Bulan Terakhir</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="kategori" data-value="year">1 Tahun Terakhir</a></li>
              </ul>
            </div>
          </div>
        </div>

        <div class="chart-container">
          <canvas id="kategoriChart"></canvas>
        </div>
        
        <div class="chart-legend">
          <div class="legend-item">
            <span class="legend-color atk"></span>
            <span>G. ATK</span>
          </div>
          <div class="legend-item">
            <span class="legend-color kebersihan"></span>
            <span>G. Kebersihan</span>
          </div>
          <div class="legend-item">
            <span class="legend-color listrik"></span>
            <span>G. Listrik</span>
          </div>
          <div class="legend-item">
            <span class="legend-color komputer"></span>
            <span>G.B. Komputer</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Row kedua: Grafik Barang Masuk dan Keluar --}}
    <div class="dashboard-row">
      <div class="wide-chart-section">
        <div class="chart-header">
          <h2>Grafik Barang Masuk dan Keluar</h2>
          <div class="chart-controls">
            <div class="dropdown">
              <button class="filter-btn dropdown-toggle" type="button" id="masukKeluarFilterDropdown" aria-expanded="false">
                <i class="bi bi-funnel"></i> 5 Tahun
              </button>
              <ul class="dropdown-menu" aria-labelledby="masukKeluarFilterDropdown">
                <li><a class="dropdown-item filter-option" href="#" data-type="masukkeluar" data-value="3y">3 Tahun Terakhir</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="masukkeluar" data-value="5y">5 Tahun (2021-2025)</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="masukkeluar" data-value="7y">7 Tahun Terakhir</a></li>
              </ul>
            </div>
          </div>
        </div>
        
        <div class="chart-container">
          <canvas id="masukKeluarChart"></canvas>
        </div>
        
        <div class="chart-legend">
          <div class="legend-item">
            <span class="legend-color masuk"></span>
            <span>Barang Masuk</span>
          </div>
          <div class="legend-item">
            <span class="legend-color keluar"></span>
            <span>Barang Keluar</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Chart.js Library --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const FILTER_URL = "{{ route('pb.dashboard.filter') }}";

      /* ====================== Helper Functions (harus di atas) ====================== */
      function fmt(d){ 
        const z=n=>String(n).padStart(2,'0'); 
        return `${z(d.getDate())}/${z(d.getMonth()+1)}/${d.getFullYear()}`;
      }
      function setRangeHint(el, text, titleText){ 
        if(!el) return; 
        el.textContent = text; 
        el.title = titleText || text; 
      }

      /* ====================== Grafik Barang Keluar per Kategori ====================== */
      const kategoriLabels = {!! json_encode($keluarPerKategoriLabels) !!};
      const kategoriData = {!! json_encode($keluarPerKategoriData) !!};
      const kategoriColors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6'];

      const kategoriChart = new Chart(document.getElementById('kategoriChart').getContext('2d'), {
        type: 'bar',
        data: {
          labels: kategoriLabels,
          datasets: [{
            label: 'Barang Keluar',
            data: kategoriData,
            backgroundColor: kategoriColors,
            borderRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { 
            legend: { display: false },
            tooltip: {
              callbacks: {
                title: function(context) {
                  return context[0].label;
                },
                label: function(context) {
                  return context.dataset.label + ': ' + context.parsed.y + ' barang';
                }
              }
            }
          },
          scales: {
            y: { 
              beginAtZero: true, 
              ticks: { 
                color: '#6B7280',
                callback: function(value) {
                  return value + ' barang';
                }
              }, 
              grid: { color: '#F3F4F6' },
              title: {
                display: true,
                text: 'Jumlah Barang',
                color: '#374151',
                font: {
                  size: 12,
                  weight: 'bold'
                }
              }
            },
            x: { 
              ticks: { color: '#6B7280', maxRotation: 45, minRotation: 45 }, 
              grid: { display: false },
              title: {
                display: true,
                text: 'Kategori',
                color: '#374151',
                font: {
                  size: 12,
                  weight: 'bold'
                }
              }
            }
          },
          interaction: {
            mode: 'index',
            intersect: false,
          }
        }
      });

      /* ====================== Grafik Barang Masuk dan Keluar ====================== */
      const masukKeluarData = {
        labels: {!! json_encode($years) !!},
        datasets: {!! json_encode($masukKeluarData) !!}
      };

      const masukKeluarChart = new Chart(document.getElementById('masukKeluarChart').getContext('2d'), {
        type: 'bar',
        data: masukKeluarData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { 
            legend: { display: false },
            tooltip: {
              callbacks: {
                title: function(context) {
                  return 'Tahun ' + context[0].label;
                },
                label: function(context) {
                  return context.dataset.label + ': ' + context.parsed.y + ' barang';
                }
              }
            }
          },
          scales: {
            y: { 
              beginAtZero: true, 
              ticks: { 
                color: '#6B7280',
                callback: function(value) {
                  return value + ' barang';
                }
              }, 
              grid: { color: '#F3F4F6' },
              title: {
                display: true,
                text: 'Jumlah Barang',
                color: '#374151',
                font: {
                  size: 12,
                  weight: 'bold'
                }
              }
            },
            x: { 
              ticks: { color: '#6B7280' }, 
              grid: { display: false },
              title: {
                display: true,
                text: 'Tahun',
                color: '#374151',
                font: {
                  size: 12,
                  weight: 'bold'
                }
              }
            }
          },
          interaction: {
            mode: 'index',
            intersect: false,
          }
        }
      });

      /* ====================== Dropdown Toggle Functionality ====================== */
      // Handle dropdown toggle
      document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          
          console.log('Dropdown clicked'); // Debug log
          
          // Close other dropdowns first
          document.querySelectorAll('.dropdown-menu').forEach(menu => {
            if (menu !== this.nextElementSibling) {
              menu.classList.remove('show');
            }
          });
          
          // Toggle current dropdown
          const menu = this.nextElementSibling;
          if (menu) {
            menu.classList.toggle('show');
            console.log('Menu toggled, show class:', menu.classList.contains('show')); // Debug log
          }
        });
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
          document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
          });
        }
      });

      /* ====================== Filter Functions ====================== */
      // Fungsi untuk filter data grafik kategori
      function filterKategori(filterType) {
        fetch(`${FILTER_URL}?type=kategori&filter=${filterType}`)
          .then(response => {
            if (!response.ok) {
              throw new Error('Network response was not ok');
            }
            return response.json();
          })
          .then(data => {
            kategoriChart.data.labels = data.labels;
            kategoriChart.data.datasets[0].data = data.data;
            kategoriChart.data.datasets[0].backgroundColor = data.colors;
            kategoriChart.update('active');

            // Update badge rentang waktu
            const hint = document.getElementById('rangeHintKategori');
            if (data.range && data.range.start && data.range.end){
              const s = new Date(data.range.start), e = new Date(data.range.end);
              const txt = `${fmt(s)} – ${fmt(e)}`;
              setRangeHint(hint, txt, txt);
            } else {
              setRangeHint(hint, 'Semua Data', 'Semua Data');
            }
          })
          .catch(error => {
            console.error('Error filtering kategori data:', error);
            // Restore original data on error
            kategoriChart.data.labels = kategoriLabels;
            kategoriChart.data.datasets[0].data = kategoriData;
            kategoriChart.data.datasets[0].backgroundColor = kategoriColors;
            kategoriChart.update();
            setRangeHint(document.getElementById('rangeHintKategori'), 'Semua Data', 'Semua Data');
          });
      }

      // Fungsi untuk filter data grafik masuk keluar
      function filterMasukKeluar(filterType) {
        // Show loading state
        masukKeluarChart.data.datasets = [];
        masukKeluarChart.update();
        
        fetch(`${FILTER_URL}?type=masukkeluar&filter=${filterType}`)
          .then(response => {
            if (!response.ok) {
              throw new Error('Network response was not ok');
            }
            return response.json();
          })
          .then(data => {
            masukKeluarChart.data.labels = data.labels;
            masukKeluarChart.data.datasets = data.datasets;
            masukKeluarChart.update('active');
          })
          .catch(error => {
            console.error('Error filtering masuk keluar data:', error);
            // Restore original data on error
            masukKeluarChart.data.labels = {!! json_encode($years) !!};
            masukKeluarChart.data.datasets = {!! json_encode($masukKeluarData) !!};
            masukKeluarChart.update();
          });
      }

      /* ====================== Filter Dropdown Functionality ====================== */
      document.querySelectorAll('.filter-option').forEach(item => {
        item.addEventListener('click', function(e) {
          e.preventDefault();
          const type  = this.getAttribute('data-type');
          const value = this.getAttribute('data-value');
          
          // Update teks pada tombol dropdown
          const dropdownButton = this.closest('.dropdown').querySelector('.dropdown-toggle');
          dropdownButton.innerHTML = `<i class="bi bi-funnel"></i> ${this.textContent}`;
          
          // Close dropdown
          this.closest('.dropdown-menu').classList.remove('show');
          
          if (type === 'kategori') {
            filterKategori(value);
          } else if (type === 'masukkeluar') {
            filterMasukKeluar(value);
          }
        });
      });

      /* ====================== Chart Resize Handler ====================== */
      window.addEventListener('resize', function() {
        kategoriChart.resize();
        masukKeluarChart.resize();
      });

      // Set awal badge "Semua Data"
      setRangeHint(document.getElementById('rangeHintKategori'), 'Semua Data', 'Semua Data');
    });
  </script>

</x-layouts.app>