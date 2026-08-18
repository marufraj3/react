@extends('layouts.app')
@section('title', 'Confirm Password')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="sf-card-surface" style="padding:30px">
            <h2 class="fw-bold text-center mb-1">Confirm Password</h2>
            <p class="text-center sf-faint mb-4" style="font-size:13.5px">Please confirm your password before continuing.</p>
            @if($errors->any())<div class="sf-form-msg sf-form-msg--error">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf
                <div class="sf-field">
                    <label>Password <span class="req">*</span></label>
                    <input id="password" type="password" class="sf-input" name="password" required autofocus />
                </div>
                <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg sf-btn--block">Confirm Password</button>
            </form>
        </div>
    </div>
</div>
@endsection
