@extends('frontEnd.layouts.master')
@section('title', optional($generalsetting)->name ?? 'Online Shop')

@push('seo')
<meta name="description" content="{{ optional($seo)->meta_description ?? 'নির্বাচিত পণ্য, সহজ অর্ডার এবং সারা দেশে দ্রুত ডেলিভারি।' }}">
@endpush

@push('css')
<style>
/* ========================================
   HOME — DESIGN TOKENS
   ======================================== */
.home-v2{background:var(--paper,#fff);overflow:hidden}
.home-inner{max-width:1240px;margin:0 auto;padding:0 20px}

/* ========================================
   HERO SECTION
   ======================================== */
.home-hero{padding:20px 0 0}
.hero-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:16px;align-items:stretch}
.hero-main{border-radius:16px;overflow:hidden;position:relative;min-height:360px}
.hero-carousel{height:100%}
.hero-slide{position:relative;overflow:hidden;height:auto;min-height:360px}
.hero-slide img{width:100%;height:100%;object-fit:cover;display:block;min-height:360px}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(100deg,rgba(0,0,0,.72) 0%,rgba(0,0,0,.35) 45%,rgba(0,0,0,.08) 100%);z-index:1}
.hero-content{position:absolute;z-index:2;left:clamp(24px,5vw,56px);bottom:clamp(24px,4vw,52px);color:#fff;max-width:520px}
.hero-badge{display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:800;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.25);border-radius:99px;padding:6px 14px;backdrop-filter:blur(4px);margin-bottom:12px}
.hero-content h1{font-size:clamp(1.6rem,3vw,2.8rem);line-height:1.08;font-weight:850;letter-spacing:-.04em;margin:0 0 10px}
.hero-content>p{margin:0 0 18px;font-size:.95rem;opacity:.88;line-height:1.5}
.hero-actions{display:flex;gap:10px;flex-wrap:wrap}
.hero-cta-primary{display:inline-flex;align-items:center;gap:8px;background:#fff;color:#111!important;padding:12px 22px;border-radius:100px;font-weight:800;font-size:.88rem;transition:transform .2s ease,box-shadow .2s ease;box-shadow:0 4px 16px rgba(0,0,0,.2);text-decoration:none}
.hero-cta-primary:hover{transform:translateY(-2px);box-shadow:0 6px 24px rgba(0,0,0,.25)}
.hero-cta-secondary{display:inline-flex;align-items:center;gap:8px;background:transparent;color:#fff!important;padding:12px 22px;border-radius:100px;font-weight:700;font-size:.88rem;border:1.5px solid rgba(255,255,255,.4);transition:background .2s ease,border-color .2s ease;text-decoration:none}
.hero-cta-secondary:hover{background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.7)}

/* Owl Carousel Override */
.hero-main .owl-carousel{height:100%}
.hero-main .owl-stage-outer{height:100%}
.hero-main .owl-stage{height:100%}
.hero-main .owl-item{height:100%}
.hero-main .owl-dots{position:absolute;bottom:16px;left:50%;transform:translateX(-50%);z-index:5;display:flex;gap:6px}
.hero-main .owl-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.4);transition:all .25s ease}
.hero-main .owl-dot.active{width:24px;border-radius:99px;background:#fff}

/* Side Cards */
.hero-side{display:grid;grid-template-rows:1fr 1fr;gap:16px}
.side-card{border-radius:16px;padding:24px 22px;display:flex;flex-direction:column;justify-content:center;position:relative;overflow:hidden;transition:transform .2s ease,box-shadow .2s ease;cursor:pointer;text-decoration:none}
.side-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.side-card.side-deal{background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff}
.side-card.side-support{background:var(--soft,#f7f7f7);border:1px solid var(--line,#e8e8e8);color:var(--ink,#1a1a1a)}
.side-tag{font-size:.68rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;opacity:.7;margin-bottom:6px}
.side-card h3{font-size:1.15rem;font-weight:800;margin:0 0 6px;line-height:1.25}
.side-card p{font-size:.85rem;opacity:.75;margin:0 0 14px;line-height:1.4}
.side-link{font-size:.82rem;font-weight:800;display:inline-flex;align-items:center;gap:6px;transition:gap .2s ease}
.side-card:hover .side-link{gap:10px}
.side-deal .side-link{color:#4ade80}
.side-support .side-link{color:var(--accent,#e53e3e)}

/* Side card image background + overlay */
.side-card{position:relative;overflow:hidden}
.side-card__bg{position:absolute;inset:0;background-size:cover;background-position:center;background-repeat:no-repeat;transition:transform .4s ease;z-index:0}
.side-card:hover .side-card__bg{transform:scale(1.04)}
.side-card__shade{position:absolute;inset:0;background:linear-gradient(135deg,rgba(26,26,46,.85) 0%,rgba(22,33,62,.72) 60%,rgba(22,33,62,.45) 100%);z-index:1}
.side-card__shade--light{background:linear-gradient(135deg,rgba(255,255,255,.92) 0%,rgba(255,255,255,.7) 60%,rgba(255,255,255,.4) 100%)}
.side-card__deco{position:absolute;right:-20px;top:-20px;font-size:140px;opacity:.08;z-index:0;pointer-events:none}
.side-deal .side-card__deco{color:#fff}
.side-support .side-card__deco{color:var(--accent,#e53e3e)}
.side-card__body{position:relative;z-index:2}
.side-deal.side-card{padding-left:22px;padding-right:22px}
.side-support.side-card.with-bg{background:transparent;border-color:transparent}
.side-card.with-bg{border:0}

/* ========================================
   TRUST STRIP
   ======================================== */
.trust-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:28px 0 38px}
.trust-item{background:var(--paper,#fff);border:1px solid var(--line,#e8e8e8);border-radius:14px;padding:16px 14px;display:flex;align-items:center;gap:12px;transition:border-color .2s ease,box-shadow .2s ease}
.trust-item:hover{border-color:var(--accent,#e53e3e);box-shadow:0 2px 10px rgba(0,0,0,.06)}
.trust-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:10px;background:var(--soft,#f7f7f7);flex-shrink:0}
.trust-icon i{font-size:18px;color:var(--accent,#e53e3e)}
.trust-item strong{display:block;font-size:.82rem;font-weight:800;line-height:1.3}
.trust-item small{display:block;font-size:.72rem;color:var(--muted,#888);margin-top:2px}

/* ========================================
   SECTIONS
   ======================================== */
.home-section{margin-bottom:40px}
.section-heading{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:16px;gap:12px;flex-wrap:wrap}
.section-heading h2{font-size:1.4rem;margin:0;font-weight:850;letter-spacing:-.02em;color:var(--ink,#1a1a1a);display:flex;align-items:center;gap:8px}
.section-heading p{margin:3px 0 0;color:var(--muted,#888);font-size:.88rem}
.section-link{font-weight:750;font-size:.88rem;color:var(--accent,#e53e3e);display:inline-flex;align-items:center;gap:5px;transition:gap .2s ease;white-space:nowrap;text-decoration:none}
.section-link:hover{gap:9px}

/* ========================================
   CATEGORIES
   ======================================== */
.category-scroll{display:grid;grid-template-columns:repeat(6,1fr);gap:12px}
.category-card{background:var(--paper,#fff);border:1px solid var(--line,#e8e8e8);border-radius:14px;padding:12px 8px;text-align:center;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease;cursor:pointer;text-decoration:none;display:block}
.category-card:hover{transform:translateY(-3px);border-color:var(--accent,#e53e3e);box-shadow:0 8px 25px rgba(0,0,0,.08)}
.category-card img{width:72px;height:72px;object-fit:cover;border-radius:12px;display:block;margin:0 auto 8px;background:var(--soft,#f7f7f7)}
.category-card span{font-size:.82rem;font-weight:750;color:var(--ink,#1a1a1a);display:block;line-height:1.3}

/* ========================================
   FLASH DEALS
   ======================================== */
.flash-header{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;flex-wrap:wrap}
.flash-title-group h2{color:var(--danger,#e53e3e)}
.flash-title-group h2 i{margin-right:2px}
.countdown-bar{display:flex;align-items:center;gap:5px}
.cd-label{font-size:.78rem;font-weight:700;color:var(--muted,#888);margin-right:4px}
.cd-block{background:var(--ink,#1a1a1a);color:#fff;border-radius:8px;padding:5px 8px;text-align:center;min-width:42px}
.cd-block span{font-size:1.1rem;font-weight:800;display:block;line-height:1}
.cd-block small{font-size:.6rem;opacity:.65;text-transform:uppercase;letter-spacing:.04em}
.cd-sep{font-weight:800;font-size:1.1rem;color:var(--ink,#1a1a1a);margin:0 1px}

/* ========================================
   PRODUCT GRID
   ======================================== */
.product-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}

/* ========================================
   AD BANNER
   ======================================== */
.ad-banner{border-radius:16px;overflow:hidden;position:relative;display:block}
.ad-banner img{width:100%;display:block;max-height:220px;object-fit:cover;transition:transform .4s ease}
.ad-banner:hover img{transform:scale(1.02)}

/* ========================================
   WHY SHOP WITH US
   ======================================== */
.why-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.why-item{text-align:center;padding:28px 16px;border-radius:14px;border:1px solid var(--line,#e8e8e8);background:var(--paper,#fff);transition:transform .2s ease,box-shadow .2s ease}
.why-item:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(0,0,0,.08)}
.why-icon{width:56px;height:56px;display:grid;place-items:center;border-radius:50%;background:var(--soft,#f7f7f7);margin:0 auto 14px}
.why-icon i{font-size:22px;color:var(--accent,#e53e3e)}
.why-item h4{font-size:.92rem;font-weight:800;margin:0 0 6px}
.why-item p{font-size:.78rem;color:var(--muted,#888);margin:0;line-height:1.5}

/* ========================================
   CTA BANNER
   ======================================== */
.cta-banner{background:var(--ink,#1a1a1a);color:#fff;border-radius:16px;padding:36px 40px;display:flex;justify-content:space-between;align-items:center;gap:24px}
.cta-banner h2{font-size:1.4rem;font-weight:850;margin:0 0 6px}
.cta-banner p{margin:0;opacity:.8;font-size:.9rem}

/* ========================================
   SCROLL REVEAL
   ======================================== */
.reveal{opacity:0;transform:translateY(24px);transition:opacity .6s cubic-bezier(.4,0,.2,1),transform .6s cubic-bezier(.4,0,.2,1)}
.reveal.revealed{opacity:1;transform:translateY(0)}

/* ========================================
   RESPONSIVE — TABLET
   ======================================== */
@media(max-width:991px){
    .hero-grid{grid-template-columns:1fr}
    .hero-side{grid-template-columns:1fr 1fr;grid-template-rows:auto}
    .hero-main{min-height:300px}
    .hero-slide{min-height:300px}
    .hero-slide img{min-height:300px}
    .trust-strip{grid-template-columns:repeat(2,1fr)}
    .category-scroll{grid-template-columns:repeat(4,1fr)}
    .product-grid{grid-template-columns:repeat(3,1fr)}
    .why-grid{grid-template-columns:repeat(2,1fr)}
}

/* ========================================
   RESPONSIVE — MOBILE
   ======================================== */
@media(max-width:575px){
    .home-v2{padding-top:0}
    .home-inner{padding:0 14px}
    .home-hero{padding:14px 0 0}
    .hero-main{min-height:260px;border-radius:12px}
    .hero-slide{min-height:260px}
    .hero-slide img{min-height:260px}
    .hero-grid{gap:10px}
    .hero-side{gap:10px}
    .side-card{padding:18px 16px}
    .side-card h3{font-size:1rem}
    .hero-content h1{font-size:1.3rem}
    .hero-content>p{font-size:.82rem;margin-bottom:14px}
    .hero-cta-primary,.hero-cta-secondary{padding:10px 16px;font-size:.8rem}
    .trust-strip{grid-template-columns:1fr 1fr;gap:8px;margin:20px 0 28px}
    .trust-item{padding:12px 10px;gap:8px}
    .trust-item small{display:none}
    .trust-icon{width:36px;height:36px;border-radius:8px}
    .trust-icon i{font-size:15px}
    .category-scroll{display:flex;overflow-x:auto;gap:8px;padding-bottom:6px;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;scrollbar-width:none}
    .category-scroll::-webkit-scrollbar{display:none}
    .category-scroll .category-card{flex-shrink:0;width:95px;scroll-snap-align:start}
    .category-scroll .category-card img{width:60px;height:60px}
    .category-scroll .category-card span{font-size:.75rem}
    .home-section{margin-bottom:30px}
    .section-heading h2{font-size:1.15rem}
    .flash-header{flex-direction:column;align-items:flex-start;gap:10px}
    .cd-block{padding:4px 6px;min-width:36px}
    .cd-block span{font-size:.95rem}
    .product-grid{grid-template-columns:repeat(2,1fr);gap:10px}
    .why-grid{grid-template-columns:1fr 1fr;gap:10px}
    .why-item{padding:20px 12px}
    .why-icon{width:44px;height:44px}
    .why-icon i{font-size:18px}
    .cta-banner{padding:24px 20px;flex-direction:column;align-items:flex-start;border-radius:12px}
    .cta-banner h2{font-size:1.15rem}
}

@media(max-width:380px){
    .hero-side{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
<div class="home-v2">

    {{-- ===== 1. HERO SECTION ===== --}}
    <section class="home-hero">
        <div class="home-inner">
            <div class="hero-grid">
                {{-- Hero Carousel --}}
                <div class="hero-main">
                    @if(count($sliders) > 0)
                    <div class="hero-carousel owl-carousel owl-theme">
                        @foreach($sliders as $slider)
                        <div class="hero-slide">
                            <img src="{{ asset($slider->image) }}" alt="{{ optional($generalsetting)->name }}" fetchpriority="high">
                            <div class="hero-overlay"></div>
                            <div class="hero-content">
                                <span class="hero-badge"><i class="fa-solid fa-bolt"></i> নির্বাচিত অফার</span>
                                <h1>{{ $slider->title2 ?? 'পছন্দের পণ্য, সহজে অর্ডার করুন' }}</h1>
                                <p>{{ $slider->title1 ?? 'মানসম্মত পণ্য, নিরাপদ অর্ডার ও সারা দেশে দ্রুত ডেলিভারি।' }}</p>
                                <div class="hero-actions">
                                    <a class="hero-cta-primary" href="{{ $slider->link ?? '#shop-now' }}">Shop Now <i class="fa-solid fa-arrow-right"></i></a>
                                    <a class="hero-cta-secondary" href="{{ route('hotdeals') }}">View Deals</a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div style="width:100%;min-height:360px;background:#f7f7f7;display:grid;place-items:center;border-radius:16px;color:#888;font-size:14px">
                        <i class="fa-solid fa-image" style="font-size:40px;opacity:.3"></i>
                    </div>
                    @endif
                </div>

                {{-- Side Cards (Slider Bottom Ads — controllable from admin) --}}
                @php
                    $dealAd  = isset($sliderbottomads) && $sliderbottomads->count() > 0 ? $sliderbottomads->get(0) : null;
                    $helpAd  = isset($sliderbottomads) && $sliderbottomads->count() > 1 ? $sliderbottomads->get(1) : null;
                    $dealHref = optional($dealAd)->link ?: route('hotdeals');
                    $helpHref = optional($helpAd)->link ?: (optional($contact)->hotline ? 'tel:'.optional($contact)->hotline : route('contact'));
                @endphp
                <div class="hero-side">
                    <a class="side-card side-deal" href="{{ $dealHref }}" aria-label="Hot Deals">
                        @if($dealAd && $dealAd->image)
                            <div class="side-card__bg" style="background-image:url('{{ asset($dealAd->image) }}')"></div>
                            <div class="side-card__shade"></div>
                        @else
                            <div class="side-card__deco"><i class="fa-solid fa-fire"></i></div>
                        @endif
                        <div class="side-card__body">
                            <span class="side-tag"><i class="fa-solid fa-fire"></i> HOT DEALS</span>
                            <h3>আজকের সেরা ডিল</h3>
                            <p>বিশেষ দামে নির্বাচিত পণ্য কিনুন, স্টক থাকা পর্যন্ত।</p>
                            <span class="side-link">Shop Now <i class="fa-solid fa-arrow-right"></i></span>
                        </div>
                    </a>
                    <a class="side-card side-support" href="{{ $helpHref }}" aria-label="Customer Support">
                        @if($helpAd && $helpAd->image)
                            <div class="side-card__bg" style="background-image:url('{{ asset($helpAd->image) }}')"></div>
                            <div class="side-card__shade side-card__shade--light"></div>
                        @else
                            <div class="side-card__deco"><i class="fa-solid fa-headset"></i></div>
                        @endif
                        <div class="side-card__body">
                            <span class="side-tag"><i class="fa-solid fa-headset"></i> SUPPORT</span>
                            <h3>অর্ডারে সাহায্য লাগবে?</h3>
                            <p>আমাদের team আপনাকে সাহায্য করবে।</p>
                            <span class="side-link">Call Now <i class="fa-solid fa-phone"></i></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="home-inner">

        {{-- ===== 2. TRUST STRIP ===== --}}
        <section class="trust-strip reveal">
            <div class="trust-item">
                <div class="trust-icon"><i class="fa-solid fa-truck-fast"></i></div>
                <div><strong>দ্রুত ডেলিভারি</strong><small>সারা বাংলাদেশে</small></div>
            </div>
            <div class="trust-item">
                <div class="trust-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div><strong>Cash on Delivery</strong><small>পণ্য পেয়ে মূল্য দিন</small></div>
            </div>
            <div class="trust-item">
                <div class="trust-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <div><strong>নিরাপদ কেনাকাটা</strong><small>বিশ্বস্ত order process</small></div>
            </div>
            <div class="trust-item">
                <div class="trust-icon"><i class="fa-solid fa-rotate-left"></i></div>
                <div><strong>ইজি রিটার্ন</strong><small>সহজ ফেরত নীতি</small></div>
            </div>
        </section>

        {{-- ===== 3. POPULAR CATEGORIES ===== --}}
        <section class="home-section reveal">
            <div class="section-heading">
                <div>
                    <h2>জনপ্রিয় ক্যাটাগরি</h2>
                    <p>আপনার প্রয়োজনীয় পণ্য দ্রুত খুঁজে নিন</p>
                </div>
            </div>
            <div class="category-scroll">
                @foreach($menucategories->take(12) as $category)
                <a class="category-card" href="{{ route('category', $category->slug) }}">
                    <img loading="lazy" src="{{ asset($category->image) }}" alt="{{ $category->name }}">
                    <span>{{ Str::limit($category->name, 22) }}</span>
                </a>
                @endforeach
            </div>
        </section>

        {{-- ===== 4. FLASH DEALS + COUNTDOWN ===== --}}
        @if(count($hotdeal_top) > 0)
        <section id="shop-now" class="home-section flash-section reveal">
            <div class="flash-header">
                <div class="flash-title-group">
                    <h2><i class="fa-solid fa-bolt"></i> Flash Deals</h2>
                    <p>স্টক শেষ হওয়ার আগে অর্ডার করুন</p>
                </div>
                <div class="countdown-bar">
                    <span class="cd-label">শেষ সময়:</span>
                    <div class="cd-block"><span class="cd-hours">00</span><small>ঘণ্টা</small></div>
                    <span class="cd-sep">:</span>
                    <div class="cd-block"><span class="cd-mins">00</span><small>মিনিট</small></div>
                    <span class="cd-sep">:</span>
                    <div class="cd-block"><span class="cd-secs">00</span><small>সেকেন্ড</small></div>
                </div>
                <a class="section-link" href="{{ route('hotdeals') }}">সব দেখুন <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="product-grid">
                @foreach($hotdeal_top as $product)
                    @include('frontEnd.layouts.partials.product-card', ['product'=>$product])
                @endforeach
            </div>
        </section>
        @endif

        {{-- ===== 6. AD BANNER (Slider Bottom — third entry if available, else none) ===== --}}
        @php
            $bottomAd = isset($sliderbottomads) && $sliderbottomads->count() > 2 ? $sliderbottomads->get(2) : null;
            if (!$bottomAd && isset($campaognads) && $campaognads->count() > 0) {
                $bottomAd = $campaognads->first();
            }
        @endphp
        @if($bottomAd && $bottomAd->image)
        <section class="home-section reveal">
            <a href="{{ $bottomAd->link ?? '#' }}" class="ad-banner">
                <img loading="lazy" src="{{ asset($bottomAd->image) }}" alt="বিশেষ অফার">
            </a>
        </section>
        @endif

        {{-- ===== 6b. ALL PRODUCTS (Home: Show All Products) ===== --}}
        @if(isset($all_products) && $all_products && count($all_products) > 0)
            <section class="home-section reveal">
                <div class="section-heading">
                    <div>
                        <h2>সব পণ্য</h2>
                        <p>আপনার জন্য বাছাই করা সব পণ্য</p>
                    </div>
                    <a class="section-link" href="{{ route('shop') }}">সব দেখুন <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="product-grid">
                    @foreach($all_products as $product)
                        @include('frontEnd.layouts.partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ===== 7. DYNAMIC HOME CATEGORY PRODUCTS ===== --}}
        @if($homeproducts)
        @foreach($homeproducts as $homecat)
        @if(count($homecat->products) > 0)
        <section class="home-section reveal">
            <div class="section-heading">
                <div>
                    <h2>{{ $homecat->name }}</h2>
                    <p>আপনার জন্য বাছাই করা পণ্য</p>
                </div>
                <a class="section-link" href="{{ route('category', $homecat->slug) }}">সব দেখুন <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="product-grid">
                @foreach($homecat->products as $product)
                    @include('frontEnd.layouts.partials.product-card', ['product'=>$product])
                @endforeach
            </div>
        </section>
        @endif
        @endforeach
        @endif

        {{-- ===== 8. WHY SHOP WITH US ===== --}}
        <section class="home-section reveal">
            <div class="section-heading">
                <div>
                    <h2>কেন আমাদের থেকে কিনবেন?</h2>
                    <p>আমরা আপনার সেরা শপিং অভিজ্ঞতা নিশ্চিত করি</p>
                </div>
            </div>
            <div class="why-grid">
                <div class="why-item">
                    <div class="why-icon"><i class="fa-solid fa-truck-fast"></i></div>
                    <h4>দ্রুত ডেলিভারি</h4>
                    <p>ঢাকায় ২৪-৪৮ ঘণ্টা, সারা দেশে ৩-৫ দিনে পৌঁছে যাবে</p>
                </div>
                <div class="why-item">
                    <div class="why-icon"><i class="fa-solid fa-rotate-left"></i></div>
                    <h4>ইজি রিটার্ন</h4>
                    <p>পণ্যে সমস্যা হলে সহজেই ফেরত দিন, কোনো ঝামেলা নেই</p>
                </div>
                <div class="why-item">
                    <div class="why-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <h4>নিরাপদ পেমেন্ট</h4>
                    <p>ক্যাশ অন ডেলিভারি — পণ্য হাতে পেয়ে মূল্য দিন</p>
                </div>
                <div class="why-item">
                    <div class="why-icon"><i class="fa-solid fa-headset"></i></div>
                    <h4>সহায়তা</h4>
                    <p>যেকোনো সমস্যায় আমাদের সাথে যোগাযোগ করুন</p>
                </div>
            </div>
        </section>

        {{-- ===== 9. CTA BANNER ===== --}}
        <section class="home-section reveal">
            <div class="cta-banner">
                <div>
                    <h2>পছন্দের পণ্য খুঁজে পাচ্ছেন না?</h2>
                    <p>আমাদের সাথে যোগাযোগ করুন — সঠিক পণ্যটি খুঁজতে সাহায্য করব।</p>
                </div>
                <a class="hero-cta-primary" href="{{ route('contact') }}">যোগাযোগ করুন <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </section>

    </div>
</div>
@endsection

@push('script')
<script>
(function($){
    /* ---- Hero Carousel ---- */
    function initHeroCarousel(){
        var $carousel = $('.hero-carousel');
        if (!$carousel.length) return;
        var slideCount = $carousel.find('.hero-slide').length;
        if (slideCount === 0) return;

        var opts = {
            items: 1,
            loop: slideCount > 1,
            autoplay: slideCount > 1,
            autoplayTimeout: 5000,
            dots: slideCount > 1,
            nav: false,
            smartSpeed: 600,
            autoplayHoverPause: true,
            touchDrag: true,
            mouseDrag: false,
            autoHeight: false,
            callbacks: true
        };

        $carousel.owlCarousel(opts);

        /* Force height fix after init */
        setTimeout(function(){
            var firstImg = $carousel.find('.hero-slide img').first();
            if (firstImg.length && firstImg[0].complete) {
                var h = firstImg.height();
                if (h > 100) {
                    $carousel.css('height', h + 'px');
                    $carousel.find('.owl-stage-outer').css('height', h + 'px');
                }
            }
        }, 300);
    }

    /* Wait for images then init */
    if ($('.hero-carousel').length) {
        var heroImages = $('.hero-carousel .hero-slide img');
        var loaded = 0;
        var total = heroImages.length;
        if (total === 0) { initHeroCarousel(); }
        else {
            heroImages.each(function(){
                if (this.complete) { loaded++; }
                else {
                    $(this).on('load error', function(){ loaded++; if(loaded>=total) initHeroCarousel(); });
                }
            });
            if (loaded >= total) initHeroCarousel();
            /* Fallback — init after 3s even if images not loaded */
            setTimeout(initHeroCarousel, 3000);
        }
    }

    /* ---- Flash Deal Countdown ---- */
    function startCountdown() {
        var hEl = document.querySelector('.cd-hours');
        var mEl = document.querySelector('.cd-mins');
        var sEl = document.querySelector('.cd-secs');
        if (!hEl || !mEl || !sEl) return;

        function pad(n) { return n.toString().padStart(2, '0'); }

        setInterval(function() {
            var now = new Date();
            var midnight = new Date();
            midnight.setHours(24, 0, 0, 0);
            var diff = Math.max(0, midnight - now);
            var h = Math.floor(diff / 3600000);
            var m = Math.floor((diff % 3600000) / 60000);
            var s = Math.floor((diff % 60000) / 1000);
            hEl.textContent = pad(h);
            mEl.textContent = pad(m);
            sEl.textContent = pad(s);
        }, 1000);
    }
    startCountdown();

    /* ---- Scroll Reveal ---- */
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });

        document.querySelectorAll('.reveal').forEach(function(el) {
            observer.observe(el);
        });
    } else {
        document.querySelectorAll('.reveal').forEach(function(el) {
            el.classList.add('revealed');
        });
    }

})(jQuery);
</script>
@endpush