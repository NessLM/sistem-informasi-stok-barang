<x-layouts.app title="'Dashboard • Pengelola Barang'" :menu="$menu">

  <h1>Dashboard</h1>
  <div class="card">
    Selamat datang, <b>{{ auth()->user()->nama }}</b> — Anda masuk sebagai <b>Pengelola Barang.</b>
  </div>

</x-layouts.app>