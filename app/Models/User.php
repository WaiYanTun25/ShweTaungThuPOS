<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\CausesActivity;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, CausesActivity, LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        $logOptions = LogOptions::defaults()
            ->setDescriptionForEvent(function (string $eventName) {
                $userName = "{userName}";
                return "{$userName} {$eventName} the User ({$this->name})";
            });


        $logOptions->logName = 'USER';

        return $logOptions;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Set the join_date attribute to the current date if it's not provided
            $user->join_date = $user->join_date ?? now();
            $user->user_code = static::generateItemCode();
            
            $updatedUserToRelatedBranch = Branch::find($user->branch_id);
            $updatedUserToRelatedBranch->total_employee += 1;
            $updatedUserToRelatedBranch->save();
        });
    }

    protected static function generateItemCode()
    {
        // Get the last item for the given supplier from the database
        $lastUser = static::latest('user_code')->first();
        $userPrefix = 'SP'; 
        if (!$lastUser) {
            return $userPrefix . '-000001';
        }
    
        // Extract the numeric part of the existing item code and increment it
        $numericPart = (int)substr($lastUser->user_code, -6);
        $newNumericPart = str_pad($numericPart + 1, 6, '0', STR_PAD_LEFT);
    
        return $userPrefix . '-' . $newNumericPart;
    }

    public function branch()
    {
        return $this->hasOne(Branch::class, 'id', 'branch_id');
    }

    public function townshipData()
    {
        return $this->belongsTo(Township::class, 'township', 'id');
    }

    public function cityData()
    {
        return $this->belongsTo(City::class, 'city');
    }

    public function activity()
    {
        return $this->hasMany(Activity::class, 'id', 'causer_id');
    }

    public function hasActivityLogs()
    {
        return $this->activity()->exists();
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
