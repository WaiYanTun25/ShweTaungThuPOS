<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class BranchScope implements Scope
{
    protected $columnName;

    public function __construct($columnName = 'branch_id')
    {
        $this->columnName = $columnName;
    }
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user && $user->branch_id !== 0) {
            $builder->where($this->columnName, $user->branch_id);
        }
    }
}
