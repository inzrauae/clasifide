<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $banners = Banner::when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.banners', [
            'banners' => $banners,
            'status' => $status,
            'placements' => config('marketplace.banner_placements'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $data['image'] = ImageService::store($request->file('image'), 'banners', 1600)['path'];
        $data['status'] = 'active';
        Banner::create($data);

        return back()->with('status', 'Banner created and live for its dates.');
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $this->validated($request, false);
        if ($request->hasFile('image')) {
            ImageService::delete($banner->image);
            $data['image'] = ImageService::store($request->file('image'), 'banners', 1600)['path'];
        }
        $banner->update($data);

        return back()->with('status', 'Banner updated.');
    }

    public function destroy(Banner $banner)
    {
        ImageService::delete($banner->image);
        $banner->delete();

        return back()->with('status', 'Banner deleted.');
    }

    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'advertiser' => [$creating ? 'required' : 'sometimes', 'string', 'max:120'],
            'placement' => [$creating ? 'required' : 'sometimes', Rule::in(array_keys(config('marketplace.banner_placements')))],
            'url' => ['nullable', 'url', 'max:255'],
            'starts_at' => [$creating ? 'required' : 'sometimes', 'date'],
            'ends_at' => [$creating ? 'required' : 'sometimes', 'date', 'after_or_equal:starts_at'],
            'status' => ['sometimes', Rule::in(['pending', 'awaiting_payment', 'active', 'rejected', 'expired'])],
            'price' => ['nullable', 'integer', 'min:0'],
            'image' => [$creating ? 'required' : 'nullable', 'image', 'max:4096'],
        ]);
    }
}
