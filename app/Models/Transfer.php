<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;

class Transfer extends Model
{
    use HasFactory;
    public $timestamps = false;

    const SENT = "sent";
    const RECEIVE = "received";
    
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope('from_branch_id'));

        static::creating(function ($model) {
            // Generate voucher_no if it's not already set
            if (!$model->voucher_no) {
                $model->voucher_no = $model->generateVoucherNo();
            }

            if (!$model->status) {
                $model->status = self::SENT;
            }

            // for transaction_date
            if (!$model->transaction_date) {
                $model->transaction_date = now();
            }
        });
    }

    private function generateVoucherNo()
    {
        $branchId = $this->from_branch_id;
        $uniqueId = uniqid();
        $timestamp = now()->timestamp;
        return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 12);
        return "I-{$branchId}-" . substr($uniqueId, 0, -4) . substr($timestamp, 0, -4);
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
