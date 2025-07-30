<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['order_id', 'title', 'message', 'is_read'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function customercommunication()
    {
        return $this->hasMany(CustomerCommunication::class);
    }
}