<x-layouts.app title="'Dashboard • Penanggung Jawab'" :menu="$menu">
  <header class="page"><h1>Dashboard</h1></header>
  <section class="card">
    <p>Selamat datang, <strong>{{ auth()->user()->nama }}</strong> — Anda masuk sebagai <b>Penanggung Jawab</b>.</p>
  </section>
</x-layouts.app>
