<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AlumnoPanelProvider;
use App\Providers\Filament\ApoderadoPanelProvider;
use App\Providers\Filament\DocentePanelProvider;

return [
    EventServiceProvider::class,
    AppServiceProvider::class,
    AdminPanelProvider::class,
    DocentePanelProvider::class,
    AlumnoPanelProvider::class,
    ApoderadoPanelProvider::class,
];
