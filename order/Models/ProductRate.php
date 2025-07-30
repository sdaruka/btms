<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRate extends Model
{
    protected $fillable = [
        'product_id',
        'rate',
        'effective_date',
    ];
}
