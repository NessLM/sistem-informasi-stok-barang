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
        @foreach ($items as $it)
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
                    <details {{ $active ? 'open' : '' }}>
                        <summary>
                            @if (!empty($it['icon']))
                                <i class="bi {{ $it['icon'] }}"></i>
                            @endif
                            <span>{{ $it['label'] }}</span>
                            <i class="bi bi-chevron-right caret" aria-hidden="true"></i>
                        </summary>

                        <div class="sb-children">
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

                    </details>
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
                                <tr>
                                    <td>Jln. Kapten Soiraiman 1, Sri Pemandang, Sungailiat</td>
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
                                <tr>
                                    <td>Jln. K.H. Agus Salim, Belakang Masjid, Desa Pemali</td>
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
                                <tr>
                                    <td>Jln. Sisingamangaraja, No. 05, Desa Air Ruai</td>
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
                                <tr>
                                    <td>Jln. Sisingamangaraja, Perumahan Bumi Arwana Permai Blok F22, Desa Air Ruai</td>
                                </tr>
                            </table>
                            <div class="social-media-section">
                                <div class="social-media-links">
                                    <a href="https://wa.me/6281317741708" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/whatsapp.png') }}" alt="Whatsapp"
                                            class="social-icon">
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
                                <tr>
                                    <td>Jln. Kompleks Nangnung Utara, Kost Oren No. 02, Sungailiat</td>
                                </tr>
                            </table>
                            <div class="social-media-section">
                                <div class="social-media-links">
                                    <a href="https://wa.me/6283132155465" target="_blank">
                                        <img src="{{ asset('assets/banner/sosial_media/whatsapp.png') }}" alt="Whatsapp"
                                            class="social-icon">
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
            const modal = document.getElementById('devModal');
            const closeBtn = document.querySelector('.close');
            const orgLogo = document.querySelector('.org-logo');
            const orgName = document.querySelector('.org-name');

            // Fungsi untuk membuka modal
            function openModal() {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden'; // Mencegah scroll di latar belakang
            }

            // Fungsi untuk menutup modal
            function closeModal() {
                modal.classList.remove('show');
                document.body.style.overflow = ''; // Mengembalikan scroll
            }

            // Event listener untuk logo (saat sidebar collapsed)
            if (orgLogo) {
                orgLogo.addEventListener('click', openModal);
            }

            // Event listener untuk teks (saat sidebar expanded)
            if (orgName) {
                orgName.addEventListener('click', openModal);
            }

            // Event listener untuk tombol close
            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }

            // Tutup modal jika klik di luar konten modal
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            // Tutup modal dengan tombol Escape
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && modal.classList.contains('show')) {
                    closeModal();
                }
            });
        });
    </script>
</aside>
