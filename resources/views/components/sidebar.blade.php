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
                $selfActive = !empty($it['route']) &&
                              (request()->routeIs($it['route']) || request()->routeIs($it['route'] . '.*'));

                $hasChildren = !empty($it['children'] ?? []);
                $childActive = false;
                if ($hasChildren) {
                    foreach ($it['children'] as $ch) {
                        if (!empty($ch['route']) &&
                            (request()->routeIs($ch['route']) || request()->routeIs($ch['route'] . '.*'))) {
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
                                        $isChildActive = request()->routeIs($ch['route']) ||
                                                         request()->routeIs($ch['route'] . '.*');
                                    }
                                    $routeParams = $ch['params'] ?? [];
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
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="sb-logout" type="submit">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>
