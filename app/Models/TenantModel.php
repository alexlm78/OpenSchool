<?php

namespace App\Models;

use App\Models\Scopes\TenancyScope;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

abstract class TenantModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenancyScope());

        static::creating(function (Model $model): void {
            if (! $model->getTable() || ! array_key_exists('school_id', $model->getAttributes())) {
                return;
            }

            if ($model->getAttribute('school_id') !== null) {
                return;
            }

            $schoolId = app(TenantContext::class)->getSchoolId();
            if ($schoolId === null) {
                return;
            }

            $model->setAttribute('school_id', $schoolId);
        });
    }
}
