@props([
    'title' => 'Stokita', // <title> tab & default heading
    'menu' => [], // menu untuk sidebar
    'heading' => null, // override heading (opsional)
    'showCrest' => true, // crest kanan
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

    {{-- CSS sidebar milikmu --}}
    <link rel="stylesheet" href="{{ asset('assets/css/components/sidebar.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/datapengguna-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/staff/admin/riwayat.css') }}">
    {{-- halaman tertentu boleh push CSS sendiri dari slot --}}

    <style>
        body {
            margin: 0;
            background: #EFF0EE;
        }

        /* ====== Sidebar width variable (sinkron dengan state collapsed) ====== */
        .layout {
            --sb-w: 270px;
        }

        .layout.is-collapsed {
            --sb-w: 80px;
        }

        .modal-footer .btn {
            min-height: 42px;
            /* samain tinggi */
        }

        /* ====== Konten: offset & lebar mengikuti sidebar (tanpa overflow) ====== */
        main.content {
    margin-left: var(--sb-w) !important;
    width: calc(100% - var(--sb-w)) !important;
    padding: 20px !important; /* Kembalikan padding */
    transition: margin-left .3s ease, width .3s ease;
    margin-top: 70px; /* Sesuaikan dengan tinggi header */
    min-height: calc(100vh - 70px);
    box-sizing: border-box;
}

        /* Kartu fallback */
        .card {
            background: #fff;
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, .06)
        }

        /* ===== Header Global (flat, nempel kiri/kanan/atas) ===== */
        .page-header {
    display: flex; 
    align-items: center; 
    justify-content: space-between; 
    gap: 12px;
    background: #fff; 
    padding: 14px 18px; 
    margin: 0; 
    border: 0; 
    border-radius: 0;
    border-bottom: 1px solid #e5e7eb; 
    box-shadow: 0 12px 16px -12px #CBCCCB;
    position: fixed; 
    top: 0; 
    left: var(--sb-w);
    width: calc(100% - var(--sb-w));
    z-index: 1000;
    transition: left .3s ease, width .3s ease;
    height: 70px; /* Tambahkan tinggi tetap */
    box-sizing: border-box; /* Pastikan padding termasuk dalam tinggi */
}

        .ph-left {
            display: flex;
            align-items: center;
            gap: 10px
        }

        .ph-title {
            margin: 0;
            font-weight: 600;
            letter-spacing: -.01em;
            line-height: 1.2;
            font-size: clamp(18px, 2.2vw, 26px);
            color: #111827;
        }

        .ph-badge {
            width: 40px;
            height: 40px;
            /* Tambahkan tinggi yang sama dengan lebar agar tidak gepeng */
            object-fit: contain;
            /* Efek tebal: tambahkan shadow dan stroke */
            filter: drop-shadow(0px 1px 1px rgba(0, 0, 0, 0.5));
            /* Animasi logo bupati dengan jeda 5 detik */
            animation: logoAnimation 5s infinite;
        }

        /* Untuk browser yang tidak support filter, gunakan alternatif */
        @supports not (filter: drop-shadow(0px 0px 0px)) {
            .ph-badge {
                border: 1.5px solid rgba(0, 0, 0, 0.3);
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            }
        }

        @media (max-width:640px) {
            .ph-badge {
                width: 34px
            }
        }

        /* 🔑 Hanya tampilkan loader jika <html> TIDAK punya class no-loader */
        html.no-loader #page-loader {
            display: none !important;
        }

        /* Animasi untuk logo bupati */
        /* Animasi untuk logo bupati */
        @keyframes logoAnimation {

            0%,
            62.5% {
                transform: translateY(0) rotateY(0) scale(1);
            }

            67.5% {
                transform: translateY(-4px) rotateY(0) scale(1.05);
                /* Sedikit membesar */
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

        @media (max-width:640px) {
            .ph-badge {
                width: 34px;
                height: 34px;
                /* Jaga proporsi di mobile */
            }
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

    {{-- Loader global (akan auto-hidden jika <html> punya class no-loader) --}}
    <x-page-loader variant="a" />

    <div class="layout">
        {{-- Sidebar (tetap) --}}
        <x-sidebar :items="$menu" :user="auth()->user()" brand="Stokita" />

        {{-- ===== Area konten ===== --}}
        <main class="content">
            @php
                $logo = asset('assets/banner/logo_bupati.png'); // crest kanan
                $pageHeading = $heading ?? $title; // judul header
            @endphp

            {{-- Header nempel kiri/kanan/atas + shadow bawah --}}
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
    </div>

    {{-- Toggle collapse sidebar --}}
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
