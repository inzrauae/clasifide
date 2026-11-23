<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Listing extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public const STATUSES = [
        'pending_payment' => 'Awaiting payment',
        'pending' => 'Under review',
        'active' => 'Live',
        'rejected' => 'Rejected',
        'sold' => 'Sold',
        'expired' => 'Expired',
    ];

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'negotiable' => 'boolean',
            'price_from' => 'boolean',
            'is_flagged' => 'boolean',
            'top_until' => 'datetime',
            'featured_until' => 'datetime',
            'urgent_until' => 'datetime',
            'bumped_at' => 'datetime',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // ---------------------------------------------------------------- relations

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort');
    }

    public function stats(): HasMany
    {
        return $this->hasMany(ListingStat::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(ListingPromotion::class);
    }

    // ---------------------------------------------------------------- scopes

    public function scopeLive(Builder $q): Builder
    {
        return $q->where('status', 'active')
            ->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** Top ads first, then most recently bumped/published. */
    public function scopeRanked(Builder $q): Builder
    {
        return $q->orderByRaw('CASE WHEN top_until IS NOT NULL AND top_until > ? THEN 0 ELSE 1 END', [now()])
            ->orderByDesc(DB::raw('COALESCE(bumped_at, published_at, created_at)'));
    }

    // ---------------------------------------------------------------- helpers

    public function isTop(): bool
    {
        return $this->top_until && $this->top_until->isFuture();
    }

    public function isFeatured(): bool
    {
        return $this->featured_until && $this->featured_until->isFuture();
    }

    public function isUrgent(): bool
    {
        return $this->urgent_until && $this->urgent_until->isFuture();
    }

    public function isLive(): bool
    {
        return $this->status === 'active' && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function statusLabel(): string
    {
        if ($this->status === 'active' && $this->expires_at && $this->expires_at->isPast()) {
            return 'Expired';
        }

        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function priceLabel(): string
    {
        if (! $this->price) {
            return 'Price on request';
        }

        return ($this->price_from ? 'From ' : '').'Rs '.number_format($this->price).($this->price_unit ?: '');
    }

    public function place(): string
    {
        return $this->city && $this->city !== $this->district ? "{$this->city}, {$this->district}" : $this->district;
    }

    public function coverUrl(): ?string
    {
        $img = $this->relationLoaded('images') ? $this->images->first() : $this->images()->first();

        return $img?->url();
    }

    public function url(): string
    {
        return route('listings.show', [$this->id, $this->slug]);
    }

    public function sellerName(): string
    {
        return $this->store?->name ?? $this->contact_name ?? $this->user?->name ?? 'Seller';
    }

    public function maskedPhone(): string
    {
        return mb_substr($this->phone, 0, 4).'XXX XXXX';
    }

    public function telHref(): string
    {
        return 'tel:'.preg_replace('/[^\d+]/', '', $this->phone);
    }

    public function whatsappHref(): string
    {
        $num = preg_replace('/\D/', '', $this->whatsapp ?: $this->phone);
        $num = preg_replace('/^0/', '94', $num);
        $text = rawurlencode("Hi, is your ad \"{$this->title}\" on 4U.lk still available?");

        return "https://wa.me/{$num}?text={$text}";
    }

    /** Bump a dashboard counter and today's stat row in one go. */
    public function track(string $metric): void
    {
        $column = ['views' => 'views_count', 'phone_reveals' => 'phone_reveals',
            'whatsapp_clicks' => 'whatsapp_clicks', 'messages' => 'messages_count'][$metric];

        static::whereKey($this->id)->increment($column);

        $stat = ListingStat::firstOrCreate(['listing_id' => $this->id, 'date' => now()->toDateString()]);
        $stat->increment($metric);
    }
}
