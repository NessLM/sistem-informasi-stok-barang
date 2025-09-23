@props([
    'brand' => 'Stokita',
    'brandLogo' => asset('assets/banner/sidebar/logo_icon_sidebar.png'),
    'user' => null,
    'items' => [],
])

<aside class="sb">
    <div class="sb-brand">
        <img class="brand-img" src="{{ $brandLogo }}" alt="{{ $brand }}" />
        <button class="brand-action" type="button" aria-label="Menu" data-toggle="sidebar">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <nav class="sb-nav" role="navigation" aria-label="Menu Samping">
        @foreach ($items as $index => $it)
            @php
                $selfActive =
                    !empty($it['route']) &&
                    (request()->routeIs($it['route']) || request()->routeIs($it['route'] . '.*'));

                $hasChildren = !empty($it['children'] ?? []);
                $childActive = false;
                if ($hasChildren) {
                    foreach ($it['children'] as $ch) {
                        if (
                            !empty($ch['route']) &&
                            (request()->routeIs($ch['route']) || request()->routeIs($ch['route'] . '.*'))
                        ) {
                            $childActive = true;
                            break;
                        }
                    }
                }
                $active = $selfActive || $childActive;
            @endphp

            <div class="sb-item {{ $active ? 'is-active' : '' }}">
                @if ($hasChildren)
                    {{-- Menu dengan children memiliki link utama + dropdown --}}
                    <div class="sb-parent-item {{ $active ? 'is-active' : '' }}">
                        <a href="{{ isset($it['route']) ? route($it['route']) : '#' }}"
                            class="sb-main-link {{ $active ? 'is-active' : '' }}"
                            @if($active) aria-current="page" @endif>
                    
                            @if (!empty($it['icon']))
                                <i class="bi {{ $it['icon'] }}"></i>
                            @endif
                            <span>{{ $it['label'] }}</span>
                        </a>

                        {{-- Toggle button untuk dropdown --}}
                        <button class="sb-toggle" type="button" data-toggle="submenu"
                            aria-expanded="{{ $active ? 'true' : 'false' }}"
                            aria-controls="submenu-{{ $index }}">
                            <i class="bi bi-chevron-right caret" aria-hidden="true"></i>
                        </button>
                    </div>

                    {{-- Submenu children --}}
                    <div class="sb-children {{ $active ? 'show' : '' }}" id="submenu-{{ $index }}">
                        @foreach ($it['children'] as $ch)
                            @php
                                // ===== PENENTU AKTIF YANG BENAR UNTUK CHILD =====
                                // Semua child share route name sama (admin.datakeseluruhan.gudang),
                                // jadi kita WAJIB cocokkan juga parameter uniknya (slug).
                                $routeParams = $ch['params'] ?? [];
                                $childRouteName = $ch['route'] ?? null;

                                // slug yg dipasang di menu untuk child ini
                                $childSlug = $routeParams['slug'] ?? null;

                                // slug yang sedang aktif di URL sekarang
                                $currentSlug = request()->route('slug');

                                // aktif hanya jika:
                                // - route name cocok
                                // - dan slug (bila ada) sama persis
                                $isChildActive = false;
                                if (!empty($childRouteName)) {
                                    if (request()->routeIs($childRouteName) || request()->routeIs($childRouteName . '.*')) {
                                        // Jika menu punya slug, cocokkan dengan slug URL
                                        if ($childSlug !== null) {
                                            $isChildActive = ($currentSlug === $childSlug);
                                        } else {
                                            // fallback: kalau tak ada slug di menu, anggap aktif hanya dengan route match
                                            $isChildActive = true;
                                        }
                                    }
                                }
                            @endphp

                            {{-- 🔧 gunakan 'is-active' agar CSS highlight jalan --}}
                            <a href="{{ isset($ch['route']) ? route($ch['route'], $routeParams) : '#' }}"
                                class="sb-link {{ $isChildActive ? 'is-active' : '' }}">
                                @if (!empty($ch['icon']))
                                    <i class="bi {{ $ch['icon'] }}"></i>
                                @endif
                                <span>{{ $ch['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    {{-- Menu tanpa children (link biasa) --}}
                    <a href="{{ isset($it['route']) ? route($it['route']) : '#' }}"
                        class="sb-link {{ $selfActive ? 'is-active' : '' }}">
                        @if (!empty($it['icon']))
                            <i class="bi {{ $it['icon'] }}"></i>
                        @endif
                        <span>{{ $it['label'] }}</span>
                    </a>
                @endif
            </div>
        @endforeach
    </nav>

    <div class="sb-footer">
        {{-- === Identitas institusi di footer === --}}
        <div class="sb-org">
            {{-- Logo institusi (hanya tampil saat collapsed) --}}
            <div class="org-logo">
                <img src="{{ asset('assets/banner/sidebar/icon_polmanbabel.png') }}" alt="Polman Babel">
            </div>

            {{-- Teks institusi (hanya tampil saat expanded) --}}
            <div class="org-name">POLMAN BABEL</div>
            <hr>
        </div>

        {{-- Logout: expanded = teks saja, collapsed = ikon-only --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="sb-logout" type="submit">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>

    <!-- Modal/Popup Data Pengembang -->
    <div id="devModal" class="modal-s">
        <div class="modal-content-s">
            <!-- Header modal (tidak bisa discroll) -->
            <div class="modal-header-s">
                <button type="button" class="btn-close-s close" data-bs-dismiss="modal" aria-label="Close"></button>
                <h3>Data Pengembang</h3>
            </div>

            <!-- Konten utama modal (bisa discroll) -->
            <div class="modal-body-s">
                <!-- 3 Pengembang di atas -->
                <div class="developers-grid">
                    <!-- Pengembang 1 -->
                    <div class="developer-item">
                        <img src="{{ asset('assets/banner/developers/developer1.jpg') }}" alt="Foto Pengembang"
                            class="dev-photo">
                        <div class="dev-details">
                            <table>
                                <tr>
                                    <td>Fateh Tikal Zamzami</td>
                                </tr>
                                <tr>
                                    <td>Mahasiswa POLMAN BABEL D-IV Teknologi Rekayasa Perangkat Lunak</td>
                                </tr>
                            </table>
                            <div class="social-media-section">
                                <div class="social-media-links">
                                    <a href="https://wa.me/62895416356500" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/whatsapp.png') }}"
                                            alt="Whatsapp" class="social-icon">
                                    </a>
                                    <a href="https://www.instagram.com/fatehtikal_/" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/instagram.png') }}"
                                            alt="Instagram" class="social-icon">
                                    </a>
                                    <a href="https://www.linkedin.com/in/fateh-tikal-zamzami/" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/linkedin.png') }}"
                                            alt="LinkedIn" class="social-icon">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pengembang 2 -->
                    <div class="developer-item">
                        <img src="{{ asset('assets/banner/developers/developer2.jpg') }}" alt="Foto Pengembang"
                            class="dev-photo">
                        <div class="dev-details">
                            <table>
                                <tr>
                                    <td>Pramudya Zulmy Alfarizi </td>
                                </tr>
                                <tr>
                                    <td>Mahasiswa POLMAN BABEL D-IV Teknologi Rekayasa Perangkat Lunak</td>
                                </tr>
                            </table>
                            <div class="social-media-section">
                                <div class="social-media-links">
                                    <a href="https://wa.me/6285758733879" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/whatsapp.png') }}"
                                            alt="Whatsapp" class="social-icon">
                                    </a>
                                    <a href="https://www.instagram.com/polmanbabel/" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/instagram.png') }}"
                                            alt="Instagram" class="social-icon">
                                    </a>
                                    <a href="https://www.linkedin.com/in/pramudya-zulmy-al-farizi-59019a331/"
                                        target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/linkedin.png') }}"
                                            alt="LinkedIn" class="social-icon">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pengembang 3 -->
                    <div class="developer-item">
                        <img src="{{ asset('assets/banner/developers/developer3.jpg') }}" alt="Foto Pengembang"
                            class="dev-photo">
                        <div class="dev-details">
                            <table>
                                <tr>
                                    <td>Arya Ramadhani</td>
                                </tr>
                                <tr>
                                    <td>Mahasiswa POLMAN BABEL D-IV Teknologi Rekayasa Perangkat Lunak</td>
                                </tr>
                            </table>
                            <div class="social-media-section">
                                <div class="social-media-links">
                                    <a href="https://wa.me/6283199522223" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/whatsapp.png') }}"
                                            alt="Whatsapp" class="social-icon">
                                    </a>
                                    <a href="https://www.instagram.com/aryaa.rdn/" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/instagram.png') }}"
                                            alt="Instagram" class="social-icon">
                                    </a>
                                    <a href="https://www.linkedin.com/in/arya-ramadhani-40a612369/" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/linkedin.png') }}"
                                            alt="LinkedIn" class="social-icon">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2 Pengembang di bawah -->
                <div class="developers-bottom">
                    <!-- Pengembang 4 -->
                    <div class="developer-item">
                        <img src="{{ asset('assets/banner/developers/developer4.jpg') }}" alt="Foto Pengembang"
                            class="dev-photo">
                        <div class="dev-details">
                            <table>
                                <tr>
                                    <td>Raihan Adi Pratama</td>
                                </tr>
                                <tr>
                                    <td>Mahasiswa POLMAN BABEL D-IV Teknologi Rekayasa Perangkat Lunak</td>
                                </tr>
                            </table>
                            <div class="social-media-section">
                                <div class="social-media-links">
                                    <a href="https://wa.me/6281317741708" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/whatsapp.png') }}"
                                            alt="Whatsapp" class="social-icon">
                                    </a>
                                    <a href="https://www.instagram.com/nessbroh/" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/instagram.png') }}"
                                            alt="Instagram" class="social-icon">
                                    </a>
                                    <a href="https://www.linkedin.com/in/raihanadiprtm/" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/linkedin.png') }}"
                                            alt="LinkedIn" class="social-icon">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pengembang 5 -->
                    <div class="developer-item">
                        <img src="{{ asset('assets/banner/developers/developer5.jpg') }}" alt="Foto Pengembang"
                            class="dev-photo">
                        <div class="dev-details">
                            <table>
                                <tr>
                                    <td>Agung Sopian</td>
                                </tr>
                                <tr>
                                    <td>Mahasiswa POLMAN BABEL D-IV Teknologi Rekayasa Perangkat Lunak</td>
                                </tr>
                            </table>
                            <div class="social-media-section">
                                <div class="social-media-links">
                                    <a href="https://wa.me/6283132155465" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/whatsapp.png') }}"
                                            alt="Whatsapp" class="social-icon">
                                    </a>
                                    <a href="https://www.instagram.com/agungspnn/" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/instagram.png') }}"
                                            alt="Instagram" class="social-icon">
                                    </a>
                                    <a href="https://www.linkedin.com/in/agungspnn/" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/linkedin.png') }}"
                                            alt="LinkedIn" class="social-icon">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer modal (tidak bisa discroll) -->
            <div class="modal-footer-s">
                <div class="website-info">
                    <p><strong>Politeknik Manufaktur Negeri Bangka Belitung</strong></p>
                    <p>Kunjungi website kami : <a href="https://polman-babel.ac.id" target="_blank"
                            class="website-link">polman-babel.ac.id</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Sidebar script loaded');

            function closeOtherSubmenus(exceptSubmenu = null) {
                document.querySelectorAll('.sb-children.show').forEach(function(submenu) {
                    if (submenu !== exceptSubmenu) {
                        submenu.classList.remove('show');
                        const toggleBtn = submenu.closest('.sb-item').querySelector('.sb-toggle');
                        if (toggleBtn) {
                            toggleBtn.setAttribute('aria-expanded', 'false');
                            const caret = toggleBtn.querySelector('.caret');
                            if (caret) {
                                caret.style.transform = 'rotate(0deg)';
                            }
                        }
                    }
                });
            }

            const toggleButtons = document.querySelectorAll('.sb-toggle');
            const sidebarToggle = document.querySelector('[data-toggle="sidebar"]');

            toggleButtons.forEach(function(toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    const parentItem = this.closest('.sb-item');
                    const submenu = parentItem.querySelector('.sb-children');
                    const caret = this.querySelector('.caret');

                    const isExpanded = this.getAttribute('aria-expanded') === 'true';

                    if (isExpanded) {
                        submenu.classList.remove('show');
                        this.setAttribute('aria-expanded', 'false');
                        if (caret) caret.style.transform = 'rotate(0deg)';
                    } else {
                        closeOtherSubmenus(submenu);
                        submenu.classList.add('show');
                        this.setAttribute('aria-expanded', 'true');
                        if (caret) caret.style.transform = 'rotate(90deg)';
                    }
                });
            });

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    const layout = document.querySelector('.layout');
                    const willCollapse = !layout.classList.contains('is-collapsed');
                    if (willCollapse) closeOtherSubmenus();
                });
            }

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.sb-item') && !e.target.matches('.sb-toggle')) {
                    closeOtherSubmenus();
                }
            });

            // Modal logic
            const modal = document.getElementById('devModal');
            const closeBtn = document.querySelector('.btn-close-s.close');
            const orgLogo = document.querySelector('.org-logo');
            const orgName = document.querySelector('.org-name');

            function openModal() {
                if (modal) {
                    modal.classList.add('show');
                    document.body.style.overflow = 'hidden';
                    document.body.style.paddingRight = '15px';
                }
            }

            function closeModal() {
                if (modal) {
                    modal.classList.remove('show');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }
            }

            if (orgLogo) orgLogo.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); openModal(); });
            if (orgName) orgName.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); openModal(); });
            if (closeBtn) closeBtn.addEventListener('click', function(e) { e.preventDefault(); e.stopPropagation(); closeModal(); });
            document.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && modal && modal.classList.contains('show')) closeModal(); });
            if (modal) {
                modal.addEventListener('click', function(e) { e.stopPropagation(); });
                const modalContent = modal.querySelector('.modal-content-s');
                if (modalContent) modalContent.addEventListener('click', function(e) { e.stopPropagation(); });
            }

            console.log('Sidebar initialization completed (child active strictly by slug).');
        });
    </script>
</aside>
