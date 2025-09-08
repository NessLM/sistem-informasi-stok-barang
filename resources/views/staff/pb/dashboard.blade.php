<x-layouts.app title="'Dashboard • Pembantu Bendahara'" :menu="$menu">
    <header class="page"><h1>Dashboard</h1></header>
    <section class="card">
      <p>Selamat datang, {{ auth()->user()->nama }} — Anda masuk sebagai Pembantu Bendahara.</p>
    </section>
  </x-layouts.app>
  