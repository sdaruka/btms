<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order_Item_Measurement extends Model
{
    protected $fillable = [
        'order_item_id',
        'measurement_id',
        'value', // Assuming you want to store a value for the measurement
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function measurement()
    {
        return $this->belongsTo(Measurement::class);
    }
}
