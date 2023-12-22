<?php

namespace Database\Seeders;

use App\Models\Inventory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $itemData = [
            [
                "category_id" => 2,
                "category_id" => 1,
                "supplier_id" =>  2,
                "item_name" => "test1",
                "unit_detail" => [
                    [
                        "unit_id" => 1,
                        "rate" => 1,
                        "vip_price" => 1500,
                        "retail_price" => 1200,
                        "wholesale_price" => 1100,
                        "reorder_level" => 12,
                        "reorder_period" => 14
                    ],
                    [
                        "unit_id" => 2,
                        "rate" => 10,
                        "vip_price" => 15000,
                        "retail_price" => 12000,
                        "wholesale_price" => 11000,
                        "reorder_level" => 20,
                        "reorder_period" => 25
                    ]
                ]
            ]
        ];

        $unitData = [
            
        ]
    }
}
