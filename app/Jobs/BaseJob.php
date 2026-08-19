<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Middleware\CorrelationIdAware;
use App\Support\CorrelationId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class BaseJob implements ShouldQueue
{
    use Queueable;

    public ?string $correlationId = null;

    public function __construct()
    {
        $this->correlationId = CorrelationId::getCurrentOrDefault();
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new CorrelationIdAware,
        ];
    }
}
