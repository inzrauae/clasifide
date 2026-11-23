<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Package;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function packages()
    {
        return view('admin.packages', ['packages' => Package::orderBy('sort')->get()]);
    }

    public function updatePackage(Request $request, Package $package)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'price' => ['required', 'integer', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:366'],
            'stores' => ['required', 'integer', 'min:0'],
            'promotions' => ['required', 'integer', 'min:0'],
            'active_ads' => ['required', 'integer', 'min:1'],
            'premium_ads' => ['required', 'integer', 'min:0'],
            'features_text' => ['nullable', 'string', 'max:2000'],
        ]);

        $package->update([
            ...collect($data)->except('features_text')->all(),
            'analytics' => $request->boolean('analytics'),
            'lead_tools' => $request->boolean('lead_tools'),
            'is_active' => $request->boolean('is_active'),
            'features' => collect(preg_split('/\r?\n/', $data['features_text'] ?? ''))->map('trim')->filter()->values()->all(),
        ]);

        return back()->with('status', "{$package->name} package saved.");
    }

    public function categories()
    {
        return view('admin.categories', ['categories' => Category::whereNull('parent_id')->orderBy('sort')->withCount('listings')->get()]);
    }

    public function updateCategory(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'posting_fee' => ['required', 'integer', 'min:0'],
            'free_ads_per_month' => ['required', 'integer', 'min:0', 'max:100'],
            'sort' => ['required', 'integer'],
        ]);
        $category->update($data + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', "{$category->name} saved.");
    }
}
