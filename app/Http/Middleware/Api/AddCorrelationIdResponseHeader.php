<?php

declare(strict_types=1);

namespace App\Http\Middleware\Api;

use App\Support\CorrelationId;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class AddCorrelationIdResponseHeader
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $incomingHeader = $request->header(CorrelationId::HEADER);
        $correlation = CorrelationId::fromHeaderOrGenerate(
            \is_string($incomingHeader) ? $incomingHeader : null,
        );

        CorrelationId::setCurrent($correlation);

        Log::shareContext($correlation->asContext());

        $response = $next($request);

        if (! $response->headers->has(CorrelationId::HEADER)) {
            $response->headers->set(CorrelationId::HEADER, $correlation->toString());
        }

        return $response;
    }
}
