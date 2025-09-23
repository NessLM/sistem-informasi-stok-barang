@props([
    'title' => 'Stokita', // <title> tab & judul default
    'menu' => [], // menu untuk sidebar
    'heading' => null, // override judul (opsional)
    'showCrest' => true, // tampilkan lambang kanan
])

<!doctype html>
<html lang="id" class="font-sans">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title }}</title>

    {{-- 🔧 PRELOAD STATE COLLAPSE SIDEBAR (Mencegah Flash)
         - Baca localStorage sb-collapsed seawal mungkin (di <head>)
         - Jika '1' → tambahkan class 'sb-collapsed' ke <html>
         - CSS dan offset konten akan langsung menggunakan state ini sebelum render --}}
    <script>
        (function() {
            try {
                if (localStorage.getItem('sb-collapsed') === '1') {
                    document.documentElement.classList.add('sb-collapsed');
                }
            } catch (e) {
                /* Abaikan error */
            }
        })();
    </script>

    {{-- Font & Ikon --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- CSS Kustom --}}
    <link rel="stylesheet" href="{{ asset('assets/css/components/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/data_pengguna.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/riwayat.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/data_keseluruhan.css') }}">

    <style>
        /* ===== VARIABEL & DASAR ===== */
        :root {
            --header-height: 70px;
            --sidebar-width: 270px;
            --sidebar-collapsed-width: 80px;
            --transition-duration: .3s;
            --primary-bg: #EFF0EE;
            --card-bg: #fff;
            --text-color: #111827;
            --border-color: #e5e7eb;
            --shadow-color: rgba(0, 0, 0, .06);
            --header-shadow: 0 12px 16px -12px #CBCCCB;
        }

        body {
            margin: 0;
            background: var(--primary-bg);
            font-family: 'Poppins', sans-serif;
        }

        /* ===== LAYOUT UTAMA ===== */
        .layout {
            --sb-w: var(--sidebar-width);
        }

        /* State collapsed via JS */
        .layout.is-collapsed {
            --sb-w: var(--sidebar-collapsed-width);
        }

        /* Anti-flash: jika <html> memiliki class .sb-collapsed */
        html.sb-collapsed .layout {
            --sb-w: var(--sidebar-collapsed-width);
        }

        /* ===== HEADER RESPONSIF ===== */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: var(--card-bg);
            padding: 14px 18px;
            margin: 0;
            border: 0;
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--header-shadow);
            position: fixed;
            top: 0;
            left: var(--sb-w);
            width: calc(100% - var(--sb-w));
            z-index: 1000;
            transition: left var(--transition-duration) ease, width var(--transition-duration) ease;
            height: var(--header-height);
            box-sizing: border-box;
        }

        .ph-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 0;
            /* Memungkinkan text-overflow */
        }

        .ph-title {
            margin: 0;
            font-weight: 600;
            letter-spacing: -.01em;
            line-height: 1.2;
            font-size: clamp(18px, 2.2vw, 26px);
            color: var(--text-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ph-badge {
            width: 40px;
            height: 40px;
            object-fit: contain;
            filter: drop-shadow(0px 1px 1px rgba(0, 0, 0, 0.5));
            animation: logoAnimation 5s infinite;
            flex-shrink: 0;
            /* Mencegah penyusutan pada layar kecil */
        }

        /* Fallback untuk browser yang tidak mendukung filter */
        @supports not (filter: drop-shadow(0px 0px 0px)) {
            .ph-badge {
                border: 1.5px solid rgba(0, 0, 0, 0.3);
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            }
        }

        /* ===== KONTEN UTAMA ===== */
        main.content {
            margin-left: var(--sb-w) !important;
            width: calc(100% - var(--sb-w)) !important;
            padding: 20px !important;
            transition: margin-left var(--transition-duration) ease, width var(--transition-duration) ease;
            margin-top: var(--header-height);
            min-height: calc(100vh - var(--header-height));
            box-sizing: border-box;
        }

        /* Kartu dasar */
        .card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 10px 24px var(--shadow-color);
        }

        /* ===== MEDIA QUERIES UNTUK RESPONSIVITAS ===== */

        /* Tablet (768px ke bawah) */
        @media (max-width: 768px) {
            .page-header {
                padding: 12px 15px;
            }

            .ph-title {
                font-size: clamp(16px, 4vw, 22px);
            }

            .ph-badge {
                width: 35px;
                height: 35px;
            }
        }

        /* Mobile (640px ke bawah) */
        @media (max-width: 640px) {
            .page-header {
                padding: 10px 12px;
                flex-wrap: wrap;
            }

            .ph-left {
                order: 1;
                width: 100%;
                justify-content: center;
                margin-bottom: 5px;
            }

            .ph-title {
                font-size: 18px;
                text-align: center;
            }

            .ph-badge {
                width: 30px;
                height: 30px;
                order: 2;
                margin-left: auto;
            }

            /* Pada mobile sangat kecil, sembunyikan lambang jika perlu */
            @media (max-width: 400px) {
                .ph-badge {
                    display: none;
                }
            }
        }

        /* Mobile sangat kecil (360px ke bawah) */
        @media (max-width: 360px) {
            .page-header {
                padding: 8px 10px;
            }

            .ph-title {
                font-size: 16px;
            }
        }

        /* ===== ANIMASI LOGO ===== */
        @keyframes logoAnimation {

            0%,
            62.5% {
                transform: translateY(0) rotateY(0) scale(1);
            }

            67.5% {
                transform: translateY(-4px) rotateY(0) scale(1.05);
            }

            77.5% {
                transform: translateY(-4px) rotateY(90deg) scale(1.05);
            }

            82.5% {
                transform: translateY(-4px) rotateY(180deg) scale(1.05);
            }

            87.5% {
                transform: translateY(-4px) rotateY(270deg) scale(1.05);
            }

            92.5% {
                transform: translateY(-4px) rotateY(360deg) scale(1.05);
            }

            100% {
                transform: translateY(0) rotateY(360deg) scale(1);
            }
        }

        /* ===== LOADER ===== */
        /* Sembunyikan loader jika <html> memiliki class no-loader */
        html.no-loader #page-loader {
            display: none !important;
        }

        /* ===== TOMBOL MODAL ===== */
        .modal-footer .btn {
            min-height: 42px;
            /* Menyamakan tinggi tombol */
        }

        /* ✅ Safety override header & konten saat mobile */
        @media (max-width: 992px) {
            .layout { --sb-w: 0px !important; }
            .page-header { left: 0 !important; width: 100% !important; }
            main.content { margin-left: 0 !important; width: 100% !important; }
        }

                /* ===== Hamburger di header (copy gaya brand-action) ===== */
        .ph-hamburger{display:none}
        @media (max-width:992px){
        .ph-hamburger{display:grid;width:36px;height:36px;place-items:center;border:0;background:transparent;border-radius:10px;margin-right:4px}
        .page-header{height:56px;padding:10px 14px}
        main.content{margin-top:56px !important}
        }


    </style>
</head>

<body>
    {{-- 🔑 Early script: tampilkan loader hanya untuk RELOAD.
         Kecuali ada flag 'forceLoaderOnce' (mis. dari LOGIN/LOGOUT) yang memaksa sekali tampil. --}}
    <script>
        (function() {
            try {
                var nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
                var type = nav ? nav.type : (performance.navigation && performance.navigation.type === 1 ? 'reload' :
                    'navigate');
                var force = sessionStorage.getItem('forceLoaderOnce') === '1';

                if (type !== 'reload' && !force) {
                    document.documentElement.classList.add('no-loader');
                }
                if (force) sessionStorage.removeItem('forceLoaderOnce');
            } catch (e) {}
        })();
    </script>

    {{-- Loader global --}}
    <x-page-loader variant="a" />

    <div class="layout">
        {{-- Sidebar --}}
        <x-sidebar :items="$menu" :user="auth()->user()" brand="Stokita" />

        {{-- ===== AREA KONTEN ===== --}}
        <main class="content">
            @php
                $logo = asset('assets/banner/logo_bupati.png'); // lambang kanan
                $pageHeading = $heading ?? $title; // judul header
            @endphp

            {{-- Header responsif --}}
            <header class="page-header" aria-label="Judul Halaman">
                <div class="ph-left">
                  <!-- tombol pemanggil: style sama dengan sidebar -->
                  <button class="brand-action ph-hamburger" type="button" aria-label="Menu" data-toggle="sidebar">
                    <i class="bi bi-list"></i>
                  </button>
                  <h1 class="ph-title">{{ $pageHeading }}</h1>
                </div>
                @if ($showCrest)
                  <img class="ph-badge" src="{{ $logo }}" alt="Lambang">
                @endif
            </header>
              
              

            {{-- Slot konten halaman --}}
            {{ $slot }}
        </main>
    </div>

    {{-- Toggle collapse sidebar
       - Simpan state ke localStorage 'sb-collapsed'
       - Pasang/lepaskan class di <html> (anti flash) dan .layout (runtime) --}}
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const btn = document.querySelector("[data-toggle='sidebar']");
            const layout = document.querySelector(".layout");

            // Sinkronkan class .layout.is-collapsed dengan class preload <html>.sb-collapsed
            if (document.documentElement.classList.contains('sb-collapsed')) {
                layout.classList.add('is-collapsed');
            }

            if (btn) {
                btn.addEventListener("click", () => {
                    const willCollapse = !layout.classList.contains("is-collapsed");

                    layout.classList.toggle("is-collapsed", willCollapse);

                    // Persist ke localStorage
                    try { localStorage.setItem('sb-collapsed', willCollapse ? '1' : '0'); } catch (e) {}

                    // Juga toggle class di <html> agar page berikutnya langsung benar tanpa flash
                    document.documentElement.classList.toggle('sb-collapsed', willCollapse);
                });
            }
        });
    </script>

    {{-- ✅ LOGOUT HOOK: paksa loader muncul SEKALI di halaman tujuan (login)
        saat user logout. Tidak menampilkan overlay di halaman saat ini. --}}
    <script>
        (function() {
            const flagOnce = () => {
                try {
                    sessionStorage.setItem('forceLoaderOnce', '1');
                } catch (e) {}
            };

            // 1) Form POST /logout (paling umum di Laravel)
            document.addEventListener('submit', function(ev) {
                const f = ev.target;
                if (!f || !f.action) return;
                if (/\/logout(\?|$)/i.test(f.action)) {
                    flagOnce();
                    // tidak perlu show overlay sekarang — biarkan login page yang tampilkan
                }
            }, true);

            // 2) Kalau pakai <a> GET atau tombol custom, kasih atribut data-logout
            document.addEventListener('click', function(ev) {
                const el = ev.target.closest('a[data-logout], button[data-logout]');
                if (el) {
                    flagOnce();
                }
            }, true);
        })();
    </script>

    {{-- Tempat untuk modal html di-append --}}
    @stack('modals')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Tempat untuk script js tambahan per-halaman --}}
    @stack('scripts')
</body>

</html>
