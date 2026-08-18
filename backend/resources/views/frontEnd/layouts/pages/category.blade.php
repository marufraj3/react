@extends('frontEnd.layouts.master')
@section('title', $category->name)
@section('breadcrumb'){{ $category->name }}@endsection

@push('css')
<style>
/* ========================================
   CATEGORY LISTING — REDESIGNED
   ======================================== */
.cat-listing{background:var(--paper,#fff);padding:24px 0 50px}
.cat-inner{max-width:1280px;margin:0 auto;padding:0 20px}

/* ---- Page Header ---- */
.cat-page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px}
.cat-page-header .left h1{font-size:clamp(1.25rem,2.5vw,1.7rem);font-weight:850;margin:0 0 4px;letter-spacing:-.02em;color:var(--ink,#1a1a1a)}
.cat-page-header .left .cat-meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.cat-count-chip{display:inline-flex;align-items:center;gap:5px;background:var(--soft,#f5f5f5);border:1px solid var(--line,#e8e8e8);padding:4px 12px;border-radius:99px;font-size:.76rem;font-weight:700;color:var(--muted,#888)}
.cat-count-chip strong{color:var(--ink,#1a1a1a)}

/* ---- Toolbar ---- */
.cat-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.filter-toggle-btn{display:none;align-items:center;gap:7px;background:var(--paper,#fff);border:1px solid var(--line,#e8e8e8);padding:9px 16px;border-radius:10px;font-size:.82rem;font-weight:700;cursor:pointer;color:var(--ink,#1a1a1a);transition:border-color .2s ease}
.filter-toggle-btn:hover{border-color:var(--accent,#e53935)}
.filter-toggle-btn i{font-size:14px}
.sort-wrap{position:relative}
.sort-select{appearance:none;background:var(--paper,#fff);border:1px solid var(--line,#e8e8e8);padding:9px 36px 9px 14px;border-radius:10px;font-size:.82rem;font-weight:600;color:var(--ink,#1a1a1a);cursor:pointer;min-width:155px;transition:border-color .2s ease}
.sort-select:focus{border-color:var(--accent,#e53935);outline:none}
.sort-arrow{position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;font-size:10px;color:var(--muted,#888)}

/* ---- Active Filters ---- */
.active-filters{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.active-filters:empty{display:none}
.af-chip{display:inline-flex;align-items:center;gap:5px;background:#fff0f0;border:1px solid #fecaca;border-radius:8px;padding:5px 10px;font-size:.74rem;font-weight:600;color:#b91c1c}
.af-chip a{color:#dc2626;font-size:14px;text-decoration:none;margin-left:2px;font-weight:700}
.af-clear{font-size:.78rem;font-weight:700;color:var(--accent,#e53935);text-decoration:none}

/* ---- Layout Grid ---- */
.cat-layout{display:grid;grid-template-columns:240px 1fr;gap:22px;align-items:start}

/* ---- Sidebar ---- */
.cat-sidebar{position:sticky;top:90px}
.sb-card{background:var(--paper,#fff);border:1px solid var(--line,#e8e8e8);border-radius:14px;padding:16px 14px;margin-bottom:12px;transition:box-shadow .2s ease}
.sb-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.04)}
.sb-card h6{font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--ink,#1a1a1a);margin:0 0 10px;padding-bottom:9px;border-bottom:1px solid var(--line,#e8e8e8);display:flex;align-items:center;gap:7px}
.sb-card h6 i{font-size:13px;color:var(--accent,#e53935)}
.sb-link{display:flex;align-items:center;justify-content:space-between;padding:7px 10px;border-radius:8px;font-size:.82rem;font-weight:600;color:var(--ink,#1a1a1a);text-decoration:none;transition:all .15s ease;margin-bottom:2px}
.sb-link:hover{background:var(--soft,#f5f5f5);color:var(--accent,#e53935);padding-left:14px}
.sb-link i{font-size:10px;opacity:.35;transition:opacity .15s ease}
.sb-link:hover i{opacity:1}
.price-range-form{display:flex;flex-direction:column;gap:10px}
.price-inputs{display:flex;gap:8px;align-items:center}
.price-inputs input{flex:1;width:100%;padding:9px 10px;border:1px solid var(--line,#e8e8e8);border-radius:8px;font-size:.82rem;color:var(--ink,#1a1a1a);background:var(--paper,#fff);transition:border-color .2s ease;box-sizing:border-box}
.price-inputs input:focus{outline:none;border-color:var(--accent,#e53935)}
.price-inputs input::placeholder{color:#bbb;font-size:.78rem}
.price-inputs span{color:var(--muted,#888);font-size:.78rem;font-weight:600;flex-shrink:0}
.price-apply{width:100%;padding:10px;border:none;border-radius:10px;background:var(--ink,#1a1a1a);color:#fff;font-size:.82rem;font-weight:750;cursor:pointer;transition:opacity .2s ease}
.price-apply:hover{opacity:.85}

/* ========================================
   PRODUCT GRID — THIS IS THE KEY FIX
   ======================================== */
.product-grid{display:grid!important;grid-template-columns:repeat(3,1fr);gap:14px}

/* Product card override for listing */
.product-grid .product-card-v2{height:100%;border-radius:14px}

/* ---- Empty State ---- */
.cat-empty{text-align:center;padding:60px 20px;grid-column:1/-1}
.cat-empty i{font-size:52px;opacity:.2;display:block;margin-bottom:16px;color:var(--ink,#1a1a1a)}
.cat-empty h3{font-size:1.1rem;font-weight:800;color:var(--ink,#1a1a1a);margin:0 0 6px}
.cat-empty p{font-size:.88rem;margin:0;color:var(--muted,#888)}

/* ---- Pagination ---- */
.cat-pagination{display:flex;align-items:center;justify-content:center;gap:5px;margin-top:36px;flex-wrap:wrap}
.pg-btn{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 4px;border:1px solid var(--line,#e8e8e8);border-radius:10px;background:var(--paper,#fff);font-size:.82rem;font-weight:700;color:var(--ink,#1a1a1a);text-decoration:none;transition:all .2s ease;cursor:pointer}
.pg-btn:hover{border-color:var(--accent,#e53935);color:var(--accent,#e53935)}
.pg-btn.active{background:var(--ink,#1a1a1a);color:#fff;border-color:var(--ink,#1a1a1a)}
.pg-btn.disabled{opacity:.35;pointer-events:none}
.pg-dots{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;font-size:.8rem;color:var(--muted,#888);font-weight:600}

/* ---- Mobile Overlay & Drawer ---- */
.filter-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:998}
.filter-overlay.show{display:block;opacity:1;animation:fadeIn .2s ease}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.fd-header{display:none;align-items:center;justify-content:space-between;margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid var(--line,#e8e8e8)}
.fd-header h3{font-size:1rem;font-weight:800;margin:0}
.fd-close{width:34px;height:34px;display:grid;place-items:center;border:1px solid var(--line,#e8e8e8);border-radius:10px;background:none;cursor:pointer;font-size:15px;color:var(--ink,#1a1a1a);transition:background .15s ease}
.fd-close:hover{background:var(--soft,#f5f5f5)}

/* ========================================
   RESPONSIVE
   ======================================== */
@media(max-width:991px){
    .cat-layout{grid-template-columns:1fr}
    .cat-sidebar{display:none;position:fixed;top:0;left:-100%;width:300px;max-width:85vw;height:100vh;background:var(--paper,#fff);z-index:999;overflow-y:auto;transition:left .35s cubic-bezier(.4,0,.2,1);padding:20px;box-shadow:4px 0 24px rgba(0,0,0,.12)}
    .cat-sidebar.open{left:0}
    .filter-toggle-btn{display:inline-flex}
    .fd-header{display:flex!important}
    .product-grid{grid-template-columns:repeat(3,1fr)!important}
}

@media(max-width:767px){
    .cat-listing{padding:16px 0 40px}
    .cat-inner{padding:0 14px}
    .cat-page-header .left h1{font-size:1.15rem}
    .cat-toolbar{gap:8px}
    .sort-select{min-width:auto;flex:1;font-size:.78rem;padding:8px 32px 8px 12px}
    .product-grid{grid-template-columns:repeat(2,1fr)!important;gap:10px!important}
    .cat-pagination{margin-top:28px;gap:4px}
    .pg-btn{min-width:34px;height:34px;font-size:.76rem;border-radius:8px}
    .pg-dots{width:34px;height:34px}
}

@media(max-width:380px){
    .product-grid{gap:8px!important}
}
</style>
@endpush

@section('content')
<div class="cat-listing">
    <div class="cat-inner">

        {{-- Header --}}
        <div class="cat-page-header">
            <div class="left">
                <h1>{{ $category->name }}</h1>
                <div class="cat-meta">
                    <span class="cat-count-chip"><strong>{{ $products->total() }}</strong> পণ্য</span>
                </div>
            </div>
        </div>

        {{-- Active Filters --}}
        <div class="active-filters">
            @if(request('min_price'))
            <span class="af-chip">ন্যূনতম: ৳{{ request('min_price') }} <a href="{{ request()->fullUrlWithQuery(['min_price' => null, 'page' => null]) }}">&times;</a></span>
            @endif
            @if(request('max_price'))
            <span class="af-chip">সর্বোচ্চ: ৳{{ request('max_price') }} <a href="{{ request()->fullUrlWithQuery(['max_price' => null, 'page' => null]) }}">&times;</a></span>
            @endif
            @if(request('min_price') || request('max_price'))
            <a href="{{ request()->fullUrlWithQuery(['min_price' => null, 'max_price' => null, 'page' => null]) }}" class="af-clear">সব মুছুন</a>
            @endif
        </div>

        {{-- Toolbar --}}
        <div class="cat-toolbar">
            <button class="filter-toggle-btn" id="open-filter">
                <i class="fa-solid fa-sliders"></i> ফিল্টার
            </button>
            <div class="sort-wrap">
                <form method="GET" action="{{ route('category', $category->slug) }}" style="margin:0">
                    @if(request('min_price'))<input type="hidden" name="min_price" value="{{ request('min_price') }}">@endif
                    @if(request('max_price'))<input type="hidden" name="max_price" value="{{ request('max_price') }}">@endif
                    <select class="sort-select" name="sort" onchange="this.form.submit()">
                        <option value="1" @selected(request('sort')==1 || !request('sort'))>সর্বশেষ</option>
                        <option value="3" @selected(request('sort')==3)>দাম বেশি → কম</option>
                        <option value="4" @selected(request('sort')==4)>দাম কম → বেশি</option>
                        <option value="5" @selected(request('sort')==5)>নাম A-Z</option>
                    </select>
                </form>
                <span class="sort-arrow"><i class="fa-solid fa-chevron-down"></i></span>
            </div>
        </div>

        {{-- Layout --}}
        <div class="cat-layout">

            {{-- Sidebar --}}
            <aside class="cat-sidebar" id="cat-sidebar">
                <div class="fd-header">
                    <h3>ফিল্টার</h3>
                    <button class="fd-close" id="close-filter"><i class="fa-solid fa-xmark"></i></button>
                </div>

                @if($subcategories->isNotEmpty())
                <div class="sb-card">
                    <h6><i class="fa-solid fa-layer-group"></i> সাব-ক্যাটাগরি</h6>
                    @foreach($subcategories as $sub)
                    <a class="sb-link" href="{{ route('subcategory', $sub->slug) }}">
                        <span>{{ $sub->subcategoryName }}</span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    @endforeach
                </div>
                @endif

                <div class="sb-card">
                    <h6><i class="fa-solid fa-tag"></i> মূল্যসীমা</h6>
                    <form class="price-range-form" method="GET" action="{{ route('category', $category->slug) }}">
                        @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                        <div class="price-inputs">
                            <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="ন্যূনতম" min="0">
                            <span>—</span>
                            <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="সর্বোচ্চ" min="0">
                        </div>
                        <button class="price-apply" type="submit">ফিল্টার প্রয়োগ করুন</button>
                    </form>
                </div>
            </aside>

            {{-- Products --}}
            <section>
                <div class="product-grid">
                    @forelse($products as $product)
                        @include('frontEnd.layouts.partials.product-card', ['product' => $product])
                    @empty
                    <div class="cat-empty">
                        <i class="fa-solid fa-box-open"></i>
                        <h3>কোনো পণ্য পাওয়া যায়নি</h3>
                        <p>এই ক্যাটাগরিতে এখনো পণ্য যোগ করা হয়নি।</p>
                    </div>
                    @endforelse
                </div>

                @if($products->hasPages())
                <div class="cat-pagination">
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
            </section>

        </div>
    </div>
</div>

<div class="filter-overlay" id="filter-overlay"></div>
@endsection

@push('script')
<script>
(function($){
    var $sb=$('#cat-sidebar'),$ov=$('#filter-overlay');
    function openF(){$sb.addClass('open');$ov.addClass('show');$('body').css('overflow','hidden')}
    function closeF(){$sb.removeClass('open');$ov.removeClass('show');$('body').css('overflow','')}
    $('#open-filter').on('click',openF);
    $('#close-filter,#filter-overlay').on('click',closeF);
})(jQuery);
</script>
@endpush