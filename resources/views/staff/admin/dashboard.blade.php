<x-layouts.app title="Dashboard Admin" :menu="$menu">
  {{-- CSS khusus dashboard --}}
  <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/dashboard.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

  <div class="dashboard-container">

    {{-- Row pertama: Ringkasan dan Grafik Per Bagian --}}
    <div class="dashboard-row">
      {{-- ========================= RINGKASAN ========================= --}}
      <div class="summary-section">
        <h2>Ringkasan</h2>

        {{-- [CHANGE] Kembali ke layout KLASIK: ikon bulat di kiri, angka & label di kanan --}}
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

      {{-- ======================= GRAFIK PER BAGIAN ======================= --}}
      <div class="chart-section">
        <div class="chart-header chart-header--wrap"> {{-- [NEW] wrap supaya badge tanggal tak mepet --}}
          <div class="chart-header-left">
            <h2>Grafik Per Bagian</h2>

            {{-- [CHANGE] badge keterangan rentang (akan diisi JS) --}}
            <span id="rangeHintBagian" class="range-hint" title="Semua Data">Semua Data</span>
          </div>

          <div class="chart-filter">
            {{-- Pager (muncul jika data > 9) --}}
            <div class="pager" id="bagianPager" style="display:none">
              <button class="pager-btn" id="bagianPrev" title="Sebelumnya" aria-label="Sebelumnya">
                <i class="bi bi-chevron-left"></i>
              </button>
              <span class="pager-info" id="bagianPagerInfo">1/1</span>
              <button class="pager-btn" id="bagianNext" title="Berikutnya" aria-label="Berikutnya">
                <i class="bi bi-chevron-right"></i>
              </button>
            </div>

            {{-- Filter waktu --}}
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

        {{-- Wadah kanvas chart (dipakai juga untuk drag) --}}
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

    {{-- =================== GRAFIK PENGELUARAN PER TAHUN =================== --}}
    <div class="dashboard-row">
      <div class="wide-chart-section">
        <div class="chart-header chart-header--wrap">
          <div class="chart-header-left">
            <h2>Grafik Pengeluaran per Tahun</h2>
            {{-- [NEW] badge rentang tahunan ditampilkan juga (opsional) --}}
            <span id="rangeHintTahun" class="range-hint" title="Semua Data">Semua Data</span>
          </div>

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

  {{-- Chart.js --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const FILTER_URL = "{{ route('admin.dashboard.filter') }}";

      /* ====================== Grafik Per Bagian (Keluar only) ====================== */
      const PER_PAGE = 9; // << batas 9 bar
      let allLabels = {!! json_encode($bagianLabels) !!};   // full
      let allData   = {!! json_encode($keluarData) !!};     // full
      let pageStart = 0;                                     // index mulai

      const bagianCtx = document.getElementById('bagianChart').getContext('2d');
      const bagianChart = new Chart(bagianCtx, {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'Keluar', data: [], backgroundColor: '#EF4444', borderRadius: 4 }] },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { color: '#6B7280' }, grid: { color: '#F3F4F6' } },
            x: { ticks: { color: '#6B7280', maxRotation: 45, minRotation: 45 }, grid: { display: false } }
          }
        }
      });

      const pagerBox  = document.getElementById('bagianPager');
      const pagerInfo = document.getElementById('bagianPagerInfo');
      const btnPrev   = document.getElementById('bagianPrev');
      const btnNext   = document.getElementById('bagianNext');

      function totalPages() { return Math.max(1, Math.ceil(allLabels.length / PER_PAGE)); }
      function currentPage() { return Math.floor(pageStart / PER_PAGE) + 1; }
      function renderPager() {
        pagerBox.style.display = (allLabels.length > PER_PAGE) ? 'flex' : 'none';
        pagerInfo.textContent = `${currentPage()}/${totalPages()}`;
        btnPrev.disabled = pageStart === 0;
        btnNext.disabled = (pageStart + PER_PAGE) >= allLabels.length;
      }
      function sliceData() {
        const end = Math.min(pageStart + PER_PAGE, allLabels.length);
        bagianChart.data.labels = allLabels.slice(pageStart, end);
        bagianChart.data.datasets[0].data = allData.slice(pageStart, end);
        bagianChart.update();
        renderPager();
      }
      sliceData(); // first render

      btnPrev.addEventListener('click', () => { if (pageStart >= PER_PAGE){ pageStart -= PER_PAGE; sliceData(); }});
      btnNext.addEventListener('click', () => { if (pageStart + PER_PAGE < allLabels.length){ pageStart += PER_PAGE; sliceData(); }});

      // ===== Drag / swipe untuk paging (visual cue di CSS) =====
      (function enableDragToPage(){
        const box = document.getElementById('bagianChartBox');
        let isDown = false, startX = 0;
        box.addEventListener('mousedown', e => { isDown = true; startX = e.clientX; box.classList.add('dragging'); });
        window.addEventListener('mouseup',   () => { if(!isDown) return; isDown = false; box.classList.remove('dragging'); });
        box.addEventListener('mouseleave',   () => { if(!isDown) return; isDown = false; box.classList.remove('dragging'); });
        box.addEventListener('mousemove', e => {
          if(!isDown) return;
          const delta = e.clientX - startX;
          if(Math.abs(delta) > 60){
            if(delta < 0) btnNext.click(); else btnPrev.click();
            isDown = false; box.classList.remove('dragging');
          }
        });
        // Touch
        box.addEventListener('touchstart', e => { startX = e.touches[0].clientX; isDown = true; box.classList.add('dragging'); }, {passive:true});
        box.addEventListener('touchend',   () => { isDown = false; box.classList.remove('dragging'); });
        box.addEventListener('touchmove',  e => {
          if(!isDown) return;
          const delta = e.touches[0].clientX - startX;
          if(Math.abs(delta) > 60){ if(delta < 0) btnNext.click(); else btnPrev.click(); isDown = false; box.classList.remove('dragging');}
        }, {passive:true});
      })();

      // ===== Helper format tanggal untuk badge (ID locale) =====
      function fmt(d){ const z=n=>String(n).padStart(2,'0'); return `${z(d.getDate())}/${z(d.getMonth()+1)}/${d.getFullYear()}`; }
      function setRangeHint(el, text, titleText){ el.textContent = text; el.title = titleText || text; }

      // ===== Filter dropdown (keduanya) =====
      document.querySelectorAll('.filter-option').forEach(el => {
        el.addEventListener('click', function(e) {
          e.preventDefault();
          const type  = this.getAttribute('data-type');
          const value = this.getAttribute('data-value');
          this.closest('.dropdown').querySelector('.dropdown-toggle').innerHTML =
            `<i class="bi bi-funnel"></i> ${this.textContent}`;
          if (type === 'bagian')  filterBagian(value);
          else                    filterPengeluaran(value);
        });
      });

      // ====== Filter Per Bagian (update data + badge tanggal) ======
      function filterBagian(filterType){
        fetch(`${FILTER_URL}?type=bagian&filter=${filterType}`)
          .then(r => r.json())
          .then(d => {
            allLabels = d.labels || [];
            allData   = d.keluar || [];
            pageStart = 0;
            sliceData();

            const hint = document.getElementById('rangeHintBagian');
            if (d.range && d.range.start && d.range.end){
              const s = new Date(d.range.start), e = new Date(d.range.end);
              const txt = `${fmt(s)} – ${fmt(e)}`;
              setRangeHint(hint, txt, txt);
            } else {
              setRangeHint(hint, 'Semua Data', 'Semua Data');
            }
          })
          .catch(console.error);
      }

      /* ====================== Pengeluaran per Tahun ====================== */
      const pengeluaranData = {
        labels: {!! json_encode($pengeluaranLabels) !!},
        datasets: {!! json_encode($pengeluaranData) !!} // label 'Keluar' + colors sudah dari controller
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

      function renderYearLegend(years, colorsMap){
        const box = document.getElementById('legendYears'); box.innerHTML = '';
        years.forEach(y => {
          const item  = document.createElement('div'); item.className = 'legend-item';
          const color = document.createElement('span'); color.className = 'legend-color';
          color.style.backgroundColor = colorsMap[y] || '#8B5CF6';
          const text  = document.createElement('span'); text.textContent = y;
          item.appendChild(color); item.appendChild(text); box.appendChild(item);
        });
      }
      renderYearLegend({!! json_encode($years) !!}, {!! json_encode($colorsForYears) !!});

      function filterPengeluaran(filterType){
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

            // [NEW] badge rentang untuk grafik tahunan (mis. '2016–2025')
            const hintT = document.getElementById('rangeHintTahun');
            if (d.labels && d.labels.length){
              const txt = `${d.labels[0]} – ${d.labels[d.labels.length-1]}`;
              setRangeHint(hintT, txt, txt);
            } else setRangeHint(hintT, 'Semua Data', 'Semua Data');
          })
          .catch(console.error);
      }

      // [NEW] set awal -> badge “Semua Data”
      setRangeHint(document.getElementById('rangeHintBagian'), 'Semua Data', 'Semua Data');
      setRangeHint(document.getElementById('rangeHintTahun'),  'Semua Data', 'Semua Data');
    });
  </script>
</x-layouts.app>
