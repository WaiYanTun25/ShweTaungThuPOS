<?php

namespace App\Traits;

use App\Models\Branch;

trait CategoryTrait
{
    public function getCategory($id)
    {
        return Branch::findOrFail($id);
    }

    public function createCategory($request)
    {
        $branch = new Branch();
        $branch->name = $request->name;
        $branch->phone_number = $request->phone_number;
        $branch->address = $request->address;
        $branch->save();

        return $branch;
    }

    public function updateCategory($branch, $request)
    {
        $branch->name = $request->name;
        $branch->phone_number = $request->phone_number;
        $branch->address = $request->address;
        $branch->save();

        return $branch;
    }
}
