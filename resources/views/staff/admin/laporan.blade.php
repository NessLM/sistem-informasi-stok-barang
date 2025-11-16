{{-- resources/views/staff/admin/laporan.blade.php --}}
<x-layouts.app title="Laporan" :menu="$menu">
  <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/laporan.css') }}">

  <div class="page-body">
    <div class="card">
      <h3>Laporan Stock Opname</h3>

      <div class="table-responsive">
        <table class="table table-bordered table-laporan">
          <thead>
            <tr>
              <th class="text-start">LAPORAN</th>
              <th class="col-aksi">AKSI</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($reports as $report)
              <tr>
                <td class="text-start">{{ $report['title'] }}</td>
                <td class="text-center">
                  @if ($report['exists']) {{-- PERBAIKAN: Gunakan 'exists' bukan 'file_url' --}}
                    <span class="riwayat-bukti-icon" 
                          data-quarter="{{ $report['quarter'] }}" 
                          data-year="{{ $report['year'] }}"
                          data-title="{{ $report['title'] }}" 
                          title="Pratinjau Laporan"
                          style="cursor: pointer; color: #3498db; font-size: 18px;">
                      <i class="bi bi-eye-fill"></i>
                    </span>
                  @else
                    <span class="text-muted" title="Laporan belum tersedia">-</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ===== Modal untuk preview laporan HTML ===== --}}
  <div id="lapModal" class="lap-modal" aria-hidden="true" role="dialog" aria-labelledby="lapModalTitle">
    <div class="lap-modal__backdrop" data-close></div>

    <div class="lap-modal__dialog lap-modal__dialog--large">
      <div class="lap-modal__toolbar">
        <h3 id="lapModalTitle" class="lap-modal__title">Pratinjau Laporan</h3>
        <button class="lap-modal__close" type="button" data-close aria-label="Tutup">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="lap-modal__body">
        {{-- Container untuk konten HTML laporan --}}
        <div id="laporanContent" class="laporan-content">
          {{-- Konten laporan akan dimuat di sini via AJAX --}}
        </div>
        
        {{-- Loading indicator --}}
        <div id="laporanLoading" class="laporan-loading">
          <div class="loading-spinner"></div>
          <p>Memuat laporan...</p>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      // ===== LapModal functions =====
      const lapModal = document.getElementById('lapModal');
      const laporanContent = document.getElementById('laporanContent');
      const laporanLoading = document.getElementById('laporanLoading');
      const lapTtl = document.getElementById('lapModalTitle');

      async function openLapModal(quarter, year, title) {
        console.log('Opening modal for:', quarter, year, title); // Debug log
        
        lapTtl.textContent = title || 'Pratinjau Laporan';
        
        // Tampilkan loading
        laporanLoading.style.display = 'block';
        laporanContent.style.display = 'none';
        laporanContent.innerHTML = '';
        
        try {
          // Load konten laporan via AJAX
          const response = await fetch(`/admin/laporan/preview/${quarter}/${year}`);
          
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          
          const html = await response.text();
          
          laporanContent.innerHTML = html;
          laporanLoading.style.display = 'none';
          laporanContent.style.display = 'block';
          
        } catch (error) {
          console.error('Error loading report:', error);
          laporanLoading.style.display = 'none';
          laporanContent.innerHTML = `
            <div class="alert alert-danger">
              <i class="bi bi-exclamation-triangle"></i>
              Gagal memuat laporan. Silakan coba lagi.<br>
              Error: ${error.message}
            </div>
          `;
          laporanContent.style.display = 'block';
        }
        
        lapModal.classList.add('is-open');
        lapModal.setAttribute('aria-hidden', 'false');

        if (window.innerWidth <= 768) {
          document.body.classList.add('modal-open-mobile');
        }
      }

      function closeLapModal() {
        lapModal.classList.remove('is-open');
        lapModal.setAttribute('aria-hidden', 'true');
        laporanContent.innerHTML = '';
        document.body.classList.remove('modal-open-mobile');
      }

      // ===== Event Listeners =====
      document.addEventListener('click', (e) => {
        const eye = e.target.closest('.riwayat-bukti-icon');
        if (eye) {
          const quarter = eye.getAttribute('data-quarter');
          const year = eye.getAttribute('data-year');
          const title = eye.getAttribute('data-title');
          console.log('Icon clicked:', quarter, year, title); // Debug log
          openLapModal(quarter, year, title);
        }
        
        if (e.target.closest('[data-close]')) {
          closeLapModal();
        }
      });

      // Keyboard navigation
      document.addEventListener('keydown', (e) => {
        if (!lapModal.classList.contains('is-open')) return;
        
        if (e.key === 'Escape') closeLapModal();
      });

      // Responsive handling
      window.addEventListener('resize', function() {
        if (lapModal.classList.contains('is-open')) {
          if (window.innerWidth <= 768) {
            document.body.classList.add('modal-open-mobile');
          } else {
            document.body.classList.remove('modal-open-mobile');
          }
        }
      });
    </script>
  @endpush
</x-layouts.app>