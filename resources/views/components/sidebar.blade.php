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
                    <div class="sb-parent-item">
                        {{-- Link utama (ke halaman overview) --}}
                        <a href="{{ isset($it['route']) ? route($it['route']) : '#' }}"
                            class="sb-main-link {{ $selfActive ? 'is-active' : '' }}">
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
                                $isChildActive = false;
                                if (!empty($ch['route'])) {
                                    $isChildActive =
                                        request()->routeIs($ch['route']) || request()->routeIs($ch['route'] . '.*');
                                }
                                $routeParams = $ch['params'] ?? [];
                            @endphp

                            <a href="{{ isset($ch['route']) ? route($ch['route'], $routeParams) : '#' }}"
                                class="sb-link {{ $isChildActive ? 'active' : '' }}">
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

            // Fungsi untuk menutup semua submenu kecuali yang diberikan
            function closeOtherSubmenus(exceptSubmenu = null) {
                console.log('Closing other submenus');
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

            // Debug: Cek elemen yang ditemukan
            const toggleButtons = document.querySelectorAll('.sb-toggle');
            const sidebarToggle = document.querySelector('[data-toggle="sidebar"]');

            console.log('Found toggle buttons:', toggleButtons.length);
            console.log('Found sidebar toggle:', sidebarToggle);

            // Handle submenu toggle - FIXED VERSION
            toggleButtons.forEach(function(toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    console.log('Submenu toggle clicked');
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    const parentItem = this.closest('.sb-item');
                    const submenu = parentItem.querySelector('.sb-children');
                    const caret = this.querySelector('.caret');

                    console.log('Submenu found:', submenu);

                    // Toggle status submenu
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    console.log('Current state - expanded:', isExpanded);

                    if (isExpanded) {
                        // Tutup submenu
                        submenu.classList.remove('show');
                        this.setAttribute('aria-expanded', 'false');
                        if (caret) {
                            caret.style.transform = 'rotate(0deg)';
                        }
                    } else {
                        // Buka submenu dan tutup yang lain
                        closeOtherSubmenus(submenu);
                        submenu.classList.add('show');
                        this.setAttribute('aria-expanded', 'true');
                        if (caret) {
                            caret.style.transform = 'rotate(90deg)';
                        }
                    }
                });
            });

            // Handle sidebar collapse toggle - FIXED VERSION
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    console.log('Sidebar toggle clicked');
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    const layout = document.querySelector('.layout');
                    if (layout) {
                        layout.classList.toggle('is-collapsed');
                        console.log('Sidebar collapsed:', layout.classList.contains('is-collapsed'));

                        // Tutup semua dropdown saat collapsed
                        if (layout.classList.contains('is-collapsed')) {
                            closeOtherSubmenus();
                        }
                    }
                });
            }

            // Tutup semua submenu saat klik di luar - FIXED
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.sb-item') && !e.target.matches('.sb-toggle')) {
                    closeOtherSubmenus();
                }
            });

            // ===== MODAL FUNCTIONALITY - FIXED =====
            const modal = document.getElementById('devModal');
            const closeBtn = document.querySelector('.btn-close-s.close');
            const orgLogo = document.querySelector('.org-logo');
            const orgName = document.querySelector('.org-name');

            console.log('Modal elements:', {
                modal: modal,
                closeBtn: closeBtn,
                orgLogo: orgLogo,
                orgName: orgName
            });

            // Fungsi untuk membuka modal
            function openModal() {
                console.log('Opening modal');
                if (modal) {
                    modal.classList.add('show');
                    document.body.style.overflow = 'hidden';
                    document.body.style.paddingRight = '15px'; // Prevent content shift
                }
            }

            // Fungsi untuk menutup modal
            function closeModal() {
                console.log('Closing modal');
                if (modal) {
                    modal.classList.remove('show');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }
            }

            // Event listener untuk logo institusi
            if (orgLogo) {
                orgLogo.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openModal();
                });
            }

            // Event listener untuk teks institusi (saat sidebar expanded)
            if (orgName) {
                orgName.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openModal();
                });
            }

            // Event listener untuk tombol close
            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeModal();
                });
            }

            // Tutup modal jika klik di luar konten modal
            document.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            // Tutup modal dengan tombol Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal && modal.classList.contains('show')) {
                    closeModal();
                }
            });

            // Prevent modal content clicks from closing modal
            if (modal) {
                modal.addEventListener('click', function(e) {
                    e.stopPropagation();
                });

                const modalContent = modal.querySelector('.modal-content-s');
                if (modalContent) {
                    modalContent.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                }
            }

            // ===== ACTIVE STATE MANAGEMENT =====
            // Fungsi untuk menangani klik pada item gudang
            function handleGudangClick(event) {
                console.log('Gudang item clicked');

                // Hapus kelas aktif dari semua item gudang
                document.querySelectorAll('.sb-link').forEach(link => {
                    link.classList.remove('is-active');
                });

                // Tambahkan kelas aktif ke item yang diklik
                this.classList.add('is-active');

                // Simpan status aktif di localStorage
                if (this.href && this.href !== '#') {
                    localStorage.setItem('activeGudang', this.href);
                }
            }

            // Tambahkan event listener ke semua link gudang
            document.querySelectorAll('.sb-link').forEach(link => {
                link.addEventListener('click', handleGudangClick);
            });

            // Pulihkan status aktif dari localStorage
            const activeHref = localStorage.getItem('activeGudang');
            if (activeHref) {
                const activeLink = document.querySelector(`.sb-link[href="${activeHref}"]`);
                if (activeLink) {
                    activeLink.classList.add('is-active');
                }
            }

            // Handle active state untuk main link pada menu dengan children
            document.querySelectorAll('.sb-main-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    console.log('Main link clicked');

                    // Hapus active class dari semua main links
                    document.querySelectorAll('.sb-main-link').forEach(mainLink => {
                        mainLink.classList.remove('is-active');
                    });

                    // Tambahkan active class ke yang diklik
                    this.classList.add('is-active');

                    // Simpan status aktif di localStorage
                    if (this.href && this.href !== '#') {
                        localStorage.setItem('activeMainLink', this.href);
                    }
                });
            });

            // Pulihkan status aktif main link dari localStorage
            const activeMainHref = localStorage.getItem('activeMainLink');
            if (activeMainHref) {
                const activeMainLink = document.querySelector(`.sb-main-link[href="${activeMainHref}"]`);
                if (activeMainLink) {
                    activeMainLink.classList.add('is-active');
                }
            }

            console.log('Sidebar initialization completed');
        });
    </script>
</aside>
