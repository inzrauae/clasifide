<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Pick one live banner for a slot (rotates randomly) and count the impression. */
    public static function pick(string $placement): ?self
    {
        $banner = static::where('placement', $placement)
            ->where('status', 'active')
            ->whereDate('starts_at', '<=', today())
            ->whereDate('ends_at', '>=', today())
            ->whereNotNull('image')
            ->inRandomOrder()->first();

        $banner?->increment('impressions');

        return $banner;
    }

    public function placementLabel(): string
    {
        return config("marketplace.banner_placements.{$this->placement}.name", $this->placement);
    }
}
