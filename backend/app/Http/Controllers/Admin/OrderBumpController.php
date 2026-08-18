<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\OrderBump;
use App\Models\Product;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class OrderBumpController extends Controller
{
    public function index()
    {
        $bumps = OrderBump::with(['product:id,name,new_price,old_price', 'product.image', 'campaign:id,name'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('backEnd.order_bump.index', compact('bumps'));
    }

    public function create()
    {
        return view('backEnd.order_bump.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        OrderBump::create($data);

        Toastr::success('অর্ডার বাম্প তৈরি হয়েছে', 'Success');

        return redirect()->route('admin.order_bumps.index');
    }

    public function edit($id)
    {
        $bump = OrderBump::findOrFail($id);

        return view('backEnd.order_bump.edit', $this->formData() + compact('bump'));
    }

    public function update(Request $request, $id)
    {
        $bump = OrderBump::findOrFail($id);

        $bump->update($this->validated($request));

        Toastr::success('অর্ডার বাম্প আপডেট হয়েছে', 'Success');

        return redirect()->route('admin.order_bumps.index');
    }

    public function destroy($id)
    {
        OrderBump::destroy($id);

        Toastr::success('অর্ডার বাম্প মুছে ফেলা হয়েছে', 'Success');

        return redirect()->back();
    }

    /**
     * দ্রুত চালু/বন্ধ করার টগল।
     */
    public function toggle($id)
    {
        $bump = OrderBump::findOrFail($id);
        $bump->update(['status' => !$bump->status]);

        Toastr::success($bump->status ? 'বাম্পটি চালু হয়েছে' : 'বাম্পটি বন্ধ হয়েছে', 'Success');

        return redirect()->back();
    }

    protected function formData(): array
    {
        return [
            'products'  => Product::select('id', 'name', 'new_price')->orderBy('name')->get(),
            'campaigns' => Campaign::select('id', 'name')->orderByDesc('id')->get(),
        ];
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'product_id'      => 'required|exists:products,id',
            'title'           => 'nullable|string|max:255',
            'subtitle'        => 'nullable|string|max:255',
            'discount_type'   => 'required|in:flat,percent',
            'discount_value'  => 'required|numeric|min:0',
            'min_cart_amount' => 'nullable|numeric|min:0',
            'campaign_id'     => 'nullable|exists:campaigns,id',
            'sort_order'      => 'nullable|integer|min:0',
        ]);

        // percent হলে ১০০-এর বেশি ছাড় মানে ঋণাত্মক দাম — আটকে দিই।
        if ($data['discount_type'] === 'percent') {
            $data['discount_value'] = min(100, (float) $data['discount_value']);
        }

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['status']     = $request->boolean('status');

        return $data;
    }
}
