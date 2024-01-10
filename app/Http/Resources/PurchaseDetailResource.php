<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'voucher_no' => $this->voucher_no,
            'payment_status' => $this->payment_status,
            'supplier_name' => $this->supplier->name,
            'supplier_address' => $this->supplier->address,
            'causer_name' => $this->createActivity->causer->name,
            'payment_method' => $this->getPaymentMethodsName(),
            'purchase_date' => formatToCustomDate($this->purchase_date),
            'total_quantity' => $this->total_amount,
            'tax_percentage' => $this->tax_percentage,
            'tax_amount' => $this->tax_amount,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount' => $this->discount_amount,
            'total_payment_amount' => $this->getPaymentTotal(),
            'pay_amount' => $this->pay_amount,
            'remain_amount' => $this->remain_amount,
            'purchase_details' =>$this->purchase_details->map(function ($detail) {
                    return [
                        'item_name' => $detail->item->item_name,
                        'quantity' => $detail->quantity,
                        'discount_amount' => $detail->discount_amount,
                        'amount' => $detail->amount,
                    ];
                }),
        ];
    }

    private function getPaymentMethodsName()
    {
        $paymentMethods = [];
        foreach ($this->payments as $paymentMethod) {
            $paymentMethods[] = $paymentMethod->payment_method->name;
        }
        return implode(', ', $paymentMethods);
    }

    private function getPaymentTotal()
    {
        $total = 0;
        foreach ($this->payments as $payment) {
            $total += $payment->pay_amount;
        }
        return $total;
    }
}