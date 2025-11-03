<x-layouts.app title="Dashboard Pengelola Barang" :menu="$menu">

  @push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/staff/pb/dashboard.css') }}">
  @endpush

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

  <div class="dashboard-container">

    {{-- Row pertama: Ringkasan dan Grafik Barang Keluar --}}
    <div class="dashboard-row">
      {{-- ========================= RINGKASAN ========================= --}}
      <div class="summary-section">
        <div class="summary-header">
          <h2>Ringkasan</h2>
        </div>

        <div class="summary-cards summary-cards--classic">
          {{-- Card: Total Jenis Barang --}}
          <div class="summary-card summary-card--classic">
            <div class="summary-card__icon-circle">
              <i class="bi bi-box"></i>
            </div>
            <div class="summary-card__body">
              <div class="summary-card__number" id="totalJenisBarang">{{ $totalJenisBarang }}</div>
              <div class="summary-card__label">Total Jenis Barang</div>
            </div>
          </div>

          {{-- Card: Total Barang --}}
          <div class="summary-card summary-card--classic">
            <div class="summary-card__icon-circle">
              <i class="bi bi-box-seam"></i>
            </div>
            <div class="summary-card__body">
              <div class="summary-card__number" id="totalBarang">{{ $totalBarang }}</div>
              <div class="summary-card__label">Total Barang</div>
            </div>
          </div>
        </div>
      </div>

      {{-- ======================= GRAFIK BARANG KELUAR ======================= --}}
      <div class="chart-section">
        <div class="chart-header chart-header--horizontal">
          <div class="chart-header-horizontal-item">
            <h2>Barang Keluar per Bagian</h2>
          </div>

          <div class="chart-header-horizontal-item">
            <span id="rangeHintKategori" class="range-hint" title="Semua Data">Semua Data</span>
          </div>

          <div class="chart-header-horizontal-item" style="margin-left: auto;">
            <div class="dropdown">
              <button class="filter-btn dropdown-toggle" type="button" id="kategoriFilterDropdown"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-funnel"></i> Semua
                <i class="bi bi-chevron-right arrow-icon"></i>
              </button>
              <ul class="dropdown-menu" aria-labelledby="kategoriFilterDropdown">
                <li><a class="dropdown-item filter-option" href="#" data-type="kategori" data-value="all">Semua</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="kategori" data-value="week">1 Minggu
                    Terakhir</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="kategori" data-value="month">1 Bulan
                    Terakhir</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="kategori" data-value="year">1 Tahun
                    Terakhir</a></li>
              </ul>
            </div>
          </div>
        </div>

        <div class="chart-container">
          <canvas id="kategoriChart"></canvas>
        </div>

        <div class="chart-legend" id="bagianLegend">
          {{-- Legend akan di-generate oleh JavaScript --}}
        </div>
      </div>
    </div>

    {{-- =================== GRAFIK PENGELUARAN PER TAHUN =================== --}}
    <div class="dashboard-row">
      <div class="wide-chart-section">
        <div class="chart-header chart-header--wrap">
          <div class="chart-header-left">
            <h2>Grafik Pengeluaran per Tahun</h2>
          </div>

          <div class="chart-controls">
            <div class="dropdown">
              <button class="filter-btn dropdown-toggle" type="button" id="pengeluaranFilterDropdown"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-funnel"></i> Semua
                <i class="bi bi-chevron-right arrow-icon"></i>
              </button>
              <ul class="dropdown-menu" aria-labelledby="pengeluaranFilterDropdown">
                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran" data-value="all">Semua</a>
                </li>
                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran" data-value="5y">5 Tahun
                    Terakhir</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran" data-value="7y">7 Tahun
                    Terakhir</a></li>
                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran" data-value="10y">10 Tahun
                    Terakhir</a></li>
              </ul>
            </div>
          </div>
        </div>

        <div class="chart-container">
          <canvas id="pengeluaranChart"></canvas>
        </div>
        <div class="chart-legend yearly" id="legendYears"></div>
      </div>
    </div>

  </div>

  {{-- Chart.js Library --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const FILTER_URL = "{{ route('pb.dashboard.filter') }}";

      // Helper Functions
      function fmt(d) {
        const z = n => String(n).padStart(2, '0');
        return `${z(d.getDate())}/${z(d.getMonth() + 1)}/${d.getFullYear()}`;
      }

      function setRangeHint(el, text, titleText) {
        if (!el) return;
        el.textContent = text;
        el.title = titleText || text;
      }

      // Data dari controller
      const kategoriLabels = {!! json_encode($keluarPerKategoriLabels) !!};
      const kategoriData = {!! json_encode($keluarPerKategoriData) !!};
      
      // Generate warna dinamis sesuai jumlah bagian
      function generateColors(count) {
        const baseColors = [
          '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EF4444',
          '#06B6D4', '#EC4899', '#F97316', '#14B8A6', '#A855F7',
          '#FB923C', '#34D399'
        ];
        const colors = [];
        for (let i = 0; i < count; i++) {
          colors.push(baseColors[i % baseColors.length]);
        }
        return colors;
      }

      const bagianColors = generateColors(kategoriLabels.length);

      // Render legend untuk bagian
      function renderBagianLegend(labels, colors) {
        const box = document.getElementById('bagianLegend');
        box.innerHTML = '';
        labels.forEach((label, idx) => {
          const item = document.createElement('div');
          item.className = 'legend-item';
          const color = document.createElement('span');
          color.className = 'legend-color';
          color.style.backgroundColor = colors[idx] || '#6B7280';
          const text = document.createElement('span');
          text.textContent = label;
          item.appendChild(color);
          item.appendChild(text);
          box.appendChild(item);
        });
      }

      renderBagianLegend(kategoriLabels, bagianColors);

      // Chart Barang Keluar per Bagian
      const kategoriChart = new Chart(document.getElementById('kategoriChart').getContext('2d'), {
        type: 'bar',
        data: {
          labels: kategoriLabels,
          datasets: [{
            label: 'Barang Keluar',
            data: kategoriData,
            backgroundColor: bagianColors,
            borderRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { color: '#6B7280' },
              grid: { color: '#F3F4F6' }
            },
            x: {
              ticks: { color: '#6B7280', maxRotation: 45, minRotation: 45 },
              grid: { display: false }
            }
          }
        }
      });

      // Chart Pengeluaran per Tahun
      const pengeluaranData = {
        labels: {!! json_encode($pengeluaranLabels) !!},
        datasets: {!! json_encode($pengeluaranData) !!}
      };

      const dataValues = pengeluaranData.datasets[0].data || [];
      const maxValue = Math.max(...dataValues, 0);
      const minValue = Math.min(...dataValues, 0);
      const suggestedMin = minValue > 0 ? minValue * 0.8 : 0;
      const suggestedMax = maxValue > 0 ? maxValue * 1.2 : 1000000;

      const pengeluaranChart = new Chart(document.getElementById('pengeluaranChart').getContext('2d'), {
        type: 'bar',
        data: pengeluaranData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function (context) {
                  const value = context.raw || 0;
                  return 'Rp ' + value.toLocaleString('id-ID');
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: false,
              suggestedMin: suggestedMin,
              suggestedMax: suggestedMax,
              ticks: {
                color: '#6B7280',
                callback: function (value) {
                  return 'Rp ' + value.toLocaleString('id-ID');
                }
              },
              grid: { color: '#F3F4F6' }
            },
            x: {
              ticks: { color: '#6B7280' },
              grid: { display: false }
            }
          }
        }
      });

      function renderYearLegend(years, colorsMap) {
        const box = document.getElementById('legendYears');
        box.innerHTML = '';
        years.forEach(y => {
          const item = document.createElement('div');
          item.className = 'legend-item';
          const color = document.createElement('span');
          color.className = 'legend-color';
          color.style.backgroundColor = colorsMap[y] || '#8B5CF6';
          const text = document.createElement('span');
          text.textContent = y;
          item.appendChild(color);
          item.appendChild(text);
          box.appendChild(item);
        });
      }

      renderYearLegend({!! json_encode($years) !!}, {!! json_encode($colorsForYears) !!});

      // Bootstrap Dropdown Events
      document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(dropdown => {
        dropdown.addEventListener('show.bs.dropdown', function () {
          this.setAttribute('aria-expanded', 'true');
        });
        dropdown.addEventListener('hide.bs.dropdown', function () {
          this.setAttribute('aria-expanded', 'false');
        });
      });

      // Filter Functions
      function filterKategori(filterType) {
        fetch(`${FILTER_URL}?type=kategori&filter=${filterType}`)
          .then(response => response.json())
          .then(data => {
            kategoriChart.data.labels = data.labels;
            kategoriChart.data.datasets[0].data = data.data;
            kategoriChart.data.datasets[0].backgroundColor = data.colors;
            kategoriChart.update('active');

            renderBagianLegend(data.labels, data.colors);

            const hint = document.getElementById('rangeHintKategori');
            if (data.range && data.range.start && data.range.end) {
              const s = new Date(data.range.start), e = new Date(data.range.end);
              const txt = `${fmt(s)} – ${fmt(e)}`;
              setRangeHint(hint, txt, txt);
            } else {
              setRangeHint(hint, 'Semua Data', 'Semua Data');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            setRangeHint(document.getElementById('rangeHintKategori'), 'Semua Data', 'Semua Data');
          });
      }

      function filterPengeluaran(filterType) {
        fetch(`${FILTER_URL}?type=pengeluaran&filter=${filterType}`)
          .then(r => r.json())
          .then(d => {
            pengeluaranChart.data.labels = d.labels;
            pengeluaranChart.data.datasets[0].data = d.data;
            pengeluaranChart.data.datasets[0].backgroundColor =
              d.labels.map(y => d.colors[y] || '#8B5CF6');
            pengeluaranChart.update();
            renderYearLegend(d.labels, d.colors);
          })
          .catch(console.error);
      }

      // Filter Dropdown Functionality
      document.querySelectorAll('.filter-option').forEach(item => {
        item.addEventListener('click', function (e) {
          e.preventDefault();
          const type = this.getAttribute('data-type');
          const value = this.getAttribute('data-value');

          const dropdownButton = this.closest('.dropdown').querySelector('.dropdown-toggle');
          dropdownButton.innerHTML = `<i class="bi bi-funnel"></i> ${this.textContent} <i class="bi bi-chevron-right arrow-icon"></i>`;

          if (type === 'kategori') {
            filterKategori(value);
          } else if (type === 'pengeluaran') {
            filterPengeluaran(value);
          }
        });
      });

      // Chart Resize Handler
      window.addEventListener('resize', function () {
        kategoriChart.resize();
        pengeluaranChart.resize();
      });

      setRangeHint(document.getElementById('rangeHintKategori'), 'Semua Data', 'Semua Data');

      // Animasi number counting
      const numberElements = document.querySelectorAll('.summary-card__number');
      numberElements.forEach(element => {
        const finalValue = element.textContent;
        if (!isNaN(finalValue)) {
          element.textContent = '0';
          setTimeout(() => {
            animateCount(element, 0, parseInt(finalValue), 1000);
          }, 800);
        }
      });

      function animateCount(element, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
          if (!startTimestamp) startTimestamp = timestamp;
          const progress = Math.min((timestamp - startTimestamp) / duration, 1);
          const value = Math.floor(progress * (end - start) + start);
          element.textContent = value.toLocaleString();
          if (progress < 1) {
            window.requestAnimationFrame(step);
          }
        };
        window.requestAnimationFrame(step);
      }
    });
  </script>

</x-layouts.app>