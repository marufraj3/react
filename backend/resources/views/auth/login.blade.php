@extends('layouts.app')
@section('title', 'Login')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="sf-card-surface" style="padding:30px">
            <h2 class="fw-bold text-center mb-1">Welcome Back</h2>
            <p class="text-center sf-faint mb-4" style="font-size:13.5px">Login to your account to continue.</p>
            @if($errors->any())
                <div class="sf-form-msg sf-form-msg--error">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="sf-field">
                    <label>Email Address <span class="req">*</span></label>
                    <input id="email" type="email" class="sf-input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus />
                </div>
                <div class="sf-field">
                    <label>Password <span class="req">*</span></label>
                    <input id="password" type="password" class="sf-input @error('password') is-invalid @enderror" name="password" required />
                </div>
                <label class="sf-check mb-3"><input type="checkbox" name="remember" /> Remember me</label>
                <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block">Login</button>
                @if (Route::has('password.request'))
                    <div class="text-center mt-3"><a class="sf-link" style="font-size:13px" href="{{ route('password.request') }}">Forgot your password?</a></div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
