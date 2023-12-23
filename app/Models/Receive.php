<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\BranchScope;

class Receive extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = "transfers";

    // const for enum status
    const SENT = "sent";
    const RECEIVE = "received";

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope('to_branch_id'));

        static::updating(function ($model) {
            // Generate voucher_no if it's not already set

            if (!$model->status) {
                $model->status = self::SENT;
            }

            // for transaction_date
            if (!$model->receive_date) {
                $model->transaction_date = now();
            }
        });
    }

    public function transfer_details()
    {
        return $this->hasMany(TransferDetail::class, 'voucher_no', 'voucher_no');
    }

    public function units()
    {
        return $this->hasManyThrough(Unit::class, TransferDetail::class, 'voucher_no', 'id', 'voucher_no', 'unit_id');
    }

    public function items()
    {
        return $this->hasManyThrough(Item::class, TransferDetail::class, 'voucher_no', 'id', 'voucher_no', 'item_id');
    }
}
