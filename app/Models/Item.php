<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        // Creating event to generate and set the item_code
        static::creating(function ($item) {
            $item->item_code = static::generateItemCode($item->supplier_id);
        });
    }

    protected static function generateItemCode($supplierId)
    {
        // Get the last item for the given supplier from the database
        $lastItem = static::where('supplier_id', $supplierId)->latest('item_code')->first();
        // Get the supplier's prefix
        $supplier = Supplier::find($supplierId);
        $supplierPrefix = $supplier->prefix;
        if (!$lastItem) {
            return $supplierPrefix . '-000001';
        }
    
        // Extract the numeric part of the existing item code and increment it
        $numericPart = (int)substr($lastItem->item_code, -6);
        $newNumericPart = str_pad($numericPart + 1, 6, '0', STR_PAD_LEFT);
    
        return $supplierPrefix . '-' . $newNumericPart;
    }

    public function itemUnitDetails()
    {
        return $this->hasmany(ItemUnitDetail::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    // for purchase
    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class, 'item_id');
    }
}
