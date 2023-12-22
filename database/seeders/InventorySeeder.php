<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Inventory;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inventoriesData = [
            [
                'branch_id' => 1,
                'item_id' => 1,
                'unit_id' => 1,
                'quantity' => 10
            ],
            [
                'branch_id' => 1,
                'item_id' => 1,
                'unit_id' => 2,
                'quantity' => 10
            ]
        ];

        foreach((array)$inventoriesData as $data)
        {
            $createInventory = new Inventory();
            $createInventory->branch_id = $data->branch_id;
            $createInventory->item_id = $data->item_id;
            $createInventory->unit_id = $data->unit_id;
            $createInventory->quantity = $data->quantity;
            $createInventory->save();
        }
    }
}
