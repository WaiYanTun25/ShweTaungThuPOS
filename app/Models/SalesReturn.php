<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class SalesReturn extends Model
{
    use HasFactory, LogsActivity;
    public $timestamps = false;

    protected $fillable = ['voucher_no','branch_id', 'customer_id', 'customer_name', 'total_quantity', 'amount', 'total_amount', 'tax_percentage', 'tax_amount', 'discount_percentage', 'discount_amount', 'pay_amount', 'remark', 'purchase_return_date' , 'payment_method_id', 'amount_type', 'is_lock'];
    
    public function getActivitylogOptions(): LogOptions
    {
        $logOptions = LogOptions::defaults()
            ->logOnly(static::getFillable())
            ->setDescriptionForEvent(function (string $eventName) {
                // $userName = Auth::user()->name ?? 'Unknown User';
                $userName = "{userName}";
                return "{$userName} {$eventName} the Sales Return (Voucher_no {$this->voucher_no})";
            });
            
        
        $logOptions->logName = 'SALES_RETURN';

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
            if (!$model->sales_return_date) {
                $model->sales_return_date = now();
            }
        });
    }

    private function generateVoucherNo()
    {
        // Get the last voucher number without the branch scope
        $lastVoucherNo = static::withoutGlobalScope(BranchScope::class)->where('voucher_no', 'like', 'SAL-R-%')->max('voucher_no');

        // Generate a new voucher number based on the last voucher number
        if ($lastVoucherNo) {
            $voucherNo = ++$lastVoucherNo;
        } else {
            // Get the current count of existing records and increment it
            $count = static::count() + 1;

            // Generate a formatted voucher number with leading zeros
            $voucherNo = "SAL-R-" . str_pad($count, 10, '0', STR_PAD_LEFT);
        }

        return $voucherNo;
    }

    public function branch()
    {
        return $this->hasOne(Branch::class, 'id', 'branch_id');
    }

    public function sales_return_details()
    {
        return $this->hasMany(SalesReturnDetail::class, 'sale_return_id', 'id');
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
        // return $this->hasMany(Payment::class, 'purchase_id', 'id');
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }
}
