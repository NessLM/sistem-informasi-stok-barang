@props([
  'crest'   => asset('assets/banner/logo_bupati.png'),
  'brand'   => asset('assets/banner/logo_stokita_01.png'),
  'variant' => 'a',
])

<div id="page-loader" data-variant="{{ $variant }}" aria-live="polite">
  <div class="loader-card"
       role="progressbar"
       aria-valuemin="0"
       aria-valuemax="100"
       aria-valuenow="0"
       aria-label="Memuat halaman">

    <img class="crest"     src="{{ $crest }}" alt="" aria-hidden="true" loading="eager" decoding="async" fetchpriority="high">
    <img class="brand-img" src="{{ $brand }}" alt="" aria-hidden="true" loading="eager" decoding="async" fetchpriority="high">

    <div class="progress" aria-hidden="true">
      <div class="progress-bar" style="width:0%"></div>
    </div>
  </div>
</div>

@once
  <style>
    #page-loader{
      --loader-bg:#ffffff; --bar-color:#184c63; --track-color:#dfdfdf;
      --gap:20px; --crest-w:84px; --brand-w:150px; --bar-w:540px; --bar-h:8px; --bar-radius:999px;
      --hide-delay:250ms;
    }

    /* Hormati flag global: jika no-loader → jangan tampilkan overlay */
    html.no-loader #page-loader{ display:none !important; }

    #page-loader{
      position:fixed; inset:0; z-index:9999;
      display:grid; place-items:center;
      background:var(--loader-bg);
      opacity:1; visibility:visible;
      transition:opacity .35s ease, visibility .35s ease;
    }
    #page-loader.is-hidden{ opacity:0; visibility:hidden; }

    .loader-card{ display:flex; flex-direction:column; align-items:center; gap:var(--gap); transform:translateY(-18px); }
    .crest{ width:var(--crest-w); height:auto; object-fit:contain; }
    .brand-img{ width:var(--brand-w); height:auto; object-fit:contain; }

    .progress{ width:min(var(--bar-w),78vw); height:var(--bar-h); border-radius:var(--bar-radius); background:var(--track-color); overflow:hidden; }
    .progress-bar{ height:100%; width:0%; background:var(--bar-color); border-radius:inherit; transition:width .18s ease; }

    #page-loader[data-variant="a"]{ --bar-color:#184c63; --track-color:#dfdfdf; }
    #page-loader[data-variant="b"]{ --bar-color:#0a4561; --track-color:#d7dde3; }

    @media (prefers-reduced-motion: reduce){
      #page-loader, #page-loader .progress-bar{ transition:none; }
    }

    .inert-on-body > *:not(#page-loader){ pointer-events:none; }
  </style>

  <script>
    class PageLoader {
      constructor(rootId='page-loader', opts = {}){
        this.root = document.getElementById(rootId);
        this.card = this.root?.querySelector('.loader-card') ?? null;
        this.bar  = this.root?.querySelector('.progress-bar') ?? null;
        this.cfg = Object.assign({
          ease:0.15, startAt:10, simStart:30, simStep:5, simMax:90,
          finishDelay:250, simInterval:200, maxAliveMs:12000
        }, opts);
        this.value=0; this.rafId=null; this.simId=null; this.killTimer=null;
      }
      set(v){ if(!this.bar||!this.card) return; this.value=Math.max(0,Math.min(100,v)); this.bar.style.width=this.value+'%'; this.card.setAttribute('aria-valuenow', String(this.value)); }
      tweenTo(target){
        if(!this.bar) return;
        target = Math.max(this.value, Math.min(100, target));
        cancelAnimationFrame(this.rafId);
        const step = () => {
          const d = (target - this.value) * this.cfg.ease;
          if (Math.abs(d) < 0.5){ this.set(target); return; }
          this.set(this.value + d);
          this.rafId = requestAnimationFrame(step);
        };
        step();
      }
      start(){ if(!this.root) return; document.body.classList.add('inert-on-body'); this.tweenTo(this.cfg.startAt); this.killTimer=setTimeout(()=>this.finish(), this.cfg.maxAliveMs); }
      simulate(){
        if(!this.root) return;
        let x=this.cfg.simStart;
        this.simId=setInterval(()=>{ x=Math.min(x+this.cfg.simStep, this.cfg.simMax); this.tweenTo(x); if(x>=this.cfg.simMax){ clearInterval(this.simId); this.simId=null; } }, this.cfg.simInterval);
      }
      finish(){
        if(!this.root) return;
        if(this.simId){ clearInterval(this.simId); this.simId=null; }
        if(this.killTimer){ clearTimeout(this.killTimer); this.killTimer=null; }
        this.tweenTo(100);
        setTimeout(()=>{ this.root.classList.add('is-hidden'); document.body.classList.remove('inert-on-body'); }, this.cfg.finishDelay);
      }
    }

    // 🔑 Inisialisasi HANYA jika tidak dimatikan oleh layout (no-loader)
    document.addEventListener('DOMContentLoaded', () => {
-load      window.pageLoader = new PageLoader();
      window.pageLoader.start();
      window.pageLoader.simulate(); // hapus jika tak ingin simulasi otomatis
    });
    window.addEventListener('load', () => window.pageLoader?.finish());
    </script>
  @endonce
  