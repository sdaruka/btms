<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerCommunication extends Model
{
     use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'subject',
        'content',
        'logged_by_user_id',
    ];

    /**
     * Get the customer that the communication is about.
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user (staff) who logged this communication.
     */
    public function loggedBy()
    {
        return $this->belongsTo(User::class, 'logged_by_user_id');
    }

    /**
     * Define the valid communication types (optional, but good for consistency)
     */
    public static function getTypes()
    {
        return [
            'Call',
            'Email',
            'Message', // e.g., SMS, WhatsApp
            'In-Person',
            'Other',
        ];
    }
}
