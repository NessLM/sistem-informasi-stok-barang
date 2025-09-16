@props([
    'title' => 'Stokita',
    'menu' => [],
    'heading' => null,
    'showCrest' => true,
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- CSS sidebar --}}
    <link rel="stylesheet" href="{{ asset('assets/css/components/sidebar.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/datapengguna-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/riwayat.css') }}">

    <style>
        body { margin:0; background:#EFF0EE; }

        .layout { --sb-w:270px; }
        .layout.is-collapsed { --sb-w:80px; }

        .modal-footer .btn { min-height:42px; }

        main.content{
            margin-left:var(--sb-w) !important;
            width:calc(100% - var(--sb-w)) !important;
            padding:0 !important;
            transition: margin-left .3s ease, width .3s ease;
            margin-top:100px;
        }

        .card{ background:#fff; border-radius:14px; padding:18px; box-shadow:0 10px 24px rgba(0,0,0,.06) }

        .page-header{
            display:flex; align-items:center; justify-content:space-between; gap:12px;
            background:#fff; padding:14px 18px; margin:0; border:0; border-radius:0;
            border-bottom:1px solid #e5e7eb; box-shadow:0 12px 16px -12px #CBCCCB;
            position:fixed; top:0; left:var(--sb-w); width:calc(100% - var(--sb-w));
            z-index:1000; transition:left .3s ease, width .3s ease;
        }
        .ph-left{ display:flex; align-items:center; gap:10px }
        .ph-title{ margin:0; font-weight:600; letter-spacing:-.01em; line-height:1.2; font-size:clamp(18px,2.2vw,26px); color:#111827; }
        .ph-badge{ width:40px; height:auto; object-fit:contain }
        @media (max-width:640px){ .ph-badge{ width:34px } }

        /* 🔑 Hanya tampilkan loader jika <html> TIDAK punya class no-loader */
        html.no-loader #page-loader { display:none !important; }
    </style>
</head>

<body>
    {{-- Loader global --}}
    <x-page-loader variant="a" />

    <div class="layout">
        <x-sidebar :items="$menu" :user="auth()->user()" brand="Stokita" />

        <main class="content">
            @php
                $logo = asset('assets/banner/logo_bupati.png');
                $pageHeading = $heading ?? $title;
            @endphp

            <header class="page-header" aria-label="Judul Halaman">
                <div class="ph-left"><h1 class="ph-title">{{ $pageHeading }}</h1></div>
                @if ($showCrest)
                  <img class="ph-badge" src="{{ $logo }}" alt="Lambang">
                @endif
            </header>

            {{ $slot }}
        </main>
    </div>

    <script>
      document.addEventListener("DOMContentLoaded", () => {
        const btn = document.querySelector("[data-toggle='sidebar']");
        if (btn) {
          btn.addEventListener("click", () => {
            document.querySelector(".layout").classList.toggle("is-collapsed");
          });
        }
      });
    </script>

    @stack('modals')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
