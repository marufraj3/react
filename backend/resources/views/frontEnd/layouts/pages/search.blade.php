@extends('frontEnd.layouts.master')
@section('title','Search: '.$keyword)
@section('breadcrumb')Search results for "{{ $keyword }}"@endsection

@push('css')
<style>
/* ========================================
   SEARCH RESULTS PAGE
   ======================================== */
.search-page{padding:24px 0 50px;background:var(--paper,#fff)}
.search-inner{max-width:1200px;margin:0 auto;padding:0 20px}

/* ---- Header ---- */
.search-header{margin-bottom:24px}
.search-header h1{font-size:clamp(1.2rem,2.5vw,1.6rem);font-weight:850;margin:0 0 6px;color:var(--ink,#1a1a1a);letter-spacing:-.02em}
.search-header h1 mark{background:none;color:var(--accent,#e53935);font-weight:850}
.search-meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.search-count{font-size:.86rem;color:var(--muted,#888)}
.search-count strong{color:var(--ink,#1a1a1a);font-weight:800}
.search-back{display:inline-flex;align-items:center;gap:6px;font-size:.82rem;font-weight:700;color:var(--accent,#e53935);text-decoration:none;transition:gap .15s ease}
.search-back:hover{gap:10px}

/* ---- Search Bar (inline) ---- */
.search-bar-inline{display:flex;max-width:480px;margin-bottom:24px}
.search-bar-inline input{flex:1;padding:11px 16px;border:1.5px solid var(--line,#e8e8e8);border-radius:12px 0 0 12px;font-size:.88rem;color:var(--ink,#1a1a1a);outline:none;transition:border-color .2s ease}
.search-bar-inline input:focus{border-color:var(--accent,#e53935)}
.search-bar-inline button{padding:11px 20px;background:var(--ink,#1a1a1a);color:#fff;border:none;border-radius:0 12px 12px 0;font-size:.88rem;font-weight:750;cursor:pointer;white-space:nowrap;transition:opacity .2s ease}
.search-bar-inline button:hover{opacity:.85}

/* ---- Product Grid ---- */
.search-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}

/* ---- Empty State ---- */
.search-empty{text-align:center;padding:60px 20px}
.search-empty i{font-size:52px;opacity:.15;display:block;margin-bottom:14px;color:var(--ink,#1a1a1a)}
.search-empty h3{font-size:1.1rem;font-weight:800;margin:0 0 6px;color:var(--ink,#1a1a1a)}
.search-empty p{font-size:.88rem;margin:0 0 18px;color:var(--muted,#888)}

/* ---- Pagination ---- */
.search-pagination{display:flex;align-items:center;justify-content:center;gap:5px;margin-top:36px;flex-wrap:wrap}
.pg-btn{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 4px;border:1px solid var(--line,#e8e8e8);border-radius:10px;background:var(--paper,#fff);font-size:.82rem;font-weight:700;color:var(--ink,#1a1a1a);text-decoration:none;transition:all .2s ease;cursor:pointer}
.pg-btn:hover{border-color:var(--accent,#e53935);color:var(--accent,#e53935)}
.pg-btn.active{background:var(--ink,#1a1a1a);color:#fff;border-color:var(--ink,#1a1a1a)}
.pg-btn.disabled{opacity:.35;pointer-events:none}
.pg-dots{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;font-size:.8rem;color:var(--muted,#888);font-weight:600}

/* ========================================
   RESPONSIVE
   ======================================== */
@media(max-width:991px){
    .search-grid{grid-template-columns:repeat(3,1fr)}
}

@media(max-width:767px){
    .search-page{padding:16px 0 40px}
    .search-inner{padding:0 14px}
    .search-header h1{font-size:1.15rem}
    .search-bar-inline{max-width:100%}
    .search-grid{grid-template-columns:repeat(2,1fr);gap:10px}
    .search-pagination{margin-top:28px;gap:4px}
    .pg-btn{min-width:34px;height:34px;font-size:.76rem;border-radius:8px}
    .pg-dots{width:34px;height:34px}
}
</style>
@endpush

@section('content')
<div class="search-page">
    <div class="search-inner">

        {{-- Header --}}
        <div class="search-header">
            <h1>সার্চ ফলাফল: <mark>"{{ $keyword }}"</mark></h1>
            <div class="search-meta">
                <span class="search-count"><strong>{{ $products->total() }}</strong> পণ্য পাওয়া গেছে</span>
                <a class="search-back" href="{{ route('home') }}"><i class="fa-solid fa-arrow-left" style="font-size:12px"></i> হোমে ফিরুন</a>
            </div>
        </div>

        {{-- Search Again --}}
        <form class="search-bar-inline" action="{{ route('search') }}" method="GET">
            <input type="text" name="keyword" value="{{ $keyword }}" placeholder="আবার সার্চ করুন..." autofocus>
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>

        {{-- Results --}}
        @if($products->count() > 0)
        <div class="search-grid">
            @foreach($products as $product)
                @include('frontEnd.layouts.partials.product-card', ['product' => $product])
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
        <div class="search-pagination">
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
        <div class="search-empty">
            <i class="fa-solid fa-magnifying-glass"></i>
            <h3>কোনো পণ্য পাওয়া যায়নি</h3>
            <p>"{{ $keyword }}" দিয়ে কোনো পণ্য মেলেনি। অন্য কিছু দিয়ে সার্চ করুন।</p>
            <a class="search-back" href="{{ route('home') }}" style="background:var(--ink,#1a1a1a);color:#fff;padding:10px 24px;border-radius:10px;font-size:.84rem"><i class="fa-solid fa-bag-shopping"></i> কেনাকাটা শুরু করুন</a>
        </div>
        @endif

    </div>
</div>
@endsection