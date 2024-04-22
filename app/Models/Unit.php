<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;
    public $timestamps = false;

    public function unitDetails()
    {
        return $this->hasMany(ItemUnitDetail::class, 'unit_id');
    }
}
