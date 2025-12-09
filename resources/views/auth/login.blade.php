{{-- resources/views/auth/login.blade.php --}}
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

    {{-- 🔒 RULE CEPAT: kalau <html> punya class no-loader, sembunyikan overlay.
         Ditaruh di <head> supaya mencegah “kedip” loader sebelum CSS komponen ter-parse. --}}
    <style>
        html.no-loader #page-loader {
            display: none !important
        }
    </style>
</head>

<body>
    {{-- 🔑 EARLY SCRIPT (HARUS sebelum <x-page-loader>):
         - Default: matikan loader untuk navigasi biasa (type != 'reload')
         - Kalau ada flag 'forceLoaderOnce' (di-set saat submit), BIARKAN loader tampil sekali di halaman ini,
           lalu bersihkan flag (supaya tidak menetap) --}}
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

    {{-- Loader global (akan otomatis disembunyikan jika <html> punya class no-loader) --}}
    <x-page-loader crest="{{ asset('assets/banner/logo_bupati.png') }}"
        brand="{{ asset('assets/logo_stokita_01.png') }}" />



    <main class="page-wrap" role="main">
        <div class="panel" aria-label="Panel login">
            <section class="card">
                <div class="card-left" aria-hidden="true">
                    <img class="banner" src="{{ asset('assets/banner/kantor_bupati.jpg') }}" alt="Kantor Bupati">
                    <img class="emblem" src="{{ asset('assets/banner/logo_bupati.png') }}" alt="Logo Bupati">
                </div>

                <div class="card-right">
                    <div class="form-box" role="form" aria-labelledby="login-title">
                        <h1 id="login-title" class="title">LOGIN</h1>

                        {{-- Pakai id agar gampang ditarget JS --}}
                        <form class="login-form" id="loginForm" method="POST" action="{{ route('login.attempt') }}"
                            novalidate>
                            @csrf

                            <label for="username">Username</label>
                            <div class="field">
                                <input id="username" name="username" type="text"
                                    placeholder="Masukkan Username Anda" autocomplete="username" required />
                            </div>

                            <label for="password">Password</label>
                            <div class="field field-pass">
                                <input id="password" name="password" type="password"
                                    placeholder="Masukkan Password Anda" autocomplete="current-password" required />
                                <button class="toggle-pass" type="button" aria-label="Tampilkan password"
                                    aria-pressed="false">
                                    <i class="bi bi-eye-slash" aria-hidden="true"></i>
                                </button>
                            </div>

                            <button class="btn" type="submit">LOGIN</button>

                            {{-- TAMBAHKAN INI ↓ --}}
                            <div style="text-align:center;margin-top:15px">
                                <a href="{{ route('password.request') }}"
                                    style="color:#3498db;text-decoration:none;font-size:14px">
                                    Lupa Password?
                                </a>
                            </div>

                            @error('username')
                                <p style="color:#c0392b;margin-top:10px">{{ $message }}</p>
                            @enderror
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </main>


    <script src="{{ asset('assets/js/login.js') }}"></script>

    {{-- ✅ BEHAVIOR LOGIN:
        - Jangan tampilkan overlay di halaman login saat submit
        - Hanya set flag agar HALAMAN BERIKUTNYA menampilkan loader SEKALI --}}
    <script>
        (function() {
            var form = document.getElementById('loginForm');
            if (!form) return;

            form.addEventListener('submit', function() {
                try {
                    // 1) Tetap biarkan halaman ini TANPA overlay: jangan mengubah class no-loader, jangan men-tampil-kan overlay
                    // 2) Paksa HALAMAN TUJUAN (setelah redirect) menampilkan loader sekali:
                    sessionStorage.setItem('forceLoaderOnce', '1');

                    // (Opsional UX): disable tombol supaya tidak double submit
                    var btn = form.querySelector('button[type="submit"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.style.opacity = .75;
                        btn.style.cursor = 'not-allowed';
                    }
                } catch (e) {}
            });
        })();
    </script>
</body>

</html>
