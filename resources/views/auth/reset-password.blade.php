<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
</head>
<body>
    <main class="page-wrap" role="main">
        <div class="panel" aria-label="Panel reset password">
            <section class="card">
                <div class="card-left" aria-hidden="true">
                    <img class="banner" src="{{ asset('assets/banner/kantor_bupati.jpg') }}" alt="Kantor Bupati">
                    <img class="emblem" src="{{ asset('assets/banner/logo_bupati.png') }}" alt="Logo Bupati">
                </div>

                <div class="card-right">
                    <div class="form-box" role="form">
                        <h1 class="title">RESET PASSWORD</h1>

                        <form class="login-form" method="POST" action="{{ route('password.update') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <input type="hidden" name="email" value="{{ $email }}">

                            <label for="password">Password Baru</label>
                            <div class="field field-pass">
                                <input id="password" name="password" type="password" 
                                    placeholder="Masukkan password baru" 
                                    required />
                                <button class="toggle-pass" type="button" aria-label="Tampilkan password">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                            <small style="color:#666;font-size:12px">Minimal 8 karakter, mengandung huruf dan angka</small>

                            <label for="password_confirmation" style="margin-top:15px">Konfirmasi Password</label>
                            <div class="field field-pass">
                                <input id="password_confirmation" name="password_confirmation" type="password" 
                                    placeholder="Ketik ulang password baru" 
                                    required />
                                <button class="toggle-pass" type="button" aria-label="Tampilkan password">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>

                            @error('password')
                                <p style="color:#c0392b;margin-top:10px">{{ $message }}</p>
                            @enderror
                            @error('email')
                                <p style="color:#c0392b;margin-top:10px">{{ $message }}</p>
                            @enderror

                            <button class="btn" type="submit">RESET PASSWORD</button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script src="{{ asset('assets/js/login.js') }}"></script>
</body>
</html>