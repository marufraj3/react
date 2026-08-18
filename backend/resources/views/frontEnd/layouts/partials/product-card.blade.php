{{--
    AVORONNO-style product card using this repository's existing actions.
    The eye button, Order Now button and quick-order popup hooks are kept intact.
--}}
@php
    $__img = optional($product->image)->image ?? 'public/uploads/default/no-image.png';
    $__url = route('product', $product->slug);
    $__price = (float) ($product->new_price ?? 0);
    $__oldPrice = (float) ($product->old_price ?? 0);
    $__off = $__oldPrice > $__price && $__price > 0
        ? (int) round((($__oldPrice - $__price) / $__oldPrice) * 100)
        : 0;
    $__variantRows = (isset($product->variantPrices) && $product->variantPrices->count())
        ? $product->variantPrices
        : collect();
    $__hasTrackedVariantStock = $__variantRows->contains(fn ($variant) => $variant->stock !== null);
    $__stockWasLoaded = isset($product->stock) || (method_exists($product, 'getAttributes') && array_key_exists('stock', $product->getAttributes()));
    $__stock = $__hasTrackedVariantStock
        ? (int) $__variantRows->sum(fn ($variant) => max(0, (int) $variant->stock))
        : ($__stockWasLoaded ? (int) $product->stock : 1);
    $__hasOptions = (isset($product->prosizes) && $product->prosizes->isNotEmpty())
        || (isset($product->procolors) && $product->procolors->isNotEmpty())
        || $__variantRows->isNotEmpty();
@endphp

<article class="product-card-v2 sf-card">
    <div class="pc2-img-wrap">
        <a href="{{ $__url }}" aria-label="{{ $product->name }}">
            <img loading="lazy" src="{{ asset($__img) }}" alt="{{ $product->name }}" onerror="this.src='{{ asset('public/uploads/default/no-image.png') }}'">
        </a>

        @if($__off > 0)
            <span class="pc2-badge">-{{ $__off }}%</span>
        @endif

        @if($__stock > 0)
            <button type="button" class="pc2-quick-view quick_view" data-id="{{ $product->id }}" title="Quick view" aria-label="Quick view">
                <i class="fa-regular fa-eye"></i>
            </button>
        @else
            <span class="pc2-stockout">Sold Out</span>
        @endif
    </div>

    <div class="pc2-info">
        <h3 class="pc2-name"><a href="{{ $__url }}">{{ Str::limit($product->name, 45) }}</a></h3>

        <div class="pc2-price-row">
            <span class="pc2-price">৳{{ number_format($__price, 0) }}</span>
            @if($__oldPrice > $__price)
                <span class="pc2-prev-price">৳{{ number_format($__oldPrice, 0) }}</span>
            @endif
        </div>

        <div class="pc2-btn-group">
            @if($__stock > 0)
                @if($__hasOptions)
                    <button type="button" class="pc2-cart-btn pc2-outline-btn qo-cart-link" data-id="{{ $product->id }}" data-url="{{ $__url }}">
                        <i class="fa-solid fa-cart-shopping"></i><span>কার্টে যোগ</span>
                    </button>
                @else
                    <button type="button" class="pc2-cart-btn pc2-outline-btn addcartbutton" data-id="{{ $product->id }}">
                        <i class="fa-solid fa-cart-shopping"></i><span>কার্টে যোগ</span>
                    </button>
                @endif

                <button type="button" class="pc2-cart-btn qo-order-link" data-id="{{ $product->id }}" data-url="{{ $__url }}">
                    <i class="fa-solid fa-bolt"></i><span>Order Now</span>
                </button>
            @else
                <a href="{{ $__url }}" class="pc2-cart-btn pc2-disabled-btn"><i class="fa-solid fa-ban"></i> Sold Out</a>
            @endif
        </div>
    </div>
</article>
