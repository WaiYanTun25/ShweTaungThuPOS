<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CashierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = new User();
        $user->name = 'Cashier 1';
        $user->branch_id = 1;
        $user->password = Hash::make('password');
        $user->phone_number = "098474848320";
        $user->save();

        $user->assignRole('Cashier');

        $user2 = new User();
        $user2->name = 'Cashier 2';
        $user2->branch_id = 2;
        $user2->password = Hash::make('password');
        $user2->phone_number = "098474848320";
        $user2->save();

        $user2->assignRole('Cashier');
    }
}
