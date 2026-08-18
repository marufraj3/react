@foreach($products as $product)
<article class="cpb-live-product {{ $loop->first ? 'is-selected' : '' }}" data-product-card="{{ $product->id }}">
    <div class="cpb-live-product-image">
        <img src="{{ asset(optional($product->image)->image ?? 'public/uploads/default.webp') }}" alt="{{ $product->name }}" loading="lazy">
        @if((float) $product->old_price > (float) $product->new_price && (float) $product->old_price > 0)
            @php $discountPercent = round((($product->old_price - $product->new_price) / $product->old_price) * 100); @endphp
            <span>{{ $discountPercent }}% OFF</span>
        @endif
    </div>
    <div class="cpb-live-product-body">
        <h3>{{ $product->name }}</h3>
        <div class="cpb-live-price">
            <strong>৳{{ number_format((float) $product->new_price, 0) }}</strong>
            @if((float) $product->old_price > (float) $product->new_price)
                <del>৳{{ number_format((float) $product->old_price, 0) }}</del>
            @endif
        </div>
        <button type="button" class="cpb-live-product-button" data-select-product="{{ $product->id }}" data-order-product>
            <span data-product-button-label>অর্ডার করুন</span>
        </button>
    </div>
</article>
@endforeach
