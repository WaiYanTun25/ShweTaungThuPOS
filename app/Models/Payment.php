<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

use Spatie\Activitylog\Models\Activity;
class Payment extends Model
{
    use HasFactory, LogsActivity;
    public $timestamps = false;
    
    public const Customer = 'Customer';
    public const Supplier = 'Supplier';

    public function getActivitylogOptions(): LogOptions
    {
        $type = "";
        $logOptions = LogOptions::defaults()
            ->setDescriptionForEvent(function (string $eventName) {
                $userName = "{userName}";
                $type = $this->type == "customer" ? self::Customer : self::Supplier;
                return "{$userName} {$eventName} the {$type} Payment (Voucher_no {$this->voucher_no})";
            });


        $logOptions->logName = $type .'_PAYMENT';

        return $logOptions;
    }

    protected static function boot()
    {
        parent::boot();

        // Add a global scope to filter by branch
        // static::addGlobalScope(new BranchScope());

        // Define a callback function to be executed when a new model is being created
        static::creating(function ($model) {
            // Generate voucher_no if it's not already set
            if (!$model->voucher_no) {
                $model->voucher_no = $model->generateVoucherNo();
            }

            // Set purchase_date to current date if it's not already set
            if (!$model->payment_date) {
                $model->payment_date = now();
            }
        });
    }

    private function generateVoucherNo()
    {
        // Get the last voucher number without the branch scope
        $lastVoucherNo = static::max('voucher_no');

        // Generate a new voucher number based on the last voucher number
        if ($lastVoucherNo) {
            $voucherNo = ++$lastVoucherNo;
        } else {
            // Get the current count of existing records and increment it
            $count = static::count() + 1;

            // Generate a formatted voucher number with leading zeros
            $voucherNo = "PAY-" . str_pad($count, 10, '0', STR_PAD_LEFT);
        }
        return $voucherNo;
    }

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function createActivity()
    {
        // return $this->hasOne(Activity::class, 'subject_id', 'id');
        return $this->hasOne(Activity::class, 'subject_id', 'id')->where('subject_type', get_class($this));
    }

    
}
