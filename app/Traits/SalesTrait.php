<?php

namespace App\Traits;

use App\Models\{
    Inventory,
    PurchaseDetail,
    Sale
};

trait SalesTrait
{
    public function createOrUpdateSales($data, $branchId, $update = false, $prevData = null)
    {
        if($update) {
            $createSale = $prevData;
        }else{
            $createSale = new Sale();
        }
        
        $pay_amount = 0;
        
        $createSale->branch_id = $branchId;
        $createSale->customer_id = $data['customer_id'];
        $createSale->amount = $data['amount'];
        if ($data['payment_status'] != 'UN_PAID') 
        {
            $pay_amount = $data['pay_amount'];
            $createSale->payment_method_id = $data['payment_method'];
        }
        $createSale->total_amount = $data['total_amount'];
        $createSale->tax_percentage = $data['tax_percentage'];
        $createSale->tax_amount = $data['tax_amount'];
        $createSale->discount_percentage = $data['discount_percentage'];
        $createSale->discount_amount = $data['discount_amount'];
        $createSale->pay_amount = $pay_amount;
        $createSale->total_quantity = collect($data['sales_details'])->sum('quantity');
        $createSale->remain_amount = $data['total_amount'] - $pay_amount;
        $createSale->payment_status = $data['payment_status'];
        $createSale->amount_type = $data['amount_type'];
        $createSale->remark = $data['remark'];
        $createSale->save();

        return $createSale;
    }

    public function createSalesDetail($data, $purchaseId)
    {
        $createdDetails = [];
        foreach ($data['purchase_details'] as $detail) {
            $createSaleDetail = new PurchaseDetail();
            $createSaleDetail->purchase_id = $purchaseId;
            $createSaleDetail->item_id = $detail['item_id'];
            $createSaleDetail->unit_id = $detail['unit_id'];
            $createSaleDetail->item_price = $detail['item_price'];
            $createSaleDetail->quantity = $detail['quantity'];
            $createSaleDetail->discount_amount = $detail['discount_amount'];
            $createSaleDetail->amount = $detail['amount'];
            $createSaleDetail->save();
            $createdDetails[] = $createSaleDetail;
        }
        return $createdDetails;
    }
}
