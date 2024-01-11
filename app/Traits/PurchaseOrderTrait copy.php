<?php

namespace App\Traits;

use App\Models\{
    Inventory,
    Purchase,
    PurchaseDetail
};

trait PurchaseOrderTrait
{
    public function createOrUpdatePurchaseOrder($data, $branchId, $update = false, $prevData = null)
    {
        if($update) {
            $createPurchase = $prevData;
        }else{
            $createPurchase = new Purchase();
        }
        $createPurchase->supplier_id = $data['supplier_id'];
        $createPurchase->branch_id = $branchId;
        $createPurchase->amount = $data['amount'];
        $createPurchase->total_amount = $data['total_amount'];
        $createPurchase->tax_percentage = $data['tax_percentage'];
        $createPurchase->tax_amount = $data['tax_amount'];
        $createPurchase->discount_percentage = $data['discount_percentage'];
        $createPurchase->discount_amount = $data['discount_amount'];
        $createPurchase->pay_amount = $data['pay_amount'];
        $createPurchase->total_quantity = collect($data['purchase_details'])->sum('quantity');
        $createPurchase->remain_amount = $data['total_amount'] - $data['pay_amount'];
        $createPurchase->payment_status = $data['payment_status'];
        $createPurchase->remark = $data['remark'];
        $createPurchase->save();

        return $createPurchase;
    }

    public function createPurchaseDetail($data, $purchaseId)
    {
        $createdDetails = [];
        foreach ($data['purchase_details'] as $detail) {
            $createPurchaseDetail = new PurchaseDetail();
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
