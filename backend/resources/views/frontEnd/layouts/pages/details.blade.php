@extends('frontEnd.layouts.master')

@section('title', $details->name)

@push('seo')
@php
    $metaTitle = $details->meta_title ?? $details->name;
    $metaDescription = $details->meta_description ?? Str::limit(strip_tags($details->description ?? ''), 160);
    $metaImage = $details->meta_image ? asset($details->meta_image) : asset(optional($details->image)->image);
@endphp
<meta name="description" content="{{ $metaDescription }}" />
<meta name="keywords" content="{{ $details->meta_keywords ?? $details->name }}" />
<meta property="og:title" content="{{ $metaTitle }}" />
<meta property="og:type" content="product" />
<meta property="og:url" content="{{ route('product', $details->slug) }}" />
<meta property="og:image" content="{{ $metaImage }}" />
<meta property="og:description" content="{{ $metaDescription }}" />
{!! app(\App\Services\ProductSchemaService::class)->productScript($details, $reviews ?? null, route('product', $details->slug)) !!}
@endpush

@section('content')

@php
    $pdOff = (!empty($details->old_price) && $details->old_price > $details->new_price)
        ? round((($details->old_price - $details->new_price) * 100) / $details->old_price) : 0;
    $pdRating = $reviews->count() ? (int) round($reviews->avg('ratting')) : 0;

    // স্টক হিসাব — ভ্যারিয়েন্ট লেভেলের ট্র্যাকিং থাকলে সেটাকে প্রাধান্য দিই,
    // নাহলে প্রোডাক্টের মেইন stock কলাম থেকে নিই,
    // কোনোটিই না থাকলে 1 ধরে নিই (স্টক চেক ডিজেবল)
    $__variantRows = ($details->variantPrices ?? collect());
    $__hasTracked = $__variantRows->contains(fn ($v) => $v->stock !== null);
    $__hasUntracked = $__variantRows->contains(fn ($v) => $v->stock === null);
    $__productStock = (int) ($details->stock ?? 0);
    if ($__hasTracked) {
        // শুধু non-null স্টক যোগ — null গুলো ফলব্যাক (প্রোডাক্ট স্টক) থেকে নিই
        $__trackedSum = (int) $__variantRows->sum(fn ($v) => $v->stock !== null ? max(0, (int) $v->stock) : 0);
        $__untrackedBonus = $__hasUntracked ? max(0, $__productStock) : 0;
        $pdStock = $__trackedSum + $__untrackedBonus;
    } else {
        // ভ্যারিয়েন্টে স্টক ট্র্যাক না হলে প্রোডাক্টের মেইন stock কলাম ব্যবহার করি
        $pdStock = $__productStock;
    }
    $pdHasStock = $pdStock > 0;
@endphp

<div class="sf-page">
    <div class="sf-container">

        <nav class="sf-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><i class="fa-solid fa-angle-right"></i>
            @if(!empty($details->category))<a href="{{ route('category', $details->category->slug) }}">{{ $details->category->name }}</a><i class="fa-solid fa-angle-right"></i>@endif
            @if(!empty($details->subcategory))<a href="{{ route('subcategory', $details->subcategory->slug) }}">{{ $details->subcategory->subcategoryName }}</a><i class="fa-solid fa-angle-right"></i>@endif
            <span class="cur">{{ Str::limit($details->name, 50) }}</span>
        </nav>

        <div class="sf-pd">
            <div class="sf-pd__grid">

                {{-- ============ GALLERY ============ --}}
                <div class="sf-pd__gallery">
                    <div class="sf-pd__mainimg">
                        @if($pdOff > 0)<span class="sf-off-badge">-{{ $pdOff }}%</span>@endif
                        <img id="sfMainImg" src="{{ asset(optional($details->images->first())->image ?? optional($details->image)->image ?? 'public/logo.png') }}" alt="{{ $details->name }}" />
                    </div>
                    @if($details->images->count() > 1)
                        <div class="sf-pd__thumbs" id="sfThumbs">
                            @foreach($details->images as $key => $image)
                                <button type="button" data-thumb data-full="{{ asset($image->image) }}" data-color-id="{{ $image->color_id ?? '' }}" class="{{ $loop->first ? 'active' : '' }}">
                                    <img src="{{ asset($image->image) }}" alt="Thumb {{ $key + 1 }}" />
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- ============ INFO / BUY BOX ============ --}}
                <div>
                    <h1 class="sf-pd__name">{{ $details->name }}</h1>

                    <div class="sf-pd__meta">
                        <span class="sf-stars">
                            @for($i = 1; $i <= 5; $i++)<i class="fa-solid fa-star {{ $i <= $pdRating ? 'on' : '' }}"></i>@endfor
                        </span>
                        <a href="#writeReview">{{ number_format($reviews->avg('ratting') ?? 0, 1) }} ({{ $reviews->count() }} reviews)</a>
                        @if($details->product_code)<span><i class="fa-solid fa-qrcode"></i> SKU: {{ $details->product_code }}</span>@endif
                        <span><i class="fa-solid fa-box"></i> {{ $details->is_digital ? 'Digital' : 'Physical' }} Product</span>
                    </div>

                    <div class="sf-pd__pricebar">
                        <span class="sf-price" id="newPrice"><span class="cur">৳</span>{{ number_format((float) $details->new_price) }}</span>
                        @if($pdOff > 0)
                            <span class="sf-old-price">৳{{ number_format((float) $details->old_price) }}</span>
                            <span class="sf-badge sf-badge--accent">Save ৳{{ number_format((float) ($details->old_price - $details->new_price)) }}</span>
                        @endif
                        <span class="sf-pd__stock {{ $pdHasStock ? 'in' : 'out' }}" id="sfStockStatus">
                            <i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px"></i>
                            {{ $pdHasStock ? 'In Stock' : 'Out of Stock' }}
                        </span>
                    </div>

                    <div class="sf-pd__points">
                        <span><i class="fa-solid fa-check"></i>Cash on Delivery available</span>
                        <span><i class="fa-solid fa-check"></i>{{ $details->is_digital ? 'Instant download after payment' : 'Delivery within 24–72 hours' }}</span>
                        @if(!empty($details->brand))<span><i class="fa-solid fa-check"></i>Brand: {{ $details->brand->name }}</span>@endif
                    </div>

                    <form action="{{ route('cart.store') }}" method="POST" name="formName">
                        @csrf
                        <input type="hidden" name="id" value="{{ $details->id }}" />
                        @if($details->pro_unit)<input type="hidden" name="pro_unit" value="{{ $details->pro_unit }}" />@endif

                        @if($details->variantPrices && $details->variantPrices->count() > 0)
                            @php
                                $productcolors = $details->variantPrices->pluck('color')->unique('id')->filter();
                                $productsizes = $details->variantPrices->pluck('size')->unique('id')->filter();
                            @endphp

                            @if($productcolors->count())
                                <div class="sf-pd__var">
                                    <label>Color <span class="sf-faint">— pick one</span></label>
                                    <div class="sf-opt-group">
                                        @foreach($productcolors as $procolor)
                                            <label class="sf-opt is-swatch" title="{{ $procolor->colorName }}">
                                                <input type="radio" name="product_color" value="{{ $procolor->id }}" style="display:none" />
                                                <i style="background:{{ $procolor->color ?? '#ccc' }}"></i>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($productsizes->count())
                                <div class="sf-pd__var">
                                    <label>Size <span class="sf-faint">— pick one</span></label>
                                    <div class="sf-opt-group" id="sfSizeGroup">
                                        @foreach($productsizes as $prosize)
                                            @php
                                                // এই সাইজের সব ভ্যারিয়েন্ট ধরে — color ভ্যারিয়েশন মিলিয়ে আসল stock বের করি
                                                $variantsForSize = $details->variantPrices->where('size_id', $prosize->id);
                                                $trackedVariants = $variantsForSize->filter(fn($v) => $v->stock !== null);
                                                $untrackedVariants = $variantsForSize->filter(fn($v) => $v->stock === null);

                                                // ট্র্যাকড ভ্যারিয়েন্ট থাকলে তাদের মোট যোগ করি
                                                $trackedStock = (int) $trackedVariants->sum(fn($v) => max(0, (int) $v->stock));
                                                $hasTrackedStock = $trackedVariants->isNotEmpty();
                                                $hasUntracked   = $untrackedVariants->isNotEmpty();

                                                // প্রোডাক্টের মেইন স্টক কলাম — ফলব্যাক হিসেবে ব্যবহার হবে
                                                $productMainStock = (int) ($details->stock ?? 0);

                                                // সাইজকে "out" ধরবো শুধু তখনই —
                                                // 1) কিছু ভ্যারিয়েন্টে stock ট্র্যাক করা হয়েছে (non-null) এবং তাদের মোট stock <= 0
                                                // 2) এবং কোনো untracked ভ্যারিয়েন্ট নেই যেগুলো main stock অনুসরণ করবে
                                                $sizeOut = $hasTrackedStock && $trackedStock <= 0 && !($hasUntracked && $productMainStock > 0);

                                                // ব্যবহারকারীকে দেখানোর "X available" কাউন্ট —
                                                // untracked ভ্যারিয়েন্ট থাকলে tracked sum + main stock এর min;
                                                // otherwise শুধু tracked sum
                                                $sizeDisplayStock = $hasUntracked
                                                    ? max($trackedStock, $productMainStock)
                                                    : $trackedStock;
                                            @endphp
                                            <label class="sf-opt size-opt {{ $sizeOut ? 'is-disabled' : '' }}" data-size-id="{{ $prosize->id }}" style="{{ $sizeOut ? 'opacity:.4;pointer-events:none;text-decoration:line-through' : '' }}">
                                                <input type="radio" name="product_size" value="{{ $prosize->id }}" {{ $sizeOut ? 'disabled' : '' }} style="display:none" />
                                                {{ $prosize->sizeName ?? $prosize->name }}
                                                @if(!$sizeOut && $hasTrackedStock)<small class="size-stock-hint" style="display:block;font-size:10px;color:#087a45;font-weight:700">{{ $sizeDisplayStock }} available</small>@elseif(!$sizeOut && !$hasTrackedStock && $productMainStock > 0)<small style="display:block;font-size:10px;color:#087a45;font-weight:700">{{ $productMainStock }} available</small>@endif
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        {{-- Wholesale tiers --}}
                        @if($details->is_wholesale && $details->wholesalePrices && $details->wholesalePrices->count() > 0)
                            <div class="sf-pd__var">
                                <label><i class="fa-solid fa-tags" style="color:var(--c-green);margin-right:6px"></i>Wholesale Pricing <span class="sf-faint">— price applies automatically</span></label>
                                <div class="sf-card-surface" style="overflow:hidden">
                                    <table style="width:100%;border-collapse:collapse;font-size:13px">
                                        <thead>
                                            <tr style="background:#f8f9fc;color:var(--c-faint);text-transform:uppercase;font-size:10.5px;letter-spacing:.6px">
                                                <th style="padding:10px 12px;text-align:left">Quantity</th>
                                                <th style="padding:10px 12px;text-align:left">Unit Price</th>
                                                <th style="padding:10px 12px;text-align:left">Stock</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($details->wholesalePrices->sortBy('min_quantity') as $tier)
                                                <tr class="wholesale-tier-row" data-min-qty="{{ $tier->min_quantity }}" data-max-qty="{{ $tier->max_quantity ?? 999999 }}" data-price="{{ $tier->wholesale_price }}" style="border-top:1px solid var(--c-line)">
                                                    <td style="padding:10px 12px;font-weight:700">{{ $tier->min_quantity }}{{ $tier->max_quantity ? ' – ' . $tier->max_quantity : '+' }} pcs</td>
                                                    <td style="padding:10px 12px;color:#087a45;font-weight:800">৳{{ number_format($tier->wholesale_price) }}</td>
                                                    <td style="padding:10px 12px;color:{{ ($tier->stock ?? 0) > 0 ? '#087a45' : 'var(--c-accent)' }}">{{ $tier->stock ?? 0 }} pcs</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        {{-- Actions --}}
                        <div class="sf-pd__actions">
                            <div class="sf-qty" style="flex-shrink:0">
                                <button type="button" data-qty="minus">−</button>
                                <input type="text" name="qty" value="1" min="1" />
                                <button type="button" data-qty="plus">+</button>
                            </div>
                            @if($pdHasStock)
                                <button type="submit" name="add_cart" class="sf-btn sf-btn--dark cart_store" data-id="{{ $details->id }}" onclick="return pdValidate()">
                                    <i class="fa-solid fa-cart-plus"></i> Add to Cart
                                </button>
                                <button type="submit" name="order_now" class="sf-btn sf-btn--primary" onclick="return pdValidate()">
                                    <i class="fa-solid fa-bolt"></i> Order Now
                                </button>
                            @else
                                <button type="button" class="sf-btn sf-btn--outline" disabled style="flex:1"><i class="fa-solid fa-ban"></i> Out of Stock</button>
                            @endif
                        </div>
                    </form>

                    {{-- Call / WhatsApp --}}
                    <div style="display:flex;gap:10px;flex-wrap:wrap">
                        @if(!empty(optional($contact)->hotline))
                            <a class="sf-btn sf-btn--green sf-btn--sm" href="tel:{{ $contact->hotline }}"><i class="fa-solid fa-phone"></i> Call: {{ $contact->hotline }}</a>
                        @endif
                        @if(!empty(optional($contact)->whatsapp))
                            <a class="sf-btn sf-btn--outline sf-btn--sm" href="https://api.whatsapp.com/send?phone={{ $contact->whatsapp }}&text=Hello, I want to know more about this product: {{ urlencode(Request::url()) }}" target="_blank" rel="noopener"><i class="fab fa-whatsapp" style="color:#25D366"></i> Ask on WhatsApp</a>
                        @endif
                    </div>

                    @if(($shippingcharge ?? collect())->count())
                        <div style="margin-top:14px;padding:12px 14px;background:var(--c-primary-50);border-radius:var(--r-sm);font-size:12.5px;font-weight:600;color:var(--c-primary)">
                            <i class="fa-solid fa-cubes"></i>
                            @foreach($shippingcharge as $value)
                                {{ $value->name }}@if(!$loop->last) · @endif
                            @endforeach
                        </div>
                    @endif

                    <div class="sf-pd__trust">
                        <div><i class="fa-solid fa-rotate-left"></i>7-Day Return</div>
                        <div><i class="fa-solid fa-shield-halved"></i>Secure Payment</div>
                        <div><i class="fa-solid fa-truck-fast"></i>Fast Delivery</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ TABS ============ --}}
        <div class="sf-tabs sf-card-surface">
            <div class="sf-tabs__nav" style="padding:0 16px">
                <button type="button" class="active" data-tab="pdDescription">Description</button>
                <button type="button" data-tab="pdVideo" @if(empty($videoType)) style="display:none" @endif>Video</button>
                <button type="button" data-tab="pdReviews">Reviews ({{ $reviews->count() }})</button>
            </div>

            <div class="sf-tabs__pane active" id="pdDescription">
                <div class="sf-prose">{!! $details->description !!}</div>
            </div>

            @php
                $videoType = $details->pro_video_type ?? ($details->pro_video ? 'youtube' : null);
                $hasVideo = ($videoType === 'youtube' && $details->pro_video) || ($videoType === 'upload' && $details->pro_video_path);
            @endphp
            <div class="sf-tabs__pane" id="pdVideo">
                @if($hasVideo)
                    @if($videoType === 'youtube' && $details->pro_video)
                        <iframe width="100%" height="420" style="border-radius:12px;border:0" src="https://www.youtube.com/embed/{{ $details->pro_video }}" allowfullscreen></iframe>
                    @elseif($videoType === 'upload' && $details->pro_video_path)
                        <video width="100%" height="420" controls style="border-radius:12px;background:#000">
                            <source src="{{ asset($details->pro_video_path) }}" type="video/mp4">
                        </video>
                    @endif
                @else
                    <div class="sf-empty"><i class="fa-solid fa-video"></i><h4>No video available</h4></div>
                @endif
            </div>

            <div class="sf-tabs__pane" id="pdReviews" style="padding:20px 24px">
                @if($reviews->count())
                    <div class="sf-rating-sum">
                        <div class="sf-rating-sum__big">
                            <b>{{ number_format($reviews->avg('ratting') ?? 0, 1) }}</b>
                            <div class="sf-stars">
                                @for($i = 1; $i <= 5; $i++)<i class="fa-solid fa-star {{ $i <= $pdRating ? 'on' : '' }}"></i>@endfor
                            </div>
                            <small>{{ $reviews->count() }} verified review(s)</small>
                        </div>
                        <button type="button" class="sf-btn sf-btn--dark sf-btn--sm" data-bs-toggle="modal" data-bs-target="#reviewModal">
                            <i class="fa-regular fa-pen-to-square"></i> Write a Review
                        </button>
                    </div>
                    @foreach($reviews->take(10) as $review)
                        <div class="sf-review">
                            <img src="{{ asset($review->image ?? 'public/uploads/default.webp') }}" alt="" onerror="this.src='{{ asset('public/logo.png') }}'" />
                            <div class="sf-review__body">
                                <b>{{ $review->name }}</b>
                                <div class="sf-stars">
                                    @for($i = 1; $i <= 5; $i++)<i class="fa-solid fa-star {{ $i <= $review->ratting ? 'on' : '' }}"></i>@endfor
                                </div>
                                <p>{{ $review->review }}</p>
                                <small>{{ optional($review->created_at)->format('d M Y') }}</small>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="sf-empty">
                        <i class="fa-regular fa-clipboard"></i>
                        <h4>No reviews yet</h4>
                        <p>Be the first one to write a review for this product.</p>
                        <button type="button" class="sf-btn sf-btn--dark sf-btn--sm" style="margin-top:14px" data-bs-toggle="modal" data-bs-target="#reviewModal">Write a Review</button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Review modal --}}
        <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:18px;border:0">
                    <div class="modal-header" style="border:0">
                        <h5 class="modal-title" style="font-weight:800">Your Review</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if(Auth::guard('customer')->user())
                            <form action="{{ route('customer.review') }}" method="POST">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $details->id }}">
                                <div class="sf-field">
                                    <label>Rating</label>
                                    <div style="display:flex;gap:6px">
                                        @for($i = 5; $i >= 1; $i--)
                                            <label style="cursor:pointer;font-size:26px;color:#e2e6ee">
                                                <input type="radio" name="ratting" value="{{ $i }}" required style="display:none" />
                                                <i class="fa-solid fa-star" style="pointer-events:none"></i>
                                            </label>
                                        @endfor
                                    </div>
                                </div>
                                <div class="sf-field">
                                    <label>Your Feedback <span class="req">*</span></label>
                                    <textarea class="sf-textarea" name="review" required placeholder="Share your experience with this product…"></textarea>
                                </div>
                                <button type="submit" class="sf-btn sf-btn--primary sf-btn--block">Submit Review</button>
                            </form>
                        @else
                            <div class="sf-empty" style="padding:30px 10px">
                                <i class="fa-solid fa-user-lock"></i>
                                <h4>Login to review</h4>
                                <a class="sf-btn sf-btn--dark" style="margin-top:12px" href="{{ route('customer.login') }}">Login / Register</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ RELATED ============ --}}
        @if(($products ?? collect())->count())
            <div class="sf-sec-head">
                <div>
                    <h2 class="sf-sec-head__ttl">Related Products</h2>
                    <p class="sf-sec-head__sub">You may also like these</p>
                </div>
            </div>
            <div class="sf-owl-nav">
                <div class="owl-carousel related_slider">
                    @foreach($products as $product)
                        <div style="padding:4px">
                            @include('frontEnd.layouts.partials.product-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
@endsection

@push('script')
<script>
    /* ---------- Variant price update ---------- */
    const variants = @json($details->variantPrices);

    function updateVariantPrice() {
        let color = $("input[name='product_color']:checked").val() || null;
        let size  = $("input[name='product_size']:checked").val() || null;
        let match = null;
        if (color && size) {
            match = variants.find(v => String(v.color_id ?? v.color) == String(color) && String(v.size_id ?? v.size) == String(size));
        }
        if (!match && color && !size) {
            match = variants.find(v => String(v.color_id ?? v.color) == String(color) && (v.size_id === null || v.size_id === ''));
        }
        if (!match && size && !color) {
            match = variants.find(v => String(v.size_id ?? v.size) == String(size) && (v.color_id === null || v.color_id === ''));
        }
        let basePrice = parseFloat({{ $details->new_price }});
        if (match && match.price !== undefined && match.price !== null && match.price !== '') {
            basePrice = parseFloat(match.price);
        }

        // স্টক ইন্ডিকেটর আপডেট করি — নির্বাচিত ভ্যারিয়েন্টের stock দেখাবো
        let stockEl = document.getElementById('sfStockStatus');
        if (stockEl) {
            let hasStock = true;
            if (match && match.stock !== undefined && match.stock !== null && match.stock !== '') {
                hasStock = parseInt(match.stock) > 0;
            } else if (!variants.length) {
                hasStock = (parseInt({{ $pdStock }})) > 0;
            } else {
                // কোনো নির্দিষ্ট ভ্যারিয়েন্ট সিলেক্ট না হলে পেজের সামগ্রিক stock দেখাই
                hasStock = (parseInt({{ $pdStock }})) > 0;
            }
            stockEl.className = 'sf-pd__stock ' + (hasStock ? 'in' : 'out');
            stockEl.innerHTML = '<i class="fa-solid fa-circle" style="font-size:8px;margin-right:4px"></i>' + (hasStock ? 'In Stock' : 'Out of Stock');
        }

        @if($details->is_wholesale && $details->wholesalePrices && $details->wholesalePrices->count() > 0)
        let qty = parseInt($("input[name='qty']").val()) || 1;
        let tier = null;
        @foreach($details->wholesalePrices->sortBy('min_quantity') as $tier)
        if (qty >= {{ $tier->min_quantity }} && qty <= {{ $tier->max_quantity ?? 999999 }}) tier = {{ $tier->wholesale_price }};
        @endforeach
        if (tier !== null) basePrice = parseFloat(tier);
        @endif

        $('#newPrice').html('<span class="cur">৳</span>' + basePrice.toLocaleString('en-US'));
    }
    $(document).on('change', "input[name='product_color'], input[name='product_size']", updateVariantPrice);
    $(document).on('change', "input[name='qty']", updateVariantPrice);
    $(document).ready(function () { updateVariantPrice(); });

    /* ---------- Color → size stock recalc ---------- */
    // একটা নির্দিষ্ট color সিলেক্ট করলেও size "out" দেখাবো শুধু তখনই — যখন
    // ঐ size-এর সব color combinations স্টক ০ এবং কোনো untracked fallback নেই।
    /* ---------------------------------------------- */
    function refreshSizeByColor(colorId) {
        const $sizes = $('.size-opt');
        if (!$sizes.length) return;

        $sizes.each(function(){
            const sizeId = $(this).data('size-id');
            // ঐ size-এর সব variants যেগুলো: color=null/undef OR color কারেন্ট color এর সাথে মিলে
            const matching = variants.filter(v =>
                String(v.size_id ?? v.size) == String(sizeId) &&
                ((v.color_id === null || v.color_id === '' || v.color_id === undefined) || (colorId && String(v.color_id ?? v.color) == String(colorId)))
            );

            let anyPositive = false;
            let displayStock = 0;
            let hasTracked = false;
            let hasUntrackedInSize = false;
            matching.forEach(v => {
                if (v.stock === null || v.stock === undefined || v.stock === '') {
                    hasUntrackedInSize = true;
                } else {
                    hasTracked = true;
                    const s = parseInt(v.stock) || 0;
                    if (s > 0) anyPositive = true;
                    displayStock += s;
                }
            });

            // সেই size-এর সব variants (সব color মিলিয়ে) আলাদাভাবে গণনা করি
            // — কারণ নির্দিষ্ট color-এ সাইজ out হলেও অন্য color-এ থাকতে পারে,
            // এখানে আমরা শুধুমাত্র "selected color এর সাথে মিল" দেখাই (UI সহজ রাখতে)
            const productStock = parseInt({{ $pdStock }});

            // Size out হবে শুধুমাত্র তখনই যখন:
            // ১) এই কম্বোতে কিছু tracked variant স্টক আছে, সবগুলোর স্টক <= 0 এবং
            // ২) কোনো untracked variant নেই যেটা product stock দিয়ে fallback নিত
            const sizeOut = hasTracked && !anyPositive && !(hasUntrackedInSize && productStock > 0);

            $(this).toggleClass('is-disabled', !!sizeOut);
            $(this).css('opacity', sizeOut ? 0.4 : 1);
            $(this).css('pointer-events', sizeOut ? 'none' : '');
            $(this).css('text-decoration', sizeOut ? 'line-through' : '');

            const $input = $(this).find('input[name=product_size]');
            $input.prop('disabled', !!sizeOut);

            // "available" কাউন্ট আপডেট
            const $hint = $(this).find('.size-stock-hint');
            $hint.remove();
            if (!sizeOut) {
                if (hasTracked && displayStock > 0) {
                    const finalStock = hasUntrackedInSize
                        ? Math.max(displayStock, productStock)
                        : displayStock;
                    $(this).append('<small class="size-stock-hint" style="display:block;font-size:10px;color:#087a45;font-weight:700">' + finalStock + ' available</small>');
                } else if (!hasTracked && productStock > 0) {
                    $(this).append('<small class="size-stock-hint" style="display:block;font-size:10px;color:#087a45;font-weight:700">' + productStock + ' available</small>');
                }
            }

            // size out হয়ে গেলে পুরনো সিলেকশন সরাই
            if (sizeOut && $input.is(':checked')) { $input.prop('checked', false); }
        });
    }

    $(document).on('change', "input[name='product_color']", function () {
        refreshSizeByColor($(this).val());
    });
    $(document).ready(function () {
        // পেজে ঢুকে যদি কোনো color ডিফল্ট চেক করা থাকে, তাহলে সাইজগুলো রি-ক্যালক করি
        var initialColor = $("input[name='product_color']:checked").val();
        if (initialColor) refreshSizeByColor(initialColor);
    });

    /* ---------- Color → image filter ---------- */
    var productImages = @json($details->images->map(fn($img) => ['src' => asset($img->image), 'color_id' => $img->color_id]));
    $(document).on('change', "input[name='product_color']", function () {
        var colorId = $(this).val() ? String($(this).val()) : null;
        var filtered = productImages.filter(function (img) { return img.color_id && String(img.color_id) === colorId; });
        if (!filtered.length) filtered = productImages;
        var main = filtered[0];
        if (main) {
            $('#sfMainImg').attr('src', main.src);
            $('#sfThumbs button').removeClass('active');
            $('#sfThumbs button[data-color-id="' + (colorId || '') + '"]').first().addClass('active');
        }
    });

    /* ---------- Variant validation (only when options exist) ---------- */
    function pdValidate() {
        var sizeInputs = $("input[name='product_size']");
        var colorInputs = $("input[name='product_color']");
        if (sizeInputs.length && !sizeInputs.filter(':checked').length) { toastr.warning('Please select a size'); return false; }
        if (colorInputs.length && !colorInputs.filter(':checked').length) { toastr.warning('Please select a color'); return false; }
        return true;
    }

    /* ---------- Review stars ---------- */
    $(document).on('click', '#reviewModal .fa-star', function () {
        var val = $(this).parent().find('input').val();
        $('#reviewModal .fa-star').css('color', '#e2e6ee');
        $('#reviewModal input[type=radio]').each(function () {
            if (parseInt($(this).val()) >= parseInt(val)) $(this).siblings('i').css('color', '#F5A623');
        });
    });

    /* ---------- Related slider ---------- */
    $(".related_slider").owlCarousel({
        margin: 14, items: 5, loop: true, dots: false, nav: true, autoplay: true, autoplayTimeout: 5000, autoplayHoverPause: true,
        navText: ["<i class='fa-solid fa-angle-left'></i>", "<i class='fa-solid fa-angle-right'></i>"],
        responsive: { 0: { items: 2 }, 640: { items: 3 }, 992: { items: 4 }, 1200: { items: 5 } }
    });

    /* ---------- dataLayer: view_item ---------- */
    window.dataLayer = window.dataLayer || [];
    dataLayer.push({ ecommerce: null });
    dataLayer.push({
        event: "view_item",
        ecommerce: {
            items: [{
                item_name: @json($details->name),
                item_id: "{{ $details->id }}",
                price: "{{ $details->new_price }}",
                item_brand: @json(optional($details->brand)->name),
                item_category: "{{ optional($details->category)->name }}",
                currency: "BDT",
                quantity: {{ $pdStock }}
            }]
        }
    });
</script>
@endpush
