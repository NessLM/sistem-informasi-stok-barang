{{-- resources/views/components/page-loader.blade.php
   =========================================================================
   PAGE LOADER (overlay layar penuh)
   Cara pakai:
     <x-page-loader
        crest="{{ asset('assets/banner/logo_bupati.png') }}"
        brand="{{ asset('assets/banner/logo_stokita_01.png') }}"
        variant="a"
     />
   Semua warna/ukuran bisa dioverride via CSS variables di elemen root:
     <x-page-loader style="--bar-color:#0f0; --brand-w:220px" />
   ========================================================================= --}}

   @props([
    // Path aset gambar
    'crest'   => asset('assets/banner/logo_bupati.png'),
    'brand'   => asset('assets/banner/logo_stokita_01.png'),
  
    // Skema warna (opsional): 'a' atau 'b'
    'variant' => 'a',
  ])
  
  <div id="page-loader" data-variant="{{ $variant }}" aria-live="polite">
    <div class="loader-card"
         role="progressbar"
         aria-valuemin="0"
         aria-valuemax="100"
         aria-valuenow="0"
         aria-label="Memuat halaman">
  
      {{-- Gambar dekoratif (tak dibacakan screen reader) --}}
      <img class="crest"
           src="{{ $crest }}"
           alt=""
           aria-hidden="true"
           loading="eager"
           decoding="async"
           fetchpriority="high">
  
      <img class="brand-img"
           src="{{ $brand }}"
           alt=""
           aria-hidden="true"
           loading="eager"
           decoding="async"
           fetchpriority="high">
  
      <div class="progress" aria-hidden="true">
        <div class="progress-bar" style="width:0%"></div>
      </div>
    </div>
  </div>
  
  @once
    <style>
    /* ========================================================================
       PARAMETER UTAMA (bisa dioverride di attribute style elemen #page-loader)
       ======================================================================== */
    #page-loader{
      /* WARNA */
      --loader-bg: #ffffff;      /* latar overlay */
      --bar-color: #184c63;      /* warna bar terisi */
      --track-color: #dfdfdf;    /* warna track */
  
      /* UKURAN & SPASI */
      --gap: 20px;               /* jarak antar elemen */
      --crest-w: 84px;           /* lebar crest */
      --brand-w: 150px;          /* lebar brand */
      --bar-w: 540px;            /* lebar progress */
      --bar-h: 8px;              /* tinggi progress */
      --bar-radius: 999px;       /* sudut progress */
  
      /* ANIMASI */
      --hide-delay: 250ms;       /* jeda saat menyembunyikan overlay */
    }
  
    /* ===== OVERLAY ===== */
    #page-loader{
      position: fixed; inset: 0; z-index: 9999;
      display: grid; place-items: center;
      background: var(--loader-bg);
      opacity: 1; visibility: visible;
      transition: opacity .35s ease, visibility .35s ease;
    }
    #page-loader.is-hidden{ opacity: 0; visibility: hidden; }
  
    /* Konten loader */
    #page-loader .loader-card{
      display: flex; flex-direction: column; align-items: center;
      gap: var(--gap);
      transform: translateY(-18px);
    }
  
    /* Gambar */
    #page-loader .crest{ width: var(--crest-w); height: auto; object-fit: contain; }
    #page-loader .brand-img{ width: var(--brand-w); height: auto; object-fit: contain; }
  
    /* Progress */
    #page-loader .progress{
      width: min(var(--bar-w), 78vw);
      height: var(--bar-h);
      border-radius: var(--bar-radius);
      background: var(--track-color);
      overflow: hidden;
    }
    #page-loader .progress-bar{
      height: 100%; width: 0%;
      background: var(--bar-color);
      border-radius: inherit;
      transition: width .18s ease;
    }
  
    /* Varian warna (opsional) */
    #page-loader[data-variant="a"]{
      --bar-color: #184c63; --track-color: #dfdfdf;
    }
    #page-loader[data-variant="b"]{
      --bar-color: #0a4561; --track-color: #d7dde3;
    }
  
    /* Reduced motion: minimalkan animasi */
    @media (prefers-reduced-motion: reduce) {
      #page-loader{ transition: none; }
      #page-loader .progress-bar{ transition: none; }
    }
  
    /* Saat overlay aktif, nonaktifkan pointer di konten belakang */
    .inert-on-body > *:not(#page-loader){ pointer-events: none; }
    </style>
  
    <script>
    /* =========================================================================
       PAGE LOADER CONTROLLER
       API:
         window.pageLoader.tweenTo(40)  // animasi ke 40%
         window.pageLoader.finish()     // penuhkan bar & sembunyikan overlay
       ========================================================================= */
    class PageLoader {
      constructor(rootId='page-loader', opts = {}) {
        this.root = document.getElementById(rootId);
        this.card = this.root?.querySelector('.loader-card') ?? null;
        this.bar  = this.root?.querySelector('.progress-bar') ?? null;
  
        this.cfg = Object.assign({
          ease: 0.15,       // easing frame-to-frame (0..1)
          startAt: 10,      // mulai di 10% agar terlihat hidup
          simStart: 30,     // simulasi awal (optional)
          simStep: 5,       // kenaikan tiap interval
          simMax: 90,       // berhenti sebelum penuh
          finishDelay: 250, // sinkron dgn CSS --hide-delay
          simInterval: 200, // ms
          maxAliveMs: 12000 // fallback: auto-finish max 12s
        }, opts);
  
        this.value = 0;
        this.rafId = null;
        this.simId = null;
        this.killTimer = null;
      }
  
      set(v){
        if(!this.bar || !this.card) return;
        this.value = Math.max(0, Math.min(100, v));
        this.bar.style.width = this.value + '%';
        this.card.setAttribute('aria-valuenow', String(this.value));
      }
  
      tweenTo(target){
        if(!this.bar) return;
        target = Math.max(this.value, Math.min(100, target)); // tak mundur
        cancelAnimationFrame(this.rafId);
        const step = () => {
          const d = (target - this.value) * this.cfg.ease;
          if (Math.abs(d) < 0.5) { this.set(target); return; }
          this.set(this.value + d);
          this.rafId = requestAnimationFrame(step);
        };
        step();
      }
  
      start(){
        if(!this.root) return;
        document.body.classList.add('inert-on-body');
        this.tweenTo(this.cfg.startAt);
        // Fallback: kalau load tak pernah datang
        this.killTimer = setTimeout(() => this.finish(), this.cfg.maxAliveMs);
      }
  
      simulate(){
        if(!this.root) return;
        let x = this.cfg.simStart;
        this.simId = setInterval(() => {
          x = Math.min(x + this.cfg.simStep, this.cfg.simMax);
          this.tweenTo(x);
          if (x >= this.cfg.simMax) { clearInterval(this.simId); this.simId = null; }
        }, this.cfg.simInterval);
      }
  
      finish(){
        if(!this.root) return;
        if (this.simId) { clearInterval(this.simId); this.simId = null; }
        if (this.killTimer) { clearTimeout(this.killTimer); this.killTimer = null; }
        this.tweenTo(100);
        setTimeout(() => {
          this.root.classList.add('is-hidden');
          document.body.classList.remove('inert-on-body');
        }, this.cfg.finishDelay);
      }
    }
  
    // Inisialisasi
    document.addEventListener('DOMContentLoaded', () => {
  const noLoader = document.body.hasAttribute('data-no-loader');
  if (noLoader) {
    document.getElementById('page-loader')?.classList.add('is-hidden');
    return;
  }

  window.pageLoader = new PageLoader();
  window.pageLoader.start();
  window.pageLoader.simulate();
});

    window.addEventListener('load', () => window.pageLoader?.finish());
    </script>
  @endonce
  