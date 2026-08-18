@extends('frontEnd.layouts.master')

@section('title', 'Blog')

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <div class="sf-page-head" style="border-radius:var(--r-lg);margin-top:18px">
            <div class="sf-container">
                <h1><i class="fa-solid fa-newspaper" style="color:#ffb02e;margin-right:10px"></i>Our Blog</h1>
                <p style="color:#c3cdea;font-size:14px;margin-top:6px">Tips, guides and shopping inspiration from our team.</p>
            </div>
        </div>

        @if($blogs->count())
            <div class="sf-blogs" style="margin-top:22px">
                @foreach($blogs as $blog)
                    <article class="sf-blog">
                        <a href="{{ route('blog.details', $blog->slug) }}"><img src="{{ asset($blog->image) }}" alt="{{ $blog->title }}" loading="lazy" /></a>
                        <div class="sf-blog__body">
                            <h4><a href="{{ route('blog.details', $blog->slug) }}">{{ $blog->title }}</a></h4>
                            <p class="sf-clamp-2">{{ Str::limit(strip_tags($blog->description ?? ''), 130) }}</p>
                            <div class="sf-blog__meta">
                                <span><i class="fa-regular fa-calendar" style="margin-right:5px"></i>{{ optional($blog->created_at)->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            {{ $blogs->onEachSide(1)->links('pagination::bootstrap-5') }}
        @else
            <div class="sf-empty sf-card-surface">
                <i class="fa-solid fa-newspaper"></i>
                <h4>No articles yet</h4>
                <p>Fresh content is on the way.</p>
            </div>
        @endif
    </div>
</div>
@endsection
