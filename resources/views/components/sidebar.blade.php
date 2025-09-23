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
                    (request()->routeIs($it['route']) || request()->routeIs($it['route'].'.*'));

                $hasChildren = !empty($it['children'] ?? []);
                $childActive = false;

                if ($hasChildren) {
                    foreach ($it['children'] as $ch) {
                        if (!empty($ch['route']) &&
                            (request()->routeIs($ch['route']) || request()->routeIs($ch['route'].'.*'))) {
                            $childActive = true; break;
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
                           @if($active) aria-current="page" @endif>
                            @if (!empty($it['icon'])) <i class="bi {{ $it['icon'] }}"></i>@endif
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
                                    if (request()->routeIs($childRouteName) || request()->routeIs($childRouteName.'.*')) {
                                        $isChildActive = $childSlug !== null ? ($currentSlug === $childSlug) : true;
                                    }
                                }
                            @endphp

                            <a href="{{ isset($ch['route']) ? route($ch['route'], $routeParams) : '#' }}"
                               class="sb-link {{ $isChildActive ? 'is-active' : '' }}">
                                @if (!empty($ch['icon'])) <i class="bi {{ $ch['icon'] }}"></i>@endif
                                <span>{{ $ch['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <a href="{{ isset($it['route']) ? route($it['route']) : '#' }}"
                       class="sb-link {{ $selfActive ? 'is-active' : '' }}">
                        @if (!empty($it['icon'])) <i class="bi {{ $it['icon'] }}"></i>@endif
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

    {{-- ===== JS kecil untuk submenu (tetap) ===== --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function closeOtherSubmenus(except = null) {
                document.querySelectorAll('.sb-children.show').forEach(function (sm) {
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

            document.querySelectorAll('.sb-toggle').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault(); e.stopImmediatePropagation();
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
                sidebarToggle.addEventListener('click', function () {
                    // saat collapse/close, tutup semua submenu
                    closeOtherSubmenus();
                });
            }

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.sb-item') && !e.target.matches('.sb-toggle')) {
                    closeOtherSubmenus();
                }
            });
        });
    </script>
</aside>

{{-- ✅ Backdrop ADA DI LUAR <aside> (wajib untuk overlay mobile) --}}
<div id="sb-backdrop" aria-hidden="true"></div>
