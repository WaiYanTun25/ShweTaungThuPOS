<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitConvertDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['unit_convert_id', 'from_unit_id', 'from_qty', 'to_unit_id', 'to_qty'];

    public function fromUnit()
    {
        return $this->belongsTo(Unit::class, 'from_unit_id' , 'id');
    }

    public function toUnit()
    {
        return $this->belongsTo(Unit::class, 'to_unit_id', 'id');
    }
}
