<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Supplier extends Model
{
    use HasFactory, LogsActivity;
    public $timestamps = false;
    protected $fillable = ['name', 'prefix', 'phone_number', 'address', 'township', 'city', 'join_date'];


    public function getActivitylogOptions(): LogOptions
    {
        $logOptions = LogOptions::defaults()
            ->setDescriptionForEvent(function (string $eventName) {
                $userName = "{userName}";
                return "{$userName} {$eventName} the Supplier ({$this->name})";
            });


        $logOptions->logName = 'SUPPLIER';

        return $logOptions;
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            // Set the join_date attribute to the current date if it's not provided
            $supplier->join_date = $supplier->join_date ?? now();
        });
    }

    public function townshipData()
    {
        return $this->belongsTo(Township::class, 'township', 'id');
    }

    public function cityData()
    {
        return $this->belongsTo(City::class, 'city');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'subject_id')->where('type', 'Supplier');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'supplier_id');
    }

    public function getDebtAmount()
    {
        // Get the total remaining amount from sales
        $totalRemainAmount = $this->purchases()->sum('remain_amount');

        // Get the total payment amount
        $totalPaymentAmount = $this->payments()->sum('pay_amount');

        // Calculate and return the debt amount
        return $totalRemainAmount - $totalPaymentAmount;
    }
}
