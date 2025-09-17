@props([
    'brand' => 'Stokita',
    'brandLogo' => asset('assets/banner/sidebar/logo_icon_sidebar.png'), // ← LOGO STOKITA
    'user' => null,
    'items' => [], // [label, route?, icon?, children?]
])

<aside class="sb">
    <div class="sb-brand">
        <img class="brand-img" src="{{ $brandLogo }}" alt="{{ $brand }}" />

        {{-- ikon menu kanan (bootstrap icons) --}}
        <button class="brand-action" type="button" aria-label="Menu" data-toggle="sidebar">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <nav class="sb-nav" role="navigation" aria-label="Menu Samping">
        @foreach ($items as $it)
            @php
                /* =========================================================
                   [ADD] Logic aktif:
                   - $selfActive: route cocok persis ATAU prefix route.* cocok
                   - $childActive: salah satu anak aktif (kalau ada children)
                   - $active: parent dianggap aktif jika self OR child aktif
                   ========================================================= */
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
                    {{-- [ADD] Parent otomatis terbuka jika aktif/ada child aktif --}}
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
                                    $isChildActive =
                                        !empty($ch['route']) &&
                                        (request()->routeIs($ch['route']) || request()->routeIs($ch['route'] . '.*'));
                                @endphp
                                <a href="{{ isset($ch['route']) ? route($ch['route']) : '#' }}"
                                    class="sb-link {{ $isChildActive ? 'is-active' : '' }}">
                                    @if (!empty($ch['icon']))
                                        <i class="bi {{ $ch['icon'] }}"></i>
                                    @endif
                                    <span>{{ $ch['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </details>
                @else
                    {{-- Link biasa: beri class is-active bila route cocok --}}
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
        <div class="modal-content">
            <!-- Header modal (tidak bisa discroll) -->
            <div class="modal-header-s">
                <button type="button" class="btn-close close" data-bs-dismiss="modal" aria-label="Close"></button>
                <h3>Data Pengembang</h3>
            </div>

            <!-- Konten utama modal (bisa discroll) -->
            <div class="modal-body">
                <!-- 3 Pengembang di atas -->
                <div class="developers-grid">
                    <!-- Pengembang 1 -->
                    <div class="developer-item">
                        <img src="{{ asset('assets/banner/developers/developer1.jpg') }}" alt="Foto Pengembang"
                            class="dev-photo">
                        <div class="dev-details">
                            <table>
                                <tr>
                                    <td><strong>Nama</strong> </td>
                                    <td>: Fateh Tikal Zamzami</td>
                                </tr>
                                <tr>
                                    <td><strong>NPM</strong> </td>
                                    <td>: 1062312</td>
                                </tr>
                                <tr>
                                    <td><strong>PRODI</strong> </td>
                                    <td>: D-IV Teknologi Rekayasa Perangkat Lunak</td>
                                </tr>
                                <tr>
                                    <td><strong>Alamat</strong> </td>
                                    <td>: Jln. Kapten Soiraiman 1, Sri Pemandang</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Pengembang 2 -->
                    <div class="developer-item">
                        <img src="{{ asset('assets/banner/developers/developer2.jpg') }}" alt="Foto Pengembang"
                            class="dev-photo">
                        <div class="dev-details">
                            <table>
                                <tr>
                                    <td><strong>Nama</strong> </td>
                                    <td>: Pramudya Zulmy Alfarizi </td>
                                </tr>
                                <tr>
                                    <td><strong>NPM</strong> </td>
                                    <td>: 1062323</td>
                                </tr>
                                <tr>
                                    <td><strong>PRODI</strong> </td>
                                    <td>: D-IV Teknologi Rekayasa Perangkat Lunak</td>
                                </tr>
                                <tr>
                                    <td><strong>Alamat</strong> </td>
                                    <td>: Jln. K.H. Agus Salim, Belakang Masjid, Desa Pemali</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Pengembang 3 -->
                    <div class="developer-item">
                        <img src="{{ asset('assets/banner/developers/developer3.jpg') }}" alt="Foto Pengembang"
                            class="dev-photo">
                        <div class="dev-details">
                            <table>
                                <tr>
                                    <td><strong>Nama</strong> </td>
                                    <td>: Arya Ramadhani</td>
                                </tr>
                                <tr>
                                    <td><strong>NPM</strong> </td>
                                    <td>: 1062337</td>
                                </tr>
                                <tr>
                                    <td><strong>PRODI</strong> </td>
                                    <td>: D-IV Teknologi Rekayasa Perangkat Lunak</td>
                                </tr>
                                <tr>
                                    <td><strong>Alamat</strong> </td>
                                    <td>: Jln. Sisingamangaraja, No. 05, Desa Air Ruai</td>
                                </tr>
                            </table>
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
                                    <td><strong>Nama</strong> </td>
                                    <td>: Raihan Adi Pratama</td>
                                </tr>
                                <tr>
                                    <td><strong>NPM</strong> </td>
                                    <td>: 1062325</td>
                                </tr>
                                <tr>
                                    <td><strong>PRODI</strong> </td>
                                    <td>: D-IV Teknologi Rekayasa Perangkat Lunak</td>
                                </tr>
                                <tr>
                                    <td><strong>Alamat</strong> </td>
                                    <td>: Perumahan Bumi Arwana Permai Blok F22, Jln. Sisingamangaraja, Desa Air Ruai</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Pengembang 5 -->
                    <div class="developer-item">
                        <img src="{{ asset('assets/banner/developers/developer5.jpg') }}" alt="Foto Pengembang"
                            class="dev-photo">
                        <div class="dev-details">
                            <table>
                                <tr>
                                    <td><strong>Nama</strong> </td>
                                    <td>: Agung Sopian</td>
                                </tr>
                                <tr>
                                    <td><strong>NPM</strong> </td>
                                    <td>: 1062303</td>
                                </tr>
                                <tr>
                                    <td><strong>PRODI</strong> </td>
                                    <td>: D-IV Teknologi Rekayasa Perangkat Lunak</td>
                                </tr>
                                <tr>
                                    <td><strong>Alamat</strong> </td>
                                    <td>: Jln. Kompleks Nangnung Utara, Sungailiat</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer modal (tidak bisa discroll) -->
            <div class="modal-footer">
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
