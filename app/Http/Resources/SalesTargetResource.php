<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesTargetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if($this->percentage > 0)
        {
            $percentage_text = "ယခင်လထက် ". $this->percentage . " ပိုများပါသည်။";
        }else{
            $percentage_text = "ယခင်လထက် ". $this->percentage . " ပိုနည်းပါသည်။";
        }
        return [
            "total" => $this->total_amount,
            "percentage_text" => $percentage_text,
            "target" => $this->target_percentage
        ];
    }
}
