<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;

class Damage extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope());

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

    private function generateVoucherNo()
    {
        $lastVoucherNo = static::withoutGlobalScope(BranchScope::class)->where('voucher_no', 'like', 'INV-D%')->max('voucher_no');
        // $lastVoucherNo = static::withoutGlobalScope(BranchScope::class)->latest('voucher_no')->pluck('voucher_no');
        
        if($lastVoucherNo) {
            $voucherNo = ++$lastVoucherNo;
        }else {
             // Get the current count of existing records and increment it
            $count = static::count() + 1;

            // Generate a formatted voucher number with leading zeros
            $voucherNo = "INV-D-" . str_pad($count, 10, '0', STR_PAD_LEFT);
        }
       

        return $voucherNo;
        // // Get the current count of existing records and increment it
        // $count = static::count() + 1;

        // // Generate a formatted voucher number with leading zeros
        // $voucherNo = "INV-D-" . str_pad($count, 10, '0', STR_PAD_LEFT);

        // return $voucherNo;
    }

    public function branch()
    {
        return $this->hasOne(Branch::class, 'id', 'branch_id');
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
}
