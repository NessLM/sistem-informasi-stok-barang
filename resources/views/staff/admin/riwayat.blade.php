{{-- resources/views/staff/pj/riwayat.blade.php --}}

<x-layouts.app title="Riwayat" :menu="$menu">
    <div class="container-fluid">
        <h1 class="mb-4">Filter Data</h1>
        
        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="card-title mb-0">Keluaran</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <form id="filterForm" class="d-inline-block">
                            <div class="btn-group">
                                <!-- Alur Barang Filter -->
                                <select name="alur_barang" class="form-select me-2" onchange="this.form.submit()">
                                    <option value="Semua" {{ request('alur_barang') == 'Semua' ? 'selected' : '' }}>Semua</option>
                                    <option value="Keluar" {{ request('alur_barang') == 'Keluar' ? 'selected' : '' }}>Keluar</option>
                                    <option value="Masuk" {{ request('alur_barang') == 'Masuk' ? 'selected' : '' }}>Masuk</option>
                                </select>
                                
                                <!-- Periode Filter -->
                                <select name="periode" class="form-select me-2" onchange="this.form.submit()">
                                    <option value="">Pilih Periode</option>
                                    <option value="1_minggu_terakhir" {{ request('periode') == '1_minggu_terakhir' ? 'selected' : '' }}>1 Minggu Terakhir</option>
                                    <option value="1_bulan_terakhir" {{ request('periode') == '1_bulan_terakhir' ? 'selected' : '' }}>1 Bulan Terakhir</option>
                                    <option value="1_tahun_terakhir" {{ request('periode') == '1_tahun_terakhir' ? 'selected' : '' }}>1 Tahun Terakhir</option>
                                </select>
                                
                                <!-- Reset Button -->
                                <a href="{{ route('riwayat.index') }}" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
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
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->waktu)->format('H.i') }} WIB</td>
                                    <td>{{ $item->nama_barang }}</td>
                                    <td>{{ $item->jumlah }}</td>
                                    <td>{{ $item->bagian }}</td>
                                    <td>
                                        @if($item->bukti)
                                            <span class="text-success">✅</span>
                                        @else
                                            <span class="text-danger">❌</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $item->alur_barang == 'Keluar' ? 'bg-danger' : 'bg-success' }}">
                                            {{ $item->alur_barang }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">Tidak ada data ditemukan</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    @push('styles')
        <style>
            .card {
                border: none;
                border-radius: 10px;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            }
            
            .card-title {
                font-weight: 600;
                color: #333;
            }
            
            .table th {
                background-color: #f8f9fa;
                font-weight: 600;
                border-top: none;
            }
            
            .badge {
                font-size: 0.85em;
                padding: 0.5em 0.75em;
            }
            
            .bg-danger {
                background-color: #dc3545 !important;
            }
            
            .bg-success {
                background-color: #198754 !important;
            }
            
            .form-select {
                width: auto;
                display: inline-block;
            }
        </style>
    @endpush
</x-layouts.app>