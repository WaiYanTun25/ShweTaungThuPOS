<?php

namespace App\Traits;

use App\Models\{
    Inventory,
    PurchaseDetail,
    Sale,
    Supplier
};

trait SupplierTrait
{
    public function getSupplierName($supplierId)
    {
        $getSupplier = Supplier::find($supplierId);
        
        return $getSupplier->name;
    }
}