/**
 * Campaign Visual Builder
 * A dependency-free section/grid/widget editor with responsive preview, history,
 * templates, dynamic storefront widgets and secure server persistence.
 */
(function () {
    'use strict';

    const root = document.getElementById('campaign-page-builder');
    if (!root) return;

    const canvas = document.getElementById('cpb-canvas');
    const canvasShell = document.getElementById('cpb-canvas-shell');
    const inspector = document.getElementById('cpb-inspector');
    const inspectorBody = document.getElementById('cpb-inspector-body');
    const inspectorTitle = document.getElementById('cpb-inspector-title');
    const inspectorKicker = document.getElementById('cpb-inspector-kicker');
    const layers = document.getElementById('cpb-layers');
    const palette = document.getElementById('cpb-palette');
    const saveState = document.getElementById('cpb-save-state');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const context = parseJsonScript('cpb-builder-context', {}) || {};
    const storageKey = 'cpb-draft-' + root.dataset.campaignId;
    let model;
    let selected = null;
    let history = [];
    let historyIndex = -1;
    let dirty = false;
    let saving = false;
    let autosaveTimer = null;
    let zoom = 100;
    let dragPayload = null;
    let lastContentInput = null;

    const CATEGORIES = {
        conversion: 'Conversion blocks',
        content: 'Content',
        media: 'Media & dynamic',
        layout: 'Layout'
    };

    const TOKENS = [
        '{{campaign.name}}', '{{campaign.deadline}}', '{{campaign.description}}',
        '{{product.name}}', '{{product.price}}', '{{product.old_price}}',
        '{{product.discount}}', '{{contact.phone}}', '{{contact.whatsapp}}'
    ];

    const WIDGETS = {
        announcement: {
            label: 'Announcement', icon: 'fe-volume-2', category: 'conversion',
            defaults: { text: '🔥 আজকের বিশেষ অফার — স্টক শেষ হওয়ার আগেই অর্ডার করুন' },
            fields: [{ key: 'text', label: 'ঘোষণার লেখা', type: 'textarea' }],
            baseStyle: { background: '#172033', color: '#ffffff', padding: '11px 18px', textAlign: 'center' },
            render: (w, editor) => `<div class="cpb-announcement">${editable(w, 'text', editor)}</div>`
        },
        hero: {
            label: 'Hero section', icon: 'fe-star', category: 'conversion', imageField: 'image',
            defaults: {
                badge: '✓ ক্যাশ অন ডেলিভারি', title: context.campaign?.name || 'আপনার প্রোডাক্টের শক্তিশালী শিরোনাম',
                description: context.campaign?.description || 'কেন এই প্রোডাক্টটি আপনার কাস্টমারের জীবন সহজ করবে—এক লাইনে স্পষ্ট করুন।',
                button: 'এখনই অর্ডার করুন', link: '#order_form', image: context.campaign?.image || context.product?.image || ''
            },
            fields: [
                { key: 'badge', label: 'ব্যাজ' }, { key: 'title', label: 'প্রধান শিরোনাম', type: 'textarea' },
                { key: 'description', label: 'সাব-হেডলাইন', type: 'textarea' },
                { key: 'button', label: 'বাটনের লেখা' }, { key: 'link', label: 'বাটন লিংক' }
            ],
            baseStyle: { background: '#f0fdf4', color: '#14532d', padding: '72px 28px' },
            render: (w, editor) => {
                const image = safeUrl(value(w, 'image'));
                return `<div class="cpb-hero-layout">
                    <div class="cpb-hero-content">
                        <span class="cpb-badge" style="background:#dcfce7;color:#166534">${editable(w, 'badge', editor)}</span>
                        <h1 class="cpb-hero-title">${editable(w, 'title', editor)}</h1>
                        <p class="cpb-hero-copy">${editable(w, 'description', editor)}</p>
                        <a class="cpb-btn" href="${attr(safeHref(value(w, 'link', '#order_form')))}" style="background:#16a34a;color:#fff">${editable(w, 'button', editor)}</a>
                    </div>
                    <div class="cpb-hero-media">${image
                        ? `<img src="${attr(image)}" alt="${attr(strip(value(w, 'title')))}">`
                        : `<div class="cpb-image-placeholder"><div><i class="fe-image"></i><br>Hero image আপলোড করুন</div></div>`}
                    </div>
                </div>`;
            }
        },
        heading: {
            label: 'Heading', icon: 'fe-type', category: 'content',
            defaults: { text: 'আপনার আকর্ষণীয় শিরোনাম', tag: 'h2' },
            fields: [{ key: 'text', label: 'শিরোনাম', type: 'textarea' }, { key: 'tag', label: 'HTML tag', type: 'select', options: ['h1', 'h2', 'h3', 'h4'] }],
            baseStyle: { color: '#172033', fontSize: '36px', fontWeight: '800', textAlign: 'center', padding: '8px' },
            render: (w, editor) => {
                const tag = ['h1', 'h2', 'h3', 'h4'].includes(value(w, 'tag')) ? value(w, 'tag') : 'h2';
                return `<${tag} style="margin:0;line-height:1.18">${editable(w, 'text', editor)}</${tag}>`;
            }
        },
        text: {
            label: 'Rich text', icon: 'fe-align-left', category: 'content',
            defaults: { text: 'আপনার পণ্য বা অফার সম্পর্কে বিস্তারিত লিখুন। গুরুত্বপূর্ণ সুবিধাগুলো সহজ ভাষায় তুলে ধরুন।' },
            fields: [{ key: 'text', label: 'লেখা', type: 'textarea' }],
            baseStyle: { color: '#526071', fontSize: '17px', padding: '8px', textAlign: 'left' },
            render: (w, editor) => `<div style="line-height:1.75">${editable(w, 'text', editor)}</div>`
        },
        image: {
            label: 'Image', icon: 'fe-image', category: 'media', imageField: 'image',
            defaults: { image: '', alt: 'Campaign image', caption: '' },
            fields: [{ key: 'alt', label: 'Alt text' }, { key: 'caption', label: 'ক্যাপশন' }],
            baseStyle: { padding: '8px', textAlign: 'center' },
            render: (w, editor) => {
                const image = safeUrl(value(w, 'image'));
                return `<figure style="margin:0">${image
                    ? `<img src="${attr(image)}" alt="${attr(strip(value(w, 'alt')))}" style="display:block;width:100%;height:auto;border-radius:12px">`
                    : `<div class="cpb-image-placeholder"><div><i class="fe-upload-cloud"></i><br>ইমেজ আপলোড করুন</div></div>`}
                    ${value(w, 'caption') ? `<figcaption style="padding-top:8px;color:#7b8493;font-size:12px">${editable(w, 'caption', editor)}</figcaption>` : ''}
                </figure>`;
            }
        },
        button: {
            label: 'CTA button', icon: 'fe-mouse-pointer', category: 'conversion',
            defaults: { text: 'অর্ডার করতে ক্লিক করুন', link: '#order_form' },
            fields: [{ key: 'text', label: 'বাটনের লেখা' }, { key: 'link', label: 'লিংক' }],
            baseStyle: { background: 'transparent', color: '#ffffff', padding: '12px', textAlign: 'center' },
            render: (w, editor) => `<a class="cpb-btn" href="${attr(safeHref(value(w, 'link', '#order_form')))}" style="background:#16a34a;color:#fff">${editable(w, 'text', editor)}</a>`
        },
        benefits: {
            label: 'Benefits', icon: 'fe-check-circle', category: 'conversion',
            defaults: {
                title1: 'প্রিমিয়াম কোয়ালিটি', text1: 'সেরা উপকরণ ও যত্নে তৈরি।', icon1: '✨',
                title2: 'দ্রুত ডেলিভারি', text2: 'সারাদেশে নিরাপদ ডেলিভারি।', icon2: '🚚',
                title3: 'সহজ অর্ডার', text3: 'ফর্ম পূরণ করেই অর্ডার করুন।', icon3: '✓'
            },
            fields: [
                { key: 'icon1', label: 'আইকন ১' }, { key: 'title1', label: 'সুবিধা ১' }, { key: 'text1', label: 'বর্ণনা ১', type: 'textarea' },
                { key: 'icon2', label: 'আইকন ২' }, { key: 'title2', label: 'সুবিধা ২' }, { key: 'text2', label: 'বর্ণনা ২', type: 'textarea' },
                { key: 'icon3', label: 'আইকন ৩' }, { key: 'title3', label: 'সুবিধা ৩' }, { key: 'text3', label: 'বর্ণনা ৩', type: 'textarea' }
            ],
            baseStyle: { background: 'transparent', color: '#172033', padding: '10px' },
            render: (w, editor) => `<div class="cpb-benefit-grid">${[1,2,3].map(i => `<article class="cpb-benefit-card"><div class="cpb-benefit-icon">${editable(w, 'icon'+i, editor)}</div><h3>${editable(w, 'title'+i, editor)}</h3><p style="margin:0;color:#697487">${editable(w, 'text'+i, editor)}</p></article>`).join('')}</div>`
        },
        products: {
            label: 'Product cards', icon: 'fe-shopping-bag', category: 'conversion',
            defaults: { title: 'আপনার পছন্দের প্যাকেজটি বেছে নিন', button: 'অর্ডার করুন' },
            fields: [{ key: 'title', label: 'সেকশনের শিরোনাম' }, { key: 'button', label: 'বাটনের লেখা' }],
            baseStyle: { background: 'transparent', color: '#172033', padding: '10px' },
            render: (w, editor) => `<div><h2 style="text-align:center;margin:0 0 24px">${editable(w, 'title', editor)}</h2><div class="cpb-product-grid" data-cpb-dynamic="products" data-button-label="${attr(strip(value(w, 'button')))}">${productPreview(w)}</div></div>`
        },
        offer: {
            label: 'Price offer', icon: 'fe-tag', category: 'conversion',
            defaults: { eyebrow: 'আজকের বিশেষ মূল্য', price: '{{product.price}}', oldPrice: '{{product.old_price}}', note: 'সীমিত সময়ের অফার', button: 'অফারটি নিন' },
            fields: [
                { key: 'eyebrow', label: 'অফার লেবেল' }, { key: 'price', label: 'বর্তমান মূল্য' },
                { key: 'oldPrice', label: 'পুরনো মূল্য' }, { key: 'note', label: 'নোট' }, { key: 'button', label: 'বাটন' }
            ],
            baseStyle: { background: 'transparent', color: '#172033', padding: '10px' },
            render: (w, editor) => `<div class="cpb-offer-box"><strong style="color:#b45309">${editable(w, 'eyebrow', editor)}</strong><div style="margin:8px 0"><span class="cpb-price">৳${editable(w, 'price', editor)}</span><span class="cpb-old-price">৳${editable(w, 'oldPrice', editor)}</span></div><p>${editable(w, 'note', editor)}</p><a class="cpb-btn" href="#order_form" style="background:#ea580c;color:#fff">${editable(w, 'button', editor)}</a></div>`
        },
        bundle: {
            label: 'Bundle offer', icon: 'fe-package', category: 'conversion',
            defaults: { one: '১ পিস', onePrice: '৳{{product.price}}', two: '২ পিস — জনপ্রিয়', twoPrice: 'বিশেষ ছাড়', three: '৩ পিস — সেরা মূল্য', threePrice: 'ফ্রি ডেলিভারি' },
            fields: [
                { key: 'one', label: 'প্যাকেজ ১' }, { key: 'onePrice', label: 'মূল্য ১' },
                { key: 'two', label: 'প্যাকেজ ২' }, { key: 'twoPrice', label: 'মূল্য ২' },
                { key: 'three', label: 'প্যাকেজ ৩' }, { key: 'threePrice', label: 'মূল্য ৩' }
            ],
            baseStyle: { padding: '10px', color: '#172033' },
            render: (w, editor) => `<div class="cpb-bundle-grid">${[['one','onePrice'],['two','twoPrice'],['three','threePrice']].map((keys, i) => `<article class="cpb-bundle-card" style="${i===1?'border:2px solid #16a34a;transform:translateY(-4px)':''}"><div style="font-size:12px;color:#16a34a;font-weight:800">${i===1?'MOST POPULAR':''}</div><h3>${editable(w, keys[0], editor)}</h3><strong style="font-size:21px">${editable(w, keys[1], editor)}</strong></article>`).join('')}</div>`
        },
        countdown: {
            label: 'Countdown', icon: 'fe-clock', category: 'conversion',
            defaults: { title: 'অফার শেষ হতে বাকি', deadline: context.campaign?.deadline || '{{campaign.deadline}}' },
            fields: [{ key: 'title', label: 'শিরোনাম' }, { key: 'deadline', label: 'Deadline', type: 'datetime-local' }],
            baseStyle: { background: '#f8fafc', color: '#172033', padding: '24px', textAlign: 'center' },
            render: (w, editor) => `<div data-cpb-countdown="${attr(strip(value(w, 'deadline')))}"><h3>${editable(w, 'title', editor)}</h3><div class="cpb-countdown"><div class="cpb-countdown-unit"><strong data-days>00</strong><small>দিন</small></div><div class="cpb-countdown-unit"><strong data-hours>00</strong><small>ঘণ্টা</small></div><div class="cpb-countdown-unit"><strong data-minutes>00</strong><small>মিনিট</small></div><div class="cpb-countdown-unit"><strong data-seconds>00</strong><small>সেকেন্ড</small></div></div></div>`
        },
        testimonials: {
            label: 'Testimonials', icon: 'fe-message-circle', category: 'conversion',
            defaults: {
                quote1: 'প্রোডাক্টের কোয়ালিটি সত্যিই দারুণ।', name1: '— সন্তুষ্ট কাস্টমার',
                quote2: 'সময়মতো ডেলিভারি পেয়েছি, ধন্যবাদ।', name2: '— ভেরিফাইড ক্রেতা',
                quote3: 'আবারও অর্ডার করব ইনশাআল্লাহ।', name3: '— নিয়মিত কাস্টমার'
            },
            fields: [
                { key: 'quote1', label: 'রিভিউ ১', type: 'textarea' }, { key: 'name1', label: 'নাম ১' },
                { key: 'quote2', label: 'রিভিউ ২', type: 'textarea' }, { key: 'name2', label: 'নাম ২' },
                { key: 'quote3', label: 'রিভিউ ৩', type: 'textarea' }, { key: 'name3', label: 'নাম ৩' }
            ],
            baseStyle: { padding: '10px', color: '#172033' },
            render: (w, editor) => `<div class="cpb-testimonial-grid">${[1,2,3].map(i => `<article class="cpb-testimonial-card"><div style="color:#f59e0b;margin-bottom:10px">★★★★★</div><p>“${editable(w,'quote'+i,editor)}”</p><strong>${editable(w,'name'+i,editor)}</strong></article>`).join('')}</div>`
        },
        reviews: {
            label: 'Review gallery', icon: 'fe-camera', category: 'media',
            defaults: { title: 'আমাদের কাস্টমারদের বাস্তব অভিজ্ঞতা' },
            fields: [{ key: 'title', label: 'শিরোনাম' }],
            baseStyle: { padding: '10px', color: '#172033' },
            render: (w, editor) => `<div><h2 style="text-align:center;margin-bottom:24px">${editable(w, 'title', editor)}</h2><div class="cpb-review-grid" data-cpb-dynamic="reviews">${reviewPreview()}</div></div>`
        },
        trust: {
            label: 'Trust & guarantee', icon: 'fe-shield', category: 'conversion',
            defaults: { title1: 'কোয়ালিটি গ্যারান্টি', text1: 'মান নিয়ে নিশ্চিন্ত থাকুন।', title2: 'নিরাপদ ডেলিভারি', text2: 'সারাদেশে যত্নসহকারে পৌঁছে দিই।', title3: 'সাপোর্ট', text3: 'প্রয়োজনে আমাদের সাথে কথা বলুন।' },
            fields: [
                { key: 'title1', label: 'ট্রাস্ট ১' }, { key: 'text1', label: 'বর্ণনা ১' },
                { key: 'title2', label: 'ট্রাস্ট ২' }, { key: 'text2', label: 'বর্ণনা ২' },
                { key: 'title3', label: 'ট্রাস্ট ৩' }, { key: 'text3', label: 'বর্ণনা ৩' }
            ],
            baseStyle: { padding: '10px', color: '#172033' },
            render: (w, editor) => `<div class="cpb-trust-grid">${['🛡️','🚚','☎️'].map((icon,i) => `<article class="cpb-trust-card" style="text-align:center"><div class="cpb-trust-icon" style="margin-left:auto;margin-right:auto">${icon}</div><h3>${editable(w,'title'+(i+1),editor)}</h3><p style="margin:0;color:#697487">${editable(w,'text'+(i+1),editor)}</p></article>`).join('')}</div>`
        },
        faq: {
            label: 'FAQ', icon: 'fe-help-circle', category: 'conversion',
            defaults: { q1: 'কীভাবে অর্ডার করব?', a1: 'নিচের ফর্মে নাম, ফোন ও ঠিকানা দিয়ে অর্ডার কনফার্ম করুন।', q2: 'ডেলিভারি কত দিনে হবে?', a2: 'এলাকা অনুযায়ী সাধারণত ২–৫ কার্যদিবস সময় লাগে।', q3: 'পেমেন্ট কীভাবে করব?', a3: 'ক্যাশ অন ডেলিভারি বা সক্রিয় অনলাইন পেমেন্ট পদ্ধতি ব্যবহার করতে পারবেন।' },
            fields: [
                { key: 'q1', label: 'প্রশ্ন ১' }, { key: 'a1', label: 'উত্তর ১', type: 'textarea' },
                { key: 'q2', label: 'প্রশ্ন ২' }, { key: 'a2', label: 'উত্তর ২', type: 'textarea' },
                { key: 'q3', label: 'প্রশ্ন ৩' }, { key: 'a3', label: 'উত্তর ৩', type: 'textarea' }
            ],
            baseStyle: { padding: '10px', color: '#172033' },
            render: (w, editor) => `<div class="cpb-faq">${[1,2,3].map(i => `<details ${i===1?'open':''}><summary>${editable(w,'q'+i,editor)}</summary><p>${editable(w,'a'+i,editor)}</p></details>`).join('')}</div>`
        },
        video: {
            label: 'YouTube video', icon: 'fe-video', category: 'media',
            defaults: { url: '', title: 'ভিডিওতে বিস্তারিত দেখুন' },
            fields: [{ key: 'title', label: 'শিরোনাম' }, { key: 'url', label: 'YouTube URL / ID' }],
            baseStyle: { padding: '10px', color: '#172033' },
            render: (w, editor) => `<div><h2 style="text-align:center">${editable(w,'title',editor)}</h2><div class="cpb-video-placeholder" data-cpb-youtube="${attr(strip(value(w,'url')))}"><div><i class="fe-play-circle"></i><strong>${value(w,'url') ? 'ভিডিও প্রিভিউ storefront-এ চলবে' : 'YouTube URL যোগ করুন'}</strong><br><small>${text(value(w,'url'))}</small></div></div></div>`
        },
        urgency: {
            label: 'Urgency / stock', icon: 'fe-alert-circle', category: 'conversion',
            defaults: { text: '⚡ দ্রুত অর্ডার করুন — সীমিত স্টক বাকি আছে!' },
            fields: [{ key: 'text', label: 'জরুরি বার্তা' }],
            baseStyle: { padding: '8px', color: '#991b1b' },
            render: (w, editor) => `<div class="cpb-urgency">${editable(w,'text',editor)}</div>`
        },
        cta: {
            label: 'CTA banner', icon: 'fe-zap', category: 'conversion',
            defaults: { title: 'সিদ্ধান্ত নিতে প্রস্তুত?', text: 'এখনই অর্ডার করুন এবং বিশেষ অফারটি নিশ্চিত করুন।', button: 'অর্ডার করুন', link: '#order_form' },
            fields: [{ key: 'title', label: 'শিরোনাম' }, { key: 'text', label: 'বর্ণনা' }, { key: 'button', label: 'বাটন' }, { key: 'link', label: 'লিংক' }],
            baseStyle: { background: '#172033', color: '#ffffff', padding: '42px 25px', textAlign: 'center', borderRadius: '16px' },
            render: (w, editor) => `<div><h2 style="font-size:34px;margin-bottom:8px">${editable(w,'title',editor)}</h2><p style="color:#d4d9e3">${editable(w,'text',editor)}</p><a class="cpb-btn" href="${attr(safeHref(value(w,'link','#order_form')))}" style="background:#22c55e;color:#fff">${editable(w,'button',editor)}</a></div>`
        },
        checkout: {
            label: 'Order form', icon: 'fe-credit-card', category: 'media',
            defaults: { title: 'অর্ডার করতে তথ্য দিন', subtitle: 'পণ্য নির্বাচন, অর্ডার সারাংশ ও checkout form এখানে দেখাবে।' },
            fields: [{ key: 'title', label: 'শিরোনাম' }, { key: 'subtitle', label: 'সাবটাইটেল' }],
            baseStyle: { padding: '10px', color: '#172033' },
            render: (w, editor) => `<div><h2 style="text-align:center;margin-bottom:18px">${editable(w,'title',editor)}</h2><div class="cpb-checkout-placeholder" data-cpb-dynamic="checkout" data-subtitle="${attr(strip(value(w,'subtitle')))}"><div><i class="fe-shopping-cart"></i><strong>Dynamic checkout & order form</strong><br><small>${editable(w,'subtitle',editor)}</small></div></div></div>`
        },
        divider: {
            label: 'Divider', icon: 'fe-minus', category: 'layout', defaults: {}, fields: [],
            baseStyle: { padding: '14px 8px' },
            render: () => '<hr style="height:1px;margin:0;border:0;background:#dfe4ec">'
        },
        spacer: {
            label: 'Spacer', icon: 'fe-more-horizontal', category: 'layout',
            defaults: { height: '50' }, fields: [{ key: 'height', label: 'উচ্চতা (px)', type: 'number' }],
            baseStyle: { padding: '0' },
            render: w => `<div style="height:${number(value(w,'height'),50,10,500)}px"></div>`
        },
        custom_html: {
            label: 'Custom HTML', icon: 'fe-code', category: 'layout',
            defaults: { html: '<div style="padding:20px;text-align:center">আপনার custom HTML</div>' },
            fields: [{ key: 'html', label: 'HTML', type: 'code' }],
            baseStyle: { padding: '8px' },
            render: w => cleanRich(value(w,'html'))
        },
        footer: {
            label: 'Footer', icon: 'fe-layout', category: 'layout',
            defaults: { text: '© সর্বস্বত্ব সংরক্ষিত', contact: 'প্রয়োজনে কল করুন: {{contact.phone}}' },
            fields: [{ key: 'text', label: 'কপিরাইট' }, { key: 'contact', label: 'যোগাযোগ' }],
            baseStyle: { background: '#111827', color: '#d1d5db', padding: '25px', textAlign: 'center' },
            render: (w, editor) => `<footer><div>${editable(w,'text',editor)}</div><small>${editable(w,'contact',editor)}</small></footer>`
        }
    };

    const PUBLISHED_CSS = `
.cpb-published-page{overflow:hidden;color:#172033;font-family:Arial,"Noto Sans Bengali",sans-serif;line-height:1.55;background:#fff}
.cpb-published-page *{box-sizing:border-box}.cpb-published-page img{max-width:100%}.cpb-published-page h1,.cpb-published-page h2,.cpb-published-page h3,.cpb-published-page p{margin-top:0}
.cpb-published-page .cpb-section-grid{display:grid}.cpb-published-page .cpb-hero-layout{display:grid;grid-template-columns:1.05fr .95fr;align-items:center;gap:40px}
.cpb-published-page .cpb-badge{display:inline-flex;margin-bottom:14px;padding:6px 12px;border-radius:999px;font-size:12px;font-weight:800}.cpb-published-page .cpb-hero-title{margin:0 0 16px;font-size:clamp(34px,5vw,62px);line-height:1.08}.cpb-published-page .cpb-hero-copy{margin-bottom:23px;color:#526071;font-size:18px}.cpb-published-page .cpb-hero-media img{display:block;width:100%;max-height:520px;border-radius:22px;object-fit:cover;box-shadow:0 24px 50px rgba(25,35,52,.18)}
.cpb-published-page .cpb-btn{display:inline-flex;padding:14px 26px;border:0;border-radius:10px;align-items:center;justify-content:center;gap:8px;text-decoration:none;font-weight:800;cursor:pointer}.cpb-published-page .cpb-announcement{padding:10px 18px;text-align:center;font-weight:700}
.cpb-published-page .cpb-benefit-grid,.cpb-published-page .cpb-testimonial-grid,.cpb-published-page .cpb-trust-grid,.cpb-published-page .cpb-bundle-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.cpb-published-page .cpb-benefit-card,.cpb-published-page .cpb-testimonial-card,.cpb-published-page .cpb-trust-card,.cpb-published-page .cpb-bundle-card{padding:22px;border:1px solid #e8ebf0;border-radius:15px;background:#fff;box-shadow:0 7px 22px rgba(27,36,53,.06)}.cpb-published-page .cpb-benefit-icon,.cpb-published-page .cpb-trust-icon{display:grid;width:44px;height:44px;margin-bottom:14px;border-radius:12px;place-items:center;background:#ecfdf3;font-size:23px}
.cpb-published-page .cpb-offer-box{padding:30px;border:2px dashed #f59e0b;border-radius:18px;background:#fffbeb;text-align:center}.cpb-published-page .cpb-price{font-size:38px;font-weight:900}.cpb-published-page .cpb-old-price{margin-left:8px;color:#94a3b8;font-size:21px;text-decoration:line-through}.cpb-published-page .cpb-countdown{display:flex;flex-wrap:wrap;justify-content:center;gap:10px}.cpb-published-page .cpb-countdown-unit{min-width:72px;padding:13px 10px;border-radius:12px;color:#fff;background:#172033;text-align:center}.cpb-published-page .cpb-countdown-unit strong{display:block;font-size:25px;line-height:1}.cpb-published-page .cpb-countdown-unit small{color:#bcc3d1;font-size:10px;text-transform:uppercase}
.cpb-published-page .cpb-product-grid,.cpb-published-page .cpb-review-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.cpb-published-page .cpb-product-preview,.cpb-published-page .cpb-review-preview{overflow:hidden;border:1px solid #e7ebf1;border-radius:14px;background:#fff;box-shadow:0 8px 24px rgba(27,36,53,.07)}.cpb-published-page .cpb-product-preview img,.cpb-published-page .cpb-review-preview img{display:block;width:100%;height:210px;object-fit:cover}.cpb-published-page .cpb-product-preview-body{padding:15px}
.cpb-published-page .cpb-video-frame{position:relative;height:0;padding-bottom:56.25%;overflow:hidden;border-radius:14px;background:#111}.cpb-published-page .cpb-video-frame iframe{position:absolute;inset:0;width:100%;height:100%;border:0}.cpb-published-page .cpb-faq details{margin-bottom:10px;border:1px solid #e5e9f0;border-radius:10px;background:#fff}.cpb-published-page .cpb-faq summary{padding:15px 17px;cursor:pointer;font-weight:750}.cpb-published-page .cpb-faq details p{padding:0 17px 15px;color:#697487}.cpb-published-page .cpb-urgency{display:flex;padding:14px 18px;border:1px solid #fecaca;border-radius:11px;align-items:center;justify-content:center;gap:9px;color:#991b1b;background:#fef2f2;font-weight:750;text-align:center}
@media(max-width:767px){.cpb-published-page .cpb-hero-layout,.cpb-published-page .cpb-benefit-grid,.cpb-published-page .cpb-testimonial-grid,.cpb-published-page .cpb-trust-grid,.cpb-published-page .cpb-bundle-grid,.cpb-published-page .cpb-product-grid,.cpb-published-page .cpb-review-grid{grid-template-columns:1fr!important}.cpb-published-page .cpb-section-grid{grid-template-columns:1fr!important}.cpb-published-page .cpb-hero-title{font-size:36px}.cpb-published-page .cpb-hero-layout{gap:24px}.cpb-published-page section{background-attachment:scroll!important}}
`;

    function parseJsonScript(id, fallback) {
        try { return JSON.parse(document.getElementById(id)?.textContent || 'null') ?? fallback; }
        catch (_) { return fallback; }
    }

    function uid(prefix) {
        return (prefix || 'cpb') + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
    }

    function text(value) {
        return String(value ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[ch]));
    }
    const attr = text;
    function strip(value) { return String(value ?? '').replace(/<[^>]*>/g, '').trim(); }

    function cleanRich(value) {
        const template = document.createElement('template');
        template.innerHTML = String(value ?? '');
        template.content.querySelectorAll('script,style,iframe,object,embed,form,input,textarea,select,link,meta,svg,math').forEach(el => el.remove());
        template.content.querySelectorAll('*').forEach(el => {
            Array.from(el.attributes).forEach(attribute => {
                if (/^on/i.test(attribute.name) || ['srcdoc', 'formaction', 'xlink:href'].includes(attribute.name.toLowerCase())) el.removeAttribute(attribute.name);
                if (['href', 'src'].includes(attribute.name.toLowerCase()) && /^(?:javascript|vbscript|data):/i.test(attribute.value.replace(/\s/g, ''))) el.removeAttribute(attribute.name);
            });
        });
        return template.innerHTML;
    }

    function safeUrl(url) {
        const normalized = String(url ?? '').trim();
        if (!normalized || /^(?:javascript|vbscript|data):/i.test(normalized.replace(/\s/g, ''))) return '';
        return normalized;
    }

    function safeHref(url) {
        const normalized = safeUrl(url);
        return normalized || '#order_form';
    }

    function number(value, fallback, min, max) {
        const parsed = Number.parseFloat(value);
        return Math.min(max, Math.max(min, Number.isFinite(parsed) ? parsed : fallback));
    }

    function value(widget, key, fallback) {
        const definition = WIDGETS[widget.type] || {};
        const defaultValue = definition.defaults?.[key] ?? fallback ?? '';
        return widget.content?.[key] ?? defaultValue;
    }

    function editable(widget, key, editor) {
        const content = cleanRich(value(widget, key));
        return editor ? `<span contenteditable="true" spellcheck="true" data-cpb-field="${attr(key)}">${content}</span>` : content;
    }

    function productPreview(widget) {
        const products = Array.isArray(context.products) && context.products.length ? context.products.slice(0, 3) : [{ name: 'আপনার প্রোডাক্ট', price: 0, old_price: 0, image: '' }];
        return products.map(product => `<article class="cpb-product-preview" data-product-id="${attr(product.id || '')}">
            ${safeUrl(product.image) ? `<img src="${attr(product.image)}" alt="${attr(product.name)}">` : '<div class="cpb-image-placeholder">Product image</div>'}
            <div class="cpb-product-preview-body"><h3 style="margin-bottom:6px">${text(product.name)}</h3><div><strong style="color:#16a34a;font-size:20px">৳${text(product.price)}</strong>${Number(product.old_price)>Number(product.price)?` <del style="color:#94a3b8">৳${text(product.old_price)}</del>`:''}</div><a href="#order_form" class="cpb-btn" style="width:100%;margin-top:12px;background:#16a34a;color:#fff">${text(value(widget,'button','অর্ডার করুন'))}</a></div>
        </article>`).join('');
    }

    function reviewPreview() {
        const reviews = Array.isArray(context.reviews) ? context.reviews.slice(0, 6) : [];
        if (!reviews.length) return '<div class="cpb-image-placeholder" style="grid-column:1/-1">Legacy campaign edit থেকে review images যোগ করুন</div>';
        return reviews.map(review => `<article class="cpb-review-preview"><img src="${attr(safeUrl(review.image))}" alt="Customer review"></article>`).join('');
    }

    function defaultStyle(type) {
        return Object.assign({ background: 'transparent', color: '#172033', fontSize: '16px', fontWeight: '400', textAlign: 'left', padding: '0', margin: '0', borderRadius: '0px', boxShadow: 'none' }, WIDGETS[type]?.baseStyle || {});
    }

    function newWidget(type, overrides) {
        const definition = WIDGETS[type];
        return {
            id: uid('widget'), type,
            content: Object.assign({}, definition?.defaults || {}, overrides?.content || {}),
            style: Object.assign({}, defaultStyle(type), overrides?.style || {})
        };
    }

    function newSection(widgets, overrides) {
        return {
            id: uid('section'), label: overrides?.label || 'Section',
            settings: Object.assign({
                background: '#ffffff', backgroundImage: '', color: '#172033',
                paddingTop: 56, paddingBottom: 56, paddingX: 24,
                maxWidth: 1140, columns: 1, gap: 18, borderRadius: 0
            }, overrides?.settings || {}),
            widgets: widgets || []
        };
    }

    function emptyModel() {
        return {
            version: 2,
            settings: { background: '#ffffff', fontFamily: 'Arial, "Noto Sans Bengali", sans-serif', customCss: '' },
            sections: []
        };
    }

    function templateModel(name) {
        const result = emptyModel();
        if (name === 'clean') {
            result.sections = [
                newSection([newWidget('hero')], { label: 'Hero', settings: { background: '#f0fdf4', paddingTop: 65, paddingBottom: 65 } }),
                newSection([newWidget('heading', { content: { text: 'কেন এই প্রোডাক্টটি বেছে নেবেন?' } }), newWidget('benefits')], { label: 'Benefits' }),
                newSection([newWidget('products')], { label: 'Products', settings: { background: '#f8fafc' } }),
                newSection([newWidget('trust')], { label: 'Trust' }),
                newSection([newWidget('checkout')], { label: 'Checkout', settings: { background: '#f0fdf4' } }),
                newSection([newWidget('footer')], { label: 'Footer', settings: { background: '#111827', paddingTop: 0, paddingBottom: 0, paddingX: 0 } })
            ];
        } else if (name === 'video') {
            result.sections = [
                newSection([newWidget('announcement'), newWidget('countdown')], { label: 'Urgency', settings: { background: '#172033', paddingTop: 0, paddingBottom: 12, paddingX: 0 } }),
                newSection([newWidget('hero')], { label: 'Hero', settings: { background: '#fff7ed' } }),
                newSection([newWidget('video')], { label: 'Product video' }),
                newSection([newWidget('benefits'), newWidget('offer')], { label: 'Benefits and offer', settings: { background: '#f8fafc' } }),
                newSection([newWidget('products'), newWidget('testimonials')], { label: 'Products and proof' }),
                newSection([newWidget('cta')], { label: 'CTA', settings: { paddingTop: 25, paddingBottom: 25 } }),
                newSection([newWidget('checkout')], { label: 'Checkout', settings: { background: '#fff7ed' } }),
                newSection([newWidget('footer')], { label: 'Footer', settings: { background: '#111827', paddingTop: 0, paddingBottom: 0, paddingX: 0 } })
            ];
        } else {
            result.sections = [
                newSection([newWidget('announcement'), newWidget('countdown')], { label: 'Announcement', settings: { background: '#172033', paddingTop: 0, paddingBottom: 12, paddingX: 0 } }),
                newSection([newWidget('hero')], { label: 'Hero', settings: { background: '#f0fdf4', paddingTop: 68, paddingBottom: 68 } }),
                newSection([newWidget('benefits')], { label: 'Core benefits', settings: { background: '#ffffff' } }),
                newSection([newWidget('heading', { content: { text: 'বিশেষ অফারটি দেখে নিন' } }), newWidget('offer'), newWidget('bundle')], { label: 'Offer', settings: { background: '#fffbeb' } }),
                newSection([newWidget('products')], { label: 'Products', settings: { background: '#f8fafc' } }),
                newSection([newWidget('heading', { content: { text: 'কাস্টমাররা কেন আমাদের বিশ্বাস করেন' } }), newWidget('testimonials'), newWidget('reviews')], { label: 'Social proof' }),
                newSection([newWidget('trust'), newWidget('faq')], { label: 'Trust and FAQ', settings: { background: '#f8fafc' } }),
                newSection([newWidget('urgency'), newWidget('cta')], { label: 'Final CTA' }),
                newSection([newWidget('checkout')], { label: 'Checkout', settings: { background: '#f0fdf4' } }),
                newSection([newWidget('footer')], { label: 'Footer', settings: { background: '#111827', paddingTop: 0, paddingBottom: 0, paddingX: 0 } })
            ];
        }
        return result;
    }

    function normalizeModel(input) {
        if (!input) return null;
        if (typeof input === 'string') {
            try { input = JSON.parse(input); } catch (_) { return null; }
        }
        if (input.version === 2 && Array.isArray(input.sections)) {
            input.settings = Object.assign(emptyModel().settings, input.settings || {});
            input.sections = input.sections.map(section => ({
                id: section.id || uid('section'), label: section.label || 'Section',
                settings: Object.assign(newSection().settings, section.settings || {}),
                widgets: (section.widgets || []).filter(w => WIDGETS[w.type]).map(w => ({
                    id: w.id || uid('widget'), type: w.type,
                    content: Object.assign({}, WIDGETS[w.type].defaults || {}, w.content || {}),
                    style: Object.assign({}, defaultStyle(w.type), w.style || {})
                }))
            }));
            return input;
        }
        // Compatibility with the first unfinished builder JSON (array of section/children records).
        if (Array.isArray(input)) {
            const legacy = emptyModel();
            const typeMap = { order_placeholder: 'checkout', features: 'benefits', icon_box: 'benefits', accordion: 'faq' };
            input.forEach(item => {
                if (!item || !item.type) return;
                if (item.type === 'section') {
                    const widgets = (item.children || []).map(child => {
                        const type = typeMap[child.type] || child.type;
                        return WIDGETS[type] ? newWidget(type, { content: child.data || {}, style: child.style || {} }) : null;
                    }).filter(Boolean);
                    legacy.sections.push(newSection(widgets, { settings: legacySectionSettings(item.style || {}) }));
                } else {
                    const type = typeMap[item.type] || item.type;
                    if (!WIDGETS[type]) return;
                    if (!legacy.sections.length) legacy.sections.push(newSection([]));
                    legacy.sections[legacy.sections.length - 1].widgets.push(newWidget(type, { content: item.data || {}, style: item.style || {} }));
                }
            });
            return legacy;
        }
        return null;
    }

    function legacySectionSettings(style) {
        const padding = String(style.padding || '56px 24px').match(/\d+/g) || [];
        return {
            background: style.background || '#ffffff',
            paddingTop: Number(padding[0] || 56), paddingBottom: Number(padding[0] || 56),
            paddingX: Number(padding[1] || 24), borderRadius: Number.parseInt(style['border-radius'] || 0, 10)
        };
    }

    function sectionStyle(section) {
        const s = section.settings;
        const bgImage = safeUrl(s.backgroundImage);
        return [
            `background:${s.background || '#fff'}`,
            bgImage ? `background-image:url("${bgImage.replace(/["\\]/g, '')}")` : '',
            bgImage ? 'background-size:cover;background-position:center' : '',
            `color:${s.color || '#172033'}`,
            `padding:${number(s.paddingTop,56,0,400)}px ${number(s.paddingX,24,0,200)}px ${number(s.paddingBottom,56,0,400)}px`,
            `border-radius:${number(s.borderRadius,0,0,200)}px`
        ].filter(Boolean).join(';');
    }

    function gridStyle(section) {
        const s = section.settings;
        return `max-width:${number(s.maxWidth,1140,320,1920)}px;margin:0 auto;grid-template-columns:repeat(${number(s.columns,1,1,4)},minmax(0,1fr));gap:${number(s.gap,18,0,100)}px`;
    }

    function widgetStyle(widget) {
        const s = widget.style || {};
        const safe = [];
        if (s.background) safe.push(`background:${s.background}`);
        if (s.color) safe.push(`color:${s.color}`);
        if (s.fontSize) safe.push(`font-size:${number(s.fontSize,16,8,120)}px`);
        if (s.fontWeight) safe.push(`font-weight:${String(s.fontWeight).replace(/[^0-9a-z-]/gi,'')}`);
        if (s.textAlign && ['left','center','right','justify'].includes(s.textAlign)) safe.push(`text-align:${s.textAlign}`);
        if (s.padding) safe.push(`padding:${safeBox(s.padding)}`);
        if (s.margin) safe.push(`margin:${safeBox(s.margin)}`);
        if (s.borderRadius) safe.push(`border-radius:${number(s.borderRadius,0,0,200)}px`);
        if (s.boxShadow && /^[0-9a-z(),.\s-]+$/i.test(s.boxShadow)) safe.push(`box-shadow:${s.boxShadow}`);
        return safe.join(';');
    }

    function safeBox(value) {
        const normalized = String(value ?? '0').trim();
        return /^-?\d+(?:\.\d+)?(?:px|%|em|rem)?(?:\s+-?\d+(?:\.\d+)?(?:px|%|em|rem)?){0,3}$/.test(normalized) ? normalized : '0';
    }

    function renderWidget(widget, editor) {
        const definition = WIDGETS[widget.type];
        if (!definition) return '';
        const output = `<div class="cpb-widget-output cpb-widget-${attr(widget.type)}" style="${attr(widgetStyle(widget))}">${definition.render(widget, editor)}</div>`;
        if (!editor) return `<div class="cpb-published-widget cpb-published-${attr(widget.type)}" data-cpb-widget-id="${attr(widget.id)}">${output}</div>`;
        const active = selected?.kind === 'widget' && selected.widgetId === widget.id ? ' is-selected' : '';
        return `<div class="cpb-editor-widget${active}" draggable="true" data-widget-id="${attr(widget.id)}">
            <div class="cpb-widget-toolbar">
                <button class="cpb-chrome-btn cpb-drag-handle" type="button" title="Drag"><i class="fe-move"></i></button>
                <button class="cpb-chrome-btn" type="button" data-action="widget-up" title="উপরে"><i class="fe-arrow-up"></i></button>
                <button class="cpb-chrome-btn" type="button" data-action="widget-duplicate" title="Duplicate"><i class="fe-copy"></i></button>
                <button class="cpb-chrome-btn is-danger" type="button" data-action="widget-delete" title="Delete"><i class="fe-trash-2"></i></button>
            </div>${output}</div>`;
    }

    function renderSection(section, editor) {
        const widgets = section.widgets.map(widget => renderWidget(widget, editor)).join('');
        if (!editor) return `<section class="cpb-published-section" data-cpb-section-id="${attr(section.id)}" style="${attr(sectionStyle(section))}"><div class="cpb-section-grid" style="${attr(gridStyle(section))}">${widgets}</div></section>`;
        const active = selected?.kind === 'section' && selected.sectionId === section.id ? ' is-selected' : '';
        return `<section class="cpb-editor-section${active}" draggable="true" data-section-id="${attr(section.id)}" style="${attr(sectionStyle(section))}">
            <div class="cpb-section-handle">
                <button class="cpb-chrome-btn cpb-drag-handle" type="button" title="Drag section"><i class="fe-move"></i></button>
                <button class="cpb-chrome-btn" type="button" data-action="section-up" title="উপরে"><i class="fe-arrow-up"></i></button>
                <button class="cpb-chrome-btn" type="button" data-action="section-add" title="উইজেট যোগ"><i class="fe-plus"></i></button>
                <button class="cpb-chrome-btn" type="button" data-action="section-duplicate" title="Duplicate"><i class="fe-copy"></i></button>
                <button class="cpb-chrome-btn is-danger" type="button" data-action="section-delete" title="Delete"><i class="fe-trash-2"></i></button>
            </div>
            <div class="cpb-section-grid" style="${attr(gridStyle(section))}">${widgets || '<div class="cpb-section-empty">বাম পাশ থেকে একটি ব্লক এখানে ড্র্যাগ করুন</div>'}</div>
        </section>`;
    }

    function renderCanvas() {
        canvas.classList.add('cpb-published-page');
        canvas.style.background = model.settings.background || '#fff';
        canvas.style.fontFamily = model.settings.fontFamily || 'Arial, sans-serif';
        if (!model.sections.length) {
            canvas.innerHTML = `<div class="cpb-canvas-empty"><div><i class="fe-layout"></i><strong>আপনার landing page তৈরি করুন</strong><span>একটি template নিন অথবা blank section যোগ করুন।</span><br><button type="button" data-action="empty-template">Template বেছে নিন</button></div></div>`;
        } else {
            canvas.innerHTML = model.sections.map(section => renderSection(section, true)).join('') + `<div class="cpb-add-section"><button type="button" data-action="add-section"><i class="fe-plus-circle"></i> নতুন সেকশন</button></div>`;
        }
        applyCustomCssPreview();
        renderLayers();
        updateHistoryButtons();
    }

    function renderLayers() {
        if (!model.sections.length) {
            layers.innerHTML = '<div class="cpb-layer-empty"><i class="fe-layers"></i><br>এখনো কোনো layer নেই</div>';
            return;
        }
        layers.innerHTML = model.sections.map((section, sectionIndex) => `<div class="cpb-layer-section">
            <button type="button" class="cpb-layer-row ${selected?.kind==='section'&&selected.sectionId===section.id?'is-active':''}" data-layer-section="${attr(section.id)}"><i class="fe-grid cpb-layer-grip"></i><span>${text(section.label || 'Section ' + (sectionIndex + 1))}</span><small>${section.widgets.length}</small></button>
            ${section.widgets.map(widget => `<button type="button" class="cpb-layer-widget ${selected?.kind==='widget'&&selected.widgetId===widget.id?'is-active':''}" data-layer-widget="${attr(widget.id)}" data-layer-parent="${attr(section.id)}"><i class="${attr(WIDGETS[widget.type]?.icon || 'fe-square')}"></i><span>${text(WIDGETS[widget.type]?.label || widget.type)}</span></button>`).join('')}
        </div>`).join('');
    }

    function buildPalette() {
        palette.innerHTML = Object.entries(CATEGORIES).map(([category, label]) => {
            const widgets = Object.entries(WIDGETS).filter(([, widget]) => widget.category === category);
            return `<section class="cpb-category" data-category="${attr(category)}"><div class="cpb-category-title"><span>${text(label)}</span><span>${widgets.length}</span></div><div class="cpb-palette-grid">${widgets.map(([type, widget]) => `<button type="button" class="cpb-palette-item" draggable="true" data-widget-type="${attr(type)}" data-search="${attr((widget.label+' '+type).toLowerCase())}"><i class="${attr(widget.icon)}"></i><span>${text(widget.label)}</span></button>`).join('')}</div></section>`;
        }).join('');
    }

    function findSection(id) { return model.sections.find(section => section.id === id); }
    function findWidget(sectionId, widgetId) { return findSection(sectionId)?.widgets.find(widget => widget.id === widgetId); }
    function selectedWidget() { return selected?.kind === 'widget' ? findWidget(selected.sectionId, selected.widgetId) : null; }

    function selectSection(sectionId, scroll) {
        selected = { kind: 'section', sectionId };
        renderCanvas();
        renderInspector();
        inspector.classList.add('is-open');
        if (scroll) document.querySelector(`[data-section-id="${cssEscape(sectionId)}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function selectWidget(sectionId, widgetId, scroll) {
        selected = { kind: 'widget', sectionId, widgetId };
        renderCanvas();
        renderInspector();
        inspector.classList.add('is-open');
        if (scroll) document.querySelector(`[data-widget-id="${cssEscape(widgetId)}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function selectPage() {
        selected = null;
        renderCanvas();
        renderInspector();
    }

    function cssEscape(value) { return window.CSS?.escape ? CSS.escape(value) : String(value).replace(/[^a-z0-9_-]/gi, '\\$&'); }

    function renderInspector() {
        if (!selected) return renderPageInspector();
        if (selected.kind === 'section') return renderSectionInspector(findSection(selected.sectionId));
        return renderWidgetInspector(selectedWidget());
    }

    function renderPageInspector() {
        inspectorTitle.textContent = 'পেজ সেটিংস';
        inspectorKicker.textContent = 'Global design ও publishing';
        inspectorBody.innerHTML = `<div class="cpb-section-label" style="margin-top:0">Global style</div>
            ${fieldHtml('পেজ ব্যাকগ্রাউন্ড', 'background', model.settings.background, 'color', 'page')}
            <div class="cpb-field"><label>Font family</label><select class="cpb-select" data-page-key="fontFamily">
                ${['Arial, "Noto Sans Bengali", sans-serif','"Cerebri Sans", Arial, sans-serif','Georgia, serif','system-ui, sans-serif'].map(font => `<option value="${attr(font)}" ${model.settings.fontFamily===font?'selected':''}>${text(font.split(',')[0].replaceAll('"',''))}</option>`).join('')}
            </select></div>
            <button type="button" class="cpb-upload-button" data-action="open-css"><i class="fe-code"></i> Custom CSS সম্পাদনা</button>
            <div class="cpb-section-label">Dynamic variables</div><div class="cpb-token-list">${TOKENS.map(token => `<button type="button" class="cpb-token" data-token="${attr(token)}">${text(token)}</button>`).join('')}</div>
            <div class="cpb-danger-zone"><strong style="display:block;font-size:11px;margin-bottom:7px">Legacy fallback</strong><p style="margin:0 0 10px;color:#7b8494;font-size:10px">Visual design মুছলে আগের field-based campaign page আবার চালু হবে।</p><button type="button" class="cpb-danger-button" data-action="clear-builder"><i class="fe-rotate-ccw"></i> Visual design মুছুন</button></div>`;
        bindInspectorInputs();
    }

    function renderSectionInspector(section) {
        if (!section) return selectPage();
        inspectorTitle.textContent = section.label || 'Section';
        inspectorKicker.textContent = 'Section / grid settings';
        const s = section.settings;
        inspectorBody.innerHTML = `<div class="cpb-settings-tabs"><button class="cpb-settings-tab is-active" data-settings-tab="layout">Layout</button><button class="cpb-settings-tab" data-settings-tab="style">Style</button><button class="cpb-settings-tab" data-settings-tab="advanced">Advanced</button></div>
            <div class="cpb-settings-pane is-active" data-settings-pane="layout">
                ${fieldHtml('Layer নাম', 'label', section.label, 'text', 'section-label')}
                <div class="cpb-field-row">${fieldHtml('Columns', 'columns', s.columns, 'number', 'section')}${fieldHtml('Gap (px)', 'gap', s.gap, 'number', 'section')}</div>
                ${fieldHtml('Content width (px)', 'maxWidth', s.maxWidth, 'number', 'section')}
                <div class="cpb-section-label">Spacing</div>
                <div class="cpb-field-row">${fieldHtml('উপরে', 'paddingTop', s.paddingTop, 'number', 'section')}${fieldHtml('নিচে', 'paddingBottom', s.paddingBottom, 'number', 'section')}</div>
                ${fieldHtml('ডানে-বামে', 'paddingX', s.paddingX, 'number', 'section')}
            </div>
            <div class="cpb-settings-pane" data-settings-pane="style">
                ${fieldHtml('Background', 'background', s.background, 'color', 'section')}
                ${fieldHtml('Text color', 'color', s.color, 'color', 'section')}
                ${imageUploadHtml('Background image', 'backgroundImage', s.backgroundImage)}
            </div>
            <div class="cpb-settings-pane" data-settings-pane="advanced">
                ${fieldHtml('Border radius (px)', 'borderRadius', s.borderRadius, 'number', 'section')}
                <div class="cpb-danger-zone"><button type="button" class="cpb-danger-button" data-action="section-delete"><i class="fe-trash-2"></i> সেকশন মুছুন</button></div>
            </div>`;
        bindInspectorInputs();
    }

    function renderWidgetInspector(widget) {
        if (!widget) return selectPage();
        const definition = WIDGETS[widget.type];
        inspectorTitle.textContent = definition.label;
        inspectorKicker.textContent = 'Widget / ' + widget.type;
        const contentFields = (definition.fields || []).map(field => fieldHtml(field.label, field.key, value(widget, field.key), field.type || 'text', 'content', field.options)).join('');
        inspectorBody.innerHTML = `<div class="cpb-settings-tabs"><button class="cpb-settings-tab is-active" data-settings-tab="content">Content</button><button class="cpb-settings-tab" data-settings-tab="style">Style</button><button class="cpb-settings-tab" data-settings-tab="advanced">Advanced</button></div>
            <div class="cpb-settings-pane is-active" data-settings-pane="content">
                ${definition.imageField ? imageUploadHtml('ইমেজ', definition.imageField, value(widget, definition.imageField)) : ''}
                ${contentFields || '<p style="color:#8c95a5;font-size:11px">এই widget-এর editable content নেই। Style tab ব্যবহার করুন।</p>'}
                <div class="cpb-section-label">Dynamic variables</div><div class="cpb-token-list">${TOKENS.map(token => `<button type="button" class="cpb-token" data-token="${attr(token)}">${text(token)}</button>`).join('')}</div>
            </div>
            <div class="cpb-settings-pane" data-settings-pane="style">
                <div class="cpb-field-row">${fieldHtml('Text color','color',widget.style.color,'color','style')}${fieldHtml('Background','background',widget.style.background,'color','style')}</div>
                <div class="cpb-field-row">${fieldHtml('Font size','fontSize',number(widget.style.fontSize,16,8,120),'number','style')}${selectField('Weight','fontWeight',widget.style.fontWeight,['300','400','500','600','700','800','900'],'style')}</div>
                ${selectField('Alignment','textAlign',widget.style.textAlign,['left','center','right','justify'],'style')}
                ${fieldHtml('Padding','padding',widget.style.padding,'text','style')}
                ${fieldHtml('Margin','margin',widget.style.margin,'text','style')}
            </div>
            <div class="cpb-settings-pane" data-settings-pane="advanced">
                ${fieldHtml('Border radius','borderRadius',number(widget.style.borderRadius,0,0,200),'number','style')}
                ${selectField('Shadow','boxShadow',widget.style.boxShadow,['none','0 4px 14px rgba(15,23,42,.08)','0 12px 30px rgba(15,23,42,.14)','0 22px 55px rgba(15,23,42,.2)'],'style')}
                <div class="cpb-danger-zone"><button type="button" class="cpb-danger-button" data-action="widget-delete"><i class="fe-trash-2"></i> Widget মুছুন</button></div>
            </div>`;
        bindInspectorInputs();
    }

    function fieldHtml(label, key, current, type, scope, options) {
        if (type === 'select') return selectField(label, key, current, options || [], scope);
        const data = scope === 'content' ? 'data-content-key' : scope === 'style' ? 'data-style-key' : scope === 'section' ? 'data-section-key' : scope === 'section-label' ? 'data-section-label' : 'data-page-key';
        if (type === 'textarea' || type === 'code') return `<div class="cpb-field"><label>${text(label)}</label><textarea class="cpb-textarea" ${data}="${attr(key)}" ${type==='code'?'spellcheck="false" style="font-family:monospace"':''}>${text(current)}</textarea></div>`;
        return `<div class="cpb-field"><label>${text(label)}</label><input class="cpb-input" type="${attr(type || 'text')}" ${data}="${attr(key)}" value="${attr(normalizeDateInput(current, type))}"></div>`;
    }

    function selectField(label, key, current, options, scope) {
        const data = scope === 'content' ? 'data-content-key' : scope === 'style' ? 'data-style-key' : scope === 'section' ? 'data-section-key' : 'data-page-key';
        return `<div class="cpb-field"><label>${text(label)}</label><select class="cpb-select" ${data}="${attr(key)}">${options.map(option => `<option value="${attr(option)}" ${String(current)===String(option)?'selected':''}>${text(option)}</option>`).join('')}</select></div>`;
    }

    function imageUploadHtml(label, key, current) {
        return `<div class="cpb-field"><label>${text(label)}</label><div class="cpb-upload" data-upload-key="${attr(key)}"><img class="cpb-upload-preview" src="${attr(safeUrl(current))}" alt=""><input type="file" hidden accept="image/jpeg,image/png,image/webp,image/gif"><button type="button" class="cpb-upload-button" data-upload-button><i class="fe-upload-cloud"></i> আপলোড করুন</button></div></div>`;
    }

    function normalizeDateInput(current, type) {
        if (type !== 'datetime-local' || !current || String(current).includes('{{')) return current || '';
        return String(current).replace(' ', 'T').slice(0, 16);
    }

    function bindInspectorInputs() {
        inspectorBody.querySelectorAll('[data-settings-tab]').forEach(tab => tab.addEventListener('click', () => {
            inspectorBody.querySelectorAll('[data-settings-tab]').forEach(item => item.classList.toggle('is-active', item === tab));
            inspectorBody.querySelectorAll('[data-settings-pane]').forEach(pane => pane.classList.toggle('is-active', pane.dataset.settingsPane === tab.dataset.settingsTab));
        }));
        inspectorBody.querySelectorAll('[data-content-key]').forEach(input => {
            input.addEventListener('focus', () => { lastContentInput = input; });
            input.addEventListener('input', () => {
                const widget = selectedWidget(); if (!widget) return;
                widget.content[input.dataset.contentKey] = input.value;
                renderCanvas(); markDirty();
            });
            input.addEventListener('change', commitHistory);
        });
        inspectorBody.querySelectorAll('[data-style-key]').forEach(input => input.addEventListener('input', () => {
            const widget = selectedWidget(); if (!widget) return;
            widget.style[input.dataset.styleKey] = input.value;
            renderCanvas(); markDirty();
        }));
        inspectorBody.querySelectorAll('[data-style-key]').forEach(input => input.addEventListener('change', commitHistory));
        inspectorBody.querySelectorAll('[data-section-key]').forEach(input => input.addEventListener('input', () => {
            const section = findSection(selected?.sectionId); if (!section) return;
            section.settings[input.dataset.sectionKey] = input.type === 'number' ? Number(input.value) : input.value;
            renderCanvas(); markDirty();
        }));
        inspectorBody.querySelectorAll('[data-section-key]').forEach(input => input.addEventListener('change', commitHistory));
        inspectorBody.querySelectorAll('[data-section-label]').forEach(input => input.addEventListener('input', () => {
            const section = findSection(selected?.sectionId); if (!section) return;
            section.label = input.value; renderLayers(); markDirty();
        }));
        inspectorBody.querySelectorAll('[data-section-label]').forEach(input => input.addEventListener('change', commitHistory));
        inspectorBody.querySelectorAll('[data-page-key]').forEach(input => input.addEventListener('input', () => {
            model.settings[input.dataset.pageKey] = input.value; renderCanvas(); markDirty();
        }));
        inspectorBody.querySelectorAll('[data-page-key]').forEach(input => input.addEventListener('change', commitHistory));
        inspectorBody.querySelectorAll('[data-token]').forEach(button => button.addEventListener('click', () => insertToken(button.dataset.token)));
        inspectorBody.querySelectorAll('[data-upload-button]').forEach(button => {
            const upload = button.closest('.cpb-upload');
            const input = upload.querySelector('input[type="file"]');
            button.addEventListener('click', () => input.click());
            input.addEventListener('change', () => uploadImage(input.files?.[0], upload.dataset.uploadKey, button));
        });
    }

    function insertToken(token) {
        let input = lastContentInput;
        if (!input || !document.body.contains(input)) input = inspectorBody.querySelector('[data-content-key]');
        if (!input) { toast('একটি text field নির্বাচন করুন।', 'error'); return; }
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? start;
        input.value = input.value.slice(0, start) + token + input.value.slice(end);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.focus();
        input.setSelectionRange?.(start + token.length, start + token.length);
    }

    async function uploadImage(file, key, button) {
        if (!file) return;
        if (!/^image\/(jpeg|png|webp|gif)$/i.test(file.type) || file.size > 5 * 1024 * 1024) {
            toast('JPG, PNG, WEBP বা GIF (সর্বোচ্চ 5MB) আপলোড করুন।', 'error'); return;
        }
        const original = button.innerHTML;
        button.disabled = true; button.innerHTML = '<i class="fe-loader"></i> আপলোড হচ্ছে...';
        try {
            const body = new FormData(); body.append('image', file);
            const response = await fetch(root.dataset.uploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.url) throw new Error(validationMessage(data) || 'ইমেজ আপলোড ব্যর্থ হয়েছে।');
            if (selected?.kind === 'section') findSection(selected.sectionId).settings[key] = data.url;
            else if (selectedWidget()) selectedWidget().content[key] = data.url;
            renderCanvas(); renderInspector(); markDirty(); commitHistory(); toast('ইমেজ আপলোড হয়েছে।', 'success');
        } catch (error) { toast(error.message || 'ইমেজ আপলোড ব্যর্থ হয়েছে।', 'error'); }
        finally { button.disabled = false; button.innerHTML = original; }
    }

    function addSection(afterSectionId) {
        const section = newSection([], { label: 'New section' });
        const index = afterSectionId ? model.sections.findIndex(item => item.id === afterSectionId) + 1 : model.sections.length;
        model.sections.splice(index, 0, section);
        selected = { kind: 'section', sectionId: section.id };
        mutate(); renderInspector();
    }

    function addWidget(type, sectionId, beforeWidgetId) {
        if (!WIDGETS[type]) return;
        let section = findSection(sectionId);
        if (!section) {
            section = newSection([], { label: 'Content section' });
            model.sections.push(section);
        }
        const widget = newWidget(type);
        const beforeIndex = beforeWidgetId ? section.widgets.findIndex(item => item.id === beforeWidgetId) : -1;
        if (beforeIndex >= 0) section.widgets.splice(beforeIndex, 0, widget); else section.widgets.push(widget);
        selected = { kind: 'widget', sectionId: section.id, widgetId: widget.id };
        mutate(); renderInspector();
    }

    function deleteSection(sectionId) {
        const index = model.sections.findIndex(section => section.id === sectionId);
        if (index < 0 || !window.confirm('এই সেকশন এবং এর সব widget মুছে ফেলবেন?')) return;
        model.sections.splice(index, 1); selected = null; mutate(); renderInspector();
    }

    function deleteWidget(sectionId, widgetId) {
        const section = findSection(sectionId); if (!section) return;
        const index = section.widgets.findIndex(widget => widget.id === widgetId);
        if (index < 0) return;
        section.widgets.splice(index, 1); selected = { kind: 'section', sectionId }; mutate(); renderInspector();
    }

    function duplicateSection(sectionId) {
        const index = model.sections.findIndex(section => section.id === sectionId); if (index < 0) return;
        const copy = clone(model.sections[index]); copy.id = uid('section'); copy.label += ' copy'; copy.widgets.forEach(widget => widget.id = uid('widget'));
        model.sections.splice(index + 1, 0, copy); selected = { kind: 'section', sectionId: copy.id }; mutate(); renderInspector();
    }

    function duplicateWidget(sectionId, widgetId) {
        const section = findSection(sectionId); if (!section) return;
        const index = section.widgets.findIndex(widget => widget.id === widgetId); if (index < 0) return;
        const copy = clone(section.widgets[index]); copy.id = uid('widget'); section.widgets.splice(index + 1, 0, copy);
        selected = { kind: 'widget', sectionId, widgetId: copy.id }; mutate(); renderInspector();
    }

    function moveSection(sectionId, direction) {
        const index = model.sections.findIndex(section => section.id === sectionId); const target = index + direction;
        if (index < 0 || target < 0 || target >= model.sections.length) return;
        [model.sections[index], model.sections[target]] = [model.sections[target], model.sections[index]]; mutate();
    }

    function moveWidget(sectionId, widgetId, direction) {
        const section = findSection(sectionId); if (!section) return;
        const index = section.widgets.findIndex(widget => widget.id === widgetId); const target = index + direction;
        if (index < 0 || target < 0 || target >= section.widgets.length) return;
        [section.widgets[index], section.widgets[target]] = [section.widgets[target], section.widgets[index]]; mutate();
    }

    function clone(value) { return JSON.parse(JSON.stringify(value)); }

    function mutate() { renderCanvas(); markDirty(); commitHistory(); }

    function commitHistory() {
        const snapshot = JSON.stringify(model);
        if (history[historyIndex] === snapshot) return;
        history = history.slice(0, historyIndex + 1); history.push(snapshot);
        if (history.length > 50) history.shift();
        historyIndex = history.length - 1; updateHistoryButtons();
    }

    function undo() {
        if (historyIndex <= 0) return;
        historyIndex--; model = JSON.parse(history[historyIndex]); selected = null; renderCanvas(); renderInspector(); markDirty(false);
    }

    function redo() {
        if (historyIndex >= history.length - 1) return;
        historyIndex++; model = JSON.parse(history[historyIndex]); selected = null; renderCanvas(); renderInspector(); markDirty(false);
    }

    function updateHistoryButtons() {
        document.getElementById('cpb-undo').disabled = historyIndex <= 0;
        document.getElementById('cpb-redo').disabled = historyIndex >= history.length - 1;
    }

    function markDirty(schedule) {
        dirty = true; setSaveState('is-dirty', 'সেভ করা হয়নি');
        try { localStorage.setItem(storageKey, JSON.stringify({ dirty: true, updatedAt: Date.now(), model })); } catch (_) {}
        clearTimeout(autosaveTimer);
        if (schedule !== false) autosaveTimer = setTimeout(() => savePage({ silent: true }), 7000);
    }

    function setSaveState(className, label) {
        saveState.className = 'cpb-save-state ' + (className || ''); saveState.textContent = label;
    }

    function exportPage() {
        const html = model.sections.map(section => renderSection(section, false)).join('');
        return { page_design: JSON.stringify(model), page_html: html, page_css: PUBLISHED_CSS + '\n' + (model.settings.customCss || '') };
    }

    async function savePage(options) {
        options = options || {};
        if (saving) return false;
        if (!model.sections.length) { if (!options.silent) toast('সেভ করার আগে অন্তত একটি section যোগ করুন।', 'error'); return false; }
        saving = true; setSaveState('is-saving', 'সেভ হচ্ছে...');
        document.getElementById('cpb-save').disabled = true;
        try {
            const response = await fetch(root.dataset.saveUrl, {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: JSON.stringify(exportPage())
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.success === false) throw new Error(validationMessage(data) || 'Landing page সেভ হয়নি।');
            dirty = false; clearTimeout(autosaveTimer); setSaveState('is-saved', 'এইমাত্র সেভ হয়েছে');
            try { localStorage.removeItem(storageKey); } catch (_) {}
            if (!options.silent) toast('Landing page সফলভাবে সেভ হয়েছে।', 'success');
            if (options.preview) window.open(data.preview || root.dataset.previewUrl, '_blank', 'noopener');
            return true;
        } catch (error) {
            setSaveState('is-error', 'সেভ ব্যর্থ');
            if (!options.silent) toast(error.message || 'Landing page সেভ হয়নি।', 'error');
            return false;
        } finally { saving = false; document.getElementById('cpb-save').disabled = false; }
    }

    function validationMessage(data) {
        if (data?.message && data.message !== 'The given data was invalid.') return data.message;
        const errors = data?.errors ? Object.values(data.errors).flat() : [];
        return errors[0] || '';
    }

    async function clearBuilder() {
        if (!window.confirm('Visual design সম্পূর্ণ মুছে legacy campaign page চালু করবেন? এই কাজটি undo করা যাবে না।')) return;
        try {
            const response = await fetch(root.dataset.clearUrl, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(validationMessage(data) || 'Design clear হয়নি।');
            localStorage.removeItem(storageKey); dirty = false; model = templateModel('direct'); history = [JSON.stringify(model)]; historyIndex = 0;
            renderCanvas(); selectPage(); setSaveState('', 'Legacy page চালু আছে'); toast(data.message || 'Legacy page active.', 'success');
        } catch (error) { toast(error.message, 'error'); }
    }

    function applyTemplate(name) {
        if (model.sections.length && !window.confirm('বর্তমান canvas বদলে নির্বাচিত template বসাবেন?')) return;
        model = templateModel(name); selected = null; closeDialogs(); mutate(); renderInspector(); toast('Template প্রয়োগ হয়েছে। সেভ করে publish করুন।', 'success');
    }

    function applyCustomCssPreview() {
        let style = document.getElementById('cpb-live-custom-css');
        if (!style) { style = document.createElement('style'); style.id = 'cpb-live-custom-css'; document.head.appendChild(style); }
        style.textContent = String(model.settings.customCss || '').replace(/<\/?style[^>]*>/gi, '').replace(/@import[^;]+;/gi, '');
    }

    function openTemplates() { document.getElementById('cpb-template-dialog').showModal(); }
    function openCss() { document.getElementById('cpb-custom-css').value = model.settings.customCss || ''; document.getElementById('cpb-css-dialog').showModal(); }
    function closeDialogs() { document.querySelectorAll('dialog[open]').forEach(dialog => dialog.close()); }

    function toast(message, type) {
        const item = document.createElement('div'); item.className = 'cpb-toast ' + (type ? 'is-' + type : '');
        item.innerHTML = `<i class="${type==='success'?'fe-check-circle':type==='error'?'fe-alert-circle':'fe-info'}"></i><span>${text(message)}</span>`;
        document.getElementById('cpb-toast-stack').appendChild(item);
        setTimeout(() => item.remove(), 4200);
    }

    function handleAction(action, sectionId, widgetId) {
        if (action === 'add-section') return addSection();
        if (action === 'empty-template') return openTemplates();
        if (action === 'open-css') return openCss();
        if (action === 'clear-builder') return clearBuilder();
        if (action === 'section-up') return moveSection(sectionId, -1);
        if (action === 'section-add') { document.querySelector('[data-panel="blocks"]')?.click(); return toast('বাম পাশ থেকে widget বেছে নিন বা drag করুন।'); }
        if (action === 'section-duplicate') return duplicateSection(sectionId);
        if (action === 'section-delete') return deleteSection(sectionId || selected?.sectionId);
        if (action === 'widget-up') return moveWidget(sectionId, widgetId, -1);
        if (action === 'widget-duplicate') return duplicateWidget(sectionId, widgetId);
        if (action === 'widget-delete') return deleteWidget(sectionId || selected?.sectionId, widgetId || selected?.widgetId);
    }

    function bindEvents() {
        document.getElementById('cpb-save').addEventListener('click', () => savePage());
        document.getElementById('cpb-preview').addEventListener('click', () => {
            if (!model.sections.length) {
                model = templateModel('direct');
                selected = null;
                renderCanvas();
                renderInspector();
                markDirty(false);
                commitHistory();
            }
            savePage({ preview: true });
        });
        document.getElementById('cpb-templates').addEventListener('click', openTemplates);
        document.getElementById('cpb-quick-templates').addEventListener('click', openTemplates);
        document.getElementById('cpb-open-css').addEventListener('click', openCss);
        document.getElementById('cpb-undo').addEventListener('click', undo);
        document.getElementById('cpb-redo').addEventListener('click', redo);
        document.getElementById('cpb-inspector-close').addEventListener('click', selectPage);

        document.querySelectorAll('[data-close-dialog]').forEach(button => button.addEventListener('click', closeDialogs));
        document.querySelectorAll('[data-template]').forEach(button => button.addEventListener('click', () => applyTemplate(button.dataset.template)));
        document.getElementById('cpb-apply-css').addEventListener('click', () => {
            model.settings.customCss = document.getElementById('cpb-custom-css').value;
            closeDialogs(); renderCanvas(); markDirty(); commitHistory(); toast('Custom CSS প্রয়োগ হয়েছে।', 'success');
        });

        document.querySelectorAll('.cpb-panel-tab').forEach(button => button.addEventListener('click', () => {
            document.querySelectorAll('.cpb-panel-tab').forEach(tab => tab.classList.toggle('is-active', tab === button));
            document.querySelectorAll('[data-panel-view]').forEach(view => view.classList.toggle('is-active', view.dataset.panelView === button.dataset.panel));
        }));

        document.querySelectorAll('.cpb-device-btn').forEach(button => button.addEventListener('click', () => {
            document.querySelectorAll('.cpb-device-btn').forEach(item => item.classList.toggle('is-active', item === button));
            canvasShell.dataset.device = button.dataset.device;
        }));

        document.getElementById('cpb-block-search').addEventListener('input', event => {
            const query = event.target.value.trim().toLowerCase();
            document.querySelectorAll('.cpb-palette-item').forEach(item => item.classList.toggle('is-hidden', query && !item.dataset.search.includes(query)));
            document.querySelectorAll('.cpb-category').forEach(category => category.style.display = category.querySelector('.cpb-palette-item:not(.is-hidden)') ? '' : 'none');
        });

        palette.addEventListener('click', event => {
            const item = event.target.closest('[data-widget-type]'); if (!item) return;
            const sectionId = selected?.sectionId || model.sections.at(-1)?.id;
            addWidget(item.dataset.widgetType, sectionId);
        });
        palette.addEventListener('dragstart', event => {
            const item = event.target.closest('[data-widget-type]'); if (!item) return;
            dragPayload = { kind: 'new-widget', type: item.dataset.widgetType };
            event.dataTransfer.effectAllowed = 'copy'; event.dataTransfer.setData('text/plain', JSON.stringify(dragPayload));
        });

        canvas.addEventListener('click', event => {
            const action = event.target.closest('[data-action]');
            const widgetEl = event.target.closest('[data-widget-id]');
            const sectionEl = event.target.closest('[data-section-id]');
            if (action) { event.preventDefault(); event.stopPropagation(); handleAction(action.dataset.action, sectionEl?.dataset.sectionId, widgetEl?.dataset.widgetId); return; }
            if (event.target.closest('a')) event.preventDefault();
            if (widgetEl) selectWidget(sectionEl.dataset.sectionId, widgetEl.dataset.widgetId);
            else if (sectionEl) selectSection(sectionEl.dataset.sectionId);
            else selectPage();
        });

        canvas.addEventListener('input', event => {
            const editableField = event.target.closest('[data-cpb-field]'); if (!editableField) return;
            const widgetEl = editableField.closest('[data-widget-id]'); const sectionEl = editableField.closest('[data-section-id]');
            const widget = findWidget(sectionEl?.dataset.sectionId, widgetEl?.dataset.widgetId); if (!widget) return;
            widget.content[editableField.dataset.cpbField] = cleanRich(editableField.innerHTML); markDirty();
        });
        canvas.addEventListener('focusout', event => { if (event.target.closest('[data-cpb-field]')) { commitHistory(); renderLayers(); } });

        canvas.addEventListener('dragstart', event => {
            const widgetEl = event.target.closest('[data-widget-id]'); const sectionEl = event.target.closest('[data-section-id]');
            if (widgetEl) dragPayload = { kind: 'widget', widgetId: widgetEl.dataset.widgetId, sectionId: sectionEl.dataset.sectionId };
            else if (sectionEl) dragPayload = { kind: 'section', sectionId: sectionEl.dataset.sectionId };
            else return;
            event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', JSON.stringify(dragPayload));
            (widgetEl || sectionEl).classList.add('cpb-dragging');
        });
        canvas.addEventListener('dragend', () => { document.querySelectorAll('.cpb-dragging,.is-drop-target').forEach(el => el.classList.remove('cpb-dragging','is-drop-target')); dragPayload = null; });
        canvas.addEventListener('dragover', event => {
            event.preventDefault(); const grid = event.target.closest('.cpb-section-grid');
            document.querySelectorAll('.is-drop-target').forEach(el => el.classList.remove('is-drop-target'));
            (grid || canvas).classList.add('is-drop-target'); event.dataTransfer.dropEffect = dragPayload?.kind === 'new-widget' ? 'copy' : 'move';
        });
        canvas.addEventListener('dragleave', event => { if (!canvas.contains(event.relatedTarget)) document.querySelectorAll('.is-drop-target').forEach(el => el.classList.remove('is-drop-target')); });
        canvas.addEventListener('drop', event => {
            event.preventDefault(); document.querySelectorAll('.is-drop-target').forEach(el => el.classList.remove('is-drop-target'));
            let payload = dragPayload;
            if (!payload) { try { payload = JSON.parse(event.dataTransfer.getData('text/plain')); } catch (_) {} }
            if (!payload) return;
            const targetSectionEl = event.target.closest('[data-section-id]'); const targetWidgetEl = event.target.closest('[data-widget-id]');
            if (payload.kind === 'new-widget') return addWidget(payload.type, targetSectionEl?.dataset.sectionId || model.sections.at(-1)?.id, targetWidgetEl?.dataset.widgetId);
            if (payload.kind === 'widget') return dropWidget(payload, targetSectionEl?.dataset.sectionId, targetWidgetEl?.dataset.widgetId);
            if (payload.kind === 'section') return dropSection(payload.sectionId, targetSectionEl?.dataset.sectionId);
        });

        layers.addEventListener('click', event => {
            const widget = event.target.closest('[data-layer-widget]'); const section = event.target.closest('[data-layer-section]');
            if (widget) selectWidget(widget.dataset.layerParent, widget.dataset.layerWidget, true);
            else if (section) selectSection(section.dataset.layerSection, true);
        });

        document.getElementById('cpb-zoom-in').addEventListener('click', () => setZoom(zoom + 10));
        document.getElementById('cpb-zoom-out').addEventListener('click', () => setZoom(zoom - 10));

        document.addEventListener('keydown', event => {
            const key = event.key.toLowerCase();
            if ((event.ctrlKey || event.metaKey) && key === 's') { event.preventDefault(); savePage(); }
            if ((event.ctrlKey || event.metaKey) && key === 'z' && !event.shiftKey) { event.preventDefault(); undo(); }
            if ((event.ctrlKey || event.metaKey) && (key === 'y' || (key === 'z' && event.shiftKey))) { event.preventDefault(); redo(); }
            if (event.key === 'Escape') closeDialogs();
        });

        window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
    }

    function dropWidget(payload, targetSectionId, beforeWidgetId) {
        const from = findSection(payload.sectionId); const target = findSection(targetSectionId) || from;
        if (!from || !target) return;
        const index = from.widgets.findIndex(widget => widget.id === payload.widgetId); if (index < 0) return;
        const [widget] = from.widgets.splice(index, 1);
        let before = beforeWidgetId ? target.widgets.findIndex(item => item.id === beforeWidgetId) : -1;
        if (before >= 0) target.widgets.splice(before, 0, widget); else target.widgets.push(widget);
        selected = { kind: 'widget', sectionId: target.id, widgetId: widget.id }; mutate(); renderInspector();
    }

    function dropSection(sectionId, targetSectionId) {
        if (!targetSectionId || sectionId === targetSectionId) return;
        const fromIndex = model.sections.findIndex(section => section.id === sectionId);
        let targetIndex = model.sections.findIndex(section => section.id === targetSectionId);
        if (fromIndex < 0 || targetIndex < 0) return;
        const [section] = model.sections.splice(fromIndex, 1);
        targetIndex = model.sections.findIndex(item => item.id === targetSectionId);
        model.sections.splice(targetIndex, 0, section); mutate();
    }

    function setZoom(next) {
        zoom = Math.max(50, Math.min(120, next));
        canvasShell.style.transform = `scale(${zoom / 100})`;
        document.getElementById('cpb-zoom-label').textContent = zoom + '%';
    }

    function loadInitialModel() {
        let initial = parseJsonScript('cpb-initial-design', null);
        if (typeof initial === 'string') { try { initial = JSON.parse(initial); } catch (_) { initial = null; } }
        model = normalizeModel(initial);
        let draft = null;
        try { draft = JSON.parse(localStorage.getItem(storageKey) || 'null'); } catch (_) {}
        if (draft?.dirty && normalizeModel(draft.model) && window.confirm('এই campaign-এর একটি unsaved local draft পাওয়া গেছে। Draft restore করবেন?')) {
            model = normalizeModel(draft.model); dirty = true; setTimeout(() => toast('Local draft restore হয়েছে। সেভ করে publish করুন।'), 400);
        }
        if (!model) { model = templateModel('direct'); setSaveState('', 'Template প্রস্তুত — এখনো publish হয়নি'); }
        else if (!dirty) setSaveState('is-saved', 'সব পরিবর্তন সেভ আছে');
    }

    function init() {
        loadInitialModel(); buildPalette(); bindEvents(); renderCanvas(); renderInspector();
        history = [JSON.stringify(model)]; historyIndex = 0; updateHistoryButtons();
    }

    init();

    // Small public API for browser debugging/integration without exposing mutable internals.
    window.CampaignPageBuilder = {
        save: () => savePage(),
        export: () => exportPage(),
        openTemplates,
        getDesign: () => clone(model)
    };
})();
