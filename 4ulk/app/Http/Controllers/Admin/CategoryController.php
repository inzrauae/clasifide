<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Report;
use App\Support\CategoryInsights;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Admin back office for each ad type (Vehicles, Property, Jobs ...):
 * an overview, a moderation queue scoped to the type, and its settings.
 */
class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')->orderBy('sort')
            ->withCount([
                'listings as live_count' => fn ($q) => $q->live(),
                'listings as pending_count' => fn ($q) => $q->where('status', 'pending'),
                'listings as flagged_count' => fn ($q) => $q->where('status', 'pending')->where('is_flagged', true),
                'listings as awaiting_fee_count' => fn ($q) => $q->where('status', 'pending_payment'),
            ])->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function show(Category $category)
    {
        $base = Listing::where('category_id', $category->id);

        return view('admin.categories.show', [
            'category' => $category,
            'stats' => [
                'live' => (clone $base)->live()->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'flagged' => (clone $base)->where('status', 'pending')->where('is_flagged', true)->count(),
                'awaiting_fee' => (clone $base)->where('status', 'pending_payment')->count(),
                'new_week' => (clone $base)->where('created_at', '>=', now()->subWeek())->count(),
                'sellers' => (clone $base)->live()->distinct()->count('user_id'),
                'reports' => Report::where('status', 'open')->whereIn('listing_id', (clone $base)->select('id'))->count(),
            ],
            'activity' => CategoryInsights::activity($category, 30),
            'prices' => CategoryInsights::prices($category),
            'specs' => CategoryInsights::specBreakdown($category),
            'districts' => CategoryInsights::districts($category),
            'revenue' => CategoryInsights::feeRevenue($category),
            'riskiest' => (clone $base)->with('user')->where('status', 'pending')->orderByDesc('risk_score')->limit(5)->get(),
        ]);
    }

    public function ads(Request $request, Category $category)
    {
        $status = $request->query('status', 'pending');
        $status = array_key_exists($status, ListingController::TABS) ? $status : 'pending';

        // Filter by the type's choice-list fields, e.g. ?spec[Fuel type]=Hybrid
        $filters = collect($request->query('spec', []))
            ->only(array_column($category->selectFields(), 'name'))
            ->filter(fn ($v) => is_string($v) && $v !== '');

        $query = ListingController::queue($request, $status)->where('category_id', $category->id);
        foreach ($filters as $field => $value) {
            $query->where('specs->'.$field, $value);
        }

        return view('admin.categories.ads', [
            'category' => $category,
            'status' => $status,
            'filters' => $filters->all(),
            'listings' => $query->paginate(30)->withQueryString(),
        ]);
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', ['category' => $category]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'short_name' => ['nullable', 'string', 'max:30'],
            'hint' => ['nullable', 'string', 'max:120'],
            'icon' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/'],
            'posting_fee' => ['required', 'integer', 'min:0'],
            'free_ads_per_month' => ['required', 'integer', 'min:0', 'max:100'],
            'sort' => ['required', 'integer'],
            'fields' => ['nullable', 'array', 'max:20'],
            'fields.*.name' => ['required', 'string', 'max:40', 'distinct:ignore_case'],
            'fields.*.type' => ['required', Rule::in(array_keys(Category::FIELD_TYPES))],
            'fields.*.options' => ['nullable', 'string', 'max:1000'],
        ], [
            'fields.*.name.required' => 'Every field needs a name.',
            'fields.*.name.distinct' => 'Two fields have the same name.',
            'icon.regex' => 'Use a Lucide icon name such as car or house.',
        ]);

        $fields = collect($data['fields'] ?? [])->map(function ($f) {
            $field = ['name' => Str::squish($f['name']), 'type' => $f['type']];
            if ($f['type'] === 'select') {
                $field['options'] = collect(preg_split('/\r?\n|,/', $f['options'] ?? ''))->map(fn ($v) => trim($v))->filter()->unique()->values()->all();
            }

            return $field;
        })->values();

        $empty = $fields->first(fn ($f) => $f['type'] === 'select' && ! $f['options']);
        if ($empty) {
            return back()->withInput()->withErrors(['fields' => "Add at least one choice for \"{$empty['name']}\"."]);
        }

        $category->update([
            ...collect($data)->except('fields')->all(),
            'fields' => $fields->all(),
            'has_condition' => $request->boolean('has_condition'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.categories.edit', $category)->with('status', "{$category->name} saved.");
    }
}
