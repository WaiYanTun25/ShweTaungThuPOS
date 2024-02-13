<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopPerformingResource extends JsonResource
{
    private $type;
    public function __construct($resource, $type)
    {
        parent::__construct($resource);
        $this->type = $type;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $index = 0;

        if ( $this->type == 'customer') {
            return [
                "top_customers" => $this->resource->map(function ( $customer ) use (&$index) {
                   
                    return [
                        'number' => ++$index,
                        'customer_id' => $customer->customer_id,
                        'total_amount' => $customer->total_amount,
                        'customer_name' => $customer->customer->name,
                    ];
                })
            ];
        } else {
            return [
                "top_products" => $this->resource->map(function ( $product ) use (&$index) {
                   
                    return [
                        'number' => ++$index,
                        'item_id' => $product->item_id,
                        'total_amount' => $product->total_amount,
                        'item_name' => $product->item->item_name,
                    ];
                })
            ];
        }
        
    }
}
