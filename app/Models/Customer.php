<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use HasFactory, LogsActivity;
    public $timestamps = false;

    protected $fillable = ['name', 'phone_number', 'address', 'township', 'city', 'customer_type'];

    public const SPECIFIC = "Specific";
    public const GENERAL = "General";

    public function getActivitylogOptions(): LogOptions
    {
        $logOptions = LogOptions::defaults()
            ->setDescriptionForEvent(function (string $eventName) {
                $userName = "{userName}";
                return "{$userName} {$eventName} the Customer ({$this->name})";
            });


        $logOptions->logName = 'CUSTOMER';

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

    // customer debt
    public function getDebtAmount()
    {
        $debtAmount = self::leftJoin('sales', 'customers.id', '=', 'sales.customer_id')
            ->leftJoin('payments', function ($join) {
                $join->on('customers.id', '=', 'payments.subject_id')
                    ->where('payments.type', '=', 'Customer');
            })
            ->where('customers.id', $this->id)
            ->selectRaw('COALESCE(SUM(sales.remain_amount), 0) - COALESCE(SUM(payments.pay_amount), 0) as debt_amount')
            ->groupBy('customers.id')
            ->first();

        return $debtAmount ? $debtAmount->debt_amount : 0;
    }

    public function townshipData()
    {
        return $this->belongsTo(Township::class, 'township', 'id');
    }

    public function cityData()
    {
        return $this->belongsTo(City::class, 'city');
    }

    // public function scopeCustomerPayments($query)
    // {
    //     return $query->whereHas('payments', function ($query) {
    //         $query->where('type', 'Customer')->where('subject_id', $this->id);
    //     });
    // }

    public function customerPayments()
    {
        return $this->hasMany(Payment::class, 'subject_id')->where('type', 'Customer');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }
}
