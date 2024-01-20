<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $supplierData = [
            [
                "name" => "အောင်ကောင်းစည်1",
                "prefix" => "AA",
                "phone_number" => "0996939693",
                "address" => "၆၀ ဘီ ကမ်းနားလမ်း အလုံ",
                "township" => 2,
                "city" => 1
            ],
            [
                "name" => "အောင်ကောင်းစည်2",
                "prefix" => "BB",
                "phone_number" => "0996939693",
                "address" => "၆၀ ဘီ ကမ်းနားလမ်း အလုံ",
                "township" => 1,
                "city" => 1
            ],
            [
                "name" => "အောင်ကောင်းစည်3",
                "prefix" => "CC",
                "phone_number" => "0996939693",
                "address" => "၆၀ ဘီ ကမ်းနားလမ်း အလုံ",
                "township" => 4,
                "city" => 2
            ],
        ];

        foreach ((array)$supplierData as $data) {
            $supplier = new Supplier();
            $supplier->name = $data['name'];
            $supplier->prefix = $data['prefix'];
            $supplier->phone_number = $data['phone_number'];
            $supplier->address = $data['address'];
            $supplier->township = $data['township'];
            $supplier->city = $data['city'];
            $supplier->save();
        }
    }
}
