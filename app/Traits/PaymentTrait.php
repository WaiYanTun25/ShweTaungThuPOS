<?php

namespace App\Traits;

use App\Models\{
    Payment,
    Purchase,
    PurchaseDetail
};

trait PaymentTrait
{
    public function createPayment($data)
    {
        $createPayment = new Payment();
        $createPayment->type = 'Supplier'; // or 'Customer' based on your logic
        $createPayment->subject_id = $data['supplier_id'];
        $createPayment->payment_method_id = $data['payment_method'];
        $createPayment->pay_amount = $data['pay_amount'];
        $createPayment->save();
    
        return $createPayment;
    }
}
