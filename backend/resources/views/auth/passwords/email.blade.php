@extends('layouts.app')
@section('title', 'Reset Password')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="sf-card-surface" style="padding:30px">
            <h2 class="fw-bold text-center mb-1">Reset Password</h2>
            <p class="text-center sf-faint mb-4" style="font-size:13.5px">Enter your email and we'll send a reset link.</p>
            @if (session('status'))
                <div class="sf-form-msg sf-form-msg--success">{{ session('status') }}</div>
            @endif
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="sf-field">
                    <label>Email Address <span class="req">*</span></label>
                    <input id="email" type="email" class="sf-input" name="email" value="{{ old('email') }}" required autofocus />
                </div>
                @if($errors->any())<div class="sf-form-msg sf-form-msg--error">{{ $errors->first('email') }}</div>@endif
                <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block">Send Reset Link</button>
            </form>
        </div>
    </div>
</div>
@endsection
