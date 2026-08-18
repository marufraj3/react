<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;
use Brian2694\Toastr\Facades\Toastr;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::latest()->get();
        return view('backEnd.coupon.index', compact('coupons'));
    }

    public function create()
    {
        return view('backEnd.coupon.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:coupons',
            'type' => 'required',
            'value' => 'required|numeric|min:1',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
        ]);

        Coupon::create($this->payload($request));
        Toastr::success('Coupon created successfully', 'Success');
        return redirect()->route('admin.coupons.index');
    }

    public function edit($id)
    {
        $coupon = Coupon::findOrFail($id);
        return view('backEnd.coupon.edit', compact('coupon'));
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $request->validate([
            'code' => 'required|unique:coupons,code,' . $coupon->id,
            'type' => 'required',
            'value' => 'required|numeric|min:1',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
        ]);

        $coupon->update($this->payload($request));
        Toastr::success('Coupon updated successfully', 'Success');
        return redirect()->route('admin.coupons.index');
    }

    /**
     * সীমার ঘর ফাঁকা রাখলে "অসীম" বোঝায়, তাই খালি স্ট্রিংকে null-এ
     * রূপান্তর করি — নাহলে 0 সেভ হয়ে গিয়ে অন্যরকম অর্থ তৈরি হতো।
     */
    protected function payload(Request $request): array
    {
        $data = $request->except(['_token', '_method', 'used_count']);

        foreach (['usage_limit', 'usage_limit_per_customer'] as $field) {
            $value = $request->input($field);
            $data[$field] = ($value === null || $value === '' || (int) $value < 1)
                ? null
                : (int) $value;
        }

        return $data;
    }

    /**
     * একটি কুপনের ব্যবহারের হিসাব রিসেট করে (সীমা আবার শূন্য থেকে শুরু)।
     */
    public function resetUsage($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->forceFill(['used_count' => 0])->save();

        \App\Models\CouponUsage::where('coupon_id', $coupon->id)->delete();

        Toastr::success('কুপনটির ব্যবহারের হিসাব রিসেট করা হয়েছে।', 'Success');
        return redirect()->back();
    }

    public function destroy($id)
    {
        Coupon::destroy($id);
        Toastr::success('Coupon deleted successfully', 'Success');
        return redirect()->back();
    }
}
