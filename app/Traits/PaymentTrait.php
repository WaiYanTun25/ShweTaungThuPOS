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
        $createPayment->payment_method_id = $data['payment_method'];
        $createPayment->pay_amount = $data['pay_amount'];
        $createPayment->save();

        return $createPayment;
    }
}
