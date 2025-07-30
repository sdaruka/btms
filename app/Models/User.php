<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'address',
        'country',
        'profile_picture',
        'is_active',
        'failed_login_attempts', 
        'locked_until',          
        'customer_group_id', 
        'fcm_token', // Assuming you want to store the FCM token in the user model
        
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'locked_until' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function orders()
{
    return $this->hasMany(Order::class);
}
 public function customerGroup()
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }
    public function isAdmin()
{
    return $this->role === 'admin';
}

    public function isStaff()
{
    return $this->role === 'staff';
}

    public function isCustomer()
{
    return $this->role === 'customer';
}

    public function isTailor()
{
    return $this->role === 'tailor';
}

    public function isArtisan()
{
    return $this->role === 'artisan';

}
public function communications()
{
    return $this->hasMany(CustomerCommunication::class, 'user_id');
}

public function loggedCommunications()
{
    return $this->hasMany(CustomerCommunication::class, 'logged_by_user_id');
}
public function notifications()
    {
        return $this->hasMany(\App\Models\Notification::class);
    }
}
