<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use App\Support\CorrelationId;
use Closure;
use Illuminate\Support\Facades\Log;

final class CorrelationIdAware
{
    /**
     * @param  Closure(object): void  $next
     */
    public function handle(object $job, Closure $next): void
    {
        $storedCorrelationId = null;

        if (property_exists($job, 'correlationId') && \is_string($job->correlationId) && $job->correlationId !== '') {
            $storedCorrelationId = $job->correlationId;
        }

        $previous = CorrelationId::getCurrent();

        CorrelationId::setCurrent($storedCorrelationId ?? CorrelationId::generate());
        Log::shareContext([CorrelationId::CONTEXT_KEY => CorrelationId::getCurrentOrDefault()]);

        try {
            $next($job);
        } finally {
            CorrelationId::setCurrent($previous);
        }
    }
}
