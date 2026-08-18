@extends('frontEnd.layouts.master')

@section('title', 'Create Account')

@section('content')
<div class="sf-auth">
    <div class="sf-auth__deco"></div>
    <a class="sf-auth__back" href="{{ route('home') }}"><i class="fa-solid fa-arrow-left"></i> Back to Shop</a>

    <div class="sf-auth__card" style="max-width:520px">
        <a class="sf-auth__logo" href="{{ route('home') }}">
            @if(!empty(optional($generalsetting)->dark_logo))
                <img src="{{ asset(optional($generalsetting)->dark_logo) }}" alt="{{ optional($generalsetting)->name }}" />
            @else
                <span class="sf-logo__mark">SG</span>
            @endif
        </a>
        <h2>Create Account</h2>
        <p class="sub">Join us for faster checkout, order tracking & exclusive deals.</p>

        @if($errors->any())
            <div class="sf-form-msg sf-form-msg--error">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('customer.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="sf-field"><label>Full Name <span class="req">*</span></label>
                        <input type="text" name="name" class="sf-input" value="{{ old('name') }}" required /></div>
                </div>
                <div class="col-md-6">
                    <div class="sf-field"><label>Mobile Number <span class="req">*</span></label>
                        <input type="text" name="phone" class="sf-input" value="{{ old('phone') }}" minlength="11" maxlength="11" pattern="0[0-9]+" placeholder="017xxxxxxxx" required /></div>
                </div>
                <div class="col-md-6">
                    <div class="sf-field"><label>Email</label>
                        <input type="email" name="email" class="sf-input" value="{{ old('email') }}" placeholder="you@mail.com" /></div>
                </div>
                <div class="col-md-6">
                    <div class="sf-field"><label>Address</label>
                        <input type="text" name="address" class="sf-input" value="{{ old('address') }}" placeholder="House, Road, Area" /></div>
                </div>
                <div class="col-md-6">
                    <div class="sf-field"><label>Password <span class="req">*</span></label>
                        <input type="password" name="password" class="sf-input" minlength="8" required /></div>
                </div>
                <div class="col-md-6">
                    <div class="sf-field"><label>Confirm Password <span class="req">*</span></label>
                        <input type="password" name="password_confirmation" class="sf-input" minlength="8" required /></div>
                </div>
            </div>

            {{-- Optional: open a vendor shop --}}
            <div style="border:1px dashed var(--c-line);border-radius:var(--r-md);padding:14px 16px;margin-bottom:16px">
                <label class="sf-check" style="font-weight:700;color:var(--c-text)"><input type="checkbox" name="is_seller" value="1" id="regIsSeller" /> I want to register as a <b>Seller</b> (open my own shop)</label>

                <div id="regSellerBlock" style="display:none;margin-top:14px">
                    <div class="sf-field"><label>Shop Name <span class="req">*</span></label>
                        <input type="text" name="shop_name" class="sf-input" value="{{ old('shop_name') }}" placeholder="My Shop" /></div>
                    <div class="sf-field"><label>Shop Slug (URL)</label>
                        <input type="text" name="slug" class="sf-input" value="{{ old('slug') }}" placeholder="my-shop" /></div>
                    <div class="sf-field"><label>Shop Logo</label>
                        <input type="file" name="logo" class="sf-input" accept="image/*" /></div>
                    <div class="sf-field"><label>Shop Banner</label>
                        <input type="file" name="banner" class="sf-input" accept="image/*" /></div>
                </div>

            </div>

            <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block"><i class="fa-solid fa-user-plus"></i> Create Account</button>
        </form>

        <div class="sf-auth__foot">Already have an account? <a href="{{ route('customer.login') }}">Login here</a></div>
    </div>
</div>
@endsection

@push('script')
<script>
    $('#regIsSeller').on('change', function () { $('#regSellerBlock').toggle(this.checked); });
</script>
@endpush
