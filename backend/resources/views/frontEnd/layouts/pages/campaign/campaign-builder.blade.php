<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $pixels = $pixels ?? collect();
        $gtm_code = $gtm_code ?? collect();
        $tiktok_pixels = $tiktok_pixels ?? collect();
        $contact = $contact ?? null;
        $campName = strip_tags($campaign_data->name ?? '');
        $campId = (string) $campaign_data->id;
        $firstProduct = $products->first();
        $campValue = (float) ($firstProduct?->new_price ?? 0);
        $campaignImage = $campaign_data->image_one ?: ($campaign_data->banner ?: optional($firstProduct?->image)->image);
        $campaignDescription = strip_tags($campaign_data->short_description ?: ($campaign_data->description ?? ''));
        $discount = $firstProduct && (float) $firstProduct->old_price > (float) $firstProduct->new_price
            ? (float) $firstProduct->old_price - (float) $firstProduct->new_price
            : 0;
        $storefrontProducts = $products->map(function ($product) {
            // Size/Color names must come from variantPrices first (admin panel now
            // creates variants there); the old productsizes/productcolors pivots are
            // only a fallback. Otherwise the front end shows raw IDs like "Size 23".
            $sizeOptions = [];
            $colorOptions = [];
            foreach ($product->variantPrices ?? [] as $variant) {
                if ($variant->size_id && $variant->size) {
                    if (!isset($sizeOptions[$variant->size_id])) {
                        $sizeOptions[$variant->size_id] = [
                            'id' => (string) $variant->size_id,
                            'name' => $variant->size->sizeName ?? $variant->size->name ?? ('Size ' . $variant->size_id),
                            'stock' => 0,
                            'has_stock' => false,
                        ];
                    }
                    if ($variant->stock !== null) {
                        $sizeOptions[$variant->size_id]['stock'] += max(0, (int) $variant->stock);
                        $sizeOptions[$variant->size_id]['has_stock'] = true;
                    }
                }
                if ($variant->color_id && $variant->color) {
                    $colorOptions[$variant->color_id] = [
                        'id' => (string) $variant->color_id,
                        'name' => $variant->color->colorName ?? $variant->color->name ?? ('Color ' . $variant->color_id),
                        'hex' => $variant->color->color ?? '',
                    ];
                }
            }
            foreach ($product->sizes ?? [] as $size) {
                if (!isset($sizeOptions[$size->id])) {
                    $sizeOptions[$size->id] = ['id' => (string) $size->id, 'name' => $size->sizeName ?? $size->size_name ?? $size->name ?? '', 'stock' => 0, 'has_stock' => false];
                }
            }
            foreach ($product->colors ?? [] as $color) {
                if (!isset($colorOptions[$color->id])) {
                    $colorOptions[$color->id] = ['id' => (string) $color->id, 'name' => $color->colorName ?? $color->color_name ?? $color->name ?? '', 'hex' => $color->color ?? ''];
                }
            }

            $variantRows = collect($product->variantPrices ?? []);
            $hasVariantStock = $variantRows->contains(fn ($variant) => $variant->stock !== null);
            $totalStock = $hasVariantStock
                ? $variantRows->sum(fn ($variant) => max(0, (int) $variant->stock))
                : (int) ($product->stock ?? 0);

            return [
                'id' => (string) $product->id,
                'name' => strip_tags($product->name ?? ''),
                'price' => (float) $product->new_price,
                'old_price' => (float) $product->old_price,
                'image' => asset(optional($product->image)->image ?? 'public/uploads/default.webp'),
                'stock' => (int) $totalStock,
                'has_stock' => (bool) $hasVariantStock || ($product->stock ?? null) !== null,
                'sizes' => array_values($sizeOptions),
                'colors' => array_values($colorOptions),
                'variants' => optional($product->variantPrices)->map(fn ($variant) => [
                    'size_id' => $variant->size_id ? (string) $variant->size_id : null,
                    'color_id' => $variant->color_id ? (string) $variant->color_id : null,
                    'price' => (float) $variant->price,
                    'stock' => $variant->stock === null ? null : (int) $variant->stock,
                ])->values(),
            ];
        })->values();
        $gtmItems = $storefrontProducts->map(fn ($product, $index) => [
            'item_id' => $product['id'], 'item_name' => $product['name'], 'price' => $product['price'], 'index' => $index, 'quantity' => 1,
        ])->values();
        $tokenValues = [
            '{{campaign.name}}' => e($campName),
            '{{campaign.title}}' => e($campName),
            '{{campaign.slug}}' => e($campaign_data->slug ?? ''),
            '{{campaign.deadline}}' => e($campaign_data->deadline ?? ''),
            '{{campaign.description}}' => e($campaignDescription),
            '{{campaign.image}}' => e($campaignImage ? asset($campaignImage) : ''),
            '{{product.name}}' => e($firstProduct?->name ?? ''),
            '{{product.title}}' => e($firstProduct?->name ?? ''),
            '{{product.slug}}' => e($firstProduct?->slug ?? ''),
            '{{product.price}}' => e(number_format((float) ($firstProduct?->new_price ?? 0), 0)),
            '{{product.old_price}}' => e(number_format((float) ($firstProduct?->old_price ?? 0), 0)),
            '{{product.discount}}' => e(number_format($discount, 0)),
            '{{product.image}}' => e($firstProduct ? asset(optional($firstProduct->image)->image ?? 'public/uploads/default.webp') : ''),
            '{{contact.phone}}' => e(optional($contact)->phone ?? ''),
            '{{contact.whatsapp}}' => e(optional($contact)->whatsapp ?? ''),
        ];
        $hasRenderedSource = isset($renderPageHtml);
        $sourceHtml = $hasRenderedSource ? $renderPageHtml : ($campaign_data->page_html ?? '');
        $publishedHtml = $hasRenderedSource
            ? (string) $sourceHtml
            : strtr((string) $sourceHtml, $tokenValues);
        $publishedCss = $hasRenderedSource ? ($renderPageCss ?? '') : ($campaign_data->page_css ?? '');
        $publishedJs = $hasRenderedSource ? ($renderPageJs ?? null) : null;
        $pageType = $pageType ?? 'visual';
    @endphp

    <title>{{ $campName }} — {{ optional($generalsetting)->name }}</title>
    <meta name="description" content="{{ $campaignDescription }}">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $campName }}">
    <meta property="og:description" content="{{ $campaignDescription }}">
    <meta property="og:url" content="{{ route('campaign', $campaign_data->slug) }}">
    <link rel="canonical" href="{{ route('campaign', $campaign_data->slug) }}">
    @if($campaignImage)<meta property="og:image" content="{{ asset($campaignImage) }}">@endif
    <link rel="shortcut icon" href="{{ asset(optional($generalsetting)->favicon) }}" type="image/x-icon">

    {{-- ⭐ স্ট্রাকচার্ড ডেটা (JSON-LD) — সার্চ রেজাল্টে দাম, স্টক ও রেটিং দেখানোর জন্য --}}
    {!! app(\App\Services\ProductSchemaService::class)->campaignScript(
            $campaign_data,
            $products,
            route('campaign', $campaign_data->slug)
       ) !!}
    {{-- bootstrap.min.css (216K), FontAwesome all.css (140K) ও jQuery (88K) সরানো হয়েছে —
         এই পেজের সব স্টাইল campaign-page-renderer.css + builder-এর page_css থেকে আসে
         এবং renderer JS সম্পূর্ণ vanilla। FB ads ট্রাফিকের জন্য ~৩৫০KB সাশ্রয়। --}}
    <link rel="stylesheet" href="{{ asset('public/frontEnd/css/campaign-page-renderer.css') }}">
    @if($pageType === 'premium')
        <link rel="stylesheet" href="{{ asset('public/frontEnd/css/campaign-premium.css') }}">
    @endif
    <style id="campaign-builder-page-css">{!! $publishedCss !!}</style>

    {{-- Existing Google Analytics/custom tracking configured in General Settings. --}}
    {!! optional($generalsetting)->header_code !!}

    <script>
        window.dataLayer = window.dataLayer || [];
        window._campaignData = {
            id: @json($campId), name: @json($campName), slug: @json($campaign_data->slug),
            page_type: @json($pageType), currency: 'BDT', fb_event_id: @json($fb_view_content_event_id)
        };
        window._campaignProducts = @json($storefrontProducts);
        window.dataLayer.push({
            event: 'campaign_page_loaded', page_type: @json('campaign_landing_' . $pageType),
            campaign_id: @json($campId), campaign_name: @json($campName), currency: 'BDT', value: {{ $campValue }},
            ecommerce: { currency: 'BDT', items: @json($gtmItems) }
        });
    </script>

    @foreach($gtm_code as $gtm)
        @php $gtmContainerId = preg_match('/^GTM-/i', trim($gtm->code)) ? trim($gtm->code) : 'GTM-' . trim($gtm->code); @endphp
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f)})(window,document,'script','dataLayer',@json($gtmContainerId));</script>
    @endforeach

    @if($pixels->count() > 0)
        <script>
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
            @foreach($pixels as $pixel) fbq('init', @json($pixel->code)); @endforeach
            fbq('track', 'PageView', {}, {eventID: @json('pv_camp' . $campaign_data->id . '_' . time())});
            fbq('track', 'ViewContent', {content_name:@json($campName),content_ids:@json($products->pluck('id')->map(fn($id)=>(string)$id)->values()),content_type:'product',value:{{ $campValue }},currency:'BDT',num_items:{{ $products->count() }}}, {eventID:@json($fb_view_content_event_id)});
        </script>
    @endif

    @if($tiktok_pixels->count() > 0)
        <script>
            !function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie'];ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var i='https://analytics.tiktok.com/i18n/pixel/events.js';ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=i;ttq._t=ttq._t||{};ttq._t[e]=+new Date;ttq._o=ttq._o||{};ttq._o[e]=n||{};var o=document.createElement('script');o.type='text/javascript';o.async=!0;o.src=i+'?sdkid='+e+'&lib='+t;var a=document.getElementsByTagName('script')[0];a.parentNode.insertBefore(o,a)}}(window,document,'ttq');
            @foreach($tiktok_pixels as $tiktok) ttq.load(@json($tiktok->code)); @endforeach
            ttq.page();
            ttq.track('ViewContent',{content_name:@json($campName),content_id:@json($campId),content_type:'product',value:{{ $campValue }},currency:'BDT',quantity:1});
        </script>
    @endif
</head>
<body>
    @foreach($gtm_code as $gtm)
        @php $gtmNoscriptId = preg_match('/^GTM-/i', trim($gtm->code)) ? trim($gtm->code) : 'GTM-' . trim($gtm->code); @endphp
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmNoscriptId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endforeach
    @foreach($pixels as $pixel)
        <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $pixel->code }}&ev=PageView&noscript=1" alt=""></noscript>
    @endforeach

    <main
        id="campaign-builder-storefront"
        class="cpb-published-page cpb-page-{{ $pageType }}"
        data-page-type="{{ $pageType }}"
        data-change-product-url="{{ route('cart.changeProduct') }}"
        data-cart-update-url="{{ route('cart.update') }}"
        data-cart-increment-url="{{ route('cart.increment') }}"
        data-cart-decrement-url="{{ route('cart.decrement') }}"
        data-cart-remove-url="{{ route('cart.remove') }}"
        data-shipping-url="{{ route('shipping.charge') }}"
        data-incomplete-order-url="{{ route('incomplete.order.store') }}"
    >
        {!! $publishedHtml !!}
    </main>

    <template id="cpb-live-products-template">
        @include('frontEnd.layouts.pages.campaign.partials.builder-products')
    </template>
    <template id="cpb-live-reviews-template">
        @forelse($campaign_data->images as $review)
            <article class="cpb-live-review"><img src="{{ asset($review->image) }}" alt="Customer review" loading="lazy"></article>
        @empty
            <p class="cpb-empty-dynamic">এখনো কোনো customer review image যোগ করা হয়নি।</p>
        @endforelse
    </template>
    <template id="cpb-live-checkout-template">
        @include('frontEnd.layouts.pages.campaign.partials.builder-checkout')
    </template>

    {{-- ===== Size/Color popup (storefront style — legacy campaign page-এর মতো) ===== --}}
    <div class="cpb-modal" id="cpb-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cpb-modal-title">
        <div class="cpb-modal-bg" data-cpb-modal-close></div>
        <div class="cpb-modal-box">
            <div class="cpb-modal-head">
                <h5 id="cpb-modal-title">🛒 সাইজ ও কালার বেছে নিন</h5>
                <button type="button" class="cpb-modal-x" data-cpb-modal-close aria-label="বন্ধ করুন">✕</button>
            </div>
            <div class="cpb-modal-body">
                <div class="cpb-modal-img"><img id="cpb-mo-img" src="" alt="Product"></div>
                <div>
                    <h4 class="cpb-modal-name" id="cpb-mo-name"></h4>
                    <div class="cpb-modal-price">
                        <b id="cpb-mo-price"></b>
                        <del id="cpb-mo-old"></del>
                        <span class="cpb-modal-save" id="cpb-mo-save"></span>
                    </div>
                    <div class="cpb-modal-stock" id="cpb-mo-stock"></div>
                    <div id="cpb-size-wrap" hidden>
                        <p class="cpb-lbl">সাইজ সিলেক্ট করুন <em>*</em></p>
                        <div class="cpb-chips" id="cpb-sizes"></div>
                    </div>
                    <div id="cpb-color-wrap" hidden>
                        <p class="cpb-lbl">কালার সিলেক্ট করুন <em>*</em></p>
                        <div class="cpb-chips" id="cpb-colors"></div>
                    </div>
                    <p class="cpb-lbl">পরিমাণ</p>
                    <div class="cpb-qty">
                        <button type="button" data-cpb-qty="-1" aria-label="কমান">−</button>
                        <input type="text" id="cpb-qty-box" value="1" readonly aria-label="পরিমাণ">
                        <button type="button" data-cpb-qty="1" aria-label="বাড়ান">+</button>
                    </div>
                    <div class="cpb-modal-total"><span>সর্বমোট</span><b id="cpb-mo-total">৳ 0</b></div>
                    <button type="button" class="cpb-modal-confirm" id="cpb-mo-confirm">✓ কনফার্ম করুন — চেকআউটে যোগ হবে</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== নির্বাচিত ভ্যারিয়েন্ট সামারি (checkout-এর উপরে দেখানো হয়) ===== --}}
    <template id="cpb-selected-variant-template">
        <div class="cpb-selected-variant" id="cpb-selected-variant" hidden>
            <span id="cpb-selected-variant-text"></span>
            <button type="button" id="cpb-change-variant">সাইজ/কালার বদলান</button>
        </div>
    </template>

    <div id="cpb-store-loading" class="cpb-store-loading" hidden><span></span><strong>আপনার অর্ডার আপডেট হচ্ছে...</strong></div>
    <button id="cpb-sticky-order" class="cpb-sticky-order" type="button" hidden><span aria-hidden="true">🛍️</span><span>এখনই অর্ডার করুন</span></button>
    <div id="cpb-store-toast" class="cpb-store-toast" role="status" aria-live="polite"></div>

    <script src="{{ asset('public/frontEnd/js/campaign-page-renderer.js') }}"></script>
    @if(filled($publishedJs))
        <script id="campaign-custom-page-js">{!! $publishedJs !!}</script>
    @endif
</body>
</html>
