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

      {{-- Kolom Grafik Per Bagian --}}
      <div class="chart-section">
        <div class="chart-header">
          <div style="display:flex;align-items:center;gap:10px">
            <h2>Grafik Per Bagian</h2>
            {{-- [NEW] badge keterangan rentang waktu --}}
            <span id="bagianRangeHint" class="range-hint" title="Rentang data">Semua Data</span>
          </div>

          <div class="chart-filter">
            {{-- [NEW] Pager (Prev / X/Y / Next) --}}
            <div class="pager" id="bagianPager" style="display:none">
              <button class="pager-btn" id="bagianPrev" title="Sebelumnya" aria-label="Sebelumnya">
                <i class="bi bi-chevron-left"></i>
              </button>
              <span class="pager-info" id="bagianPagerInfo">1/1</span>
              <button class="pager-btn" id="bagianNext" title="Berikutnya" aria-label="Berikutnya">
                <i class="bi bi-chevron-right"></i>
              </button>
            </div>

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

        {{-- [CHANGE] tambahkan id untuk area drag --}}
        <div class="chart-container" id="bagianChartBox">
          <canvas id="bagianChart"></canvas>
        </div>
        <div class="chart-legend">
          <div class="legend-item">
            <span class="legend-color keluar"></span>
            <span>Keluar</span>
          </div>
        </div>
      </div>
    </div>

    {{-- Row kedua: Grafik Pengeluaran per Tahun --}}
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
        <div class="chart-legend yearly" id="legendYears"></div>
      </div>
    </div>
  </div>

  {{-- Chart.js Library --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const FILTER_URL = "{{ route('admin.dashboard.filter') }}";

      /* ====================== Grafik Per Bagian (Keluar only) ====================== */
      const PER_PAGE = 9; // << batas 9 bar
      let allLabels = {!! json_encode($bagianLabels) !!};   // full
      let allData   = {!! json_encode($keluarData) !!};     // full
      let pageStart = 0;                                     // index mulai

      const bagianCtx  = document.getElementById('bagianChart').getContext('2d');
      const rangeBadge = document.getElementById('bagianRangeHint'); // [NEW]
      const bagianChart = new Chart(bagianCtx, {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Keluar', data: [], backgroundColor: '#EF4444', borderRadius: 4 }] },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { color: '#6B7280' },
              grid:  { color: '#F3F4F6' }
            },
            x: {
              ticks: { color: '#6B7280', maxRotation: 45, minRotation: 45 },
              grid:  { display: false }
            }
          }
        }
      });

      const pagerBox  = document.getElementById('bagianPager');
      const pagerInfo = document.getElementById('bagianPagerInfo');
      const btnPrev   = document.getElementById('bagianPrev');
      const btnNext   = document.getElementById('bagianNext');

      function totalPages() { return Math.max(1, Math.ceil(allLabels.length / PER_PAGE)); }
      function currentPage(){ return Math.floor(pageStart / PER_PAGE) + 1; }
      function renderPager(){
        const pages = totalPages();
        // tampilkan pager hanya jika ada lebih dari 9
        pagerBox.style.display = (allLabels.length > PER_PAGE) ? 'flex' : 'none';
        pagerInfo.textContent  = `${currentPage()}/${pages}`;
        // disabled state
        btnPrev.disabled = pageStart === 0;
        btnNext.disabled = (pageStart + PER_PAGE) >= allLabels.length;
      }
      function sliceData(){
        const end = Math.min(pageStart + PER_PAGE, allLabels.length);
        const lab = allLabels.slice(pageStart, end);
        const dat = allData.slice(pageStart, end);
        bagianChart.data.labels = lab;
        bagianChart.data.datasets[0].data = dat;
        bagianChart.update();
        renderPager();
      }
      // init first render
      sliceData();

      // pager actions
      btnPrev.addEventListener('click', () => {
        if (pageStart >= PER_PAGE) { pageStart -= PER_PAGE; sliceData(); }
      });
      btnNext.addEventListener('click', () => {
        if (pageStart + PER_PAGE < allLabels.length) { pageStart += PER_PAGE; sliceData(); }
      });

      // [NEW] Drag / Swipe untuk next/prev halaman
      (function enableDragPager(){
        const box = document.getElementById('bagianChartBox');
        let startX = 0, isDown = false;

        const onDown = (x) => { isDown = true; startX = x; box.classList.add('dragging'); };
        const onUp   = (x) => {
          if (!isDown) return;
          isDown = false; box.classList.remove('dragging');
          const dx = x - startX;
          const THRESH = 60; // piksel
          if (dx <= -THRESH) { // geser kiri -> next
            if (pageStart + PER_PAGE < allLabels.length) { pageStart += PER_PAGE; sliceData(); }
          } else if (dx >= THRESH) { // geser kanan -> prev
            if (pageStart >= PER_PAGE) { pageStart -= PER_PAGE; sliceData(); }
          }
        };

        // Mouse
        box.addEventListener('mousedown', (e)=> onDown(e.clientX));
        window.addEventListener('mouseup',   (e)=> onUp(e.clientX));
        // Touch
        box.addEventListener('touchstart', (e)=> onDown(e.touches[0].clientX), {passive:true});
        box.addEventListener('touchend',   (e)=> {
          const t = e.changedTouches[0]; onUp(t.clientX);
        }, {passive:true});
      })();

      // Filter dropdown functionality
      document.querySelectorAll('.filter-option').forEach(item => {
        item.addEventListener('click', function(e) {
          e.preventDefault();
          const type  = this.getAttribute('data-type');
          const value = this.getAttribute('data-value');
          // Update teks pada tombol dropdown
          const dropdownButton = this.closest('.dropdown').querySelector('.dropdown-toggle');
          dropdownButton.innerHTML = `<i class="bi bi-funnel"></i> ${this.textContent}`;
          
          if (type === 'bagian') {
            filterBagian(value);
          } else {
            filterPengeluaran(value);
          }
        });
      });

      // [CHANGE] Fungsi untuk filter data grafik per bagian + update badge range
      function filterBagian(filterType) {
        fetch(`${FILTER_URL}?type=bagian&filter=${filterType}`)
          .then(response => response.json())
          .then(data => {
            allLabels = data.labels || [];
            allData   = data.keluar || [];
            pageStart = 0; // reset ke halaman 1
            // [NEW] update badge keterangan range
            document.getElementById('bagianRangeHint').textContent = data.range_text || 'Semua Data';
            sliceData();
          })
          .catch(error => console.error('Error:', error));
      }

      /* ====================== Grafik Pengeluaran per Tahun (dibiarkan) ====================== */
      const pengeluaranData = {
        labels: {!! json_encode($pengeluaranLabels) !!},
        datasets: {!! json_encode($pengeluaranData) !!}
      };

      const pengeluaranChart = new Chart(document.getElementById('pengeluaranChart').getContext('2d'), {
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
      });

      // Fungsi untuk mengubah warna legend berdasarkan tahun
      function renderYearLegend(years, colorsMap) {
        const legendContainer = document.getElementById('legendYears');
        legendContainer.innerHTML = '';
        years.forEach(year => {
          const legendItem = document.createElement('div');
          legendItem.className = 'legend-item';
          const colorSpan = document.createElement('span');
          colorSpan.className = 'legend-color';
          colorSpan.style.backgroundColor = colorsMap[year] || '#8B5CF6';
          const textSpan = document.createElement('span');
          textSpan.textContent = year;
          legendItem.appendChild(colorSpan);
          legendItem.appendChild(textSpan);
          legendContainer.appendChild(legendItem);
        });
      }
      renderYearLegend({!! json_encode($years) !!}, {!! json_encode($colorsForYears) !!});

      // Fungsi untuk filter data grafik pengeluaran per waktu
      function filterPengeluaran(filterType) {
        fetch(`${FILTER_URL}?type=pengeluaran&filter=${filterType}`)
          .then(response => response.json())
          .then(data => {
            pengeluaranChart.data.labels = data.labels;
            if (pengeluaranChart.data.datasets.length === 0) {
              pengeluaranChart.data.datasets.push({
                label: 'Keluar',
                data: data.data,
                backgroundColor: data.labels.map(y => data.colors[y] || '#8B5CF6'),
                borderRadius: 4
              });
            } else {
              pengeluaranChart.data.datasets[0].data = data.data;
              pengeluaranChart.data.datasets[0].backgroundColor =
                data.labels.map(y => data.colors[y] || '#8B5CF6');
            }
            pengeluaranChart.update();
            renderYearLegend(data.labels, data.colors);
          })
          .catch(error => console.error('Error:', error));
      }
    });
  </script>

</x-layouts.app>
