@php
    /** @var \App\Models\OrderBump|null $bump */
    $bump = $bump ?? null;
@endphp

<div class="card-body p-4">

    {{-- Product --}}
    <div class="mb-4">
        <label class="form-label-custom">অফার প্রোডাক্ট <span class="text-danger">*</span></label>
        <select name="product_id" class="form-select form-select-custom" required>
            <option value="">— প্রোডাক্ট বেছে নিন —</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}"
                    {{ (string) old('product_id', $bump->product_id ?? '') === (string) $product->id ? 'selected' : '' }}>
                    {{ $product->name }} (৳{{ number_format((float) $product->new_price, 0) }})
                </option>
            @endforeach
        </select>
        <small class="text-muted ms-1">কার্টে ইতিমধ্যে থাকা প্রোডাক্ট স্বয়ংক্রিয়ভাবে বাদ যাবে।</small>
    </div>

    {{-- Copy --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label-custom">অফার শিরোনাম</label>
            <input type="text" name="title" class="form-control form-control-custom"
                   value="{{ old('title', $bump->title ?? '') }}"
                   placeholder="যেমন: মাত্র ২৯৯ টাকায় যোগ করুন!">
        </div>
        <div class="col-md-6">
            <label class="form-label-custom">সাব-টেক্সট</label>
            <input type="text" name="subtitle" class="form-control form-control-custom"
                   value="{{ old('subtitle', $bump->subtitle ?? '') }}"
                   placeholder="যেমন: শুধু এই অর্ডারের জন্য বিশেষ ছাড়">
        </div>
    </div>

    {{-- Discount --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label-custom">ডিসকাউন্টের ধরন</label>
            <select name="discount_type" id="bump_discount_type" class="form-select form-select-custom">
                <option value="flat" {{ old('discount_type', $bump->discount_type ?? 'flat') === 'flat' ? 'selected' : '' }}>Fixed Amount (৳)</option>
                <option value="percent" {{ old('discount_type', $bump->discount_type ?? '') === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label-custom">ডিসকাউন্টের পরিমাণ <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text input-group-text-custom" id="bump_value_icon" style="border-radius:10px 0 0 10px;border-right:0;">
                    {{ old('discount_type', $bump->discount_type ?? 'flat') === 'percent' ? '%' : '৳' }}
                </span>
                <input type="number" step="0.01" min="0" name="discount_value" required
                       class="form-control form-control-custom border-start-0" style="border-radius:0 10px 10px 0;"
                       value="{{ old('discount_value', $bump->discount_value ?? '') }}" placeholder="0.00">
            </div>
        </div>
    </div>

    {{-- Targeting --}}
    <div class="p-3 bg-light rounded-3 mb-4 border">
        <h6 class="text-dark fw-bold mb-3 small text-uppercase">
            <i data-feather="filter" style="width:14px;" class="me-1"></i> কোথায় দেখাবে
        </h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label-custom mb-1">ক্যাম্পেইন</label>
                <select name="campaign_id" class="form-select form-select-custom">
                    <option value="">সব ক্যাম্পেইনে</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}"
                            {{ (string) old('campaign_id', $bump->campaign_id ?? '') === (string) $campaign->id ? 'selected' : '' }}>
                            {{ $campaign->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label-custom mb-1">সর্বনিম্ন কার্ট অ্যামাউন্ট</label>
                <div class="input-group">
                    <span class="input-group-text input-group-text-custom" style="border-radius:10px 0 0 10px;border-right:0;">৳</span>
                    <input type="number" step="0.01" min="0" name="min_cart_amount"
                           class="form-control form-control-custom border-start-0" style="border-radius:0 10px 10px 0;"
                           value="{{ old('min_cart_amount', $bump->min_cart_amount ?? '') }}" placeholder="ফাঁকা = সীমা নেই">
                </div>
            </div>
        </div>
    </div>

    {{-- Ordering + status --}}
    <div class="row g-3 mb-4 align-items-end">
        <div class="col-md-6">
            <label class="form-label-custom">সাজানোর ক্রম</label>
            <input type="number" min="0" name="sort_order" class="form-control form-control-custom"
                   value="{{ old('sort_order', $bump->sort_order ?? 0) }}">
            <small class="text-muted ms-1">ছোট সংখ্যা আগে দেখাবে। চেকআউটে সর্বোচ্চ ২টি বাম্প দেখানো হয়।</small>
        </div>
        <div class="col-md-6">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="bump_status" name="status" value="1"
                       {{ old('status', $bump->status ?? true) ? 'checked' : '' }}>
                <label class="form-check-label fw-bold" for="bump_status">চালু রাখুন</label>
            </div>
        </div>
    </div>

    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary py-2 fw-bold shadow-sm">
            <i data-feather="check-circle" class="me-1" style="width:16px;"></i> সংরক্ষণ করুন
        </button>
        <a href="{{ route('admin.order_bumps.index') }}" class="btn btn-light py-2">বাতিল</a>
    </div>
</div>
