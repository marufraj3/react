@extends('backEnd.layouts.master')
@section('title', 'Custom Code Builder')

@section('css')
<style>
    .code-builder{--ink:#172033;--muted:#667085;--line:#dfe5ee;--violet:#6d28d9}.cb-top{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:18px 0}.cb-top h3{margin:0;color:var(--ink);font-weight:800}.cb-actions{display:flex;gap:8px;flex-wrap:wrap}.cb-grid{display:grid;grid-template-columns:minmax(420px,.95fr) minmax(460px,1.05fr);gap:16px;min-height:720px}.cb-card{background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 10px 32px rgba(15,23,42,.06);overflow:hidden}.cb-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 16px;border-bottom:1px solid var(--line);background:#f8fafc}.cb-card-head strong{color:var(--ink)}.cb-tabs{display:flex;gap:5px;padding:10px 12px 0}.cb-tab{border:0;border-radius:8px 8px 0 0;padding:9px 15px;background:#eef2f7;color:#596579;font-weight:700}.cb-tab.active{background:#1e293b;color:#fff}.cb-editor{display:none}.cb-editor.active{display:block}.cb-editor textarea{display:block;width:100%;height:590px;resize:vertical;border:0;border-top:1px solid var(--line);outline:0;padding:17px;background:#0f172a;color:#dbeafe;font:13px/1.6 Consolas,Monaco,"Courier New",monospace;tab-size:2}.cb-preview{width:100%;height:665px;border:0;background:#fff}.cb-token-list{display:flex;gap:6px;flex-wrap:wrap;padding:12px 14px;border-top:1px solid var(--line);background:#fbfcfe}.cb-token{border:1px solid #ddd6fe;border-radius:999px;padding:4px 8px;background:#f5f3ff;color:#5b21b6;font:11px Consolas,monospace;cursor:pointer}.cb-help{padding:12px 15px;color:var(--muted);font-size:12px}.cb-status{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 10px;font-size:11px;font-weight:800}.cb-live{background:#dcfce7;color:#166534}.cb-off{background:#fee2e2;color:#991b1b}.cb-draft{background:#fef3c7;color:#92400e}.cb-upload{display:flex;align-items:end;gap:10px;flex-wrap:wrap;padding:14px 16px;border-bottom:1px solid var(--line)}.cb-upload label{font-size:11px;font-weight:800;color:#475569}.cb-upload input{display:block;max-width:180px;margin-top:4px;font-size:11px}.cb-savebar{position:sticky;bottom:0;display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-top:1px solid var(--line);background:rgba(255,255,255,.96);backdrop-filter:blur(10px)}.cb-save-state{font-size:12px;color:var(--muted)}@media(max-width:1100px){.cb-grid{grid-template-columns:1fr}.cb-editor textarea{height:480px}.cb-preview{height:600px}}@media(max-width:600px){.cb-top{align-items:flex-start;flex-direction:column}.cb-grid{display:block}.cb-card{margin-bottom:14px}.cb-editor textarea{height:420px}.cb-preview{height:520px}.cb-savebar{align-items:flex-start;flex-direction:column}}
</style>
@endsection

@section('content')
@php
    $previewProduct = $primaryProduct;
    $previewTokens = [
        'product_name' => strip_tags($previewProduct?->name ?? $campaign->name),
        'product_price' => number_format((float)($previewProduct?->new_price ?? 0), 0),
        'old_price' => number_format((float)($previewProduct?->old_price ?? 0), 0),
        'description' => strip_tags($campaign->short_description ?: $campaign->description),
        'featured_image' => $previewProduct ? asset(optional($previewProduct->image)->image ?? 'public/uploads/default.webp') : '',
        'stock' => (string) max(0, (int)($previewProduct?->stock ?? 0)),
    ];
@endphp
<div class="container-fluid code-builder">
    <div class="cb-top">
        <div>
            <a href="{{ route('campaign.index') }}" class="text-muted small"><i class="fe-arrow-left"></i> Landing pages</a>
            <h3>{{ $campaign->name }}</h3>
            <div class="mt-1">
                @if($campaign->is_published && $campaign->custom_page_published_at)
                    <span class="cb-status cb-live">● Custom page live</span>
                @elseif($campaign->is_published)
                    <span class="cb-status cb-draft">● Default/visual page live</span>
                @else
                    <span class="cb-status cb-off">● Unpublished</span>
                @endif
            </div>
        </div>
        <div class="cb-actions">
            @if($campaign->is_published)
                <a href="{{ route('campaign', $campaign->slug) }}" target="_blank" class="btn btn-outline-primary"><i class="fe-external-link me-1"></i> View live</a>
            @endif
            <form action="{{ route('campaign.custom-builder.template', $campaign->id) }}" method="POST" onsubmit="return confirm('AI Studio ডিজাইন লোড করলে বর্তমান draft editors প্রতিস্থাপিত হবে। চালিয়ে যাবেন?');">@csrf<button class="btn btn-dark"><i class="fe-layout me-1"></i> AI Studio ডিজাইন লোড করুন</button></form>
            <form action="{{ route('campaign.custom-builder.publish', $campaign->id) }}" method="POST" onsubmit="return confirm('Publish the currently saved draft?');">@csrf<button class="btn btn-success"><i class="fe-upload-cloud me-1"></i> Publish saved draft</button></form>
            @if($campaign->is_published)
                <form action="{{ route('campaign.unpublish', $campaign->id) }}" method="POST" onsubmit="return confirm('Unpublish this landing page? Drafts and data will be kept.');">@csrf<button class="btn btn-outline-danger"><i class="fe-eye-off me-1"></i> Unpublish</button></form>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger"><strong>Please fix the following:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="cb-grid">
        <section class="cb-card">
            <div class="cb-card-head"><strong><i class="fe-code me-1"></i> HTML / CSS / JavaScript</strong><button type="button" id="cb-refresh" class="btn btn-sm btn-outline-secondary"><i class="fe-refresh-cw"></i> Preview</button></div>

            <form class="cb-upload" action="{{ route('campaign.custom-builder.upload', $campaign->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <label>HTML<input type="file" name="html_file" accept=".html,.htm,text/html"></label>
                <label>CSS<input type="file" name="css_file" accept=".css,text/css"></label>
                <label>JavaScript<input type="file" name="js_file" accept=".js,.mjs,text/javascript"></label>
                <button class="btn btn-sm btn-dark" type="submit"><i class="fe-upload me-1"></i> Import files</button>
            </form>

            <form id="cb-source-form" action="{{ route('campaign.custom-builder.draft', $campaign->id) }}" method="POST">
                @csrf
                <div class="cb-tabs" role="tablist">
                    <button class="cb-tab active" type="button" data-editor="html">HTML</button>
                    <button class="cb-tab" type="button" data-editor="css">CSS</button>
                    <button class="cb-tab" type="button" data-editor="js">JavaScript</button>
                </div>
                <div class="cb-editor active" data-editor-pane="html"><textarea id="cb-html" name="custom_html" spellcheck="false" placeholder="Use @{{ product_name }}, @{{ products }} and @{{ checkout }} in your HTML.">{{ old('custom_html', $campaign->custom_html_draft) }}</textarea></div>
                <div class="cb-editor" data-editor-pane="css"><textarea id="cb-css" name="custom_css" spellcheck="false" placeholder="/* Styles are scoped by your own selectors */">{{ old('custom_css', $campaign->custom_css_draft) }}</textarea></div>
                <div class="cb-editor" data-editor-pane="js"><textarea id="cb-js" name="custom_js" spellcheck="false" placeholder="// Runs after the live commerce components are mounted">{{ old('custom_js', $campaign->custom_js_draft) }}</textarea></div>
                <div class="cb-token-list" aria-label="Available dynamic variables">
                    @foreach(\App\Services\CampaignCustomPageService::TOKENS as $token)
                        <button class="cb-token" type="button" data-token="&#123;&#123; {{ $token }} &#125;&#125;">&#123;&#123; {{ $token }} &#125;&#125;</button>
                    @endforeach
                </div>
                <div class="cb-help">আপনি সম্পূর্ণ HTML ডকুমেন্ট পেস্ট করতে পারবেন। Inline &lt;style&gt; ও inline &lt;script&gt; ব্লক স্বয়ংক্রিয়ভাবে CSS ও JavaScript draft-এ চলে যায় — কোনো কোড হারায় না। &lt;link&gt;, &lt;meta&gt; ও CDN &lt;script src&gt; (Tailwind, Font Awesome ইত্যাদি) HTML-এ থেকে যায়। Product/checkout placeholder না থাকলে লাইভ Laravel কম্পোনেন্ট স্বয়ংক্রিয়ভাবে যোগ হয়।</div>
                <div class="cb-savebar"><span class="cb-save-state" id="cb-save-state">Draft changes are local until saved.</span><button class="btn btn-primary px-4" type="submit"><i class="fe-save me-1"></i> Save draft</button></div>
            </form>
        </section>

        <section class="cb-card">
            <div class="cb-card-head"><div><strong>Live preview</strong><div class="small text-muted">Responsive, isolated preview of the current editors</div></div><div class="btn-group btn-group-sm"><button type="button" class="btn btn-outline-secondary" data-width="100%">Desktop</button><button type="button" class="btn btn-outline-secondary" data-width="768px">Tablet</button><button type="button" class="btn btn-outline-secondary" data-width="390px">Mobile</button></div></div>
            <div style="overflow:auto;background:#dfe5ee;text-align:center;padding:10px;min-height:680px"><iframe id="cb-preview" class="cb-preview" sandbox="allow-scripts" title="Landing page live preview"></iframe></div>
        </section>
    </div>
</div>
@endsection

@section('script')
<script>
(function(){
    const html = document.getElementById('cb-html');
    const css = document.getElementById('cb-css');
    const js = document.getElementById('cb-js');
    const frame = document.getElementById('cb-preview');
    const state = document.getElementById('cb-save-state');
    const values = @json($previewTokens);
    let timer;

    function esc(value){return String(value == null ? '' : value).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));}
    function dynamicMarkup(type){
        if(type === 'reviews') return '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px"><div style="padding:28px;background:#fff1f2;border-radius:12px;text-align:center">Customer review</div><div style="padding:28px;background:#fff1f2;border-radius:12px;text-align:center">Customer review</div><div style="padding:28px;background:#fff1f2;border-radius:12px;text-align:center">Customer review</div></div>';
        if(type === 'products') return '<div style="padding:28px;border:1px solid #e5e7eb;border-radius:14px;background:white;text-align:center"><b>'+esc(values.product_name)+'</b><p>৳ '+esc(values.product_price)+'</p><button style="padding:12px 22px;border:0;border-radius:8px;background:#dc2626;color:#fff">Select product</button></div>';
        return '<div style="padding:30px;border:2px dashed #16a34a;border-radius:14px;background:#f0fdf4;text-align:center"><b>Live Laravel checkout</b><p style="margin-bottom:0">Customer form, shipping, coupon, order bumps and cart appear here.</p></div>';
    }
    function render(){
        let body = html.value;
        Object.keys(values).forEach(key => { body = body.replace(new RegExp('\\{\\{\\s*'+key+'\\s*\\}\\}','gi'), esc(values[key])); });
        body = body.replace(/\{\{\s*reviews\s*\}\}/gi, dynamicMarkup('reviews')).replace(/\{\{\s*products\s*\}\}/gi, dynamicMarkup('products')).replace(/\{\{\s*checkout\s*\}\}/gi, dynamicMarkup('checkout'));
        const safeJs = js.value.replace(new RegExp('<\\/script', 'gi'), '<\\\\/script');
        frame.srcdoc = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>html{scroll-behavior:smooth}body{margin:0;font-family:system-ui,sans-serif;color:#172033}*{box-sizing:border-box}img{max-width:100%;height:auto}'+css.value+'</style></head><body>'+body+'<scr'+'ipt>'+safeJs+'</scr'+'ipt></body></html>';
        state.textContent = 'Preview updated. Save the draft to keep these changes.';
    }
    function schedule(){state.textContent='Unsaved changes…';clearTimeout(timer);timer=setTimeout(render,500);}
    [html,css,js].forEach(el => el.addEventListener('input', schedule));
    document.getElementById('cb-refresh').addEventListener('click', render);
    document.querySelectorAll('.cb-tab').forEach(tab => tab.addEventListener('click', () => {document.querySelectorAll('.cb-tab').forEach(x=>x.classList.remove('active'));document.querySelectorAll('.cb-editor').forEach(x=>x.classList.remove('active'));tab.classList.add('active');document.querySelector('[data-editor-pane="'+tab.dataset.editor+'"]').classList.add('active');}));
    document.querySelectorAll('[data-token]').forEach(btn => btn.addEventListener('click', () => {const active=document.querySelector('.cb-editor.active textarea');const start=active.selectionStart;active.setRangeText(btn.dataset.token,start,active.selectionEnd,'end');active.focus();schedule();}));
    document.querySelectorAll('[data-width]').forEach(btn => btn.addEventListener('click',()=>{frame.style.width=btn.dataset.width;}));
    render();
})();
</script>
@endsection
