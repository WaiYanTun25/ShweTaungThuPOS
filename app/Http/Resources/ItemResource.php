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
            'category_id' => $this->category->name,
            'supplier_id' => $this->supplier->name,
            'item_name' => $this->item_name,
            'item_unit_details' => $this->itemUnitDetails->map(function ($itemUnitDetail) {
                return [
                    'id' => $itemUnitDetail->id,
                    'unit_id' => $itemUnitDetail->unit_id,
                    'unit_name' => $itemUnitDetail->unit->name, 
                    'rate' => $itemUnitDetail->rate,
                    // Add other fields you want to include
                ];
            }),
        ];
    }
}
