<?php

declare(strict_types=1);

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureJsonAcceptHeader
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! str_contains((string) $request->header('Accept', ''), '/json')
            && ! str_contains((string) $request->header('Accept', ''), '+json')) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
