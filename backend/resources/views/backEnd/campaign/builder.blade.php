<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Visual Builder — {{ $campaign->name }}</title>
    <link rel="shortcut icon" href="{{ asset($generalsetting->favicon ?? 'public/backEnd/assets/images/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('public/backEnd/assets/css/icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/backEnd/assets/css/campaign-page-builder.css') }}">
</head>
<body>
@php
    $primaryProduct = $products->firstWhere('id', $campaign->product_id) ?: $products->first();
    $builderContext = [
        'campaign' => [
            'id' => (string) $campaign->id,
            'name' => strip_tags($campaign->name ?? ''),
            'slug' => $campaign->slug ?? '',
            'deadline' => $campaign->deadline ?? '',
            'description' => strip_tags($campaign->short_description ?? $campaign->description ?? ''),
            'image' => $campaign->banner ? asset($campaign->banner) : ($campaign->image_one ? asset($campaign->image_one) : ''),
            'preview' => route('campaign', $campaign->slug),
        ],
        'product' => $primaryProduct ? [
            'id' => (string) $primaryProduct->id,
            'name' => strip_tags($primaryProduct->name ?? ''),
            'price' => (float) ($primaryProduct->new_price ?? 0),
            'old_price' => (float) ($primaryProduct->old_price ?? 0),
            'image' => optional($primaryProduct->image)->image ? asset($primaryProduct->image->image) : '',
        ] : null,
        'products' => $products->map(fn ($product) => [
            'id' => (string) $product->id,
            'name' => strip_tags($product->name ?? ''),
            'price' => (float) ($product->new_price ?? 0),
            'old_price' => (float) ($product->old_price ?? 0),
            'image' => optional($product->image)->image ? asset($product->image->image) : '',
        ])->values(),
        'reviews' => $campaign->images->map(fn ($image) => ['image' => asset($image->image)])->values(),
        'contact' => [
            'phone' => $contact->phone ?? '',
            'whatsapp' => $contact->whatsapp ?? '',
        ],
    ];
@endphp
<div
    id="campaign-page-builder"
    class="cpb-app"
    data-campaign-id="{{ $campaign->id }}"
    data-save-url="{{ route('campaign.builder.save', $campaign->id) }}"
    data-clear-url="{{ route('campaign.builder.clear', $campaign->id) }}"
    data-upload-url="{{ route('campaign.builder.upload') }}"
    data-preview-url="{{ route('campaign', $campaign->slug) }}"
>
    <header class="cpb-topbar">
        <div class="cpb-brand">
            <a href="{{ route('campaign.index') }}" class="cpb-icon-btn" title="Landing pages-এ ফিরুন" aria-label="Back">
                <i class="fe-arrow-left"></i>
            </a>
            <div class="cpb-brand-mark">V</div>
            <div class="cpb-brand-copy">
                <div class="cpb-brand-title">{{ $campaign->name }}</div>
                <div class="cpb-brand-subtitle">Visual conversion builder</div>
            </div>
        </div>

        <div class="cpb-toolbar" aria-label="Builder tools">
            <button id="cpb-undo" class="cpb-icon-btn" type="button" title="Undo (Ctrl+Z)" disabled><i class="fe-corner-up-left"></i></button>
            <button id="cpb-redo" class="cpb-icon-btn" type="button" title="Redo (Ctrl+Shift+Z)" disabled><i class="fe-corner-up-right"></i></button>
            <button class="cpb-device-btn is-active" type="button" data-device="desktop" title="Desktop"><i class="fe-monitor"></i></button>
            <button class="cpb-device-btn" type="button" data-device="tablet" title="Tablet"><i class="fe-tablet"></i></button>
            <button class="cpb-device-btn" type="button" data-device="mobile" title="Mobile"><i class="fe-smartphone"></i></button>
            <button id="cpb-open-css" class="cpb-icon-btn" type="button" title="Custom CSS"><i class="fe-code"></i></button>
        </div>

        <div class="cpb-actions">
            <span id="cpb-save-state" class="cpb-save-state">সব পরিবর্তন সেভ আছে</span>
            <button id="cpb-templates" class="cpb-top-btn" type="button"><i class="fe-layout"></i><span>টেমপ্লেট</span></button>
            <button id="cpb-preview" class="cpb-top-btn" type="button"><i class="fe-external-link"></i><span>প্রিভিউ</span></button>
            <button id="cpb-save" class="cpb-top-btn cpb-top-btn-primary" type="button"><i class="fe-save"></i><span>সেভ করুন</span></button>
        </div>
    </header>

    <aside id="cpb-sidebar" class="cpb-sidebar">
        <div class="cpb-panel-tabs">
            <button type="button" class="cpb-panel-tab is-active" data-panel="blocks"><i class="fe-grid"></i> ব্লক</button>
            <button type="button" class="cpb-panel-tab" data-panel="layers"><i class="fe-layers"></i> লেয়ার</button>
        </div>
        <div id="cpb-block-panel" class="cpb-panel-view is-active" data-panel-view="blocks">
            <div class="cpb-panel-padding">
                <button id="cpb-quick-templates" type="button" class="cpb-templates-quick">
                    <span><i class="fe-zap"></i> High-converting template</span><i class="fe-chevron-right"></i>
                </button>
                <div class="cpb-search">
                    <i class="fe-search"></i>
                    <input id="cpb-block-search" type="search" placeholder="ব্লক খুঁজুন..." autocomplete="off">
                </div>
                <div id="cpb-palette"></div>
            </div>
        </div>
        <div id="cpb-layers" class="cpb-panel-view" data-panel-view="layers"></div>
    </aside>

    <main class="cpb-workspace">
        <div class="cpb-workspace-top">
            <button id="cpb-zoom-out" class="cpb-icon-btn" type="button" title="Zoom out"><i class="fe-minus"></i></button>
            <span id="cpb-zoom-label" class="cpb-zoom-label">100%</span>
            <button id="cpb-zoom-in" class="cpb-icon-btn" type="button" title="Zoom in"><i class="fe-plus"></i></button>
        </div>
        <div id="cpb-workspace-scroll" class="cpb-workspace-scroll">
            <div id="cpb-canvas-shell" class="cpb-canvas-shell" data-device="desktop">
                <div id="cpb-canvas" class="cpb-canvas cpb-builder-page" aria-label="Landing page canvas"></div>
            </div>
        </div>
    </main>

    <aside id="cpb-inspector" class="cpb-inspector">
        <div class="cpb-inspector-head">
            <div>
                <div id="cpb-inspector-title" class="cpb-inspector-title">পেজ সেটিংস</div>
                <div id="cpb-inspector-kicker" class="cpb-inspector-kicker">ক্যানভাস বা একটি এলিমেন্ট বাছুন</div>
            </div>
            <button id="cpb-inspector-close" type="button" class="cpb-icon-btn" title="Close"><i class="fe-x"></i></button>
        </div>
        <div id="cpb-inspector-body" class="cpb-inspector-body">
            <div class="cpb-inspector-empty"><div><i class="fe-mouse-pointer"></i>এডিট করতে একটি সেকশন বা উইজেট সিলেক্ট করুন</div></div>
        </div>
    </aside>
</div>

<dialog id="cpb-template-dialog" class="cpb-dialog">
    <div class="cpb-dialog-head">
        <div><h3>Conversion template library</h3><small>আপনার ক্যাম্পেইনের জন্য একটি সম্পূর্ণ লেআউট বেছে নিন</small></div>
        <button type="button" class="cpb-dialog-close" data-close-dialog><i class="fe-x"></i></button>
    </div>
    <div class="cpb-template-grid">
        <article class="cpb-template-card">
            <div class="cpb-template-preview"><i class="fe-shopping-bag"></i></div>
            <div class="cpb-template-body"><strong>Direct Response</strong><p>Hero, urgency, benefits, products, proof, FAQ এবং checkout।</p><button type="button" data-template="direct">এই ডিজাইন ব্যবহার করুন</button></div>
        </article>
        <article class="cpb-template-card">
            <div class="cpb-template-preview"><i class="fe-feather"></i></div>
            <div class="cpb-template-body"><strong>Clean Product</strong><p>কম distraction-সহ premium single-product landing page।</p><button type="button" data-template="clean">এই ডিজাইন ব্যবহার করুন</button></div>
        </article>
        <article class="cpb-template-card">
            <div class="cpb-template-preview"><i class="fe-video"></i></div>
            <div class="cpb-template-body"><strong>Video Sales</strong><p>Video-first story, offer, social proof এবং focused checkout।</p><button type="button" data-template="video">এই ডিজাইন ব্যবহার করুন</button></div>
        </article>
    </div>
</dialog>

<dialog id="cpb-css-dialog" class="cpb-dialog cpb-code-dialog">
    <div class="cpb-dialog-head">
        <div><h3>Custom CSS</h3><small>শুধু এই landing page-এর জন্য অতিরিক্ত CSS</small></div>
        <button type="button" class="cpb-dialog-close" data-close-dialog><i class="fe-x"></i></button>
    </div>
    <div class="cpb-code-body">
        <textarea id="cpb-custom-css" spellcheck="false" placeholder=".cpb-published-page .your-class { color: #111; }"></textarea>
    </div>
    <div class="cpb-dialog-footer">
        <button type="button" data-close-dialog>বাতিল</button>
        <button id="cpb-apply-css" type="button" class="is-primary">CSS প্রয়োগ করুন</button>
    </div>
</dialog>

<div id="cpb-toast-stack" class="cpb-toast-stack" aria-live="polite"></div>

<script id="cpb-initial-design" type="application/json">{!! json_encode($campaign->page_design, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script id="cpb-builder-context" type="application/json">{!! json_encode($builderContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script src="{{ asset('public/backEnd/assets/js/campaign-page-builder.js') }}"></script>
</body>
</html>
