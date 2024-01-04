<?php

namespace App\Http\Resources;

use App\Models\ItemUnitDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitConvertDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'convert_date' => Carbon::parse($this->convert_date)->format('d/m/Y'),
            'item_code' => $this->item->item_code,
            'item_name' => $this->item->item_name,
            'from_unit_id' => $this->convertDetail->from_unit_id,
            'from_unit_name' => $this->convertDetail->fromUnit->name,
            'to_unit_id' => $this->convertDetail->to_unit_id,
            'to_unit_name' =>  $this->convertDetail->toUnit->name,
            'from_qty' => $this->convertDetail->from_qty,
            'to_qty' => $this->convertDetail->to_qty,
            'item_unit_details' => $this->relatedUnitDetail(),
        ];
    }

    protected function relatedUnitDetail()
    {
        $relatedUnitWithRate = ItemUnitDetail::where('item_id', $this->item_id)->get();

       $result =  $relatedUnitWithRate->map(function($detail){
            return [
                'unit_id' => $detail->unit_id,
                'unit_name' => $detail->unit->name,
                'rate' => $detail->rate,
            ];
        }); 

        return $result;
    }
}
