@php
    $premiumProduct = $products->first();
    $premiumImage = $campaign_data->image_one
        ?: (optional($premiumProduct?->image)->image ?? 'public/uploads/default.webp');
    $premiumOldPrice = (float) ($premiumProduct?->old_price ?? 0);
    $premiumPrice = (float) ($premiumProduct?->new_price ?? 0);
    $premiumDiscount = $premiumOldPrice > $premiumPrice && $premiumOldPrice > 0
        ? (int) round((($premiumOldPrice - $premiumPrice) / $premiumOldPrice) * 100)
        : 0;
    $premiumGallery = collect([
        $campaign_data->image_one,
        $campaign_data->image_two,
        $campaign_data->image_three,
        $campaign_data->banner,
    ])->merge($campaign_data->images->pluck('image'))->filter()->unique()->take(6);
    $premiumSizes = $premiumProduct
        ? collect($premiumProduct->variantPrices ?? [])->pluck('size')->filter()->merge($premiumProduct->sizes ?? [])->unique('id')->values()
        : collect();
@endphp

<div class="premium-campaign">
    <header class="premium-announcement">
        <div class="premium-container premium-announcement-inner">
            <strong>🔥 {{ strip_tags($campaign_data->top_title_1 ?: 'মেগা অফার! সীমিত সময়ের বিশেষ মূল্য') }}</strong>
            @if($campaign_data->deadline)
                <div class="premium-timer" data-cpb-countdown="{{ $campaign_data->deadline }}" aria-label="অফারের বাকি সময়">
                    <span>অফার শেষ হতে বাকি:</span>
                    <b><span data-days>00</span> দিন</b>
                    <b><span data-hours>00</span>:<span data-minutes>00</span>:<span data-seconds>00</span></b>
                </div>
            @else
                <span class="premium-limited">{{ strip_tags($campaign_data->top_title_2 ?: 'স্টক থাকা পর্যন্ত') }}</span>
            @endif
        </div>
    </header>

    <section class="premium-hero">
        <div class="premium-container premium-hero-grid">
            <div class="premium-hero-copy">
                <span class="premium-kicker">PREMIUM COLLECTION</span>
                <h1>{{ strip_tags($campaign_data->heading_1 ?: ($premiumProduct?->name ?? $campaign_data->name)) }}</h1>
                <p>{{ strip_tags($campaign_data->short_description ?: $campaign_data->description) }}</p>
                @if($premiumProduct)
                    <div class="premium-price-line">
                        <strong>৳ {{ number_format($premiumPrice, 0) }}</strong>
                        @if($premiumOldPrice > $premiumPrice)<del>৳ {{ number_format($premiumOldPrice, 0) }}</del>@endif
                        @if($premiumDiscount > 0)<span>{{ $premiumDiscount }}% ডিসকাউন্ট</span>@endif
                    </div>
                @endif
                <a href="#product-grid" class="premium-cta" data-order-product>
                    <span aria-hidden="true">🛍️</span> প্রোডাক্ট পছন্দ করুন
                </a>
                <div class="premium-trust-inline" aria-label="অর্ডারের সুবিধা">
                    <span>🚚 হোম ডেলিভারি</span>
                    <span>💵 ক্যাশ অন ডেলিভারি</span>
                    <span>🎧 অর্ডার সহায়তা</span>
                </div>
            </div>
            <figure class="premium-hero-media">
                <img src="{{ asset($premiumImage) }}" alt="{{ strip_tags($premiumProduct?->name ?? $campaign_data->name) }}" width="760" height="760" fetchpriority="high" decoding="async">
                @if($campaign_data->images->isNotEmpty())
                    <figcaption>⭐ কাস্টমার মতামত <small>রিভিউ গ্যালারি দেখুন</small></figcaption>
                @endif
            </figure>
        </div>
    </section>

    <section class="premium-trust" aria-label="কেন আমাদের বেছে নেবেন">
        <div class="premium-container premium-trust-grid">
            <article><span>✓</span><strong>যাচাই করা তথ্য</strong><small>লাইভ পণ্য ও মূল্য</small></article>
            <article><span>🛡️</span><strong>কোয়ালিটি চেক</strong><small>যত্নসহকারে প্রসেসিং</small></article>
            <article><span>📦</span><strong>হোম ডেলিভারি</strong><small>নির্বাচিত ঠিকানায়</small></article>
            <article><span>🎧</span><strong>কাস্টমার সাপোর্ট</strong><small>অর্ডার সহায়তা</small></article>
        </div>
    </section>

    <section class="premium-benefits">
        <div class="premium-container premium-narrow">
            <div class="premium-section-heading">
                <span>কেন এটি আপনার জন্য</span>
                <h2>{{ strip_tags($campaign_data->heading_2 ?: 'প্রিমিয়াম কোয়ালিটি, আরাম ও দীর্ঘস্থায়িত্ব') }}</h2>
            </div>
            <div class="premium-benefit-grid">
                <article><b>✓</b><div><h3>{{ strip_tags($campaign_data->feature_1 ?: 'সেরা মানের উপকরণ') }}</h3><p>যত্ন নিয়ে বাছাই করা উপকরণ ও মানসম্মত ফিনিশিং।</p></div></article>
                <article><b>✓</b><div><h3>{{ strip_tags($campaign_data->feature_2 ?: 'আরামদায়ক ব্যবহার') }}</h3><p>প্রতিদিনের ব্যবহারের জন্য আরামদায়ক ও নির্ভরযোগ্য।</p></div></article>
                <article><b>✓</b><div><h3>{{ strip_tags($campaign_data->heading_3 ?: 'আধুনিক ডিজাইন') }}</h3><p>স্মার্ট ডিজাইন আপনার স্টাইলকে আরও আকর্ষণীয় করে।</p></div></article>
                <article><b>✓</b><div><h3>{{ strip_tags($campaign_data->heading_4 ?: 'কোয়ালিটি চেকড') }}</h3><p>ডেলিভারির আগে প্রতিটি অর্ডার যত্নসহকারে যাচাই করা হয়।</p></div></article>
            </div>
        </div>
    </section>

    @if($premiumGallery->isNotEmpty())
        <section class="premium-gallery">
            <div class="premium-container">
                <div class="premium-section-heading">
                    <span>এক নজরে</span>
                    <h2>প্রোডাক্ট গ্যালারি</h2>
                </div>
                <div class="premium-gallery-grid">
                    @foreach($premiumGallery as $galleryImage)
                        <img src="{{ asset($galleryImage) }}" alt="{{ strip_tags($campaign_data->name) }} — ছবি {{ $loop->iteration }}" width="560" height="560" loading="lazy" decoding="async">
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($premiumSizes->isNotEmpty())
        <section class="premium-sizes">
            <div class="premium-container premium-narrow">
                <div class="premium-section-heading">
                    <span>আপনার জন্য সঠিক অপশন</span>
                    <h2>উপলভ্য সাইজ</h2>
                    <p>অর্ডারের সময় প্রোডাক্ট থেকে আপনার সাইজ ও কালার নির্বাচন করুন।</p>
                </div>
                <div class="premium-size-list">
                    @foreach($premiumSizes as $size)
                        <span>{{ $size->sizeName ?? $size->size_name ?? $size->name ?? ('Size ' . $size->id) }}</span>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($campaign_data->video)
        <section class="premium-video">
            <div class="premium-container premium-narrow">
                <div class="premium-section-heading"><span>ভিডিওতে দেখুন</span><h2>প্রোডাক্টটি কাছ থেকে চিনে নিন</h2></div>
                <div data-cpb-youtube="{{ $campaign_data->video }}"></div>
            </div>
        </section>
    @endif

    <section id="product-grid" class="premium-products">
        <div class="premium-container">
            <div class="premium-section-heading">
                <span>আপনার পছন্দ বেছে নিন</span>
                <h2>{{ strip_tags($campaign_data->review ?: 'প্রোডাক্ট ও ভ্যারিয়েন্ট সিলেক্ট করুন') }}</h2>
                <p>সাইজ, কালার ও পরিমাণ নিশ্চিত করে সরাসরি চেকআউটে যান।</p>
            </div>
            <div class="premium-product-grid cpb-live-products" data-cpb-dynamic="products" data-button-label="সিলেক্ট করুন ও অর্ডার দিন"></div>
        </div>
    </section>

    @if($campaign_data->images->isNotEmpty())
        <section class="premium-reviews">
            <div class="premium-container">
                <div class="premium-section-heading"><span>সোশ্যাল প্রুফ</span><h2>আমাদের কাস্টমারদের অভিজ্ঞতা</h2></div>
                <div class="premium-review-grid" data-cpb-dynamic="reviews"></div>
            </div>
        </section>
    @endif

    <section class="premium-faq">
        <div class="premium-container premium-narrow">
            <div class="premium-section-heading"><span>জানতে চান?</span><h2>সাধারণ প্রশ্নের উত্তর</h2></div>
            <div class="premium-faq-list">
                <details open><summary>কীভাবে অর্ডার করব?</summary><p>প্রোডাক্ট বেছে সাইজ/কালার নিশ্চিত করুন, তারপর নাম, ফোন ও ঠিকানা দিয়ে অর্ডার কনফার্ম করুন।</p></details>
                <details><summary>পেমেন্ট কীভাবে করব?</summary><p>{{ strip_tags($campaign_data->billing_details ?: 'ক্যাশ অন ডেলিভারিতে পণ্য হাতে পেয়ে মূল্য পরিশোধ করতে পারবেন।') }}</p></details>
                <details><summary>ডেলিভারি সম্পর্কে কোনো বিশেষ তথ্য আছে?</summary><p>{{ strip_tags($campaign_data->note ?: 'ডেলিভারি চার্জ আপনার নির্বাচিত এলাকার ভিত্তিতে অর্ডার সামারিতে দেখানো হবে।') }}</p></details>
            </div>
        </div>
    </section>

    <section class="premium-checkout" id="order_form_section">
        <div class="premium-container premium-narrow">
            <div class="premium-section-heading">
                <span>শেষ ধাপ</span>
                <h2>অর্ডার সম্পূর্ণ করুন</h2>
                <p>সঠিক তথ্য দিন—আমাদের টিম দ্রুত আপনার অর্ডার প্রসেস করবে।</p>
            </div>
            <div data-cpb-dynamic="checkout"></div>
        </div>
    </section>

    <footer class="premium-footer">
        <div class="premium-container">
            <strong>{{ optional($generalsetting)->name ?? $campaign_data->name }}</strong>
            <p>নিরাপদ অর্ডার • মানসম্মত পণ্য • বিশ্বস্ত ডেলিভারি</p>
            @if(optional($contact ?? null)->phone)<a href="tel:{{ optional($contact)->phone }}">সহায়তা: {{ optional($contact)->phone }}</a>@endif
        </div>
    </footer>
</div>
