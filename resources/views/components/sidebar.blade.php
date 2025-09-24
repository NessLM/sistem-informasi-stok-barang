@props([
    'brand' => 'Stokita',
    'brandLogo' => asset('assets/banner/sidebar/logo_icon_sidebar.png'),
    'user' => null,
    'items' => [],
])

<aside class="sb">
    {{-- Brand + tombol hamburger (versi sidebar) --}}
    <div class="sb-brand">
        <img class="brand-img" src="{{ $brandLogo }}" alt="{{ $brand }}" />
        <button class="brand-action" type="button" aria-label="Menu" data-toggle="sidebar">
            <i class="bi bi-list"></i>
        </button>
    </div>

    {{-- NAV --}}
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
                    <div class="sb-parent-item {{ $active ? 'is-active' : '' }}">
                        <a href="{{ isset($it['route']) ? route($it['route']) : '#' }}"
                            class="sb-main-link {{ $active ? 'is-active' : '' }}"
                            @if ($active) aria-current="page" @endif>
                            @if (!empty($it['icon']))
                                <i class="bi {{ $it['icon'] }}"></i>
                            @endif
                            <span>{{ $it['label'] }}</span>
                        </a>

                        <button class="sb-toggle" type="button" data-toggle="submenu"
                            aria-expanded="{{ $active ? 'true' : 'false' }}"
                            aria-controls="submenu-{{ $index }}">
                            <i class="bi bi-chevron-right caret" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="sb-children {{ $active ? 'show' : '' }}" id="submenu-{{ $index }}">
                        @foreach ($it['children'] as $ch)
                            @php
                                $routeParams = $ch['params'] ?? [];
                                $childRouteName = $ch['route'] ?? null;
                                $childSlug = $routeParams['slug'] ?? null;
                                $currentSlug = request()->route('slug');

                                $isChildActive = false;
                                if (!empty($childRouteName)) {
                                    if (
                                        request()->routeIs($childRouteName) ||
                                        request()->routeIs($childRouteName . '.*')
                                    ) {
                                        $isChildActive = $childSlug !== null ? $currentSlug === $childSlug : true;
                                    }
                                }
                            @endphp

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

    {{-- FOOTER --}}
    <div class="sb-footer">
        <div class="sb-org">
            <div class="org-logo">
                <img src="{{ asset('assets/banner/sidebar/icon_polmanbabel.png') }}" alt="Polman Babel">
            </div>
            <div class="org-name">POLMAN BABEL</div>
            <hr>
        </div>

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

    {{-- ===== JS kecil untuk submenu (tetap) ===== --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function closeOtherSubmenus(except = null) {
                document.querySelectorAll('.sb-children.show').forEach(function(sm) {
                    if (sm !== except) {
                        sm.classList.remove('show');
                        const btn = sm.closest('.sb-item')?.querySelector('.sb-toggle');
                        if (btn) {
                            btn.setAttribute('aria-expanded', 'false');
                            const caret = btn.querySelector('.caret');
                            if (caret) caret.style.transform = 'rotate(0deg)';
                        }
                    }
                });
            }

            document.querySelectorAll('.sb-toggle').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    const item = this.closest('.sb-item');
                    const submenu = item.querySelector('.sb-children');
                    const caret = this.querySelector('.caret');
                    const isOpen = this.getAttribute('aria-expanded') === 'true';

                    if (isOpen) {
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

            const sidebarToggle = document.querySelector('aside.sb .brand-action[data-toggle="sidebar"]');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    // saat collapse/close, tutup semua submenu
                    closeOtherSubmenus();
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

            if (orgLogo) orgLogo.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openModal();
            });
            if (orgName) orgName.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openModal();
            });
            if (closeBtn) closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeModal();
            });
        });
    </script>
</aside>

{{-- ✅ Backdrop ADA DI LUAR <aside> (wajib untuk overlay mobile) --}}
<div id="sb-backdrop" aria-hidden="true"></div>
