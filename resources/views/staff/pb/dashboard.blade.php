<x-layouts.app title="Dashboard Pengelola Barang" :menu="$menu">
  
  {{-- CSS khusus dashboard PB --}}
  <link rel="stylesheet" href="{{ asset('assets/css/staff/pb/dashboard.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  
  <div class="dashboard-container">

    {{-- Row pertama: Ringkasan dan Grafik Barang Keluar --}}
    <div class="dashboard-row">
      {{-- Kolom Ringkasan --}}
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

      {{-- Kolom Grafik Barang Keluar --}}
      <div class="chart-section">
        <div class="chart-header">
          <h2>Barang Keluar</h2>
          <div class="chart-filter">
            <div class="dropdown">
              <button class="filter-btn dropdown-toggle" type="button" id="kategoriFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-funnel"></i> 5 Tahun
              </button>
              <ul class="dropdown-menu" aria-labelledby="kategoriFilterDropdown">
                <li><a class="dropdown-item filter-option" href="#" data-type="kategori" data-value="3y">3 Tahun Terakhir</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="kategori" data-value="5y">5 Tahun (2021-2025)</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="kategori" data-value="7y">7 Tahun Terakhir</a></li>
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
              <button class="filter-btn dropdown-toggle" type="button" id="masukKeluarFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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

      /* ====================== Grafik Barang Keluar per Kategori ====================== */
      const kategoriData = {
        labels: {!! json_encode($years) !!},
        datasets: {!! json_encode($keluarPerKategori) !!}
      };

      const kategoriChart = new Chart(document.getElementById('kategoriChart').getContext('2d'), {
        type: 'bar',
        data: kategoriData,
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
          
          // Close other dropdowns
          document.querySelectorAll('.dropdown-menu').forEach(menu => {
            if (menu !== this.nextElementSibling) {
              menu.classList.remove('show');
            }
          });
          
          // Toggle current dropdown
          const menu = this.nextElementSibling;
          menu.classList.toggle('show');
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

      /* ====================== Filter Dropdown Functionality ====================== */
      document.querySelectorAll('.filter-option').forEach(item => {
        item.addEventListener('click', function(e) {
          e.preventDefault();
          const type  = this.getAttribute('data-type');
          const value = this.getAttribute('data-value');
          
          // Update teks pada tombol dropdown
          const dropdownButton = this.closest('.dropdown').querySelector('.dropdown-toggle');
          let buttonText = '';
          
          if (value === '3y') buttonText = '3 Tahun Terakhir';
          else if (value === '5y') buttonText = '5 Tahun (2021-2025)';
          else if (value === '7y') buttonText = '7 Tahun Terakhir';
          
          dropdownButton.innerHTML = `<i class="bi bi-funnel"></i> ${buttonText}`;
          
          // Close dropdown
          this.closest('.dropdown-menu').classList.remove('show');
          
          if (type === 'kategori') {
            filterKategori(value);
          } else if (type === 'masukkeluar') {
            filterMasukKeluar(value);
          }
        });
      });

      /* ====================== Filter Functions ====================== */
      // Fungsi untuk filter data grafik kategori
      function filterKategori(filterType) {
        // Show loading state
        kategoriChart.data.datasets = [];
        kategoriChart.update();
        
        fetch(`${FILTER_URL}?type=kategori&filter=${filterType}`)
          .then(response => {
            if (!response.ok) {
              throw new Error('Network response was not ok');
            }
            return response.json();
          })
          .then(data => {
            kategoriChart.data.labels = data.labels;
            kategoriChart.data.datasets = data.datasets;
            kategoriChart.update('active');
          })
          .catch(error => {
            console.error('Error filtering kategori data:', error);
            // Restore original data on error
            kategoriChart.data.labels = {!! json_encode($years) !!};
            kategoriChart.data.datasets = {!! json_encode($keluarPerKategori) !!};
            kategoriChart.update();
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

      /* ====================== Chart Resize Handler ====================== */
      window.addEventListener('resize', function() {
        kategoriChart.resize();
        masukKeluarChart.resize();
      });
    });
  </script>

</x-layouts.app>