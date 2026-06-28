<?php

namespace App\Models\Scopes;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

class TenancyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! $model->getTable() || ! Schema::hasColumn($model->getTable(), 'school_id')) {
            return;
        }

        $schoolId = app(TenantContext::class)->getSchoolId();
        if ($schoolId === null) {
            return;
        }

        $builder->where($model->getTable() . '.school_id', $schoolId);
    }
}
