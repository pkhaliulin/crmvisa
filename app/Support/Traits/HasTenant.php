<?php

namespace App\Support\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasTenant
{
    public static function bootHasTenant(): void
    {
        static::creating(function ($model) {
            if (empty($model->agency_id) && auth()->check()) {
                $model->agency_id = auth()->user()->agency_id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->agency_id) {
                $builder->where(
                    $builder->getModel()->getTable() . '.agency_id',
                    auth()->user()->agency_id
                );
            }
        });
    }

    public function scopeWithoutTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
