<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'design_id',
        'rate',
        'custom_design_title',
        'custom_design_image',
        'quantity', // ✅ if you've added quantity to the order_items table
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function design()
    {
        return $this->belongsTo(Design::class);
    }

    public function designs()
{
    return $this->belongsToMany(Design::class, 'order_item_design');
}

    public function measurements()
    {
        return $this->belongsToMany(
            Measurement::class,
            'order_item_measurements'
        )->withPivot('value')->withTimestamps();
    }

    public function getDesignImageUrlAttribute()
    {
        if ($this->custom_design_image) {
            return asset('storage/' . $this->custom_design_image);
        }

        return $this->design
            ? asset('storage/' . $this->design->design_image)
            : null;
    }
}