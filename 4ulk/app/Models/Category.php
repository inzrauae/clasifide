<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'has_condition' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('categories.all'));
        static::deleted(fn () => Cache::forget('categories.all'));
    }

    /** All active top-level categories, cached. */
    public static function allCached()
    {
        return Cache::remember('categories.all', 3600, fn () => static::whereNull('parent_id')
            ->where('is_active', true)->orderBy('sort')->get());
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function label(): string
    {
        return $this->short_name ?: $this->name;
    }

    public function isPremium(): bool
    {
        return $this->posting_fee > 0;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
