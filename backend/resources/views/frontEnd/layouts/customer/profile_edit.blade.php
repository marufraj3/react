@extends('frontEnd.layouts.master')

@section('title', 'Edit Profile')

@section('content')
<div class="sf-page">
    <div class="sf-container">
        <div class="sf-account">
            @include('frontEnd.layouts.customer.sidebar')

            <div class="sf-apanel" style="max-width:720px">
                <h3><i class="fa-solid fa-user-pen"></i> Edit Profile</h3>

                <form action="{{ route('customer.profile_update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div style="display:flex;gap:16px;align-items:center;margin-bottom:20px">
                        @if($profile_edit->image)
                            <img src="{{ asset($profile_edit->image) }}" alt="" style="width:74px;height:74px;border-radius:50%;object-fit:cover;border:2.5px solid var(--c-primary-50)" />
                        @else
                            <span style="width:74px;height:74px;border-radius:50%;background:var(--c-primary-50);color:var(--c-primary);display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800">{{ strtoupper(substr($profile_edit->name ?? 'U', 0, 1)) }}</span>
                        @endif
                        <div>
                            <label class="sf-btn sf-btn--outline sf-btn--sm" style="cursor:pointer">
                                <i class="fa-solid fa-camera"></i> Change Photo
                                <input type="file" name="image" accept="image/*" style="display:none" />
                            </label>
                            <div class="sf-faint" style="font-size:11px;margin-top:6px">JPG/PNG, max 2MB</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="sf-field"><label>Full Name <span class="req">*</span></label>
                                <input type="text" name="name" class="sf-input" value="{{ $profile_edit->name }}" required /></div>
                        </div>
                        <div class="col-md-6">
                            <div class="sf-field"><label>Mobile Number <span class="req">*</span></label>
                                <input type="text" name="phone" class="sf-input" value="{{ $profile_edit->phone }}" required /></div>
                        </div>
                        <div class="col-md-6">
                            <div class="sf-field"><label>Email</label>
                                <input type="email" name="email" class="sf-input" value="{{ $profile_edit->email }}" /></div>
                        </div>
                        <div class="col-md-6">
                            <div class="sf-field"><label>District</label>
                                <select name="district" class="sf-select district">
                                    <option value="">Select district…</option>
                                    @foreach($districts as $district)
                                        <option value="{{ $district->district }}" @if($profile_edit->district == $district->district) selected @endif>{{ $district->district }}</option>
                                    @endforeach
                                </select></div>
                        </div>
                        <div class="col-md-6">
                            <div class="sf-field"><label>Area</label>
                                <select name="area" class="sf-select area">
                                    <option value="">Select area…</option>
                                    @foreach($areas as $area)
                                        <option value="{{ $area->area_name }}" @if($profile_edit->area == $area->area_name) selected @endif>{{ $area->area_name }}</option>
                                    @endforeach
                                </select></div>
                        </div>
                        <div class="col-12">
                            <div class="sf-field"><label>Address</label>
                                <textarea name="address" class="sf-textarea" style="min-height:80px">{{ $profile_edit->address }}</textarea></div>
                        </div>
                    </div>

                    <button type="submit" class="sf-btn sf-btn--primary sf-btn--lg" style="margin-top:6px"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $(".district").on("change", function () {
        var id = $(this).val();
        $.ajax({
            type: "GET", data: { id: id }, url: "{{ route('districts') }}",
            success: function (res) {
                if (res) {
                    $(".area").empty().append('<option value="">Select area…</option>');
                    $.each(res, function (key, value) { $(".area").append('<option value="' + value + '">' + value + "</option>"); });
                } else { $(".area").empty(); }
            }
        });
    });
</script>
@endpush
