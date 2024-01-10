<?php

namespace App\Traits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

trait LogUserActionsTrait
{
    protected static function bootLogUserActions()
    {
        static::creating(function (Model $model) {
            $user = Auth::user();
            info($user);

            if ($user) {
                activity()
                    ->performedOn($model)
                    ->causedBy($user)
                    ->log('created');
            }
        });

        static::updating(function (Model $model) {
            $user = Auth::user();

            if ($user) {
                activity()
                    ->performedOn($model)
                    ->causedBy($user)
                    ->log('updated');
            }
        });

        static::deleting(function (Model $model) {
            $user = Auth::user();

            if ($user) {
                activity()
                    ->performedOn($model)
                    ->causedBy($user)
                    ->log('deleted');
            }
        });
    }
}
