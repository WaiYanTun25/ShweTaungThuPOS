<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerRecentPaymentListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "recent_payemnt_list" => $this->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'voucher_no' => $payment->voucher_no,
                    'pay_amount' => $payment->pay_amount,
                    'payment_method_id' => $payment->payment_method_id,
                    'pyament_method_name' => $payment->payment_method->name ?? "",
                    'causer_name' => $payment->createActivity->causer->name ?? "",
                    'payment_date' => formatToCustomDate($payment->payment_date)
                ];
            }),
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $this->currentPage(),
                'from' => $this->firstItem(),
                'last_page' => $this->lastPage(),
                'links' => $this->links(),
                'path' => $this->path(),
                'per_page' => $this->perPage(),
                'to' => $this->lastItem(),
                'total' => $this->total(),
            ],
        ];
    }
}
