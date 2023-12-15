<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = ['မြေနီကုန်းဆိုင်ခွဲ' , 'အင်းစိန်ဆိုင်ခွဲ', 'လှည်းတန်းဆိုင်ခွဲ'];
        $address = ['မြေနီကုန်းလိပ်စာ', 'အင်းစိန် လိပ်စာ', 'လှည်းတန်းလိပ်စာ'];
        $phone_number = ['0909090909', '0989898989' , '09090890098'];

        foreach($branches as $key => $branch)
        {
            $createBranches = new Branch();
            $createBranches->name = $branch;
            $createBranches->address = $address[$key];
            $createBranches->phone_number = $phone_number[$key];
            $createBranches->save();
        }
    }
}
 