<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitConvertResource extends JsonResource
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
            'convert_date' => Carbon::parse($this->convert_date)->format('d/m/Y'),
            'item_code' => $this->item->item_code,
            'item_name' => $this->item->item_name,  
            'from_unit_id' => $this->convertDetail->from_unit_id,
            'from_unit_name' => $this->convertDetail->fromUnit->name,
            'to_unit_id' => $this->convertDetail->to_unit_id,
            'to_unit_name' =>  $this->convertDetail->toUnit->name,
            'from_qty' => $this->convertDetail->from_qty,
            'to_qty' => $this->convertDetail->to_qty
        ];
    }
}
