<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\BranchScope;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Sale extends Model
{
    use HasFactory , LogsActivity;
    public $timestamps = false;
    protected $fillable = [
        'voucher_no',
        'payment_id',
        'customer_name',
        'branch_id',
        'customer_id',
        'total_quantity',
        'amount',
        'total_amount',
        'tax_percentage',
        'tax_amount',
        'discount_percentage',
        'discount_amount',
        'payment_method_id',
        'pay_amount',
        'remain_amount',
        'payment_status',
        'remark',
        'sales_date'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        $logOptions = LogOptions::defaults()
            ->setDescriptionForEvent(function (string $eventName) {
                $userName = "{userName}";
                return "{$userName} {$eventName} the Sales (Voucher_no {$this->voucher_no})";
            });


        $logOptions->logName = 'SALES';

        return $logOptions;
    }

    protected static function boot()
    {
        parent::boot();

        // Add a global scope to filter by branch
        static::addGlobalScope(new BranchScope());

        // Define a callback function to be executed when a new model is being created
        static::creating(function ($model) {
            // Generate voucher_no if it's not already set
            if (!$model->voucher_no) {
                $model->voucher_no = $model->generateVoucherNo();
            }

            // Set purchase_date to current date if it's not already set
            if (!$model->sales_date) {
                $model->sales_date = now();
            }
        });
    }

    private function generateVoucherNo()
    {
        // Get the last voucher number without the branch scope
        $lastVoucherNo = static::withoutGlobalScope(BranchScope::class)->where('voucher_no', 'like', 'SAL%')->max('voucher_no');

        // Generate a new voucher number based on the last voucher number
        if ($lastVoucherNo) {
            $voucherNo = ++$lastVoucherNo;
        } else {
            // Get the current count of existing records and increment it
            $count = static::count() + 1;

            // Generate a formatted voucher number with leading zeros
            $voucherNo = "SAL-" . str_pad($count, 10, '0', STR_PAD_LEFT);
        }
        return $voucherNo;
    }

    public function branch()
    {
        return $this->hasOne(Branch::class, 'id', 'branch_id');
    }

    public function sales_details()
    {
        return $this->hasMany(SaleDetail::class, 'sale_id', 'id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }

    public function createActivity()
    {
        // return $this->hasOne(Activity::class, 'subject_id', 'id');
        return $this->hasOne(Activity::class, 'subject_id', 'id')->where('subject_type', get_class($this));
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }
}
