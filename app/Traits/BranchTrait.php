<?php

namespace App\Traits;

use App\Models\Branch;

trait BranchTrait
{
    public function getBranch($id)
    {
        return Branch::findOrFail($id);
    }

    public function createBranch($request)
    {
        $branch = new Branch();
        $branch->name = $request->name;
        $branch->phone_number = $request->phone_number;
        $branch->address = $request->address;
        $branch->save();

        return $branch;
    }

    public function updateBranch($branch, $request)
    {
        $branch->name = $request->name;
        $branch->phone_number = $request->phone_number;
        $branch->address = $request->address;
        $branch->save();

        return $branch;
    }
}
