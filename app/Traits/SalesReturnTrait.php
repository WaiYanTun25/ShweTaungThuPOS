<?php

namespace App\Traits;

use App\Models\{
    Inventory,
    Purchase,
    PurchaseDetail,
    PurchaseReturn,
    PurchaseReturnDetail,
    SalesReturn
};

trait SalesReturnTrait
{
    public function createOrUpdateSalesReturn($data, $branchId, $update = false, $prevData = null)
    {
        if($update) {
            $createPurchase = $prevData;
        }else{
            $createPurchase = new SalesReturn();
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
        $createPurchase->pay_amount = $data['pay_amount'];
        $createPurchase->total_quantity = collect($data['sales_return_details'])->sum('quantity');
        $createPurchase->payment_method_id = $data['payment_method'];
        $createPurchase->pay_amount = $data['pay_amount'];
        $createPurchase->remark = $data['remark'];
        $createPurchase->save();

        return $createPurchase;
    }

    public function createPurchaseDetail($data, $purchaseId)
    {
        $createdDetails = [];
        foreach ($data['purchase_return_details'] as $detail) {
            $createPurchaseDetail = new PurchaseReturnDetail();
            $createPurchaseDetail->purchase_id = $purchaseId;
            $createPurchaseDetail->item_id = $detail['item_id'];
            $createPurchaseDetail->unit_id = $detail['unit_id'];
            $createPurchaseDetail->item_price = $detail['item_price'];
            $createPurchaseDetail->quantity = $detail['quantity'];
            $createPurchaseDetail->discount_amount = $detail['discount_amount'];
            $createPurchaseDetail->amount = $detail['amount'];
            $createPurchaseDetail->save();
            $createdDetails[] = $createPurchaseDetail;
        }
        return $createdDetails;
    }
}
