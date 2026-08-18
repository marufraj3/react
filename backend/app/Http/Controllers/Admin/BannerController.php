<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BannerCategory;
use App\Models\Banner;
use Toastr;
use Image;
use File;
class BannerController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:banner-list|banner-create|banner-edit|banner-delete', ['only' => ['index','store']]);
         $this->middleware('permission:banner-create', ['only' => ['create','store']]);
         $this->middleware('permission:banner-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:banner-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = Banner::orderBy('id','DESC')->with('category')->get();
        return view('backEnd.banner.index',compact('data'));
    }
    public function create()
    {
        $categories = BannerCategory::orderBy('id','DESC')->select('id','name')->get();
        return view('backEnd.banner.create',compact('categories'));
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'link' => 'required',
            'status' => 'required',
        ]);
        
        // image with intervention 
        $file = $request->file('image');
        $name = time().$file->getClientOriginalName();
        $uploadPath = 'public/uploads/banner/';
        $file->move($uploadPath,$name);
        $fileUrl =$uploadPath.$name;

        $input = $request->all();
        $input['status'] = $request->status?1:0;
        $input['image'] = $fileUrl;
        Banner::create($input);
        $this->flushHomepageCache();
        Toastr::success('Success','Data insert successfully');
        return redirect()->route('banners.index');
    }
    
    public function edit($id)
    {
        $edit_data = Banner::find($id);
        $categories = BannerCategory::select('id','name')->get();
        return view('backEnd.banner.edit',compact('edit_data','categories'));
    }
    
    public function update(Request $request)
    {
        $this->validate($request, [
            'link' => 'required',
        ]);
        $update_data = Banner::find($request->id);
        $input = $request->all();
        $image = $request->file('image');
        if($image){
           // image with intervention 
            $file = $request->file('image');
            $name = time().$file->getClientOriginalName();
            $uploadPath = 'public/uploads/banner/';
            $file->move($uploadPath,$name);
            $fileUrl =$uploadPath.$name;
            $input['image'] = $fileUrl;
            File::delete($update_data->image);
        }else{
            $input['image'] = $update_data->image;
        }

        $input['status'] = $request->status?1:0;
        $update_data->update($input);
        $this->flushHomepageCache();

        Toastr::success('Success','Data update successfully');
        return redirect()->route('banners.index');
    }

    /**
     * হোমপেজে প্রদর্শিত ব্যানারগুলো (যেমন Slider Bottom) যাতে
     * অ্যাডমিন থেকে আপলোড/ডিলিট করার সাথে সাথেই দেখা যায়।
     */
    protected function flushHomepageCache()
    {
        // ভবিষ্যতে _v5, _v6 ... বাড়লেও ঝাড়াই — মাইনা থ্রেশহোল্ড নাম্বার সব মুছে দিই
        try {
            \Illuminate\Support\Facades\Cache::forget('frontend_homepage_v3');
            \Illuminate\Support\Facades\Cache::forget('frontend_homepage_v4');
        } catch (\Throwable $e) {
            // cache driver unavailable হলে চুপচাপ skip
        }
    }

    public function inactive(Request $request)
    {
        $inactive = Banner::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        $this->flushHomepageCache();
        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request)
    {
        $active = Banner::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        $this->flushHomepageCache();
        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }
    public function destroy(Request $request)
    {
        $delete_data = Banner::find($request->hidden_id);
        $delete_data->delete();
        $this->flushHomepageCache();
        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    }
}
