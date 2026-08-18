@php
    $subtotal = (float) str_replace([',', '.00'], '', Cart::instance('shopping')->subtotal());
    $shipping = (float) Session::get('shipping', 0);
    $discount = (float) Session::get('discount', 0);
@endphp
<table class="cart_table table table-bordered table-striped text-center mb-0">
    <thead>
        <tr>
            <th>প্রোডাক্ট</th>
            <th style="width:120px">পরিমাণ</th>
            <th style="width:95px">মূল্য</th>
        </tr>
    </thead>
    <tbody>
        @forelse(Cart::instance('shopping')->content() as $item)
            <tr>
                <td class="text-start">
                    <img src="{{ asset($item->options->image ?? 'public/uploads/default.webp') }}" width="42" height="42" alt="">
                    <strong>{{ Str::limit($item->name, 24) }}</strong>
                    @if(!empty($item->options->product_size))
                        <p>Size: {{ $item->options->product_size }}</p>
                    @endif
                    @if(!empty($item->options->product_color))
                        <p>Color: {{ $item->options->product_color }}</p>
                    @endif
                </td>
                <td>
                    <div class="quantity">
                        <button type="button" class="cart_decrement" data-id="{{ $item->rowId }}">−</button>
                        <input type="text" value="{{ $item->qty }}" readonly>
                        <button type="button" class="cart_increment" data-id="{{ $item->rowId }}">+</button>
                    </div>
                </td>
                <td>৳{{ number_format($item->price * $item->qty, 0) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3">কার্ট খালি। একটি পণ্য সিলেক্ট করুন।</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <th colspan="2" class="text-end">মোট</th>
            <td id="net_total"><strong>{{ number_format($subtotal, 0) }}</strong></td>
        </tr>
        <tr>
            <th colspan="2" class="text-end">ডেলিভারি চার্জ</th>
            <td id="cart_shipping_cost"><strong>{{ number_format($shipping, 0) }}</strong></td>
        </tr>
        @if($discount > 0)
            <tr>
                <th colspan="2" class="text-end">কুপন ছাড়</th>
                <td><strong>{{ number_format($discount, 0) }}</strong></td>
            </tr>
        @endif
        <tr>
            <th colspan="2" class="text-end">সর্বমোট</th>
            <td id="grand_total"><strong>{{ number_format($subtotal + $shipping - $discount, 0) }}</strong></td>
        </tr>
    </tfoot>
</table>
