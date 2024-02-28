<?php

namespace App\Http\Resources;

use App\Models\Item; // Make sure to import the Item model
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            // 'voucher_no' => $this->voucher_no,
            'supplier_name' => $this->item->supplier->name,
            'category_name' => $this->item->category->name,
            'item_id' => $this->item_id,
            'item_code' => $this->item->item_code,
            'item_name' => $this->item->item_name,
            'unit_id' => $this->unit_id,
            'unit_name' => $this->unit->name,
            'quantity' => $this->quantity,
            'item_unit_details' => $this->getItemUnitDetails($this->item->item_code),
        ];
    }

    private function getItemUnitDetails($item_code)
    {
        $item = Item::with(['itemUnitDetails'])->where('item_code', $item_code)->firstOrFail();
        return $item ? $item->itemUnitDetails->map(function ($itemUnitDetail) {
            return [
                'id' => $itemUnitDetail->id,
                'unit_id' => $itemUnitDetail->unit_id,
                'unit_name' => $itemUnitDetail->unit->name, 
                'rate' => $itemUnitDetail->rate,
                'retail_price' => $itemUnitDetail->retail_price,
                'wholesale_price' => $itemUnitDetail->wholesale_price,
                'vip_price' => $itemUnitDetail->vip_price,
                // Add other fields you want to include
            ];
        })->toArray() : [];
    }
}
