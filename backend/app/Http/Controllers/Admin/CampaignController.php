<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\CampaignReview;
use App\Models\Campaign;
use App\Services\CampaignPageSanitizer;
use App\Services\CampaignCustomPageService;
use App\Models\OrderBump;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Image;
use Toastr;
use Str;
use File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $show_data = Campaign::orderBy('id','DESC')->get();

        // ⭐ প্রতিটি ক্যাম্পেইনের পারফরম্যান্স — এক কুয়েরিতে সব, N+1 এড়াতে
        $stats = app(\App\Services\CampaignAnalyticsService::class)->summaryForAll(30);

        return view('backEnd.campaign.index',compact('show_data','stats'));
    }

    /**
     * ⭐ এক ক্যাম্পেইনের বিস্তারিত অ্যানালিটিক্স — ভিজিট, কার্ট, অর্ডার,
     * কনভার্শন রেট ও দৈনিক ভাঙানো হিসাব।
     */
    public function analytics(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);
        $days     = (int) $request->query('days', 30);

        // অস্বাভাবিক রেঞ্জ দিলে ডিফল্টে ফিরে যাই — নাহলে বিশাল কুয়েরি হতে পারে
        if (!in_array($days, [7, 30, 90, 365], true)) {
            $days = 30;
        }

        $service = app(\App\Services\CampaignAnalyticsService::class);

        $summary = $service->summary($campaign->id, $days);
        $daily   = $service->daily($campaign->id, $days);

        return view('backEnd.campaign.analytics', compact('campaign', 'summary', 'daily', 'days'));
    }
    /**
     * Clone a complete campaign into an unpublished marketing variant.
     */
    public function duplicate($id)
    {
        $source = Campaign::with(['images', 'products'])->findOrFail($id);

        $copy = DB::transaction(function () use ($source) {
            $campaign = $source->replicate(['slug', 'created_at', 'updated_at']);
            $campaign->name = $this->uniqueCopyName($source->name);
            $campaign->slug = $this->uniqueCampaignSlug(Str::slug($campaign->name));
            $campaign->status = false;
            $campaign->is_published = false;
            $campaign->published_at = null;

            foreach (['banner', 'image_one', 'image_two', 'image_three'] as $field) {
                if (!empty($source->{$field})) {
                    $campaign->{$field} = $this->copyCampaignMedia($source->{$field});
                }
            }
            $campaign->save();

            // The primary product lives on campaigns; all additional products live on the pivot.
            $campaign->products()->sync($source->products->pluck('id')->all());

            foreach ($source->images as $review) {
                $clone = $review->replicate(['campaign_id', 'created_at', 'updated_at']);
                $clone->campaign_id = $campaign->id;
                $clone->image = $this->copyCampaignMedia($review->image);
                $clone->save();
            }

            if (Schema::hasTable('order_bumps')) {
                OrderBump::where('campaign_id', $source->id)->get()->each(function (OrderBump $bump) use ($campaign) {
                    $clone = $bump->replicate(['campaign_id', 'impressions', 'conversions', 'created_at', 'updated_at']);
                    $clone->campaign_id = $campaign->id;
                    $clone->impressions = 0;
                    $clone->conversions = 0;
                    $clone->save();
                });
            }

            return $campaign;
        });

        Toastr::success('Campaign duplicated as an unpublished version.', 'Success');
        return redirect()->route('campaign.edit', $copy->id);
    }

    public function customBuilder($id)
    {
        $campaign = Campaign::with(['images', 'products.image', 'product.image'])->findOrFail($id);
        $productIds = $campaign->products->pluck('id')->push($campaign->product_id)->filter()->unique();
        $products = Product::whereIn('id', $productIds)->with('image')->get();
        $primaryProduct = $products->firstWhere('id', $campaign->product_id) ?: $products->first();

        return view('backEnd.campaign.custom-builder', compact('campaign', 'products', 'primaryProduct'));
    }

    public function saveCustomDraft(Request $request, $id, CampaignCustomPageService $service)
    {
        $campaign = Campaign::findOrFail($id);
        $source = $this->validatedCustomSource($request, $service);

        $campaign->forceFill([
            'custom_html_draft' => $source['html'],
            'custom_css_draft' => $source['css'],
            'custom_js_draft' => $source['js'],
        ])->save();

        Toastr::success('Custom landing page draft saved.', 'Success');
        return redirect()->route('campaign.custom-builder', $campaign->id);
    }

    /**
     * Load the built-in "AI Studio" conversion design into the draft editors so it can
     * be previewed, edited and published. Product names, prices, images, the product
     * selector and the checkout stay dynamic (admin-controlled).
     */
    public function loadStudioTemplate($id, CampaignCustomPageService $service)
    {
        $campaign = Campaign::findOrFail($id);
        $template = $service->studioTemplate();

        if (blank($template['html'] ?? null)) {
            Toastr::error('The AI Studio template is unavailable.', 'Error');
            return redirect()->route('campaign.custom-builder', $campaign->id);
        }

        $campaign->forceFill([
            'custom_html_draft' => $template['html'],
            'custom_css_draft' => $template['css'],
            'custom_js_draft' => $template['js'],
        ])->save();

        Toastr::success('AI Studio ডিজাইন লোড হয়েছে। প্রিভিউ দেখে Publish saved draft চাপুন।', 'Success');
        return redirect()->route('campaign.custom-builder', $campaign->id);
    }

    public function uploadCustomSource(Request $request, $id, CampaignCustomPageService $service)
    {
        $campaign = Campaign::findOrFail($id);
        $request->validate([
            'html_file' => ['nullable', 'file', 'max:4096'],
            'css_file' => ['nullable', 'file', 'max:1024'],
            'js_file' => ['nullable', 'file', 'max:1024'],
        ]);

        if (!$request->hasFile('html_file') && !$request->hasFile('css_file') && !$request->hasFile('js_file')) {
            throw ValidationException::withMessages(['html_file' => 'Choose at least one HTML, CSS, or JavaScript file.']);
        }

        $draft = [
            'html' => $campaign->custom_html_draft,
            'css' => $campaign->custom_css_draft,
            'js' => $campaign->custom_js_draft,
        ];

        if ($request->hasFile('html_file')) {
            $file = $request->file('html_file');
            $this->assertCodeExtension($file->getClientOriginalExtension(), ['html', 'htm'], 'HTML');
            $split = $service->splitHtmlUpload((string) file_get_contents($file->getRealPath()));
            $draft['html'] = $split['html'];
            if (filled($split['css'])) $draft['css'] = $split['css'];
            if (filled($split['js'])) $draft['js'] = $split['js'];
        }
        if ($request->hasFile('css_file')) {
            $file = $request->file('css_file');
            $this->assertCodeExtension($file->getClientOriginalExtension(), ['css'], 'CSS');
            $draft['css'] = $service->cleanCss((string) file_get_contents($file->getRealPath()));
        }
        if ($request->hasFile('js_file')) {
            $file = $request->file('js_file');
            $this->assertCodeExtension($file->getClientOriginalExtension(), ['js', 'mjs'], 'JavaScript');
            $draft['js'] = $service->cleanJs((string) file_get_contents($file->getRealPath()));
        }

        $campaign->forceFill([
            'custom_html_draft' => $draft['html'],
            'custom_css_draft' => $draft['css'],
            'custom_js_draft' => $draft['js'],
        ])->save();

        Toastr::success('Source files imported into the draft editors.', 'Success');
        return redirect()->route('campaign.custom-builder', $campaign->id);
    }

    public function publishCustom($id)
    {
        $campaign = Campaign::findOrFail($id);
        if (blank($campaign->custom_html_draft)) {
            throw ValidationException::withMessages(['custom_html' => 'Add HTML and save the draft before publishing.']);
        }

        $campaign->forceFill([
            'custom_html' => $campaign->custom_html_draft,
            'custom_css' => $campaign->custom_css_draft,
            'custom_js' => $campaign->custom_js_draft,
            'custom_page_published_at' => now(),
            'is_published' => true,
            'published_at' => now(),
            'status' => true,
        ])->save();

        Toastr::success('Custom landing page published.', 'Live');
        return redirect()->route('campaign.custom-builder', $campaign->id);
    }

    public function unpublish($id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->forceFill(['is_published' => false, 'published_at' => null])->save();

        Toastr::success('Landing page unpublished. Its draft and database data are preserved.', 'Success');
        return redirect()->back();
    }

    private function validatedCustomSource(Request $request, CampaignCustomPageService $service): array
    {
        $validated = $request->validate([
            'custom_html' => ['nullable', 'string', 'max:4194304'],
            'custom_css' => ['nullable', 'string', 'max:1048576'],
            'custom_js' => ['nullable', 'string', 'max:1048576'],
        ]);

        // Inline <style>/<script> pasted into the HTML editor are merged into the CSS/JS
        // drafts instead of being silently dropped, so a full HTML document saves completely.
        return $service->prepareSource(
            $validated['custom_html'] ?? null,
            $validated['custom_css'] ?? null,
            $validated['custom_js'] ?? null
        );
    }

    private function assertCodeExtension(string $extension, array $allowed, string $label): void
    {
        if (!in_array(strtolower($extension), $allowed, true)) {
            throw ValidationException::withMessages(['html_file' => "The {$label} file extension is not supported."]);
        }
    }

    private function uniqueCopyName(string $name): string
    {
        $base = Str::limit($name, 220, '') . ' Copy';
        $candidate = $base;
        $number = 2;
        while (Campaign::where('name', $candidate)->exists()) {
            $candidate = $base . ' ' . $number++;
        }
        return $candidate;
    }

    private function uniqueCampaignSlug(string $base): string
    {
        $base = $base !== '' ? $base : 'campaign';
        $candidate = $base;
        $number = 2;
        while (Campaign::where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $number++;
        }
        return $candidate;
    }

    private function copyCampaignMedia(?string $path): ?string
    {
        if (!$path || preg_match('#^https?://#i', $path) || !File::exists($path)) {
            return $path;
        }

        $directory = str_replace('\\', '/', dirname($path));
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $filename = pathinfo($path, PATHINFO_FILENAME) . '-copy-' . Str::lower(Str::random(8));
        $target = $directory . '/' . $filename . ($extension ? '.' . $extension : '');

        try {
            File::ensureDirectoryExists($directory);
            return File::copy($path, $target) ? $target : $path;
        } catch (\Throwable $exception) {
            report($exception);
            return $path;
        }
    }

    public function create()
    {
        $products = Product::where(['status'=>1])->select('id','name','status')->get();
        return view('backEnd.campaign.create',compact('products'));
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'short_description' => 'nullable',
            'description' => 'nullable',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'image_one' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'image_two' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'image_three' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'product_id' => 'required|array|min:1|exists:products,id',
            'image.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'review' => 'nullable|string|max:255',
            'deadline' => 'nullable|date|after:now',
            'top_title_1' => 'nullable|string|max:255',
            'top_title_2' => 'nullable|string|max:255',
            'heading_1' => 'nullable|string|max:255',
            'feature_1' => 'nullable|string|max:255',
            'feature_2' => 'nullable|string|max:255',
            'heading_2' => 'nullable|string|max:255',
            'heading_3' => 'nullable|string|max:255',
            'heading_4' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:255',
            'billing_details' => 'nullable|string|max:255',
        
        ]);
    
        // Prepare the input data
        $input = $request->except('image', 'product_id');
        $input['status'] = true;
        $input['review'] = $request->review ?: ($request->name ?? '');
    
        // Handle the first selected product ID
        $firstProductId = $request->product_id[0];
        $input['product_id'] = $firstProductId;
    
        // Handle Banner Image
        if ($request->hasFile('banner')) {
            $banner = $request->file('banner');
            $bannerName = time() . '-' . strtolower(preg_replace('/\s+/', '-', $banner->getClientOriginalName()));
            $uploadPath = 'public/uploads/campaign/';
            $bannerUrl = $uploadPath . $bannerName;
            $banner->move($uploadPath, $bannerName);
            $input['banner'] = $bannerUrl;
        }
    
        // Handle Image One
        if ($request->hasFile('image_one')) {
            $image_one = $request->file('image_one');
            $name1 = time() . '-' . strtolower(preg_replace('/\s+/', '-', $image_one->getClientOriginalName()));
            $name1 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name1);
            $uploadPath1 = 'public/uploads/campaign/';
            $imageUrl1 = $uploadPath1 . $name1;
    
            $img1 = Image::make($image_one->getRealPath());
            $img1->encode('webp', 90);
            $img1->save($imageUrl1);
            $input['image_one'] = $imageUrl1;
        }
    
        // Handle Image Two
        if ($request->hasFile('image_two')) {
            $image_two = $request->file('image_two');
            $name2 = time() . '-' . strtolower(preg_replace('/\s+/', '-', $image_two->getClientOriginalName()));
            $name2 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name2);
            $uploadPath2 = 'public/uploads/campaign/';
            $imageUrl2 = $uploadPath2 . $name2;
    
            $img2 = Image::make($image_two->getRealPath());
            $img2->encode('webp', 90);
            $img2->save($imageUrl2);
            $input['image_two'] = $imageUrl2;
        }
    
        // Handle Image Three
        if ($request->hasFile('image_three')) {
            $image_three = $request->file('image_three');
            $name3 = time() . '-' . strtolower(preg_replace('/\s+/', '-', $image_three->getClientOriginalName()));
            $name3 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name3);
            $uploadPath3 = 'public/uploads/campaign/';
            $imageUrl3 = $uploadPath3 . $name3;
    
            $img3 = Image::make($image_three->getRealPath());
            $img3->encode('webp', 90);
            $img3->save($imageUrl3);
            $input['image_three'] = $imageUrl3;
        }
    
        $input['slug'] = strtolower(Str::slug($request->name));
        $input['video'] = $this->getYouTubeVideoId($request->video);
        $input['short_description'] = $input['short_description'] ?? '';
        $input['description'] = $input['description'] ?? '';
        $input['image_one'] = $input['image_one'] ?? '';
    
        // Create a new campaign
        $campaign = Campaign::create($input);
        // Attach remaining selected products to the pivot table
        $remainingProductIds = array_slice($request->product_id, 1);
        if (!empty($remainingProductIds)) {
            $campaign->products()->attach($remainingProductIds);
        }
    
        // Handle additional images (review images)
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $image) {
                $name = time() . '-' . strtolower(preg_replace('/\s+/', '-', $image->getClientOriginalName()));
                $uploadPath = 'public/uploads/campaign/';
                $image->move($uploadPath, $name);
                $imageUrl = $uploadPath . $name;
    
                $pimage = new CampaignReview();
                $pimage->campaign_id = $campaign->id;
                $pimage->image = $imageUrl;
                $pimage->save();
            }
        }
    
        Toastr::success('Landing page created. Visual builder-এ ডিজাইন করুন।', 'Success');
        return redirect()->route('campaign.builder', $campaign->id);
    }

    
    
    public function edit($id)
    {
        // Fetch the campaign with its related images and products
        $edit_data = Campaign::with('images')->findOrFail($id);
    
     
        $select_products = DB::select('
            SELECT products.id, products.name, products.status 
            FROM products
            INNER JOIN campaign_product ON products.id = campaign_product.product_id
            WHERE campaign_product.campaign_id = ?
        ', [$id]);

    
        // Fetch all available products
        $products = Product::where('status', 1)->select('id', 'name', 'status')->get();
    
        return view('backEnd.campaign.edit', compact('edit_data', 'products','select_products'));
    }

    
    public function update(Request $request)
    { 
         $this->validate($request, [
            'name' => 'required',
            'short_description' => 'nullable',
            'description' => 'nullable',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'image_one' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'image_two' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'image_three' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'product_id' => 'required|array|min:1|exists:products,id',
            'image.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'review' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'top_title_1' => 'nullable|string|max:255',
            'top_title_2' => 'nullable|string|max:255',
            'heading_1' => 'nullable|string|max:255',
            'feature_1' => 'nullable|string|max:255',
            'feature_2' => 'nullable|string|max:255',
            'heading_2' => 'nullable|string|max:255',
            'heading_3' => 'nullable|string|max:255',
            'heading_4' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:255',
            'billing_details' => 'nullable|string|max:255',
        ]);
        // image one
        $update_data = Campaign::find($request->hidden_id);
        $input = $request->except('hidden_id','product_ids','files','image');
        $input['status'] = $request->has('status') ? 1 : 0;
        $input['video'] = $this->getYouTubeVideoId($request->video);
        $input['product_id'] = $request->product_id[0];
        
          // Handle Banner Image
        if ($request->hasFile('banner')) {
            $banner = $request->file('banner');
            $bannerName = time() . '-' . $banner->getClientOriginalName();
            $bannerName = strtolower(preg_replace('/\s+/', '-', $bannerName));
            $uploadPath = 'public/uploads/campaign/';
            $bannerUrl = $uploadPath . $bannerName;
            $banner->move($uploadPath, $bannerName);
            $input['banner'] = $bannerUrl;
            File::delete($update_data->banner);
        } else {
            $input['banner'] = $update_data->banner;
        }
        $image_one = $request->file('image_one');
        if($image_one){
            // image with intervention 
            $image_one = $request->file('image_one');
            $name1 =  time().'-'.$image_one->getClientOriginalName();
            $name1 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name1);
            $name1 = strtolower(preg_replace('/\s+/', '-', $name1));
            $uploadpath1 = 'public/uploads/campaign/';
            $imageUrl1 = $uploadpath1.$name1; 
            $img1 = Image::make($image_one->getRealPath());
            $img1->encode('webp', 90);
            $width1 = '';
            $height1 = '';
            $img1->height() > $img1->width() ? $width1=null : $height1=null;
            $img1->resize($width1, $height1, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img1->save($imageUrl1);
            $input['image_one'] = $imageUrl1;
            File::delete($update_data->image_one);
        }else{
            $input['image_one'] = $update_data->image_one;
        }
        // image two
        $image_two = $request->file('image_two');
        if($image_two){
            // image with intervention 
            $image_two = $request->file('image_two');
            $name2 =  time().'-'.$image_two->getClientOriginalName();
            $name2 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp',$name2);
            $name2 = strtolower(preg_replace('/\s+/', '-', $name2));
            $uploadpath2 = 'public/uploads/campaign/';
            $imageUrl2 = $uploadpath2.$name2; 
            $img2=Image::make($image_two->getRealPath());
            $img2->encode('webp', 90);
            $width2 = '';
            $height2 = '';
            $img2->height() > $img2->width() ? $width2=null : $height2=null;
            $img2->resize($width2, $height2, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img2->save($imageUrl2);
            $input['image_two'] = $imageUrl2;
            File::delete($update_data->image_two);
        }else{
            $input['image_two'] = $update_data->image_two;
        }
        // image three
        $image_three = $request->file('image_three');
        if($image_three){
            // image with intervention 
            $image_three = $request->file('image_three');
            $name3 =  time().'-'.$image_three->getClientOriginalName();
            $name3 = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp',$name3);
            $name3 = strtolower(preg_replace('/\s+/', '-', $name3));
            $uploadpath3 = 'public/uploads/campaign/';
            $imageUrl3 = $uploadpath3.$name3; 
            $img3 = Image::make($image_three->getRealPath());
            $img3->encode('webp', 90);
            $width3 = '';
            $height3 = '';
            $img3->height() > $img3->width() ? $width3=null : $height3=null;
            $img3->resize($width3, $height3, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img3->save($imageUrl3);
            $input['image_three'] = $imageUrl3;
            File::delete($update_data->image_three);
        }else{
            $input['image_three'] = $update_data->image_three;
        }
        // image four
        $input['slug'] = strtolower(Str::slug($request->name));
        $input['video'] = $this->getYouTubeVideoId($request->video);
        $update_data = Campaign::find($request->hidden_id);
        $update_data->update($input);
        
        // Sync remaining selected products to the pivot table
        $remainingProductIds = array_slice($request->product_id, 1);
        $update_data->products()->sync($remainingProductIds);

        $images = $request->file('image');  
        if($images){
            foreach ($images as $key => $image) {
                $name =  time().'-'.$image->getClientOriginalName();
                $name = strtolower(preg_replace('/\s+/', '-', $name));
                $uploadPath = 'public/uploads/campaign/';
                $image->move($uploadPath,$name);
                $imageUrl =$uploadPath.$name;

                $pimage             = new CampaignReview();
                $pimage->campaign_id = $update_data->id;
                $pimage->image      = $imageUrl;
                $pimage->save();
            }
        }

        Toastr::success('Settings saved. Visual builder থেকে পেজ ডিজাইন করতে পারেন।', 'Success');
        return redirect()->route('campaign.builder', $update_data->id);
    }

    public function show($id)
    {
        $campaign = Campaign::findOrFail($id);
        return redirect()->route('campaign', $campaign->slug);
    }

    /**
     * Full-screen visual editor. Campaign metadata and product association continue to
     * live in the legacy edit form; this screen owns only the published page design.
     */
    public function builder($id)
    {
        $campaign = Campaign::with(['images', 'products.image'])
            ->findOrFail($id);

        $productIds = $campaign->products->pluck('id')
            ->push($campaign->product_id)
            ->filter()
            ->unique()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->with('image')
            ->get();

        return view('backEnd.campaign.builder', compact('campaign', 'products'));
    }

    /**
     * Persist both the editable JSON and a sanitized, ready-to-render storefront snapshot.
     */
    public function saveBuilder(Request $request, $id, CampaignPageSanitizer $sanitizer)
    {
        $campaign = Campaign::findOrFail($id);

        $validated = $request->validate([
            'page_design' => ['required', 'string', 'max:2097152'],
            'page_html'   => ['required', 'string', 'max:2097152'],
            'page_css'    => ['nullable', 'string', 'max:204800'],
        ]);

        try {
            $design = $sanitizer->design($validated['page_design']);
            $html = $sanitizer->html($validated['page_html']);
            $css = $sanitizer->css($validated['page_css'] ?? null);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'page_design' => $exception->getMessage(),
            ]);
        }

        if ($html === null) {
            throw ValidationException::withMessages([
                'page_html' => 'Add at least one section before publishing the landing page.',
            ]);
        }

        $campaign->forceFill([
            'page_design' => $design,
            'page_html'   => $html,
            'page_css'    => $css,
            'custom_page_published_at' => null,
            'is_published' => true,
            'published_at' => now(),
            'status' => true,
        ])->save();

        return response()->json([
            'success'  => true,
            'message'  => 'Landing page saved successfully.',
            'saved_at' => now()->toIso8601String(),
            'preview'  => route('campaign', $campaign->slug),
        ]);
    }

    /**
     * Clear the visual page so the published custom source or premium template can render.
     */
    public function clearBuilder($id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->forceFill([
            'page_design' => null,
            'page_html'   => null,
            'page_css'    => null,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Visual design cleared. The custom source or premium template is now active.',
        ]);
    }

    /**
     * Secure image endpoint used by builder image controls.
     */
    public function uploadBuilderImage(Request $request)
    {
        $validated = $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:5120',
                'dimensions:max_width=8000,max_height=8000',
            ],
        ]);

        $image = $validated['image'];
        $extension = strtolower($image->guessExtension() ?: $image->getClientOriginalExtension());
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            throw ValidationException::withMessages(['image' => 'Unsupported image format.']);
        }

        $directory = 'uploads/campaign/builder/' . now()->format('Y/m');
        File::ensureDirectoryExists(public_path($directory), 0755, true);

        $filename = Str::uuid()->toString() . '.' . $extension;
        $image->move(public_path($directory), $filename);
        $relativePath = 'public/' . $directory . '/' . $filename;

        return response()->json([
            'success' => true,
            'url'     => asset($relativePath),
            'path'    => $relativePath,
        ]);
    }
 
    public function inactive(Request $request)
    {
        $inactive = Campaign::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request)
    {
        $active = Campaign::findOrFail($request->hidden_id);
        $active->status = 1;
        $active->is_published = true;
        $active->published_at = now();
        $active->save();
        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }
    public function destroy(Request $request)
    {
       
        $delete_data = Campaign::find($request->hidden_id);
        $delete_data->delete();
        
        $campaign = Product::whereNotNull('campaign_id')->get();
        foreach($campaign as $key=>$value){
            $product = Product::find($value->id);
            $product->campaign_id = null;
            $product->save();
        }
        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    }
    public function imgdestroy(Request $request)
    { 
        $delete_data = CampaignReview::find($request->id);
        File::delete($delete_data->image);
        $delete_data->delete();
        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    } 
    public function getYouTubeVideoId($input)
    {
        if ($input === null || trim((string) $input) === '') {
            return null;
        }

        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $input)) {
            return $input;
        }
    
        // Regular expression to match YouTube video URLs
        $pattern = '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
        
        // Execute the regex pattern
        preg_match($pattern, $input, $matches);
        
        // Check if a match was found and return the video ID or null
        return isset($matches[1]) ? $matches[1] : null;
    }

}
