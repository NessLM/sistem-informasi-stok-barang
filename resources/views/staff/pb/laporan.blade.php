{{-- resources/views/staff/pb/laporan.blade.php --}}
<x-layouts.app title="Laporan" :menu="$menu">
  {{-- CSS khusus halaman ini --}}
  <link rel="stylesheet" href="{{ asset('assets/css/staff/pb/laporan.css') }}">

  @php
    // sementara: dummy data kalau controller belum isi $reports
    $reports = $reports ?? [
      ['title' => 'LAPORAN STOCK OPNAME BULAN JULI – SEPTEMBER', 'preview_url' => asset('assets/images/laporan/contoh-opname.jpg')],
      ['title' => 'LAPORAN STOCK OPNAME BULAN APRIL – JUNI', 'preview_url' => asset('assets/images/laporan/contoh-opname.jpg')],
      ['title' => 'LAPORAN STOCK OPNAME BULAN FEBRUARI – MARET', 'preview_url' => asset('assets/images/laporan/contoh-opname.jpg')],
      ['title' => 'LAPORAN STOCK OPNAME BARANG GUDANG 2025', 'preview_url' => asset('assets/images/laporan/contoh-opname.jpg')],
    ];
  @endphp

  <div class="page-body">
    <div class="card">
      <h3>Laporan</h3>

      <div class="table-responsive">
        <table class="table table-bordered table-laporan">
          <thead>
            <tr>
              <th class="text-start">LAPORAN</th>
              <th class="col-aksi">AKSI</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($reports as $r)
              @php
                $title = is_array($r) ? ($r['title'] ?? '') : ($r->title ?? '');
                $url = is_array($r) ? ($r['preview_url'] ?? '') : ($r->preview_url ?? ($r->file_url ?? ''));
              @endphp
              <tr>
                <td class="text-start">{{ $title }}</td>
                <td class="text-center">
                  @if ($url)
                    {{-- 👁️ ikon & hover persis halaman Riwayat, tapi trigger modal custom lap-modal --}}
                    <span class="riwayat-bukti-icon" data-url="{{ $url }}" data-title="{{ $title }}" title="Pratinjau">
                      <i class="bi bi-eye-fill"></i>
                    </span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ===== Modal custom ala konten-kanan (bukan Bootstrap modal) ===== --}}
  <div id="lapModal" class="lap-modal" aria-hidden="true" role="dialog" aria-labelledby="lapModalTitle">
    <div class="lap-modal__backdrop" data-close></div>

    <div class="lap-modal__dialog">
      <div class="lap-modal__toolbar">
        <h3 id="lapModalTitle" class="lap-modal__title">Pratinjau Laporan</h3>
        <button class="lap-modal__close" type="button" data-close aria-label="Tutup">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="lap-modal__body">
        {{-- default: gambar --}}
        <img id="lapModalImg" class="lap-modal__img" alt="Pratinjau Laporan" loading="lazy">
        {{-- jika file PDF, akan switch ke iframe --}}
        <iframe id="lapModalPdf" class="lap-modal__pdf" title="Pratinjau PDF" hidden></iframe>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      // ===== LapModal: open/close + auto detect PDF vs Image =====
      const lapModal = document.getElementById('lapModal');
      const lapImg = document.getElementById('lapModalImg');
      const lapPdf = document.getElementById('lapModalPdf');
      const lapTtl = document.getElementById('lapModalTitle');

      function openLapModal(url, title) {
        lapTtl.textContent = title || 'Pratinjau';
        const isPdf = /\.pdf(\?|$)/i.test(url);
        if (isPdf) {
          lapImg.hidden = true; lapImg.src = '';
          lapPdf.hidden = false; lapPdf.src = url;
        } else {
          lapPdf.hidden = true; lapPdf.src = '';
          lapImg.hidden = false; lapImg.src = url;
        }
        lapModal.classList.add('is-open');
        lapModal.setAttribute('aria-hidden', 'false');

        // Tambahkan kelas untuk mobile
        if (window.innerWidth <= 768) {
          document.body.classList.add('modal-open-mobile');
        }
      }

      function closeLapModal() {
        lapModal.classList.remove('is-open');
        lapModal.setAttribute('aria-hidden', 'true');
        lapImg.src = ''; lapPdf.src = '';

        // Hapus kelas untuk mobile
        document.body.classList.remove('modal-open-mobile');
      }

      // Trigger dari ikon 👁️ (class sama seperti Riwayat)
      document.addEventListener('click', (e) => {
        const eye = e.target.closest('.riwayat-bukti-icon');
        if (eye) {
          const url = eye.getAttribute('data-url');
          const title = eye.getAttribute('data-title')
            || eye.closest('tr')?.querySelector('td')?.textContent?.trim();
          openLapModal(url, title);
        }
        if (e.target.closest('[data-close]')) closeLapModal();
      });

      // ESC to close
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lapModal.classList.contains('is-open')) closeLapModal();
      });

      // Responsif saat resize window
      window.addEventListener('resize', function () {
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