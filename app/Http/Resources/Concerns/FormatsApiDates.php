<?php

declare(strict_types=1);

namespace App\Http\Resources\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

trait FormatsApiDates
{
    private function toIsoString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        try {
            return Carbon::parse($value)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }
}
