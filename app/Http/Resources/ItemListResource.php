<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;

class ItemListResource extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_list' => $this->collection->map(function ($inventory) {
                return [
                    'item_code' => $inventory->item->item_code,
                    'item_name' => $inventory->item->item_name,
                    'category_name' => $inventory->item->category->name,
                    'branch_name' => $inventory->branch->name,
                    'quantity' => $inventory->quantity,
                    'last_refill_date' => $inventory->last_refill_date ? formatToCustomDate($inventory->last_refill_date) : null,
                    'reorder_level' => $inventory->item->itemUnitDetails
                        ->filter(function ($detail) use ($inventory) {
                            return $detail->item_id == $inventory->item_id
                                && $detail->unit_id == $inventory->unit_id
                                && $detail->reorder_level;
                        })
                        ->first()
                        ->reorder_level ?? null,
                    'reorder_period' => $inventory->item->itemUnitDetails
                        ->filter(function ($detail) use ($inventory) {
                            return $detail->item_id == $inventory->item_id
                                && $detail->unit_id == $inventory->unit_id
                                && $detail->reorder_level;
                        })
                        ->first()
                        ->reorder_period ?? null,
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