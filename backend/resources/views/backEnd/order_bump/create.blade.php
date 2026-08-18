@extends('backEnd.layouts.master')
@section('title', 'Add Order Bump')

@section('css')
@include('backEnd.order_bump._styles')
@endsection

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8">

            <form action="{{ route('admin.order_bumps.store') }}" method="POST">
                @csrf

                <div class="card card-modern">
                    <div class="card-header-modern">
                        <div>
                            <h5 class="mb-1 fw-bold text-dark">Create Order Bump</h5>
                            <p class="text-muted small mb-0">চেকআউটে "এই অফারটিও যোগ করুন" উইজেট হিসেবে দেখানো হবে।</p>
                        </div>
                        <a href="{{ route('admin.order_bumps.index') }}" class="btn btn-sm btn-light border rounded-pill px-3">
                            <i data-feather="arrow-left" style="width:14px;"></i> Back
                        </a>
                    </div>

                    @include('backEnd.order_bump._form', ['bump' => null])
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('backEnd.order_bump._scripts')
@endpush
