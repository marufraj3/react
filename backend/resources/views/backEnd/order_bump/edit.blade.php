@extends('backEnd.layouts.master')
@section('title', 'Edit Order Bump')

@section('css')
@include('backEnd.order_bump._styles')
@endsection

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8">

            <form action="{{ route('admin.order_bumps.update', $bump->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card card-modern">
                    <div class="card-header-modern">
                        <div>
                            <h5 class="mb-1 fw-bold text-dark">Edit Order Bump</h5>
                            <p class="text-muted small mb-0">
                                {{ number_format($bump->impressions) }} বার দেখানো হয়েছে,
                                {{ number_format($bump->conversions) }} বার গ্রহণ করা হয়েছে।
                            </p>
                        </div>
                        <a href="{{ route('admin.order_bumps.index') }}" class="btn btn-sm btn-light border rounded-pill px-3">
                            <i data-feather="arrow-left" style="width:14px;"></i> Back
                        </a>
                    </div>

                    @include('backEnd.order_bump._form')
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('backEnd.order_bump._scripts')
@endpush
