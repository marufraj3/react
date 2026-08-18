@if(!empty($products) && $products->count())
    @foreach($products as $value)
        <a class="sf-search-item" href="{{ route('product', $value->slug) }}">
            <img src="{{ asset($value->image ? $value->image->image : 'public/logo.png') }}" alt="{{ $value->name }}" />
            <span style="flex:1;min-width:0">
                <span class="sf-search-item__name sf-clamp-1">{{ $value->name }}</span>
                <span>
                    <span class="sf-search-item__price">৳{{ number_format((float) $value->new_price) }}</span>
                    @if(!empty($value->old_price) && $value->old_price > $value->new_price)
                        <span class="sf-search-item__old">৳{{ number_format((float) $value->old_price) }}</span>
                    @endif
                </span>
            </span>
            <i class="fa-solid fa-arrow-right" style="color:var(--c-faint);font-size:12px"></i>
        </a>
    @endforeach
@else
    <div class="sf-search-drop__empty"><i class="fa-regular fa-face-frown" style="display:block;font-size:22px;margin-bottom:6px"></i>No products found — try a different keyword.</div>
@endif
