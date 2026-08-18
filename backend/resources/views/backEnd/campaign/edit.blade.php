@extends('backEnd.layouts.master')
@section('title','Edit Landing Page')
@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('public/backEnd')}}/assets/libs/summernote/summernote-lite.min.css" rel="stylesheet" type="text/css" />
<style>
    .lp-setup { max-width: 1180px; margin: 0 auto; }
    .lp-hero { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; margin-bottom:22px; flex-wrap:wrap; }
    .lp-kicker { display:inline-flex; margin-bottom:6px; padding:4px 10px; border-radius:999px; background:#f3e8ff; color:#6d28d9; font-size:11px; font-weight:800; }
    .lp-hero h4 { margin:0; font-weight:800; color:#172033; }
    .lp-hero p { margin:6px 0 0; color:#6b7280; font-size:13px; }
    .lp-actions { display:flex; flex-wrap:wrap; gap:8px; }
    .lp-actions .btn { border-radius:999px; font-weight:700; }
    .btn-builder { background:#7c3aed; border-color:#7c3aed; color:#fff; }
    .btn-builder:hover { background:#6d28d9; color:#fff; }
    .lp-grid { display:grid; grid-template-columns:minmax(0,1.5fr) minmax(280px,.8fr); gap:20px; }
    .lp-card { border:0; border-radius:16px; box-shadow:0 12px 32px rgba(23,32,51,.06); overflow:hidden; }
    .lp-card .card-body { padding:22px; }
    .lp-section-title { margin-bottom:16px; font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; color:#6d28d9; }
    .lp-help { display:block; margin-top:6px; color:#8b95a5; font-size:12px; }
    .lp-thumb { max-width:100%; max-height:72px; margin-top:8px; border-radius:8px; object-fit:cover; }
    .lp-status { background:linear-gradient(180deg,#1e1633,#111827); color:#fff; }
    .lp-status h5, .lp-status p { color:#fff; }
    .lp-status p { color:#d1d5db; font-size:13px; }
    .builder-on { display:inline-flex; padding:3px 8px; border-radius:999px; background:#dcfce7; color:#166534; font-size:11px; font-weight:800; }
    .builder-off { display:inline-flex; padding:3px 8px; border-radius:999px; background:#fee2e2; color:#991b1b; font-size:11px; font-weight:800; }
    .lp-submit { min-height:48px; border:0; border-radius:12px; background:#7c3aed; color:#fff; font-weight:800; }
    .select2-container { width:100% !important; }
    .product_img { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
    .product_img img { width:64px; height:64px; object-fit:cover; border-radius:8px; }
    @media (max-width: 991px) { .lp-grid { display:block; } }
</style>
@endsection
@section('content')
@php
    $selectedIds = collect($select_products)->pluck('id')->push($edit_data->product_id)->filter()->unique();
    $hasBuilder = !empty($edit_data->page_html);
@endphp
<div class="container-fluid lp-setup">
    <div class="lp-hero">
        <div>
            <span class="lp-kicker">LANDING PAGE SETTINGS</span>
            <h4>{{ $edit_data->name }}</h4>
            <p>প্রোডাক্ট ও মিডিয়া এখানে আপডেট করুন। পেজের লেআউট Visual Builder-এ এডিট হয়।</p>
        </div>
        <div class="lp-actions">
            <a href="{{ route('campaign', $edit_data->slug) }}" target="_blank" rel="noopener" class="btn btn-success">
                <i class="fe-eye me-1"></i> Preview
            </a>
            <a href="{{ route('campaign.builder', $edit_data->id) }}" class="btn btn-builder">
                <i class="fe-layout me-1"></i> Visual Builder
            </a>
            <a href="{{ route('campaign.index') }}" class="btn btn-light">সব পেজ</a>
        </div>
    </div>

    <form action="{{ route('campaign.update') }}" method="POST" enctype="multipart/form-data" data-parsley-validate>
        @csrf
        <input type="hidden" name="hidden_id" value="{{ $edit_data->id }}">
        <div class="lp-grid">
            <div class="card lp-card">
                <div class="card-body">
                    <div class="lp-section-title">Campaign settings</div>
                    <div class="mb-3">
                        <label class="form-label" for="name">Landing page title *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name', $edit_data->name) }}" required>
                        @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="product_id">Products *</label>
                        <select class="select2 form-control" name="product_id[]" id="product_id" multiple required>
                            @foreach($products as $value)
                                <option value="{{ $value->id }}" @selected($selectedIds->contains($value->id))>{{ $value->name }}</option>
                            @endforeach
                        </select>
                        @error('product_id')<span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="deadline">Offer deadline</label>
                            <input type="datetime-local" class="form-control" name="deadline" id="deadline" value="{{ old('deadline', $edit_data->deadline ? \Illuminate\Support\Str::of($edit_data->deadline)->replace(' ', 'T')->substr(0, 16) : '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="video">YouTube URL / ID</label>
                            <input type="text" class="form-control" name="video" id="video" value="{{ old('video', $edit_data->video) }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="review">Review / offer headline</label>
                        <input type="text" class="form-control" name="review" id="review" value="{{ old('review', $edit_data->review) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Short description</label>
                        <textarea name="short_description" class="summernote form-control">{{ old('short_description', $edit_data->short_description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="summernote form-control">{{ old('description', $edit_data->description) }}</textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label class="d-block">Status</label>
                        <label class="switch">
                            <input type="checkbox" value="1" name="status" @checked((int) $edit_data->status === 1)>
                            <span class="slider round"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div>
                <div class="card lp-card lp-status mb-3">
                    <div class="card-body">
                        <h5 class="mb-2">Publish status</h5>
                        <p class="mb-3">
                            @if($hasBuilder)
                                <span class="builder-on">Visual design active</span>
                            @else
                                <span class="builder-off">Builder এখনো publish হয়নি</span>
                            @endif
                        </p>
                        <a href="{{ route('campaign.builder', $edit_data->id) }}" class="btn btn-builder w-100 mb-2">Visual Builder খুলুন</a>
                        <a href="{{ route('campaign', $edit_data->slug) }}" target="_blank" rel="noopener" class="btn btn-outline-light w-100">লাইভ Preview</a>
                    </div>
                </div>
                <div class="card lp-card mb-3">
                    <div class="card-body">
                        <div class="lp-section-title">Media</div>
                        <div class="mb-3">
                            <label class="form-label">Banner</label>
                            <input type="file" class="form-control" name="banner" accept="image/jpeg,image/png,image/webp">
                            @if($edit_data->banner)<img src="{{ asset($edit_data->banner) }}" class="lp-thumb" alt="">@endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image one</label>
                            <input type="file" class="form-control" name="image_one" accept="image/jpeg,image/png,image/webp">
                            @if($edit_data->image_one)<img src="{{ asset($edit_data->image_one) }}" class="lp-thumb" alt="">@endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image two</label>
                            <input type="file" class="form-control" name="image_two" accept="image/jpeg,image/png,image/webp">
                            @if($edit_data->image_two)<img src="{{ asset($edit_data->image_two) }}" class="lp-thumb" alt="">@endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image three</label>
                            <input type="file" class="form-control" name="image_three" accept="image/jpeg,image/png,image/webp">
                            @if($edit_data->image_three)<img src="{{ asset($edit_data->image_three) }}" class="lp-thumb" alt="">@endif
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Review images</label>
                            <input type="file" class="form-control" name="image[]" accept="image/jpeg,image/png,image/webp" multiple>
                            <div class="product_img">
                                @foreach($edit_data->images as $image)
                                    <div>
                                        <img src="{{ asset($image->image) }}" alt="">
                                        <a href="{{ route('campaign.image.destroy', ['id' => $image->id]) }}" class="btn btn-xs btn-danger">×</a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn lp-submit w-100">সেভ করে Builder-এ যান →</button>
            </div>
        </div>
    </form>
</div>
@endsection
@section('script')
<script src="{{asset('public/backEnd/')}}/assets/libs/parsleyjs/parsley.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/js/pages/form-validation.init.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs/select2/js/select2.min.js"></script>
<script src="{{asset('public/backEnd/')}}/assets/libs/summernote/summernote-lite.min.js"></script>
<script>
    $(".summernote").summernote({ placeholder: "লিখুন...", height: 140 });
    $('.select2').select2({ width: '100%' });
</script>
@endsection
