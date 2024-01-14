<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class PurchaseReturn extends Model
{
    use HasFactory, LogsActivity;
    public $timestamps = false;

    protected $fillable = ['voucher_no','branch_id', 'supplier_id', 'total_quantity', 'amount', 'total_amount', 'tax_percentage', 'tax_amount', 'discount_percentage', 'discount_amount', 'pay_amount', 'remark', 'purchase_return_date'];
    
    public function getActivitylogOptions(): LogOptions
    {
        $logOptions = LogOptions::defaults()
            ->logOnly(static::getFillable())
            ->setDescriptionForEvent(function (string $eventName) {
                $userName = Auth::user()->name ?? 'Unknown User';
                return "{$userName} {$eventName} the Purchase Return (Voucher_no {$this->voucher_no})";
            });
            
        
        $logOptions->logName = 'PURCHASE_RETURN';

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
            if (!$model->purchase_date) {
                $model->purchase_date = now();
            }
        });
    }

    private function generateVoucherNo()
    {
        // Get the last voucher number without the branch scope
        $lastVoucherNo = static::withoutGlobalScope(BranchScope::class)->where('voucher_no', 'like', 'PUR-R-%')->max('voucher_no');

        // Generate a new voucher number based on the last voucher number
        if ($lastVoucherNo) {
            $voucherNo = ++$lastVoucherNo;
        } else {
            // Get the current count of existing records and increment it
            $count = static::count() + 1;

            // Generate a formatted voucher number with leading zeros
            $voucherNo = "PUR-R-" . str_pad($count, 10, '0', STR_PAD_LEFT);
        }

        return $voucherNo;
    }

    public function branch()
    {
        return $this->hasOne(Branch::class, 'id', 'branch_id');
    }

    public function purchase_return_details()
    {
        return $this->hasMany(PurchaseReturnDetail::class, 'purchase_return_id', 'id');
    }

    public function supplier()
    {
        return $this->hasOne(Supplier::class, 'id', 'supplier_id');
    }

    public function createActivity()
    {
        return $this->hasOne(Activity::class, 'subject_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'purchase_id', 'id');
    }

}