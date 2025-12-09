<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Lupa Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
</head>
<body>
    <main class="page-wrap" role="main">
        <div class="panel" aria-label="Panel lupa password">
            <section class="card">
                <div class="card-left" aria-hidden="true">
                    <img class="banner" src="{{ asset('assets/banner/kantor_bupati.jpg') }}" alt="Kantor Bupati">
                    <img class="emblem" src="{{ asset('assets/banner/logo_bupati.png') }}" alt="Logo Bupati">
                </div>

                <div class="card-right">
                    <div class="form-box" role="form">
                        <h1 class="title">LUPA PASSWORD</h1>
                        <p style="margin-bottom: 20px; color: #666; font-size: 14px;">
                            Masukkan email admin Anda dan kami akan mengirimkan link untuk reset password.
                        </p>

                        @if (session('status'))
                            <div style="background:#d4edda;color:#155724;padding:12px;border-radius:8px;margin-bottom:20px;border:1px solid #c3e6cb">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form class="login-form" method="POST" action="{{ route('password.email') }}">
                            @csrf

                            <label for="email">Email Admin</label>
                            <div class="field">
                                <input id="email" name="email" type="email" 
                                    placeholder="Masukkan email admin" 
                                    value="{{ old('email') }}" 
                                    required autofocus />
                            </div>

                            @error('email')
                                <p style="color:#c0392b;margin-top:10px;font-size:14px">{{ $message }}</p>
                            @enderror

                            <button class="btn" type="submit">KIRIM LINK RESET</button>

                            <div style="text-align:center;margin-top:20px">
                                <a href="{{ route('login') }}" style="color:#3498db;text-decoration:none;font-size:14px">
                                    ← Kembali ke Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script src="{{ asset('assets/js/login.js') }}"></script>
</body>
</html>