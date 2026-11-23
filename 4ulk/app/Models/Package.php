<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'analytics' => 'boolean',
            'lead_tools' => 'boolean',
            'is_custom' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function isFree(): bool
    {
        return $this->price === 0 && ! $this->is_custom;
    }
}
