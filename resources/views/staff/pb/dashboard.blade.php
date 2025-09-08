<x-layouts.app title="'Dashboard • Pengelola Barang'" :menu="$menu">

  <h1>Dashboard</h1>
  <div class="card">
    <p>Selamat datang, {{ auth()->user()->nama }} — Anda masuk sebagai Pengelola Barang.</p>
  </div>

</x-layouts.app>