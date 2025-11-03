<x-layouts.app :title="$pageTitle" :menu="$menu">
    {{-- CSS khusus dashboard --}}
    <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/dashboard.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <div class="dashboard-container">

        {{-- Row pertama: Ringkasan dan Grafik Per Bulan --}}
        <div class="dashboard-row">
            {{-- ========================= RINGKASAN ========================= --}}
            <div class="summary-section">
                {{-- Header dengan judul --}}
                <div class="summary-header">
                    <h2>Ringkasan - {{ $gudang->nama }}</h2>
                    {{-- Tidak ada filter gudang untuk PJ --}}
                </div>

                {{-- KLASIK: ikon bulat di kiri, angka & label di kanan --}}
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

            {{-- ======================= GRAFIK PER BAGIAN (BULANAN) ======================= --}}
            <div class="chart-section">
                <div class="chart-header chart-header--horizontal">
                    <div class="chart-header-horizontal-item">
                        <h2>Grafik Per Bulan</h2>
                    </div>

                    <div class="chart-header-horizontal-item">
                        <span id="rangeHintBagian" class="range-hint" title="Jan–Des {{ date('Y') }}">Jan–Des {{ date('Y') }}</span>
                    </div>

                    <div class="chart-header-horizontal-item">
                        {{-- Pager lama dipertahankan tapi disembunyikan agar layout aman --}}
                        <div class="pager" id="bagianPager" style="display:none">
                            <button class="pager-btn" id="bagianPrev" title="Sebelumnya" aria-label="Sebelumnya">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <span class="pager-info" id="bagianPagerInfo">1/1</span>
                            <button class="pager-btn" id="bagianNext" title="Berikutnya" aria-label="Berikutnya">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="chart-header-horizontal-item" style="margin-left:auto;">
                        <div class="dropdown">
                            <button class="filter-btn dropdown-toggle" type="button" id="bagianFilterDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-funnel"></i> Semua
                                <i class="bi bi-chevron-right arrow-icon"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="bagianFilterDropdown">
                                <li><a class="dropdown-item filter-option" href="#" data-type="monthly" data-value="all">Semua (Jan–Des)</a></li>
                                <li><a class="dropdown-item filter-option" href="#" data-type="monthly" data-value="3m">3 Bulan Terakhir</a></li>
                                <li><a class="dropdown-item filter-option" href="#" data-type="monthly" data-value="5m">5 Bulan Terakhir</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="chart-container" id="bagianChartBox">
                    <canvas id="bagianChart"></canvas>
                </div>

                <div class="chart-legend">
                    <div class="legend-item"><span class="legend-color" style="background:#10B981"></span><span>Masuk</span></div>
                    <div class="legend-item"><span class="legend-color" style="background:#EF4444"></span><span>Keluar</span></div>
                </div>
            </div>
        </div>

        {{-- =================== GRAFIK PENGELUARAN PER TAHUN =================== --}}
        <div class="dashboard-row">
            <div class="wide-chart-section">
                <div class="chart-header chart-header--horizontal">
                    <div class="chart-header-horizontal-item">
                        <h2>Grafik Pengeluaran per Tahun - {{ $gudang->nama }}</h2>
                    </div>

                    <div class="chart-header-horizontal-item">
                        <span id="rangeHintPengeluaran" class="range-hint" title="Semua Tahun">Semua Tahun</span>
                    </div>

                    <div class="chart-header-horizontal-item" style="margin-left:auto;">
                        <div class="chart-controls">
                            <div class="dropdown">
                                <button class="filter-btn dropdown-toggle" type="button" id="pengeluaranFilterDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-funnel"></i> Semua
                                    <i class="bi bi-chevron-right arrow-icon"></i>
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
        document.addEventListener('DOMContentLoaded', function () {
            const FILTER_URL = "{{ route('pj.dashboard.filter') }}";

            /* ====================== Grafik Bulanan (Line) ====================== */
            // Pastikan bukan null
            let monthLabels = {!! json_encode($monthlyLabels ?? []) !!};
            let dataMasuk   = {!! json_encode($monthlyMasuk  ?? []) !!};
            let dataKeluar  = {!! json_encode($monthlyKeluar ?? []) !!};

            // Sembunyikan pager lama (biar layout tetap)
            const pagerBoxInit = document.getElementById('bagianPager');
            if (pagerBoxInit) pagerBoxInit.style.display = 'none';

            const bagianCtx = document.getElementById('bagianChart').getContext('2d');
            const bagianChart = new Chart(bagianCtx, {
                type: 'line',
                data: {
                    labels: monthLabels,
                    datasets: [
                        {
                            label: 'Masuk',
                            data: dataMasuk,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16,185,129,0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#10B981',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            clip: false
                        },
                        {
                            label: 'Keluar',
                            data: dataKeluar,
                            borderColor: '#EF4444',
                            backgroundColor: 'rgba(239,68,68,0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#EF4444',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            clip: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            offset: false,
                            grid: { color: '#F3F4F6', drawBorder: true },
                            ticks: { color: '#6B7280', maxRotation: 45, minRotation: 45 },
                            afterFit: (axis) => { axis.paddingLeft = 2; }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#F3F4F6' },
                            ticks: { color: '#6B7280', stepSize: 1 }
                        }
                    }
                }
            });

            // Helper badge
            function setRangeHint(el, text) { if (el) { el.textContent = text; el.title = text; } }

            // Dropdown handler (monthly & pengeluaran)
            document.querySelectorAll('.filter-option').forEach(el => {
                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    const type  = this.getAttribute('data-type');
                    const value = this.getAttribute('data-value');
                    const dd    = this.closest('.dropdown').querySelector('.dropdown-toggle');
                    dd.innerHTML = `<i class="bi bi-funnel"></i> ${this.textContent.trim()} <i class="bi bi-chevron-right arrow-icon"></i>`;
                    if (type === 'monthly') return filterMonthly(value);
                    filterPengeluaran(value);
                });
            });

            // AJAX filter monthly
            function filterMonthly(win) {
                fetch(`${FILTER_URL}?type=monthly&filter=${win}`)
                    .then(r => r.json())
                    .then(d => {
                        bagianChart.data.labels = d.labels || [];
                        bagianChart.data.datasets[0].data = d.masuk  || [];
                        bagianChart.data.datasets[1].data = d.keluar || [];
                        bagianChart.update();
                        setRangeHint(document.getElementById('rangeHintBagian'), (d.range && d.range.text) ? d.range.text : 'Jan–Des {{ date('Y') }}');
                    })
                    .catch(console.error);
            }

            /* ====================== Pengeluaran per Tahun (tetap) ====================== */
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
                        y: {
                            beginAtZero: true,
                            ticks: { color: '#6B7280' },
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

                        const hint = document.getElementById('rangeHintPengeluaran');
                        if (d.labels && d.labels.length > 0) {
                            const startYear = Math.min(...d.labels);
                            const endYear = Math.max(...d.labels);
                            setRangeHint(hint, `${startYear} – ${endYear}`);
                        } else {
                            setRangeHint(hint, 'Semua Tahun');
                        }
                    })
                    .catch(console.error);
            }

            // Badge awal
            setRangeHint(document.getElementById('rangeHintBagian'), 'Jan–Des {{ date('Y') }}');
            const initialYears = {!! json_encode($years) !!};
            if (initialYears.length > 0) {
                const startYear = Math.min(...initialYears);
                const endYear   = Math.max(...initialYears);
                setRangeHint(document.getElementById('rangeHintPengeluaran'), `${startYear} – ${endYear}`);
            }
        });

        // ===== ANIMASI TRANSISI SETELAH LOGIN (tetap) =====
        document.addEventListener('DOMContentLoaded', function () {
            const sections = document.querySelectorAll('.summary-section, .chart-section, .wide-chart-section');
            sections.forEach(section => { void section.offsetWidth; });

            const numberElements = document.querySelectorAll('.summary-card__number');
            numberElements.forEach(element => {
                const finalValue = element.textContent;
                if (!isNaN(finalValue)) {
                    element.textContent = '0';
                    element.classList.add('number-counting');
                    setTimeout(() => { animateCount(element, 0, parseInt(finalValue), 1000); }, 800);
                }
            });

            function animateCount(element, start, end, duration) {
                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    const value = Math.floor(progress * (end - start) + start);
                    element.textContent = value.toLocaleString();
                    if (progress < 1) window.requestAnimationFrame(step);
                };
                window.requestAnimationFrame(step);
            }
        });
    </script>

</x-layouts.app>
