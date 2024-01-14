<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $appends = ['last_refill_date'];

    protected static function boot()
    {
        parent::boot();

        // static::addGlobalScope(new BranchScope());
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'branch_id', 'branch_id');
    }

    public function  branch() 
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function  item() 
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    // accessor for last refill date of purchase
    public function getLastRefillDateAttribute()
    {
        // Assuming you have a relationship between Inventory and PurchaseDetail models
        $lastPurchaseDetail = $this->purchaseDetails()
            ->join('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
            ->where('purchases.branch_id', $this->branch_id)
            ->latest('purchases.purchase_date')
            ->first();
        
        $lastReceiveDetail = $this->receives()
        ->join('receives', 'transfer_details.voucher_no', '=', 'receives.voucher_no')
            ->where('receives.to_branch_id', $this->branch_id)
            ->latest('receives.transaction_date')
            ->first();

        return max($lastPurchaseDetail?->purchase_date, $lastReceiveDetail?->transaction_date) ?? null;
    }

    // Relationship with PurchaseDetail model
    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class, 'item_id', 'item_id')
            ->where('unit_id', $this->unit_id);
    }

    public function receives()
    {
        return $this->hasMany(TransferDetail::class, 'item_id', 'item_id')
            ->where('unit_id', $this->unit_id);
    }
}

// $user = $this;

//     return $this->hasMany(Inventory::class, 'branch_id', 'branch_id')
//         ->when($user->branch_id !== 0, function ($query) use ($user) {
//             return $query->where('branch_id', $user->branch_id);
//         });