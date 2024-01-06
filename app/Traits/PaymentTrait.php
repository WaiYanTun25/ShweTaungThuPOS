<?php

namespace App\Traits;

use App\Models\{
    Payment,
    Purchase,
    PurchaseDetail
};

trait PaymentTrait
{
    public function createPayment($data, $purchaseId)
    {
        $createPayment = new Payment();
        $createPayment->purchase_id = $purchaseId;
        $createPayment->pay_amount = $data['pay_amount'];
        $createPayment->save();

        return $createPayment;
    }
}
