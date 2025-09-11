<x-layouts.app title="Dashboard Admin" :menu="$menu">
  
  <h1>Dashboard</h1>
  <div class="card">
    Selamat datang, <b>{{ auth()->user()->nama }}</b> — Anda masuk sebagai <b>Admin</b>.
  </div>
  
</x-layouts.app>