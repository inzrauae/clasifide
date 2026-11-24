<?php

namespace App\Models;

use App\Support\Lang;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    // Names and hints are stored in English; Sinhala pages show the translation from lang/si.json
    // (an admin-made category with no translation just shows its English name).
    protected function name(): Attribute
    {
        return Attribute::get(fn ($v) => $v !== null && Lang::isSinhala() ? __($v) : $v);
    }

    protected function shortName(): Attribute
    {
        return Attribute::get(fn ($v) => $v !== null && Lang::isSinhala() ? __($v) : $v);
    }

    protected function hint(): Attribute
    {
        return Attribute::get(fn ($v) => $v !== null && Lang::isSinhala() ? __($v) : $v);
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

    public const FIELD_TYPES = ['text' => 'Text', 'number' => 'Number', 'select' => 'Choice list'];

    /**
     * The spec fields sellers fill in for this type of ad, cleaned up:
     * [['name' => 'Brand', 'type' => 'text|number|select', 'options' => [...]], ...]
     */
    public function specFields(): array
    {
        return collect($this->fields ?? [])
            ->filter(fn ($f) => is_array($f) && trim((string) ($f['name'] ?? '')) !== '')
            ->map(fn ($f) => [
                'name' => trim($f['name']),
                'type' => array_key_exists($f['type'] ?? '', self::FIELD_TYPES) ? $f['type'] : 'text',
                'options' => array_values(array_filter(array_map('trim', (array) ($f['options'] ?? [])), 'strlen')),
            ])
            ->values()->all();
    }

    /** Fields with a fixed list of choices: these make good filters. */
    public function selectFields(): array
    {
        return array_values(array_filter($this->specFields(), fn ($f) => $f['type'] === 'select' && $f['options']));
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
