<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $guarded = ['id'];

    public const TYPES = [
        'subscription' => 'Seller package',
        'promotion' => 'Ad promotion',
        'posting_fee' => 'Premium ad fee',
        'banner' => 'Banner advertising',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'gateway_payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->reference ??= '4U'.now()->format('ymd').strtoupper(Str::random(6));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function typeLabel(): string
    {
        return __(self::TYPES[$this->type] ?? $this->type);
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }
}
