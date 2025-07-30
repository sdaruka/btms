<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'discount', 'rate'];

    /**
     * Get the users that belong to the customer group.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'customer_group_id');
    }
}