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
                  @if ($report['exists'])
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

    <div class="lap-modal__dialog">
      {{-- Toolbar --}}
      <div class="lap-modal__toolbar">
        <h3 id="lapModalTitle" class="lap-modal__title">Pratinjau Laporan</h3>
        <button class="lap-modal__close" type="button" data-close aria-label="Tutup">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      {{-- Controls: Zoom & Pagination --}}
      <div class="lap-modal__controls">
        {{-- Zoom Controls --}}
        <div class="control-group">
          <button class="control-btn" id="zoomOut" title="Zoom Out">
            <i class="bi bi-zoom-out"></i>
          </button>
          <span class="zoom-info" id="zoomLevel">100%</span>
          <button class="control-btn" id="zoomIn" title="Zoom In">
            <i class="bi bi-zoom-in"></i>
          </button>
          <button class="control-btn" id="zoomReset" title="Reset Zoom">
            <i class="bi bi-arrow-clockwise"></i> Reset
          </button>
        </div>

        {{-- Pagination Controls --}}
        <div class="control-group">
          <button class="control-btn" id="prevPage" title="Halaman Sebelumnya">
            <i class="bi bi-chevron-left"></i> Prev
          </button>
          <span class="page-info" id="pageInfo">1 / 1</span>
          <button class="control-btn" id="nextPage" title="Halaman Selanjutnya">
            Next <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

      {{-- Modal Body --}}
      <div class="lap-modal__body">
        {{-- Container untuk konten HTML laporan --}}
        <div id="laporanContent" class="laporan-content">
          {{-- Pages will be inserted here --}}
        </div>
        
        {{-- Loading indicator --}}
        <div id="laporanLoading" class="laporan-loading" style="display: none;">
          <div class="loading-spinner"></div>
          <p>Memuat laporan...</p>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script>
      // ===== Global Variables =====
      const lapModal = document.getElementById('lapModal');
      const laporanContent = document.getElementById('laporanContent');
      const laporanLoading = document.getElementById('laporanLoading');
      const lapTtl = document.getElementById('lapModalTitle');
      const pageInfo = document.getElementById('pageInfo');
      const zoomLevel = document.getElementById('zoomLevel');

      let currentPage = 1;
      let totalPages = 1;
      let currentZoom = 1.0;
      let pages = [];
      let fullHTML = '';

      // ===== Zoom Controls =====
      const zoomStep = 0.1;
      const minZoom = 0.5;
      const maxZoom = 2.0;

      document.getElementById('zoomIn').addEventListener('click', () => {
        if (currentZoom < maxZoom) {
          currentZoom = Math.min(currentZoom + zoomStep, maxZoom);
          applyZoom();
        }
      });

      document.getElementById('zoomOut').addEventListener('click', () => {
        if (currentZoom > minZoom) {
          currentZoom = Math.max(currentZoom - zoomStep, minZoom);
          applyZoom();
        }
      });

      document.getElementById('zoomReset').addEventListener('click', () => {
        currentZoom = 1.0;
        applyZoom();
      });

      function applyZoom() {
        const activePage = laporanContent.querySelector('.laporan-page.active');
        if (activePage) {
          activePage.style.transform = `scale(${currentZoom})`;
        }
        zoomLevel.textContent = `${Math.round(currentZoom * 100)}%`;
        
        // Update button states
        document.getElementById('zoomIn').disabled = currentZoom >= maxZoom;
        document.getElementById('zoomOut').disabled = currentZoom <= minZoom;
      }

      // ===== Pagination Controls =====
      document.getElementById('prevPage').addEventListener('click', () => {
        if (currentPage > 1) {
          currentPage--;
          showPage(currentPage);
        }
      });

      document.getElementById('nextPage').addEventListener('click', () => {
        if (currentPage < totalPages) {
          currentPage++;
          showPage(currentPage);
        }
      });

      function showPage(pageNum) {
        console.log('Showing page:', pageNum, 'of', totalPages);
        
        // Hide all pages
        const allPages = laporanContent.querySelectorAll('.laporan-page');
        allPages.forEach(page => {
          page.classList.remove('active');
          page.style.display = 'none';
        });
        
        // Show current page
        const currentPageEl = laporanContent.querySelector(`[data-page="${pageNum}"]`);
        if (currentPageEl) {
          currentPageEl.classList.add('active');
          currentPageEl.style.display = 'block';
          applyZoom();
        }
        
        // Update page info
        pageInfo.textContent = `${pageNum} / ${totalPages}`;
        
        // Update button states
        document.getElementById('prevPage').disabled = pageNum === 1;
        document.getElementById('nextPage').disabled = pageNum === totalPages;
      }

      // ===== Modal Functions =====
      async function openLapModal(quarter, year, title) {
        console.log('Opening modal for:', quarter, year, title);
        
        lapTtl.textContent = title || 'Pratinjau Laporan';
        
        // Reset state
        currentPage = 1;
        currentZoom = 1.0;
        pages = [];
        
        // Show modal and loading
        lapModal.classList.add('is-open');
        lapModal.setAttribute('aria-hidden', 'false');
        laporanLoading.style.display = 'flex';
        laporanContent.innerHTML = '';
        laporanContent.style.display = 'none';
        
        try {
          // Load content
          const url = `/admin/laporan/preview/${quarter}/${year}`;
          console.log('Fetching:', url);
          
          const response = await fetch(url);
          
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
          }
          
          fullHTML = await response.text();
          console.log('HTML loaded, length:', fullHTML.length);
          
          if (!fullHTML || fullHTML.length < 100) {
            throw new Error('HTML content is too short or empty');
          }
          
          // Parse and split into pages
          splitIntoPages(fullHTML);
          
          // Show first page
          if (pages.length > 0) {
            totalPages = pages.length;
            console.log('Total pages created:', totalPages);
            
            laporanLoading.style.display = 'none';
            laporanContent.style.display = 'block';
            showPage(1);
          } else {
            throw new Error('No pages were created');
          }
          
        } catch (error) {
          console.error('Error loading report:', error);
          laporanLoading.style.display = 'none';
          laporanContent.innerHTML = `
            <div style="padding: 2rem; text-align: center;">
              <div class="alert alert-danger" style="display: inline-block; text-align: left;">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Gagal memuat laporan</strong><br>
                ${error.message}<br><br>
                <small>Quarter: ${quarter}, Year: ${year}</small>
              </div>
            </div>
          `;
          laporanContent.style.display = 'block';
        }

        if (window.innerWidth <= 768) {
          document.body.classList.add('modal-open-mobile');
        }
      }

      function splitIntoPages(html) {
        console.log('Starting splitIntoPages...');
        
        // Create parser
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const body = doc.body;
        
        if (!body) {
          console.error('No body found in parsed HTML');
          return;
        }
        
        console.log('Body parsed successfully');
        
        // Get all main elements
        const kopSurat = body.querySelector('.kop-surat');
        const judulLaporan = body.querySelector('.judul-laporan');
        const infoSurat = body.querySelector('.info-surat');
        
        // Find sections
        const allElements = Array.from(body.children);
        let masukSection = { title: null, table: null };
        let distribusiSection = { title: null, table: null };
        let keluarSection = { title: null, table: null };
        let ttd = body.querySelector('.ttd');
        
        // Parse sections
        allElements.forEach((el, idx) => {
          if (el.tagName === 'H3') {
            const text = el.textContent.trim().toLowerCase();
            const nextEl = allElements[idx + 1];
            
            if (text.includes('barang masuk')) {
              masukSection.title = el;
              masukSection.table = nextEl;
            } else if (text.includes('distribusi')) {
              distribusiSection.title = el;
              distribusiSection.table = nextEl;
            } else if (text.includes('barang keluar') || text.includes('keluar')) {
              keluarSection.title = el;
              keluarSection.table = nextEl;
            }
          }
        });
        
        console.log('Sections found:', {
          kop: !!kopSurat,
          judul: !!judulLaporan,
          info: !!infoSurat,
          masuk: !!masukSection.title,
          distribusi: !!distribusiSection.title,
          keluar: !!keluarSection.title,
          ttd: !!ttd
        });
        
        pages = [];
        
        // PAGE 1: Header + Info + Barang Masuk
        const page1 = createPage(1);
        if (kopSurat) page1.appendChild(kopSurat.cloneNode(true));
        if (judulLaporan) page1.appendChild(judulLaporan.cloneNode(true));
        if (infoSurat) page1.appendChild(infoSurat.cloneNode(true));
        
        if (masukSection.title) {
          page1.appendChild(masukSection.title.cloneNode(true));
          if (masukSection.table) {
            page1.appendChild(masukSection.table.cloneNode(true));
          }
        }
        
        laporanContent.appendChild(page1);
        pages.push(page1);
        console.log('Page 1 created');
        
        // PAGE 2: Distribusi (if exists)
        if (distribusiSection.title) {
          const page2 = createPage(2);
          if (kopSurat) page2.appendChild(kopSurat.cloneNode(true));
          if (judulLaporan) page2.appendChild(judulLaporan.cloneNode(true));
          
          page2.appendChild(distribusiSection.title.cloneNode(true));
          if (distribusiSection.table) {
            page2.appendChild(distribusiSection.table.cloneNode(true));
          }
          
          laporanContent.appendChild(page2);
          pages.push(page2);
          console.log('Page 2 created');
        }
        
        // PAGE 3: Barang Keluar + TTD (if exists)
        if (keluarSection.title) {
          const page3 = createPage(3);
          if (kopSurat) page3.appendChild(kopSurat.cloneNode(true));
          if (judulLaporan) page3.appendChild(judulLaporan.cloneNode(true));
          
          page3.appendChild(keluarSection.title.cloneNode(true));
          if (keluarSection.table) {
            page3.appendChild(keluarSection.table.cloneNode(true));
          }
          
          if (ttd) {
            page3.appendChild(ttd.cloneNode(true));
          }
          
          laporanContent.appendChild(page3);
          pages.push(page3);
          console.log('Page 3 created');
        }
        
        console.log('Total pages created:', pages.length);
      }

      function createPage(pageNum) {
        const page = document.createElement('div');
        page.className = 'laporan-page';
        page.setAttribute('data-page', pageNum);
        page.style.display = 'none';
        return page;
      }

      function closeLapModal() {
        lapModal.classList.remove('is-open');
        lapModal.setAttribute('aria-hidden', 'true');
        laporanContent.innerHTML = '';
        pages = [];
        currentPage = 1;
        currentZoom = 1.0;
        fullHTML = '';
        document.body.classList.remove('modal-open-mobile');
      }

      // ===== Event Listeners =====
      document.addEventListener('click', (e) => {
        const eye = e.target.closest('.riwayat-bukti-icon');
        if (eye) {
          e.preventDefault();
          const quarter = eye.getAttribute('data-quarter');
          const year = eye.getAttribute('data-year');
          const title = eye.getAttribute('data-title');
          openLapModal(quarter, year, title);
        }
        
        if (e.target.closest('[data-close]')) {
          e.preventDefault();
          closeLapModal();
        }
      });

      // Keyboard navigation
      document.addEventListener('keydown', (e) => {
        if (!lapModal.classList.contains('is-open')) return;
        
        if (e.key === 'Escape') {
          closeLapModal();
        } else if (e.key === 'ArrowLeft' && currentPage > 1) {
          document.getElementById('prevPage').click();
        } else if (e.key === 'ArrowRight' && currentPage < totalPages) {
          document.getElementById('nextPage').click();
        } else if (e.key === '+' || e.key === '=') {
          document.getElementById('zoomIn').click();
        } else if (e.key === '-') {
          document.getElementById('zoomOut').click();
        } else if (e.key === '0') {
          document.getElementById('zoomReset').click();
        }
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

      // Prevent zoom on double-tap for mobile
      let lastTouchEnd = 0;
      document.addEventListener('touchend', function (event) {
        const now = (new Date()).getTime();
        if (now - lastTouchEnd <= 300) {
          event.preventDefault();
        }
        lastTouchEnd = now;
      }, false);
    </script>
  @endpush
</x-layouts.app>