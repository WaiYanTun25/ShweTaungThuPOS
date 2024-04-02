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
    public function getSupplierName($prefix)
    {
        $getSupplier = Supplier::where('prefix', $prefix)->first();
        return $getSupplier->name;
    }
}