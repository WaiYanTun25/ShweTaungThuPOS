<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Customer 1',
                'phone_number' => '0998394850',
                'address' => 'ရန်ကုန် အရှေ့ပိုင်း',
                'township' => 2,
                'city' => 1,
                'customer_type' => Customer::SPECIFIC
            ],
            [
                'name' => 'Customer 2',
                'phone_number' => '0998394850',
                'address' => 'မန္တလေး အရှေ့ပိုင်း',
                'township' => 4,
                'city' => 2,
                'customer_type' => Customer::GENERAL
            ],
            [
                'name' => 'Customer 3',
                'phone_number' => '0998394850',
                'address' => 'ရန်ကုန် အနောက်ပိုင်း',
                'township' => 1,
                'city' => 1,
                'customer_type' => Customer::SPECIFIC
            ]
        ];

        foreach($customers as $data) 
        {
            Customer::create($data);
        }
    }
}