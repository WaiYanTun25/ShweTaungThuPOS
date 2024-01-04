<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitConvertDetail extends Model
{
    use HasFactory;
    public $timestamps = false;

    public function fromUnit()
    {
        return $this->belongsTo(Unit::class, 'from_unit_id' , 'id');
    }

    public function toUnit()
    {
        return $this->belongsTo(Unit::class, 'to_unit_id', 'id');
    }
}
