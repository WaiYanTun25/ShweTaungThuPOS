<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'item_name' => $this->item_name,
            'item_code' => $this->item_code,
            'supplier_name' => $this->supplier->name,
            'category_name' => $this->category->name,
            'item_unit_details' => $this->itemUnitDetails->map(function($detail){
                return $this->shakeOfUnitDetails($detail);
            }),
            'branch_with_quantities' => $this->branch_with_quantities
        ];
    }

    protected function shakeOfUnitDetails($detail)
    {
        return [
            'unit_name' => $detail->unit->name,
            'rate' => $detail->rate,
            'reorder_level' => $detail->reorder_level,
            'reorder_period' => $detail->reorder_period. " ရက်အတွင်း",
            'vip_price' => $detail->vip_price,
            'retail_price' => $detail->retail_price,
            'wholesale_price' => $detail->wholesale_price
        ];
    }
}
