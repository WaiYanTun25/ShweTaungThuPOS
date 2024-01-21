<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class SalesOrder extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['voucher_no', 'branch_id', 'customer_id', 'customer_name', 'total_quantity', 'amount', 'total_amount', 'tax_percentage', 'tax_amount', 'discount_percentage', 'discount_amount', 'remark', 'order_date'];
    
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
            if (!$model->order_date) {
                $model->order_date = now();
            }
        });
    }

    private function generateVoucherNo()
    {
        // Get the last voucher number without the branch scope
        $lastVoucherNo = static::withoutGlobalScope(BranchScope::class)->where('voucher_no', 'like', 'SAL-O-%')->max('voucher_no');

        // Generate a new voucher number based on the last voucher number
        if ($lastVoucherNo) {
            $voucherNo = ++$lastVoucherNo;
        } else {
            // Get the current count of existing records and increment it
            $count = static::count() + 1;

            // Generate a formatted voucher number with leading zeros
            $voucherNo = "SAL-O-" . str_pad($count, 10, '0', STR_PAD_LEFT);
        }

        return $voucherNo;
    }

    public function branch()
    {
        return $this->hasOne(Branch::class, 'id', 'branch_id');
    }

    public function sales_order_details()
    {
        return $this->hasMany(SalesOrderDetail::class, 'sale_order_id', 'id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }

    public function createActivity()
    {
        return $this->hasOne(Activity::class, 'subject_id', 'id');
    }
}
