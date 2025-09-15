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
</head>

<body>
    {{-- Loader tidak memengaruhi layout konten --}}
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

                        <form class="login-form" method="POST" action="{{ route('login.attempt') }}" novalidate>
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
</body>

</html>
