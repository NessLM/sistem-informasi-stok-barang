<x-layouts.app title="Dashboard Admin" :menu="$menu">

    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/dashboard.css') }}">
    @endpush
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <div class="dashboard-container">

        {{-- Row pertama: Ringkasan dan Grafik Per Gudang --}}
        <div class="dashboard-row">
            {{-- ========================= RINGKASAN ========================= --}}
            <div class="summary-section">
                {{-- Header dengan judul dan filter dropdown --}}
                <div class="summary-header">
                    <h2>Ringkasan</h2>
                    <div class="summary-filter">
                        <button class="summary-filter-btn" type="button" id="summaryFilterBtn" aria-expanded="false">
                            <i class="bi bi-funnel"></i> <span id="summaryFilterText">{{ $gudangUtama->nama }}</span>
                            <i class="bi bi-chevron-right arrow-icon"></i>
                        </button>
                        <div class="summary-dropdown-menu" id="summaryDropdownMenu">
                            @foreach ($gudangs as $gudang)
                                <button class="summary-dropdown-item" data-value="{{ $gudang->nama }}"
                                    @if($gudang->id === $gudangUtama->id) data-default="true" @endif>
                                    {{ $gudang->nama }}
                                </button>
                            @endforeach
                        </div>
                    </div>
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
                            <div class="summary-card__label">Total Stok Barang</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======================= GRAFIK PER GUDANG ======================= --}}
            <div class="chart-section">
                {{-- Layout horizontal sejajar untuk semua komponen --}}
                <div class="chart-header chart-header--horizontal">
                    <div class="chart-header-horizontal-item">
                        <h2>Grafik Per Gudang</h2>
                    </div>

                    <div class="chart-header-horizontal-item">
                        <span id="rangeHintGudang" class="range-hint" title="Semua Data">Semua Data</span>
                    </div>

                    <div class="chart-header-horizontal-item" style="margin-left:auto;">
                        {{-- Filter waktu --}}
                        <div class="dropdown">
                            <button class="filter-btn dropdown-toggle" type="button" id="gudangFilterDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-funnel"></i> Semua
                                <i class="bi bi-chevron-right arrow-icon"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="gudangFilterDropdown">
                                <li><a class="dropdown-item filter-option" href="#" data-type="gudang"
                                        data-value="all">Semua</a></li>
                                <li><a class="dropdown-item filter-option" href="#" data-type="gudang"
                                        data-value="week">1 Minggu Terakhir</a></li>
                                <li><a class="dropdown-item filter-option" href="#" data-type="gudang"
                                        data-value="month">1 Bulan Terakhir</a></li>
                                <li><a class="dropdown-item filter-option" href="#" data-type="gudang"
                                        data-value="year">1 Tahun Terakhir</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Wadah kanvas chart --}}
                <div class="chart-container">
                    <canvas id="gudangChart"></canvas>
                </div>

                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-color masuk"></span>
                        <span>Masuk</span>
                    </div>
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
                    </div>

                    <div class="chart-controls">
                        <div class="dropdown">
                            <button class="filter-btn dropdown-toggle" type="button" id="pengeluaranFilterDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-funnel"></i> Semua
                                <i class="bi bi-chevron-right arrow-icon"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="pengeluaranFilterDropdown">
                                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran"
                                        data-value="all">Semua</a></li>
                                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran"
                                        data-value="5y">5 Tahun Terakhir</a></li>
                                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran"
                                        data-value="7y">7 Tahun Terakhir</a></li>
                                <li><a class="dropdown-item filter-option" href="#" data-type="pengeluaran"
                                        data-value="10y">10 Tahun Terakhir</a></li>
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
        document.addEventListener('DOMContentLoaded', function () {
            const FILTER_URL = "{{ route('admin.dashboard.filter') }}";

            /* ====================== Atur Arrow Dropdown ====================== */
            // Untuk custom dropdown Ringkasan
            const summaryFilterBtn = document.getElementById('summaryFilterBtn');
            const summaryDropdownMenu = document.getElementById('summaryDropdownMenu');
            const summaryFilterText = document.getElementById('summaryFilterText');
            const totalJenisBarangEl = document.getElementById('totalJenisBarang');
            const totalBarangEl = document.getElementById('totalBarang');

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

                    // Filter ringkasan data
                    filterRingkasan(selectedValue);
                }
            });

            function filterRingkasan(gudangNama) {
                fetch(`${FILTER_URL}?type=ringkasan&filter=${encodeURIComponent(gudangNama)}`)
                    .then(r => r.json())
                    .then(data => {
                        // Update numbers with animation
                        animateNumber(totalJenisBarangEl, data.totalJenisBarang);
                        animateNumber(totalBarangEl, data.totalBarang);
                    })
                    .catch(console.error);
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

            /* ====================== Grafik Per Gudang (Masuk & Keluar) ====================== */
            const gudangLabels = {!! json_encode($gudangLabels) !!};
            const masukData = {!! json_encode($masukData) !!};
            const keluarData = {!! json_encode($keluarData) !!};

            const gudangCtx = document.getElementById('gudangChart').getContext('2d');
            const gudangChart = new Chart(gudangCtx, {
                type: 'line',
                data: {
                    labels: gudangLabels,
                    datasets: [
                        {
                            label: 'Masuk',
                            data: masukData,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#10B981',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        },
                        {
                            label: 'Keluar',
                            data: keluarData,
                            borderColor: '#EF4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#EF4444',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#374151',
                            borderWidth: 1
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
                                color: '#F3F4F6',
                                display: true
                            }
                        }
                    }
                }
            });

            // ===== Helper format tanggal untuk badge (ID locale) =====
            function fmt(d) {
                const z = n => String(n).padStart(2, '0');
                return `${z(d.getDate())}/${z(d.getMonth() + 1)}/${d.getFullYear()}`;
            }

            function setRangeHint(el, text, titleText) {
                if (!el) return;
                el.textContent = text;
                el.title = titleText || text;
            }

            // ===== Bootstrap Dropdown Events untuk Arrow Rotation =====
            document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(dropdown => {
                dropdown.addEventListener('show.bs.dropdown', function () {
                    this.setAttribute('aria-expanded', 'true');
                });

                dropdown.addEventListener('hide.bs.dropdown', function () {
                    this.setAttribute('aria-expanded', 'false');
                });
            });

            // ===== Filter dropdown (keduanya) =====
            document.querySelectorAll('.filter-option').forEach(el => {
                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    const type = this.getAttribute('data-type');
                    const value = this.getAttribute('data-value');
                    const dropdownToggle = this.closest('.dropdown').querySelector('.dropdown-toggle');
                    dropdownToggle.innerHTML = `<i class="bi bi-funnel"></i> ${this.textContent} <i class="bi bi-chevron-right arrow-icon"></i>`;

                    if (type === 'gudang') filterGudang(value);
                    else filterPengeluaran(value);
                });
            });

            // ====== Filter Per Gudang (update data + badge tanggal) ======
            function filterGudang(filterType) {
                fetch(`${FILTER_URL}?type=gudang&filter=${filterType}`)
                    .then(r => r.json())
                    .then(d => {
                        gudangChart.data.labels = d.labels || [];
                        gudangChart.data.datasets[0].data = d.masuk || [];
                        gudangChart.data.datasets[1].data = d.keluar || [];
                        gudangChart.update();

                        const hint = document.getElementById('rangeHintGudang');
                        if (d.range && d.range.start && d.range.end) {
                            const s = new Date(d.range.start),
                                e = new Date(d.range.end);
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
                datasets: {!! json_encode($pengeluaranData) !!}
            };
            // Ambil data mentah dulu
            const pengeluaranChartData = pengeluaranData.datasets[0].data;

            // Cari nilai maksimum dan minimum
            const maxValue = Math.max(...pengeluaranChartData, 0);
            const minValue = Math.min(...pengeluaranChartData, 0);

            // Biar sumbu Y fleksibel sesuai data
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
                            ticks: {
                                color: '#6B7280',
                                callback: function (value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                },
                            },
                            suggestedMin: suggestedMin,
                            suggestedMax: suggestedMax,
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
                    })
                    .catch(console.error);
            }

            // set awal -> badge "Semua Data"
            setRangeHint(document.getElementById('rangeHintGudang'), 'Semua Data', 'Semua Data');
        });
    </script>

</x-layouts.app>