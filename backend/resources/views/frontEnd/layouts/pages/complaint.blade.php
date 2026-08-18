@extends('frontEnd.layouts.master')

@section('title', 'File a Complaint')

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <div class="sf-page-head" style="border-radius:var(--r-lg);margin-top:18px">
            <div class="sf-container">
                <h1><i class="fa-solid fa-headset" style="color:#ffb02e;margin-right:10px"></i>File a Complaint</h1>
                <p style="color:#c3cdea;font-size:14px;margin-top:6px">Facing a problem with your order? We'll resolve it fast.</p>
            </div>
        </div>

        <div class="sf-cms" style="margin-top:22px">
            @if(session('success'))
                <div class="sf-form-msg sf-form-msg--success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="sf-form-msg sf-form-msg--error">{{ $errors->first() }}</div>
            @endif

            <form action="{{ route('complaint.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="sf-field"><label>Your Name <span class="req">*</span></label>
                            <input type="text" name="name" class="sf-input" value="{{ Auth::guard('customer')->user()->name ?? old('name') }}" required /></div>
                    </div>
                    <div class="col-md-6">
                        <div class="sf-field"><label>Mobile <span class="req">*</span></label>
                            <input type="text" name="phone" class="sf-input" value="{{ Auth::guard('customer')->user()->phone ?? old('phone') }}" required /></div>
                    </div>
                    <div class="col-12">
                        <div class="sf-field"><label>Order / Invoice ID (optional)</label>
                            <input type="text" name="order_id" class="sf-input" value="{{ old('order_id') }}" placeholder="e.g. INV-20240816-1234" /></div>
                    </div>
                    <div class="col-12">
                        <div class="sf-field"><label>What went wrong? <span class="req">*</span></label>
                            <textarea name="description" class="sf-textarea" required placeholder="Describe the issue in detail…">{{ old('description') }}</textarea></div>
                    </div>
                    <div class="col-12">
                        <div class="sf-field"><label>Attach Screenshot / Photo (optional)</label>
                            <input type="file" name="image" class="sf-input" accept="image/*" /></div>
                    </div>
                </div>
                <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg"><i class="fa-solid fa-paper-plane"></i> Submit Complaint</button>
            </form>
        </div>
    </div>
</div>
@endsection
