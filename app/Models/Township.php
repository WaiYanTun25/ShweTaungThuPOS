<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Township extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['city_id', 'name'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // for transaction_date
            if (!$model->created_date) {
                $model->created_date = now();
            }
        });
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'township', 'id');
    }

    public function city()
    {
        return $this->hasOne(City::class, 'id', 'city_id');
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'township', 'id');
    }
}
