<x-layouts.app title="Dashboard Pengelola Barang" :menu="$menu">

  @push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/staff/pb/dashboard.css') }}">
  @endpush
  {{-- CSS khusus dashboard PB --}}

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

  <div class="dashboard-container">

    {{-- Row pertama: Ringkasan dan Grafik Barang Keluar --}}
    <div class="dashboard-row">
      {{-- ========================= RINGKASAN (DITAMBAH DROPDOWN SEPERTI ADMIN) ========================= --}}
      <div class="summary-section">
        {{-- [NEW] Header dengan judul dan filter dropdown seperti admin --}}
        <div class="summary-header">
          <h2>Ringkasan</h2>
          {{-- [NEW] Filter gudang pada ringkasan seperti admin --}}
         
        </div>

        {{-- KLASIK: ikon bulat di kiri, angka & label di kanan (match CSS admin) --}}
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
        {{-- [MODIFIED] Layout horizontal sejajar dengan dropdown di ujung kanan --}}
        <div class="chart-header chart-header--horizontal">
          <div class="chart-header-horizontal-item">
            <h2>Barang Keluar</h2>
          </div>

          <div class="chart-header-horizontal-item">
            {{-- Badge keterangan rentang --}}
            <span id="rangeHintKategori" class="range-hint" title="Semua Data">Semua Data</span>
          </div>

          {{-- [MODIFIED] Pindahkan dropdown ke ujung kanan dengan margin auto --}}
          <div class="chart-header-horizontal-item" style="margin-left: auto;">
            {{-- Filter waktu dengan arrow icon seperti admin --}}
            <div class="dropdown">
              <button class="filter-btn dropdown-toggle" type="button" id="kategoriFilterDropdown"
                aria-expanded="false">
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

    {{-- =================== GRAFIK PENGELUARAN PER TAHUN (DIUBAH SEPERTI ADMIN) =================== --}}
    <div class="dashboard-row">
      <div class="wide-chart-section">
        <div class="chart-header chart-header--wrap">
          <div class="chart-header-left">
            <h2>Grafik Pengeluaran per Tahun</h2>
            {{-- [OPSIONAL-HILANGKAN BADGE TAHUN] --}}
            {{-- <span id="rangeHintTahun" class="range-hint" title="Semua Data">Semua Data</span> --}}
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

      /* ====================== [NEW] Atur Arrow Dropdown seperti admin ====================== */
      // Untuk custom dropdown Ringkasan (jika diperlukan)
      const summaryFilterBtn = document.getElementById('summaryFilterBtn');
      const summaryDropdownMenu = document.getElementById('summaryDropdownMenu');
      const summaryFilterText = document.getElementById('summaryFilterText');
      const totalJenisBarangEl = document.getElementById('totalJenisBarang');
      const totalBarangEl = document.getElementById('totalBarang');

      if (summaryFilterBtn) {
        // Toggle dropdown visibility dan atur arrow
        summaryFilterBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          const isExpanded = this.getAttribute('aria-expanded') === 'true';
          this.setAttribute('aria-expanded', !isExpanded);
          summaryDropdownMenu.classList.toggle('show');
        });

        // Close dropdown ketika klik di luar
        document.addEventListener('click', function (e) {
          if (!summaryFilterBtn.contains(e.target) && !summaryDropdownMenu.contains(e.target)) {
            summaryDropdownMenu.classList.remove('show');
            summaryFilterBtn.setAttribute('aria-expanded', 'false');
          }
        });

        // Handle filter selection untuk Ringkasan
        summaryDropdownMenu.addEventListener('click', function (e) {
          if (e.target.classList.contains('summary-dropdown-item')) {
            e.preventDefault();
            const selectedValue = e.target.getAttribute('data-value');
            const selectedText = e.target.textContent;

            summaryFilterText.textContent = selectedText;
            summaryDropdownMenu.classList.remove('show');
            summaryFilterBtn.setAttribute('aria-expanded', 'false');

            // [NEW] Panggil fungsi filter ringkasan
            filterRingkasan(selectedValue);
          }
        });
      }

      /* ====================== [NEW] Filter Function untuk Ringkasan ====================== */
      function filterRingkasan(filterType) {
        fetch(`${FILTER_URL}?type=ringkasan&filter=${filterType}`)
          .then(response => {
            if (!response.ok) {
              throw new Error('Network response was not ok');
            }
            return response.json();
          })
          .then(data => {
            // Update nilai pada card ringkasan dengan animasi
            animateNumber(totalJenisBarangEl, data.totalJenisBarang);
            animateNumber(totalBarangEl, data.totalBarang);
          })
          .catch(error => {
            console.error('Error filtering ringkasan data:', error);
          });
      }

      function animateNumber(element, targetValue) {
        const startValue = parseInt(element.textContent) || 0;
        const difference = targetValue - startValue;
        const duration = 800; // milliseconds
        const steps = 60;
        const stepValue = difference / steps;
        let currentStep = 0;

        const interval = setInterval(() => {
          currentStep++;
          const currentValue = Math.round(startValue + (stepValue * currentStep));
          element.textContent = currentValue;

          if (currentStep >= steps) {
            element.textContent = targetValue;
            clearInterval(interval);
          }
        }, duration / steps);
      }

      /* ====================== Helper Functions ====================== */
      function fmt(d) {
        const z = n => String(n).padStart(2, '0');
        return `${z(d.getDate())}/${z(d.getMonth() + 1)}/${d.getFullYear()}`;
      }
      function setRangeHint(el, text, titleText) {
        if (!el) return;
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

      /* ====================== [DIUBAH] Grafik Pengeluaran per Tahun seperti admin ====================== */
      const pengeluaranData = {
        labels: {!! json_encode($pengeluaranLabels) !!},
        datasets: {!! json_encode($pengeluaranData) !!} // label 'Keluar' + colors sudah dari controller
      };

      // Ambil nilai data dari dataset pertama
      const dataValues = pengeluaranData.datasets[0].data || [];

      // Hitung nilai maksimum & minimum dari data
      const maxValue = Math.max(...dataValues, 0);
      const minValue = Math.min(...dataValues, 0);

      // Tentukan batas sumbu Y secara dinamis
      const suggestedMin = minValue > 0 ? minValue * 0.8 : 0;
      const suggestedMax = maxValue > 0 ? maxValue * 1.2 : 1000000;

      const pengeluaranChart = new Chart(document.getElementById('pengeluaranChart').getContext('2d'), {
        type: 'bar',
        data: pengeluaranData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
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
              beginAtZero: false, // biar ga maksa mulai dari 0
              suggestedMin: suggestedMin,
              suggestedMax: suggestedMax,
              ticks: {
                color: '#6B7280',
                callback: function (value) {
                  return 'Rp ' + value.toLocaleString('id-ID');
                }
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

      /* ====================== [FIXED] Bootstrap Dropdown Events untuk Arrow Rotation ====================== */
      // Handle untuk semua dropdown Bootstrap seperti di admin
      document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(dropdown => {
        dropdown.addEventListener('show.bs.dropdown', function () {
          this.setAttribute('aria-expanded', 'true');
        });

        dropdown.addEventListener('hide.bs.dropdown', function () {
          this.setAttribute('aria-expanded', 'false');
        });
      });

      // [NEW] Custom dropdown handling untuk arrow rotation (fallback)
      document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        if (!toggle.hasAttribute('data-bs-toggle')) {
          toggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);

            // Toggle dropdown menu
            const menu = this.nextElementSibling;
            if (menu && menu.classList.contains('dropdown-menu')) {
              menu.classList.toggle('show');
            }
          });
        }
      });

      // Close dropdown ketika klik di luar
      document.addEventListener('click', function (e) {
        if (!e.target.closest('.dropdown')) {
          document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
          });
          document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
            toggle.setAttribute('aria-expanded', 'false');
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
            if (data.range && data.range.start && data.range.end) {
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

      // [DIUBAH] Fungsi untuk filter data grafik pengeluaran per tahun
      function filterPengeluaran(filterType) {
        fetch(`${FILTER_URL}?type=pengeluaran&filter=${filterType}`)
          .then(r => r.json())
          .then(d => {
            pengeluaranChart.data.labels = d.labels;
            if (pengeluaranChart.data.datasets.length === 0) {
              pengeluaranChart.data.datasets.push({
                label: 'Keluar',
                data: d.data,
                backgroundColor: d.labels.map(y => d.colors[y] || '#8B5CF6'),
                borderRadius: 4
              });
            } else {
              pengeluaranChart.data.datasets[0].data = d.data;
              pengeluaranChart.data.datasets[0].backgroundColor =
                d.labels.map(y => d.colors[y] || '#8B5CF6');
            }
            pengeluaranChart.update();
            renderYearLegend(d.labels, d.colors);

            // [OPSIONAL-HILANGKAN BADGE TAHUN] -> kalau kamu hide span di HTML, baris di bawah juga bisa dihapus
            const hintT = document.getElementById('rangeHintTahun');
            if (hintT) {
              if (d.labels && d.labels.length) {
                const txt = `${d.labels[0]} – ${d.labels[d.labels.length - 1]}`;
                setRangeHint(hintT, txt, txt);
              } else setRangeHint(hintT, 'Semua Data', 'Semua Data');
            }
          })
          .catch(console.error);
      }

      /* ====================== Filter Dropdown Functionality ====================== */
      document.querySelectorAll('.filter-option').forEach(item => {
        item.addEventListener('click', function (e) {
          e.preventDefault();
          const type = this.getAttribute('data-type');
          const value = this.getAttribute('data-value');

          // Update teks pada tombol dropdown dengan arrow icon seperti admin
          const dropdownButton = this.closest('.dropdown').querySelector('.dropdown-toggle');
          dropdownButton.innerHTML = `<i class="bi bi-funnel"></i> ${this.textContent} <i class="bi bi-chevron-right arrow-icon"></i>`;

          // Close dropdown
          const dropdownMenu = this.closest('.dropdown-menu');
          if (dropdownMenu) {
            dropdownMenu.classList.remove('show');
          }
          dropdownButton.setAttribute('aria-expanded', 'false');

          if (type === 'kategori') {
            filterKategori(value);
          } else if (type === 'pengeluaran') {
            filterPengeluaran(value);
          }
        });
      });

      /* ====================== Chart Resize Handler ====================== */
      window.addEventListener('resize', function () {
        kategoriChart.resize();
        pengeluaranChart.resize();
      });

      // Set awal badge "Semua Data"
      setRangeHint(document.getElementById('rangeHintKategori'), 'Semua Data', 'Semua Data');
      // setRangeHint(document.getElementById('rangeHintTahun'), 'Semua Data', 'Semua Data'); // [OPSIONAL-HILANGKAN BADGE TAHUN]
    });
    // ===== ANIMASI TRANSISI SETELAH LOGIN =====
    document.addEventListener('DOMContentLoaded', function () {
      // Trigger reflow untuk memastikan animasi berjalan
      const sections = document.querySelectorAll('.summary-section, .chart-section, .wide-chart-section');

      sections.forEach(section => {
        // Force reflow
        void section.offsetWidth;
      });

      // Animasi untuk counting numbers pada ringkasan
      const numberElements = document.querySelectorAll('.summary-card__number');

      numberElements.forEach(element => {
        const finalValue = element.textContent;
        if (!isNaN(finalValue)) {
          element.textContent = '0';
          element.classList.add('number-counting');

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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  

</x-layouts.app>