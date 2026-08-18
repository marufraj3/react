@extends('backEnd.layouts.master')
@section('title','Create Landing Page')
@section('css')
<link href="{{asset('public/backEnd')}}/assets/libs/summernote/summernote-lite.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('public/backEnd')}}/assets/libs/flatpickr/flatpickr.min.css" rel="stylesheet" type="text/css" />
<style>
    .lp-setup { max-width: 1180px; margin: 0 auto; }
    .lp-hero { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; margin-bottom:22px; }
    .lp-kicker { display:inline-flex; margin-bottom:6px; padding:4px 10px; border-radius:999px; background:#f3e8ff; color:#6d28d9; font-size:11px; font-weight:800; letter-spacing:.4px; }
    .lp-hero h4 { margin:0; font-weight:800; color:#172033; }
    .lp-hero p { margin:6px 0 0; color:#6b7280; font-size:13px; }
    .lp-grid { display:grid; grid-template-columns:minmax(0,1.5fr) minmax(280px,.8fr); gap:20px; }
    .lp-card { border:0; border-radius:16px; box-shadow:0 12px 32px rgba(23,32,51,.06); overflow:hidden; }
    .lp-card .card-body { padding:22px; }
    .lp-section-title { margin-bottom:16px; font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; color:#6d28d9; }
    .lp-help { display:block; margin-top:6px; color:#8b95a5; font-size:12px; }
    .lp-next { background:linear-gradient(180deg,#1e1633,#111827); color:#fff; }
    .lp-next h5 { color:#fff; font-weight:800; }
    .lp-next ol { margin:0; padding-left:18px; color:#d1d5db; font-size:13px; }
    .lp-next li { margin-bottom:8px; }
    .lp-next .lp-step { display:inline-flex; width:18px; height:18px; margin-right:6px; border-radius:50%; align-items:center; justify-content:center; background:#7c3aed; color:#fff; font-size:10px; font-weight:800; }
    .lp-submit { min-height:48px; border:0; border-radius:12px; background:#7c3aed; color:#fff; font-weight:800; }
    .lp-submit:hover { background:#6d28d9; color:#fff; }
    .select2-container { width:100% !important; }
    @media (max-width: 991px) { .lp-grid, .lp-hero { display:block; } .lp-hero .btn { margin-top:12px; } }
</style>
@endsection
@section('content')
<div class="container-fluid lp-setup">
    <div class="lp-hero">
        <div>
            <span class="lp-kicker">VISUAL BUILDER WORKFLOW</span>
            <h4>নতুন Landing Page</h4>
            <p>প্রথমে প্রোডাক্ট ও বেসিক তথ্য দিন। সেভ করার পর Visual Builder খুলবে — সেখানেই পেজ ডিজাইন ও প্রিভিউ হবে।</p>
        </div>
        <a href="{{ route('campaign.index') }}" class="btn btn-light rounded-pill">সব পেজ</a>
    </div>

    <form action="{{ route('campaign.store') }}" method="POST" enctype="multipart/form-data" data-parsley-validate>
        @csrf
        <div class="lp-grid">
            <div class="card lp-card">
                <div class="card-body">
                    <div class="lp-section-title">১. Campaign settings</div>
                    <div class="mb-3">
                        <label class="form-label" for="name">Landing page title *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name') }}" placeholder="যেমন: Summer Mega Offer" required>
                        @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="product_id">Products *</label>
                        <select class="select2 form-control @error('product_id') is-invalid @enderror" name="product_id[]" id="product_id" multiple data-placeholder="প্রোডাক্ট বেছে নিন" required>
                            @foreach($products as $value)
                                <option value="{{ $value->id }}" @selected(collect(old('product_id', []))->contains($value->id))>{{ $value->name }}</option>
                            @endforeach
                        </select>
                        <span class="lp-help">এই প্রোডাক্টগুলোই builder ও checkout-এ দেখাবে।</span>
                        @error('product_id')<span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="deadline">Offer deadline</label>
                            <input type="datetime-local" class="form-control" name="deadline" id="deadline" value="{{ old('deadline') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="video">YouTube URL / ID</label>
                            <input type="text" class="form-control" name="video" id="video" value="{{ old('video') }}" placeholder="https://youtu.be/...">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="review">Review / offer headline</label>
                        <input type="text" class="form-control" name="review" id="review" value="{{ old('review') }}" placeholder="কাস্টমার রিভিউ সেকশনের শিরোনাম">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Short description</label>
                        <textarea name="short_description" class="summernote form-control">{{ old('short_description') }}</textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="summernote form-control">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <div>
                <div class="card lp-card lp-next mb-3">
                    <div class="card-body">
                        <h5 class="mb-3">এরপর কী হবে?</h5>
                        <ol>
                            <li><span class="lp-step">1</span>এই ফর্ম সেভ করুন</li>
                            <li><span class="lp-step">2</span>Visual Builder খুলবে</li>
                            <li><span class="lp-step">3</span>Template বেছে পেজ সাজান</li>
                            <li><span class="lp-step">4</span>Preview দিয়ে লাইভ চেক করুন</li>
                        </ol>
                    </div>
                </div>
                <div class="card lp-card mb-3">
                    <div class="card-body">
                        <div class="lp-section-title">২. Media</div>
                        <div class="mb-3">
                            <label class="form-label" for="banner">Banner</label>
                            <input type="file" class="form-control" name="banner" id="banner" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="image_one">Image one</label>
                            <input type="file" class="form-control" name="image_one" id="image_one" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="image_two">Image two</label>
                            <input type="file" class="form-control" name="image_two" id="image_two" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="image_three">Image three</label>
                            <input type="file" class="form-control" name="image_three" id="image_three" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Review images</label>
                            <input type="file" class="form-control" name="image[]" accept="image/jpeg,image/png,image/webp" multiple>
                            <span class="lp-help">Builder-এর review gallery এই ছবিগুলো ব্যবহার করবে।</span>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn lp-submit w-100">সেভ করে Visual Builder খুলুন →</button>
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
