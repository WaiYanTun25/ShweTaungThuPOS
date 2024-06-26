<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
// use Spatie\Activitylog\LogOptions;
// use Spatie\Activitylog\Models\Activity;
// use Spatie\Activitylog\Traits\LogsActivity;

class PurchaseOrderDetail extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = ['purchase_order_id', 'item_id', 'unit_id', 'discount_amount', 'item_price', 'quantity', 'amount'];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    // public function createActivity()
    // {
    //     return $this->hasOne(Activity::class, 'subject_id', 'id');
    // }
}
