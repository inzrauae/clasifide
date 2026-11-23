<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingStat extends Model
{
    public $timestamps = false;

    // `date` is kept as a plain Y-m-d string so lookups match on every database
    protected $guarded = ['id'];
}
