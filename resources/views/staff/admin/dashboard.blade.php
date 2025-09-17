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
                <i class="bi bi-funnel"></i> Filter
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
            <div class="dropdown">
              <button class="filter-btn dropdown-toggle" type="button" id="pengeluaranFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-funnel"></i> Filter
              </button>
              <ul class="dropdown-menu" aria-labelledby="pengeluaranFilterDropdown">
                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran" data-value="all">Semua</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran" data-value="week">1 Minggu Terakhir</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran" data-value="month">1 Bulan Terakhir</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran" data-value="year">1 Tahun Terakhir</a></li>
              </ul>
            </div>
          </div>
        </div>
        <div class="chart-container">
          <canvas id="pengeluaranChart"></canvas>
        </div>
        <div class="chart-legend yearly">
          @foreach($years as $year)
          <div class="legend-item">
            <span class="legend-color year-{{ $year }}"></span>
            <span>{{ $year }}</span>
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  {{-- Chart.js Library --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Data untuk grafik per bagian (dari database)
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
              ticks: {
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

      // Data untuk grafik pengeluaran per waktu (dari database)
      const pengeluaranData = {
        labels: {!! json_encode($pengeluaranLabels) !!},
        datasets: {!! json_encode($pengeluaranData) !!}
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
              ticks: {
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
      const bagianChart = new Chart(bagianCtx, bagianConfig);

      const pengeluaranCtx = document.getElementById('pengeluaranChart').getContext('2d');
      const pengeluaranChart = new Chart(pengeluaranCtx, pengeluaranConfig);

      // Fungsi untuk mengubah warna legend berdasarkan tahun
      function updateLegendColors() {
        const years = {!! json_encode($years) !!};
        const colors = {!! json_encode($colorsForYears) !!};
        
        years.forEach((year) => {
          const colorElement = document.querySelector(`.year-${year}`);
          if (colorElement && colors[year]) {
            colorElement.style.backgroundColor = colors[year];
          }
        });
      }

      // Panggil fungsi untuk mengubah warna legend
      updateLegendColors();

      // Filter dropdown functionality
      document.querySelectorAll('.filter-option').forEach(item => {
        item.addEventListener('click', function(e) {
          e.preventDefault();
          const type = this.getAttribute('data-type');
          const value = this.getAttribute('data-value');
          
          // Update teks pada tombol dropdown
          const dropdownButton = this.closest('.dropdown').querySelector('.dropdown-toggle');
          dropdownButton.innerHTML = `<i class="bi bi-funnel"></i> ${this.textContent}`;
          
          if (type === 'bagian') {
            filterBagianData(value);
          } else {
            filterPengeluaranData(value);
          }
        });
      });

      // Fungsi untuk filter data grafik per bagian
      function filterBagianData(filterType) {
        // Kirim permintaan AJAX ke server untuk mendapatkan data terfilter
        fetch(`/admin/dashboard/filter?type=bagian&filter=${filterType}`)
          .then(response => response.json())
          .then(data => {
            // Update data chart
            bagianChart.data.datasets[0].data = data.keluar;
            bagianChart.data.datasets[1].data = data.masuk;
            bagianChart.update();
          })
          .catch(error => console.error('Error:', error));
      }

      // Fungsi untuk filter data grafik pengeluaran per waktu
      function filterPengeluaranData(filterType) {
        // Kirim permintaan AJAX ke server untuk mendapatkan data terfilter
        fetch(`/admin/dashboard/filter?type=pengeluaran&filter=${filterType}`)
          .then(response => response.json())
          .then(data => {
            // Update data chart
            pengeluaranChart.data.datasets = data;
            pengeluaranChart.update();
            
            // Update legend
            const legendContainer = document.querySelector('.chart-legend.yearly');
            legendContainer.innerHTML = '';
            
            data.forEach(dataset => {
              const legendItem = document.createElement('div');
              legendItem.className = 'legend-item';
              
              const colorSpan = document.createElement('span');
              colorSpan.className = 'legend-color';
              colorSpan.style.backgroundColor = dataset.backgroundColor;
              
              const textSpan = document.createElement('span');
              textSpan.textContent = dataset.label;
              
              legendItem.appendChild(colorSpan);
              legendItem.appendChild(textSpan);
              legendContainer.appendChild(legendItem);
            });
          })
          .catch(error => console.error('Error:', error));
      }
    });
  </script>

</x-layouts.app>