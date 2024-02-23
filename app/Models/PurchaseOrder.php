<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class PurchaseOrder extends Model
{
    use HasFactory, LogsActivity;
    public $timestamps = false;
    protected $fillable = ['voucher_no', 'branch_id', 'supplier_id', 'total_quantity', 'amount', 'total_amount', 'tax_percentage', 'tax_amount', 'discount_percentage', 'discount_amount', 'remark', 'order_date'];

    public function getActivitylogOptions(): LogOptions
    {
        $logOptions = LogOptions::defaults()
            ->setDescriptionForEvent(function (string $eventName) {
                // $userName = Auth::user()->name ?? 'Unknown User';
                $userName = "{userName}";
                return "{$userName} {$eventName} the Purchase Order (Voucher_no {$this->voucher_no})";
            });
            
        
        $logOptions->logName = 'PURCHASE_ORDER';

        return $logOptions;
    }

     /**
     * The "booting" method of the model.
     *
     * @return void
     */
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

    /**
     * Generates a voucher number for the purchase.
     *
     * @return string The generated voucher number.
     */
    private function generateVoucherNo()
    {
        // Get the last voucher number without the branch scope
        $lastVoucherNo = static::withoutGlobalScope(BranchScope::class)->where('voucher_no', 'like', 'PUR-O-%')->max('voucher_no');

        // Generate a new voucher number based on the last voucher number
        if ($lastVoucherNo) {
            $voucherNo = ++$lastVoucherNo;
        } else {
            // Get the current count of existing records and increment it
            $count = static::count() + 1;

            // Generate a formatted voucher number with leading zeros
            $voucherNo = "PUR-O-" . str_pad($count, 10, '0', STR_PAD_LEFT);
        }

        return $voucherNo;
    }

    public function branch()
    {
        return $this->hasOne(Branch::class, 'id', 'branch_id');
    }

    public function purchase_order_details()
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'purchase_order_id', 'id');
    }

    public function supplier()
    {
        return $this->hasOne(Supplier::class, 'id', 'supplier_id');
    }

    public function createActivity()
    {
        // return $this->hasOne(Activity::class, 'subject_id', 'id');
        return $this->hasOne(Activity::class, 'subject_id', 'id')->where('subject_type', get_class($this));
    }
}
