<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\ItemUnitDetail;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                "name" => 'အချိုရည်',
                "prefix" => 'A'
            ],
            [
                "name" => 'အိမ်သုံးအဆောင်',
                "prefix" => 'E'
            ],
            [
                "name" => 'စားသောက်ကုန်',
                "prefix" => 'S'
            ],
        ];

        foreach ((array)$categories as $data) {
            $category = new Category();
            $category->name = $data['name'];
            $category->prefix = $data['prefix'];
            $category->save();
        }

        $itemData = [
            [
                "category_id" => 1,
                "supplier_id" =>  1,
                "item_name" => "item 1",
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
                        "rate" => 6,
                        "vip_price" => 15000,
                        "retail_price" => 12000,
                        "wholesale_price" => 11000,
                        "reorder_level" => 20,
                        "reorder_period" => 25
                    ]
                ]
            ],
            [
                "category_id" => 1,
                "supplier_id" =>  2,
                "item_name" => "item 2",
                "unit_detail" => [
                    [
                        "unit_id" => 1,
                        "rate" => 1,
                        "vip_price" => 2500,
                        "retail_price" => 2400,
                        "wholesale_price" => 2100,
                        "reorder_level" => 6,
                        "reorder_period" => 10
                    ],
                    [
                        "unit_id" => 2,
                        "rate" => 6,
                        "vip_price" => 25000,
                        "retail_price" => 24000,
                        "wholesale_price" => 21000,
                        "reorder_level" => 20,
                        "reorder_period" => 25
                    ]
                ]
            ],
            [
                "category_id" => 1,
                "supplier_id" =>  3,
                "item_name" => "item 3",
                "unit_detail" => [
                    [
                        "unit_id" => 1,
                        "rate" => 1,
                        "vip_price" => 800,
                        "retail_price" => 700,
                        "wholesale_price" => 500,
                        "reorder_level" => 10,
                        "reorder_period" => 10
                    ],
                    [
                        "unit_id" => 2,
                        "rate" => 6,
                        "vip_price" => 5000,
                        "retail_price" => 4000,
                        "wholesale_price" => 3000,
                        "reorder_level" => 20,
                        "reorder_period" => 25
                    ]
                ]
            ]
        ];

        foreach ((array)$itemData as $data) {
            $item = new Item();
            $item->category_id = $data['category_id'];
            $item->supplier_id = $data['supplier_id'];
            $item->item_name = $data['item_name'];
            $item->save();

            foreach ((array)$data['unit_detail'] as $detail) {
                $itemUnitDetail = new ItemUnitDetail();
                $itemUnitDetail->item_id = $item->id;
                $itemUnitDetail->rate = $detail['rate'];
                $itemUnitDetail->unit_id = $detail['unit_id'];
                $itemUnitDetail->vip_price = $detail['vip_price'];
                $itemUnitDetail->retail_price = $detail['retail_price'];
                $itemUnitDetail->wholesale_price = $detail['wholesale_price'];
                $itemUnitDetail->reorder_level = $detail['reorder_level'];
                $itemUnitDetail->reorder_period = $detail['reorder_period'];
                $itemUnitDetail->save();
            }
        }

        $unitData = ['ဗူး', 'ကဒ်', 'ပါကင်'];


        foreach ($unitData as $data) {
            $unit = new Unit();
            $unit->name = $data;
            $unit->save();
        }

        $inventoryData = [
            [
                'branch_id' => 1,
                'item_id' => 1,
                'unit_id' => 2,
                'quantity' => 100
            ],
            [
                'branch_id' => 1,
                'item_id' => 1,
                'unit_id' => 1,
                'quantity' => 100
            ],
            [
                'branch_id' => 1,
                'item_id' => 2,
                'unit_id' => 1,
                'quantity' => 100
            ],
            [
                'branch_id' => 1,
                'item_id' => 2,
                'unit_id' => 2,
                'quantity' => 100
            ],
            [
                'branch_id' => 2,
                'item_id' => 1,
                'unit_id' => 2,
                'quantity' => 100
            ],
            [
                'branch_id' => 2,
                'item_id' => 1,
                'unit_id' => 1,
                'quantity' => 100
            ],
            [
                'branch_id' => 2,
                'item_id' => 2,
                'unit_id' => 1,
                'quantity' => 100
            ],
            [
                'branch_id' => 2,
                'item_id' => 2,
                'unit_id' => 2,
                'quantity' => 100
            ],
        ];

        foreach ((array)$inventoryData as $data) {
            $inventory = new Inventory();
            $inventory->branch_id = $data['branch_id'];
            $inventory->item_id = $data['item_id'];
            $inventory->unit_id = $data['unit_id'];
            $inventory->quantity = $data['quantity'];
            $inventory->save();
        }
    }
}
