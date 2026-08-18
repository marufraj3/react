@extends('frontEnd.layouts.master')
@section('title','Hot Deals')



@push('css')
<style>
/* ========================================
   HOT DEALS PAGE
   ======================================== */
.hotdeals-page{padding:0 0 50px;background:var(--paper,#fff)}
.hotdeals-inner{max-width:1200px;margin:0 auto;padding:0 20px}

/* ---- Hero Banner ---- */
.hd-hero{background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);padding:36px 24px;border-radius:18px;margin:24px 0 28px;text-align:center;position:relative;overflow:hidden}
.hd-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 50%,rgba(229,57,53,.15) 0%,transparent 50%),radial-gradient(circle at 80% 50%,rgba(255,187,0,.1) 0%,transparent 50%);pointer-events:none}
.hd-hero h1{font-size:clamp(1.4rem,4vw,2.2rem);font-weight:900;color:#fff;margin:0 0 6px;letter-spacing:-.03em;position:relative;z-index:1}
.hd-hero h1 i{color:#fbbf24;margin-right:4px}
.hd-hero p{margin:0 0 22px;font-size:.92rem;color:rgba(255,255,255,.7);position:relative;z-index:1}

/* ---- Countdown ---- */
.hd-countdown{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;position:relative;z-index:1}
.cd-box{min-width:72px;text-align:center;padding:10px 12px;border-radius:14px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);backdrop-filter:blur(6px);color:#fff}
.cd-box strong{display:block;font-size:1.5rem;font-weight:900;letter-spacing:.5px;line-height:1}
.cd-box small{font-size:.65rem;font-weight:700;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.06em;margin-top:2px;display:block}
.cd-sep{font-size:1.4rem;font-weight:900;color:rgba(255,255,255,.35);display:flex;align-items:center}

/* ---- Deal Strip ---- */
.deal-strip{display:flex;align-items:center;justify-content:center;gap:14px;padding:14px 18px;background:linear-gradient(135deg,#fff8f0,#fff1e6);border:2px dashed #f97316;border-radius:14px;margin-bottom:28px;flex-wrap:wrap}
.deal-strip .old-price{text-decoration:line-through;color:#999;font-size:.92rem;font-weight:600}
.deal-strip .new-price{color:var(--accent,#e53935);font-size:1.25rem;font-weight:900}
.deal-strip .save-badge{background:#16a34a;color:#fff;font-size:.78rem;font-weight:750;padding:5px 12px;border-radius:99px}

/* ---- Product Grid ---- */
.hd-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}

/* ---- Pagination ---- */
.hd-pagination{display:flex;align-items:center;justify-content:center;gap:5px;margin-top:36px;flex-wrap:wrap}
.pg-btn{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 4px;border:1px solid var(--line,#e8e8e8);border-radius:10px;background:var(--paper,#fff);font-size:.82rem;font-weight:700;color:var(--ink,#1a1a1a);text-decoration:none;transition:all .2s ease;cursor:pointer}
.pg-btn:hover{border-color:var(--accent,#e53935);color:var(--accent,#e53935)}
.pg-btn.active{background:var(--ink,#1a1a1a);color:#fff;border-color:var(--ink,#1a1a1a)}
.pg-btn.disabled{opacity:.35;pointer-events:none}
.pg-dots{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;font-size:.8rem;color:var(--muted,#888);font-weight:600}

/* ========================================
   RESPONSIVE
   ======================================== */
@media(max-width:991px){
    .hd-grid{grid-template-columns:repeat(3,1fr)}
    .hd-hero{padding:28px 18px;margin:18px 0 22px}
}

@media(max-width:767px){
    .hotdeals-page{padding-bottom:40px}
    .hotdeals-inner{padding:0 14px}
    .hd-hero{padding:22px 14px;border-radius:14px}
    .hd-hero h1{font-size:1.2rem}
    .cd-box{min-width:60px;padding:8px 10px}
    .cd-box strong{font-size:1.25rem}
    .hd-countdown{gap:6px}
    .cd-sep{font-size:1.1rem}
    .deal-strip{padding:10px 14px;gap:10px;margin-bottom:20px}
    .deal-strip .new-price{font-size:1.05rem}
    .hd-grid{grid-template-columns:repeat(2,1fr);gap:10px}
    .hd-pagination{margin-top:28px;gap:4px}
    .pg-btn{min-width:34px;height:34px;font-size:.76rem;border-radius:8px}
    .pg-dots{width:34px;height:34px}
}
</style>
@endpush

@section('content')
<div class="hotdeals-page">
    <div class="hotdeals-inner">

        {{-- Hero Banner + Countdown --}}
        <div class="hd-hero">
            <h1><i class="fa-solid fa-fire"></i> Hot Deals</h1>
            <p>সীমিত সময়ের অফার — স্টক শেষ হওয়ার আগে অর্ডার করুন</p>
            <div class="hd-countdown">
                <div class="cd-box"><span class="cd-hours">00</span><small>ঘণ্টা</small></div>
                <span class="cd-sep">:</span>
                <div class="cd-box"><span class="cd-mins">00</span><small>মিনিট</small></div>
                <span class="cd-sep">:</span>
                <div class="cd-box"><span class="cd-secs">00</span><small>সেকেন্ড</small></div>
            </div>
        </div>

        {{-- Deal Strip --}}
        @if($products->count() > 0 && $products->first()->old_price)
        @php
            $first = $products->first();
            $saveAmt = $first->old_price - $first->new_price;
        @endphp
        <div class="deal-strip">
            <span class="old-price">৳{{ number_format($first->old_price) }}</span>
            <span class="new-price">এখন মাত্র ৳{{ number_format($first->new_price) }}</span>
            <span class="save-badge">সেভ ৳{{ number_format($saveAmt) }}</span>
        </div>
        @endif

        {{-- Products --}}
        @if($products->count() > 0)
        <div class="hd-grid">
            @foreach($products as $product)
                @include('frontEnd.layouts.partials.product-card', ['product' => $product])
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="hd-pagination">
            @if($products->onFirstPage())
            <span class="pg-btn disabled"><i class="fa-solid fa-chevron-left" style="font-size:12px"></i></span>
            @else
            <a class="pg-btn" href="{{ $products->previousPageUrl() }}"><i class="fa-solid fa-chevron-left" style="font-size:12px"></i></a>
            @endif

            @for($p = 1; $p <= $products->lastPage(); $p++)
                @if($p == $products->currentPage())
                <span class="pg-btn active">{{ $p }}</span>
                @elseif($p == 1 || $p == $products->lastPage() || (abs($p - $products->currentPage()) <= 2))
                <a class="pg-btn" href="{{ $products->url($p) }}">{{ $p }}</a>
                @elseif(abs($p - $products->currentPage()) == 3)
                <span class="pg-dots">...</span>
                @endif
            @endfor

            @if($products->hasMorePages())
            <a class="pg-btn" href="{{ $products->nextPageUrl() }}"><i class="fa-solid fa-chevron-right" style="font-size:12px"></i></a>
            @else
            <span class="pg-btn disabled"><i class="fa-solid fa-chevron-right" style="font-size:12px"></i></span>
            @endif
        </div>
        @endif

        @else
        <div style="text-align:center;padding:60px 20px;color:var(--muted,#888)">
            <i class="fa-solid fa-fire" style="font-size:48px;opacity:.15;display:block;margin-bottom:14px"></i>
            <h3 style="font-size:1.1rem;font-weight:800;margin:0 0 6px;color:var(--ink,#1a1a1a)">এখন কোনো ডিল নেই</h3>
            <p style="font-size:.88rem;margin:0">শীঘ্রই নতুন ডিল আসছে, সাথে থাকুন!</p>
        </div>
        @endif

    </div>
</div>
@endsection

@push('script')
<script>
(function($){
    /* ---- Midnight Countdown ---- */
    var hEl = document.querySelector('.cd-hours');
    var mEl = document.querySelector('.cd-mins');
    var sEl = document.querySelector('.cd-secs');
    if (hEl && mEl && sEl) {
        function pad(n) { return n.toString().padStart(2, '0'); }
        setInterval(function() {
            var now = new Date();
            var midnight = new Date();
            midnight.setHours(24, 0, 0, 0);
            var diff = Math.max(0, midnight - now);
            hEl.textContent = pad(Math.floor(diff / 3600000));
            mEl.textContent = pad(Math.floor((diff % 3600000) / 60000));
            sEl.textContent = pad(Math.floor((diff % 60000) / 1000));
        }, 1000);
    }

    /* ---- Scroll Reveal ---- */
    if ('IntersectionObserver' in window) {
        var obs = new IntersectionObserver(function(entries) {
            entries.forEach(function(e) {
                if (e.isIntersecting) { e.target.classList.add('revealed'); obs.unobserve(e.target); }
            });
        }, { threshold: 0.06 });
        document.querySelectorAll('.hd-grid .product-card-v2').forEach(function(el) { obs.observe(el); });
    }
})(jQuery);
</script>

@endpush