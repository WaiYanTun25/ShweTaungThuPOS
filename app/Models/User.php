<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_code',
        'name',
        'password',
        'branch_id',
        'phone_number',
        'join_date'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Set the join_date attribute to the current date if it's not provided
            $user->join_date = $user->join_date ?? now();
            $user->user_code = static::generateItemCode();
        });
    }

    protected static function generateItemCode()
    {
        // Get the last item for the given supplier from the database
        $lastUser = static::latest('user_code')->first();
        info($lastUser);
        $userPrefix = 'SP'; 
        if (!$lastUser) {
            return $userPrefix . '-000001';
        }
    
        // Extract the numeric part of the existing item code and increment it
        $numericPart = (int)substr($lastUser->user_code, -6);
        $newNumericPart = str_pad($numericPart + 1, 6, '0', STR_PAD_LEFT);
    
        return $userPrefix . '-' . $newNumericPart;
    }

    // public function getDefaultGuardName()
    // {
    //     return null;
    // }

    // public function inventories()
    // {
    //     return $this->hasMany(Inventory::class, 'branch_id', 'branch_id');
    // }
}
