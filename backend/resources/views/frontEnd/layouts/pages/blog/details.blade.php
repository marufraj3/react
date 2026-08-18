@extends('frontEnd.layouts.master')

@section('title', $blog->title)

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <nav class="sf-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><i class="fa-solid fa-angle-right"></i>
            <a href="{{ route('blogs') }}">Blog</a><i class="fa-solid fa-angle-right"></i>
            <span class="cur">{{ Str::limit($blog->title, 40) }}</span>
        </nav>

        <div style="display:grid;grid-template-columns:1fr;gap:22px;align-items:start">
            <article class="sf-cms" style="max-width:960px">
                <img src="{{ asset($blog->image) }}" alt="{{ $blog->title }}" style="width:100%;border-radius:var(--r-md);margin-bottom:20px" />
                <div class="sf-blog__meta" style="margin-bottom:12px">
                    <span><i class="fa-regular fa-calendar" style="margin-right:5px"></i>{{ optional($blog->created_at)->format('M d, Y') }}</span>
                </div>
                <h1 style="font-size:25px;line-height:1.35;margin-bottom:14px">{{ $blog->title }}</h1>
                <div class="sf-prose">{!! $blog->description !!}</div>
            </article>
        </div>

        @if($recentBlogs->count() > 1)
            <div class="sf-sec-head">
                <div><h2 class="sf-sec-head__ttl">More Articles</h2></div>
            </div>
            <div class="sf-blogs">
                @foreach($recentBlogs->where('id', '!=', $blog->id)->take(3) as $item)
                    <article class="sf-blog">
                        <a href="{{ route('blog.details', $item->slug) }}"><img src="{{ asset($item->image) }}" alt="{{ $item->title }}" loading="lazy" /></a>
                        <div class="sf-blog__body">
                            <h4><a href="{{ route('blog.details', $item->slug) }}">{{ $item->title }}</a></h4>
                            <p class="sf-clamp-2">{{ Str::limit(strip_tags($item->description ?? ''), 100) }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
