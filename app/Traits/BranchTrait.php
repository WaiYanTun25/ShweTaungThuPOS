<?php

namespace App\Traits;

use App\Models\Branch;
use App\Models\User;

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

    public function checkBranchHasRelatedData($branchId)
    {
        $user = User::where('branch_id', $branchId)->first();
        // issues
        // damage
        // reciesve
        // inventory
        if ($user) {
            return true;
        }
    }

    // public function getBranchUsers($branchId, $perPage = null)
    // {
    //     $usersQuery = User::where('branch_id', $branchId);

    //     // Check if $perPage is provided to determine whether to paginate or not
    //     if ($perPage) {
    //         $users = $usersQuery->paginate($perPage);
    //     } else {
    //         $users = $usersQuery->get();
    //     }

    //     // Iterate through each user and format the join_date
    //     foreach ($users as $user) {
    //         $user->join_date = formatToCustomDate($user->join_date);
    //     }

    //     return $users;
    // }
}
