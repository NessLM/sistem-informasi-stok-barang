@props([
  // <title> di tab + default heading di header
  'title'        => 'Stokita',
  // Menu untuk sidebar
  'menu'         => [],
  // Override heading di header (jika null -> pakai $title)
  'heading'      => null,
  // Tampilkan crest kanan
  'showCrest'    => true,
])

<!doctype html>
<html lang="id" class="font-sans">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{ $title }}</title>

  {{-- Font & ikon --}}
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  {{-- CSS sidebar milikmu (tetap) --}}
  <link rel="stylesheet" href="{{ asset('assets/css/components/sidebar.css') }}">

  <style>
    body { background-color: #EFF0EE; }

    /* Kartu kecil fallback */
    .card{background:#fff;border-radius:14px;padding:18px;box-shadow:0 10px 24px rgba(0,0,0,.06)}

    /* ===== Header Global (bleed + shadow bawah) =====
       Base offset: -20px kiri/kanan/atas untuk menghapus padding main.content (20px).
       Lalu kita tambahkan kompensasi gutter kiri (lihat rules di bawah). */
    .page-header{
      display:flex;align-items:center;justify-content:space-between;gap:12px;
      background:#fff;
      margin: -20px -20px 12px;     /* top -20, right -20, bottom 12, left -20 (di-override di bawah) */
      padding: 14px 18px;
      border:0; border-radius:0;
      /* Shadow hanya di bawah */
      box-shadow: 0 12px 16px -12px #CBCCCB;
    }

    /* Kompensasi gutter kiri:
       - Normal: sidebar 270px, content margin-left 300px → selisih 30px
       - Collapsed: sidebar 80px, content margin-left 100px → selisih 20px
       Tambahkan selisih itu di atas offset -20px (padding) → margin-left lebih negatif. */
    .layout:not(.is-collapsed) main.content .page-header { margin-left: calc(-20px - 30px); }
    .layout.is-collapsed      main.content .page-header { margin-left: calc(-20px - 20px); }

    .ph-left{display:flex;align-items:center;gap:10px}
    .ph-title{
      font-weight:600; letter-spacing:-.01em; line-height:1.2;
      font-size:clamp(18px, 2.2vw, 26px); color:#111827; margin:0;
    }
    .ph-badge{width:40px;height:auto;object-fit:contain}
    @media (max-width:640px){ .ph-badge{width:34px} }
  </style>
</head>
<body>
  {{-- Loader global --}}
  <x-page-loader variant="a" />

  <div class="layout">
    {{-- Sidebar (tidak diubah) --}}
    <x-sidebar :items="$menu" :user="auth()->user()" brand="Stokita" />

    {{-- ===== Area konten ===== --}}
    <main class="content">
      @php
        $logo = asset('assets/banner/logo_bupati.png');  // crest kanan
        $pageHeading = $heading ?? $title;               // judul di header
      @endphp

      {{-- Header Global: nempel ke kiri (menutup gutter) + shadow bawah --}}
      <header class="page-header" aria-label="Judul Halaman">
        <div class="ph-left">
          <h1 class="ph-title">{{ $pageHeading }}</h1>
        </div>
        @if ($showCrest)
          <img class="ph-badge" src="{{ $logo }}" alt="Lambang">
        @endif
      </header>

      {{-- Konten halaman --}}
      {{ $slot }}
    </main>

    {{-- Toggle collapse sidebar --}}
    <script>
      document.addEventListener("DOMContentLoaded", () => {
        const toggleBtn = document.querySelector("[data-toggle='sidebar']");
        if (toggleBtn) {
          toggleBtn.addEventListener("click", () => {
            document.querySelector(".layout").classList.toggle("is-collapsed");
          });
        }
      });
    </script>
  </div>
</body>
</html>
