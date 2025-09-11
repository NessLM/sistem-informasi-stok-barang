{{-- resources/views/staff/pj/riwayat.blade.php --}}

<x-layouts.app title="Riwayat" :menu="$menu">
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/riwayat.css') }}">
    @endpush

    <div class="container-fluid riwayat-container">
        
        <!-- Filter Section -->
        <div class="card riwayat-filter-card mb-4">
            <div class="card-body riwayat-filter-body">
                <h3>Filter Data</h3>
                <form id="filterForm" class="riwayat-filter-form">
                    <div class="riwayat-filter-group">
                        <select name="alur_barang" class="form-select riwayat-filter-select" onchange="this.form.submit()">
                            <option value="Semua" {{ request('alur_barang') == 'Semua' ? 'selected' : '' }}>Semua</option>
                            <option value="Keluar" {{ request('alur_barang') == 'Keluar' ? 'selected' : '' }}>Keluar</option>
                            <option value="Masuk" {{ request('alur_barang') == 'Masuk' ? 'selected' : '' }}>Masuk</option>
                        </select>
                    </div>
                    
                    <div class="riwayat-filter-group">
                        <select name="periode" class="form-select riwayat-filter-select" onchange="this.form.submit()">
                            <option value="">Pilih Periode</option>
                            <option value="1_minggu_terakhir" {{ request('periode') == '1_minggu_terakhir' ? 'selected' : '' }}>1 Minggu Terakhir</option>
                            <option value="1_bulan_terakhir" {{ request('periode') == '1_bulan_terakhir' ? 'selected' : '' }}>1 Bulan Terakhir</option>
                            <option value="1_tahun_terakhir" {{ request('periode') == '1_tahun_terakhir' ? 'selected' : '' }}>1 Tahun Terakhir</option>
                        </select>
                    </div>
                    
                    <a href="{{ route('admin.riwayat.index') }}" class="btn riwayat-btn-reset">
                        <i class="bi bi-arrow-clockwise me-2"></i>Reset
                    </a>
                </form>
            </div>
        </div>

        <!-- Table Section -->
        <div class="card riwayat-table-card">
            <div class="card-body p-0">
                <div class="riwayat-table-container">
                    <table class="table table-striped table-hover riwayat-table mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Nama Barang</th>
                                <th>Jumlah</th>
                                <th>Bagian</th>
                                <th>Bukti</th>
                                <th>Alur Barang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riwayat as $index => $item)
                                <tr>
                                    <td class="fw-semibold">{{ $index + 1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->waktu)->format('H.i') }} WIB</td>
                                    <td class="fw-medium">{{ $item->nama_barang }}</td>
                                    <td><span class="badge bg-secondary">{{ $item->jumlah }}</span></td>
                                    <td>{{ $item->bagian }}</td>
                                    <td>
                                        @if($item->bukti)
                                            <span class="text-success riwayat-status-icon">✅</span>
                                        @else
                                            <span class="text-danger riwayat-status-icon">❌</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="riwayat-badge {{ $item->alur_barang == 'Keluar' ? 'riwayat-badge-keluar' : 'riwayat-badge-masuk' }}">
                                            {{ $item->alur_barang }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="riwayat-empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p>Tidak ada data ditemukan</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const filterForm = document.getElementById('filterForm');
                const selects = filterForm.querySelectorAll('select');
                
                selects.forEach(select => {
                    select.addEventListener('change', function() {
                        const table = document.querySelector('.riwayat-table');
                        table.classList.add('riwayat-loading');
                        
                        setTimeout(() => {
                            filterForm.submit();
                        }, 300);
                    });
                });
                
                // Highlight active filters
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('alur_barang') || urlParams.get('periode')) {
                    document.querySelector('.riwayat-btn-filter').classList.add('active');
                }
            });
        </script>
    @endpush
</x-layouts.app>