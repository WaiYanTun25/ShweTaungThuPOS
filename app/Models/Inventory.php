<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        // static::addGlobalScope(new BranchScope());
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'branch_id', 'branch_id');
    }

    public function  branch() 
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }
}

// $user = $this;

//     return $this->hasMany(Inventory::class, 'branch_id', 'branch_id')
//         ->when($user->branch_id !== 0, function ($query) use ($user) {
//             return $query->where('branch_id', $user->branch_id);
//         });