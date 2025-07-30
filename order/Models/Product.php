<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'description'];
    public function rates() {
        return $this->hasMany(ProductRate::class);
    }
    
    public function measurements() {
        return $this->belongsToMany(Measurement::class, 'product_measurement');
    }
    
    public function designs() {
        return $this->hasMany(Design::class);
    }
}
