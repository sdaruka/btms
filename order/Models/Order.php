<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'order_date',
        'user_id',
        'assignedto',
        'delivery_date',
        'discount',
        'received',
        'design_charge',
        'total_amount',
        'remarks',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function artisan()
{
    return $this->belongsTo(User::class, 'assignedto');
}

}
