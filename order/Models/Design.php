<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Design extends Model
{
    protected $fillable = [
        'product_id',
        'design_title',
        'design_image',
        'created_at',
        'updated_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function orderItems()
{
    return $this->belongsToMany(OrderItem::class, 'order_item_design');
}
}
