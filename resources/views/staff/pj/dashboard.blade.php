<x-layouts.app title="'Dashboard • Penanggung Jawab'" :menu="$menu">
  <h1>Dashboard</h1>
  <div class="card">
    Selamat datang, <strong>{{ auth()->user()->nama }}</strong> — Anda masuk sebagai <b>Penanggung Jawab</b>
  </div>
</x-layouts.app>
