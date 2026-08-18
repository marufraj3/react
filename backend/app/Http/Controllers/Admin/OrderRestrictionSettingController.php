<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Models\OrderRestrictionWhitelist;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class OrderRestrictionSettingController extends Controller
{
    public function index()
    {
        $data = GeneralSetting::first();

        $hasSwitch = Schema::hasColumn('general_settings', 'order_limit_enabled');

        $whitelist = Schema::hasTable('order_restriction_whitelists')
            ? OrderRestrictionWhitelist::latest()->paginate(20)
            : collect();

        $hasWhitelistTable = Schema::hasTable('order_restriction_whitelists');

        return view('backEnd.order_restriction_setting.index', compact(
            'data',
            'hasSwitch',
            'whitelist',
            'hasWhitelistTable'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'order_limit_time' => 'required|integer|min:1',
            'order_limit_qty'  => 'required|integer|min:1',
        ]);

        $setting = GeneralSetting::first();

        if (!$setting) {
            Toastr::error('জেনারেল সেটিংস পাওয়া যায়নি।', 'Error');

            return redirect()->back();
        }

        $setting->order_limit_time = $request->order_limit_time;
        $setting->order_limit_qty  = $request->order_limit_qty;

        // চেকবক্স না থাকলে ব্রাউজার কিছুই পাঠায় না — তাই সরাসরি boolean করি
        if (Schema::hasColumn('general_settings', 'order_limit_enabled')) {
            $setting->order_limit_enabled = $request->boolean('order_limit_enabled');
        }

        $setting->save();

        Toastr::success('অর্ডার রেস্ট্রিকশন সেটিংস আপডেট হয়েছে।', 'Success!');

        return redirect()->back();
    }

    /** হোয়াইটলিস্টে নতুন ফোন নম্বর যোগ */
    public function storeWhitelist(Request $request)
    {
        if (!Schema::hasTable('order_restriction_whitelists')) {
            Toastr::error('হোয়াইটলিস্ট টেবিলটি এখনো তৈরি হয়নি। মাইগ্রেশন চালান।', 'Error');

            return redirect()->back();
        }

        $request->validate([
            'phone' => 'required|string|max:30',
            'name'  => 'nullable|string|max:120',
            'note'  => 'nullable|string|max:255',
        ]);

        $phone = OrderRestrictionWhitelist::normalize($request->phone);

        if ($phone === '') {
            Toastr::error('সঠিক ফোন নম্বর দিন।', 'Error');

            return redirect()->back();
        }

        if (OrderRestrictionWhitelist::where('phone', $phone)->exists()) {
            Toastr::warning('এই নম্বরটি আগে থেকেই হোয়াইটলিস্টে আছে।', 'Warning');

            return redirect()->back();
        }

        OrderRestrictionWhitelist::create([
            'phone' => $phone,
            'name'  => $request->name,
            'note'  => $request->note,
        ]);

        Toastr::success('নম্বরটি হোয়াইটলিস্টে যোগ হয়েছে।', 'Success');

        return redirect()->back();
    }

    public function destroyWhitelist($id)
    {
        $row = OrderRestrictionWhitelist::findOrFail($id);
        $row->delete();

        Toastr::success('নম্বরটি হোয়াইটলিস্ট থেকে সরানো হয়েছে।', 'Success');

        return redirect()->back();
    }
}
