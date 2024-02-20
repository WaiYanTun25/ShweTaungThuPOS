<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
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
            'item_code' => $this->item_code,
            'category_name' => $this->category->name,
            'supplier_name' => $this->supplier->name,
            'item_name' => $this->item_name,
            'item_unit_details' => $this->itemUnitDetails->map(function ($itemUnitDetail) {
                return [
                    'id' => $itemUnitDetail->id,
                    'unit_id' => $itemUnitDetail->unit_id,
                    'unit_name' => $itemUnitDetail->unit->name, 
                    'rate' => $itemUnitDetail->rate,
                    'retail_price' => $itemUnitDetail->retail_price,
                    'wholesale_price' => $itemUnitDetail->wholesale_price,
                    'vip_price' => $itemUnitDetail->vip_price
                    // Add other fields you want to include
                ];
            }),
        ];
    }
}
