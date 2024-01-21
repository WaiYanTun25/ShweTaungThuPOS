<?php

namespace App\Http\Resources;

use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

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
            'item_id' => $this->id,
            'item_name' => $this->item_name,
            'item_code' => $this->item_code,
            'supplier_name' => $this->supplier->name,
            'supplier_id' => $this->supplier_id,
            'category_name' => $this->category->name,
            'category_id' => $this->category_id,
            'current_stock' => $this->current_stock($this->id),
            'item_unit_details' => $this->itemUnitDetails->map(function($detail){
                return $this->shakeOfUnitDetails($detail);
            }),
            'branch_with_quantities' => $this->branch_with_quantities
        ];
    }

    protected function shakeOfUnitDetails($detail)
    {
        return [
            'id' => $detail->id,
            'item_id' => $detail->item_id,
            'unit_name' => $detail->unit->name,
            'unit_id' => $detail->unit_id,
            'rate' => $detail->rate,
            'reorder_level' => $detail->reorder_level,
            'reorder_period' => $detail->reorder_period,
            'vip_price' => $detail->vip_price,
            'retail_price' => $detail->retail_price,
            'wholesale_price' => $detail->wholesale_price
        ];
    }

    protected function current_stock($id)
    {
        $branchId = Auth::user()->branch_id ;
        $branch_stock = Inventory::where('item_id', $id);
        if($branchId != 0) 
        {
            $branch_stock->where('branch_id', $branchId);
        }
        return $branch_stock->sum('quantity');
    }
}
