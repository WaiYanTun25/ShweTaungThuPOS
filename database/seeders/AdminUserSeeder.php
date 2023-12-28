<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = new User();
        $user->name = 'Admin User';
        $user->branch_id = 0;
        $user->password = Hash::make('password');
        $user->phone_number = "0983748322";
        $user->save();
        
        $user->assignRole('Admin');
    }
}
