<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

use Spatie\Activitylog\Models\Activity;

class Issue extends Model
{
    use HasFactory;
    public $timestamps = false;
    // public function getActivitylogOptions(): LogOptions
    // {
    //     $logOptions = LogOptions::defaults()
    //         ->setDescriptionForEvent(function (string $eventName) {
    //             $userName = Auth::user()->name ?? 'Unknown User';
    //             return "{$userName} {$eventName} the Issue (Voucher_no {$this->voucher_no})";
    //         });

    //     $logOptions->logName = 'ISSUE';

    //     return $logOptions;
    // }

     /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope('from_branch_id'));

        static::creating(function ($model) {
            // Generate voucher_no if it's not already set
            if (!$model->voucher_no) {
                $model->voucher_no = $model->generateVoucherNo();
            }

            // for transaction_date
            if (!$model->transaction_date) {
                $model->transaction_date = now();
            }
        });
    }

    protected static function generateItemCode($supplierId)
    {
        // Get the last item for the given supplier from the database
        $lastItem = static::latest('item_code')->first();
        // Get the issues's prefix
        $supplierPrefix = "INV-I";
        if (!$lastItem) {
            return $supplierPrefix . '-000001';
        }
    
        // Extract the numeric part of the existing item code and increment it
        $numericPart = (int)substr($lastItem->voucher_no, -6);
        $newNumericPart = str_pad($numericPart + 1, 6, '0', STR_PAD_LEFT);
    
        return $supplierPrefix . '-' . $newNumericPart;
    }

    private function generateVoucherNo()
    {
        $lastVoucherNo = static::withoutGlobalScope(BranchScope::class)->where('voucher_no', 'like', 'INV-I%')->max('voucher_no');
        // $lastVoucherNo = static::withoutGlobalScope(BranchScope::class)->latest('voucher_no')->pluck('voucher_no');
        
        if($lastVoucherNo) {
            $voucherNo = ++$lastVoucherNo;
        }else {
             // Get the current count of existing records and increment it
            $count = static::count() + 1;

            // Generate a formatted voucher number with leading zeros
            $voucherNo = "INV-I-" . str_pad($count, 10, '0', STR_PAD_LEFT);
        }
       

        return $voucherNo;
    }

    public function issuesTo()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id', 'id');
    }

    public function issuesFrom()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id', 'id');
    }

    public function transfer_details()
    {
        return $this->hasMany(TransferDetail::class, 'voucher_no', 'voucher_no');
    }

    // get transfer_detail 's unit and item
    public function units()
    {
        return $this->hasManyThrough(Unit::class, TransferDetail::class, 'voucher_no', 'id', 'voucher_no', 'unit_id');
    }

    public function items()
    {
        return $this->hasManyThrough(Item::class, TransferDetail::class, 'voucher_no', 'id', 'voucher_no', 'item_id');
    }

    public function createActivity()
    {
        // return $this->hasOne(Activity::class, 'subject_id', 'id');
        return $this->hasOne(Activity::class, 'subject_id', 'id')->where('subject_type', get_class($this));
    }
}
