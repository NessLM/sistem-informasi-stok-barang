@props([
  'brand'     => 'Stokita',
  'brandLogo' => asset('assets/banner/sidebar/logo_icon_sidebar.png'), // ← LOGO STOKITA
  'user'      => null,
  'items'     => [],  // [label, route?, icon?, children?]
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
        $active      = isset($it['route']) && request()->routeIs($it['route']);
        $hasChildren = !empty($it['children'] ?? []);
      @endphp

      <div class="sb-item {{ $active ? 'is-active' : '' }}">
        @if ($hasChildren)
          <details {{ $active ? 'open' : '' }}>
            <summary>
              @if(!empty($it['icon'])) <i class="bi {{ $it['icon'] }}"></i>@endif
              <span>{{ $it['label'] }}</span>
              <i class="bi bi-chevron-right caret" aria-hidden="true"></i>
            </summary>
            <div class="sb-children">
              @foreach ($it['children'] as $ch)
              <a href="{{ isset($ch['route']) ? route($ch['route']) : '#' }}"
                 class="sb-link {{ request()->routeIs($ch['route'] ?? '') ? 'is-active' : '' }}">
                @if(!empty($ch['icon'])) <i class="bi {{ $ch['icon'] }}"></i>@endif
                <span>{{ $ch['label'] }}</span>
              </a>
            @endforeach            
            </div>
          </details>
        @else
          <a href="{{ isset($it['route']) ? route($it['route']) : '#' }}" class="sb-link">
            @if(!empty($it['icon'])) <i class="bi {{ $it['icon'] }}"></i>@endif
            <span>{{ $it['label'] }}</span>
          </a>
        @endif
      </div>
    @endforeach
  </nav>

  <div class="sb-footer">
    <div class="sb-user">
      <i class="bi bi-person-circle"></i>
      <div class="meta">
        <Text>{{ $user->nama ?? 'Pengguna' }}</Text><Br>
          <hr>
          <small>{{ $user->role ?? '-' }}</small> 
      </div>
    </div>

    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button class="sb-logout" type="submit"></i> Logout</button>
    </form>
  </div>
</aside>
