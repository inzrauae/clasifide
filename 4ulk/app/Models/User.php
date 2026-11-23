<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'whatsapp', 'district', 'city', 'account_type',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verified_at' => 'datetime',
            'banned_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'favorites')->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->latestOfMany('ends_at');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'seller_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    /** The package currently in force (falls back to the Free package). */
    public function package(): ?Package
    {
        return $this->activeSubscription?->package ?? Package::where('slug', 'free')->first();
    }

    public function isMember(): bool
    {
        return (bool) $this->activeSubscription;
    }

    public function activeAdLimit(): int
    {
        return $this->package()?->active_ads ?? config('marketplace.free_active_ads');
    }

    public function hasAnalytics(): bool
    {
        return (bool) $this->package()?->analytics;
    }

    public function hasLeadTools(): bool
    {
        return (bool) $this->package()?->lead_tools;
    }

    public function storeLimit(): int
    {
        return $this->package()?->stores ?? 0;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    }

    public function ratingAverage(): ?float
    {
        $avg = $this->reviewsReceived()->where('is_visible', true)->avg('rating');

        return $avg ? round($avg, 1) : null;
    }

    public function unreadMessagesCount(): int
    {
        return (int) Conversation::where('buyer_id', $this->id)->sum('buyer_unread')
            + (int) Conversation::where('seller_id', $this->id)->sum('seller_unread');
    }
}
