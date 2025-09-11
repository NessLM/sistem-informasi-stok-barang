@props([
  // Dipakai untuk <title> tab browser & default heading di header
  'title'        => 'Stokita',
  // Menu untuk sidebar
  'menu'         => [],
  // (Opsional) Override heading yang tampil di header; jika null -> pakai $title
  'heading'      => null,
  // (Opsional) Tampilkan crest/lambang di kanan header (default: true)
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

  {{-- (Opsional) CSS global app --}}
  {{-- <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}"> --}}

  <style>
    body {
      background-color: #EFF0EE;
    }
    /* Kartu konten kecil (fallback) */
    .card{background:#fff;border-radius:14px;padding:18px;box-shadow:0 10px 24px rgba(0,0,0,.06)}

    /* ===== Header Global - gaya mirip gambar kedua ===== */
    .page-header{
      display:flex;align-items:center;justify-content:space-between;gap:12px;
      background:#fff;
      border:0;                 /* no border keliling */
      border-bottom:1px solid #e5e7eb; /* garis tipis bawah */
      border-radius:0;          /* no rounded */
      padding:12px 18px;        /* lebih ramping */
      margin:0 0 0 0;        /* tempel ke atas konten */
      box-shadow:none;          /* no shadow */
    }
    .ph-left{display:flex;align-items:center;gap:10px}
    .ph-title{
      font-weight:600;          /* lebih ringan dari 800 */
      letter-spacing:-.01em;
      line-height:1.15;
      font-size:clamp(18px, 2.2vw, 26px); /* ukuran mirip contoh */
      color:#111827;            /* abu-hitam */
      margin:0;
    }
    .ph-badge{width:40px;height:auto;object-fit:contain} /* crest kecil di kanan */
    @media (max-width:640px){ .ph-badge{width:34px} }
  </style>
</head>
<body>
  {{-- Loader global (punyamu) --}}
  <x-page-loader variant="a" />

  <div class="layout">
    {{-- Sidebar reusable (tidak diubah) --}}
    <x-sidebar :items="$menu" :user="auth()->user()" brand="Stokita" />

    {{-- ===== Area konten ===== --}}
    <main class="content">
      @php
        // Asset logo/crest (sesuai permintaan)
        $logo = asset('assets/banner/logo_bupati.png');

        // Heading yang ditampilkan di header
        $pageHeading = $heading ?? $title;
      @endphp

      {{-- ===== Header Global (flat) ===== --}}
      <header class="page-header" aria-label="Judul Halaman">
        <div class="ph-left">
          <h1 class="ph-title">{{ $pageHeading }}</h1>
        </div>

        @if ($showCrest)
          <img class="ph-badge" src="{{ $logo }}" alt="Lambang">
        @endif
      </header>

      {{-- Slot konten halaman --}}
      {{ $slot }}
    </main>

    {{-- Script kecil untuk collapse sidebar --}}
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
