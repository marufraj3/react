@extends('frontEnd.layouts.master')

@section('title', $page->name)

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <nav class="sf-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><i class="fa-solid fa-angle-right"></i>
            <span class="cur">{{ $page->name }}</span>
        </nav>

        <div class="sf-cms">
            <h1 style="font-size:24px;margin-bottom:16px">{{ $page->name }}</h1>
            <div class="sf-prose">{!! $page->details ?? '' !!}</div>
        </div>
    </div>
</div>
@endsection
