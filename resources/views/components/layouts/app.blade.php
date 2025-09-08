@props([
  'title' => 'Stokita',
  'menu'  => [],  // array menu dari controller
])

<!doctype html>
<html lang="id" class="font-sans">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{ $title }}</title>

  {{-- Font global --}}
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">

  {{-- Komponen lain boleh nge-push asset ke <head> --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/css/components/sidebar.css') }}">

  {{-- (opsional) CSS global app-mu --}}
  {{-- <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}"> --}}

  <style>
    /* fallback kecil untuk kartu konten yang selamat datang itu */
    .card{
      background:#fff;
      border-radius:14px;
      padding:18px;
      box-shadow:0 10px 24px rgba(0,0,0,.06)
    }
  </style>
</head>
<body>
  {{-- PAGE LOADER: aktif di semua halaman dashboard --}}
  {{-- Logo default sudah ada di komponen; kalau mau, bisa override di sini --}}
  <x-page-loader variant="a" />

  <div class="layout">
    {{-- Sidebar reusable --}}
    <x-sidebar :items="$menu" :user="auth()->user()" brand="Stokita" />
    {{-- Area konten --}}
    <main class="content">
      {{ $slot }}
    </main>

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
