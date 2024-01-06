<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = ['Cash', 'KBZ Pay', 'AYA Pay', 'CB Pay'];
        foreach($paymentMethods as $paymentMethod) {
            \App\Models\PaymentMethod::create([
                'name' => $paymentMethod
            ]);
        }
    }
}
