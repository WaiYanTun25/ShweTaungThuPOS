<?php

namespace App\Traits;

use App\Models\{
    Inventory,
    SalesOrder
};

trait SalesOrderTrait
{
    public function createOrUpdateSalesOrder($data, $branchId, $update = false, $prevData = null)
    {
        if($update) {
            $createPurchase = $prevData;
        }else{
            $createPurchase = new SalesOrder();
        }
        $createPurchase->customer_id = $data['customer_id'];
        $createPurchase->customer_name = $data['customer_name'] ?? null;
        $createPurchase->branch_id = $branchId;
        $createPurchase->amount = $data['amount'];
        $createPurchase->total_amount = $data['total_amount'];
        $createPurchase->tax_percentage = $data['tax_percentage'];
        $createPurchase->tax_amount = $data['tax_amount'];
        $createPurchase->discount_percentage = $data['discount_percentage'];
        $createPurchase->discount_amount = $data['discount_amount'];
        $createPurchase->total_quantity = collect($data['sales_order_details'])->sum('quantity');
        $createPurchase->remark = $data['remark'];
        $createPurchase->save();

        return $createPurchase;
    }
}
