<?php

namespace App\Models;

use App\Models\Scopes\TenancyScope;
use Illuminate\Foundation\Auth\User as Authenticatable;

abstract class TenantAuthenticatable extends Authenticatable
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenancyScope());
    }
}
