<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnListResource extends JsonResource
{
    private $report;
    public function __construct($resource, $report = false)
    {
        parent::__construct($resource);
        $this->report = $report;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $uniqueItemIds = [];
        $purchase_return_list = $this->data->map(function ($purchase_return)  use (&$uniqueItemIds) {

            foreach ($purchase_return['purchase_return_details'] as $detail) {
                $uniqueItemIds[] = $detail['item_id'];
            }

            return [
                'id' => $purchase_return->id,
                'voucher_no' => $purchase_return->voucher_no,
                'supplier_name' => $purchase_return->supplier->name,
                'item_name' => $this->getItemsName($purchase_return->purchase_return_details),
                'branch_name' => $purchase_return->branch->name,
                'total_quantity' => $purchase_return->total_quantity,
                // 'total_amount' => $purchase_return->total_amount,
                'pay_amount' => $purchase_return->pay_amount,
                'causer_name' => $purchase_return->createActivity->causer->name ?? "",
                'purchase_return_date' => formatToCustomDate($purchase_return->purchase_return_date)
            ];
        });

        // previous comment
        // $total_return_count = $this->data->sum(function ($purchase_return) {
        //     return count($purchase_return->purchase_return_details);
        // });

        $total_return_count = count(array_unique($uniqueItemIds));
        $total_return_amount = $this->data->sum(function ($purchase_return) {
            return $purchase_return->pay_amount;
        });

        if($this->report) {
            return [
                'total_return_count' => $total_return_count,
                'total_return_amount' => $total_return_amount,
                'purchase_return_list' => $purchase_return_list
            ];
        }else{
            return [
                'total_return_count' => $total_return_count,
                'total_return_amount' => $total_return_amount,
                'purchase_return_list' => $purchase_return_list,
                'links' => [
                    'first' => $this->data->url(1),
                    'last' => $this->data->url($this->data->lastPage()),
                    'prev' => $this->data->previousPageUrl(),
                    'next' => $this->data->nextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $this->data->currentPage(),
                    'from' => $this->data->firstItem(),
                    'last_page' => $this->data->lastPage(),
                    'links' => $this->data->links(),
                    'path' => $this->data->path(),
                    'per_page' => $this->data->perPage(),
                    'to' => $this->data->lastItem(),
                    'total' => $this->data->total(),
                ],
            ];
        }

        return parent::toArray($request);
    }

    private function getItemsName($details)
    {
        $itemsName = [];
        foreach ($details as $detail) {
            $itemsName[] = $detail->item->item_name;
        }
        return implode(', ', $itemsName);
    }

    // private function getItemCount($details)
    // {
    //     info($details);
    //     $uniqueItemIds = collect($details)->pluck('item_id')->unique()->values()->count();
    //     // $uniqueItemIdsArray = $uniqueItemIds->toArray();

    //     return $uniqueItemIds;
    // }
}
