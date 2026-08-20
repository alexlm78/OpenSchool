<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Apoderado\Pages\Notifications as ApoderadoNotificationsPage;
use App\Filament\Apoderado\Widgets\ApoderadoGpaStatWidget;
use App\Filament\Apoderado\Widgets\ApoderadoGradesWidget;
use App\Filament\Apoderado\Widgets\ApoderadoLinkedStudentsWidget;
use App\Filament\Apoderado\Widgets\ApoderadoNotificationsWidget;
use App\Filament\Apoderado\Widgets\ApoderadoPendingSubmissionsWidget;
use App\Filament\Apoderado\Widgets\ApoderadoUpcomingEvaluationsWidget;
use App\Http\Middleware\EnsureGuardianRole;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTenantFromAuth;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ApoderadoPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('apoderado')
            ->path('apoderado')
            ->login()
            ->colors([
                'primary' => Color::Purple,
            ])
            ->discoverResources(in: app_path('Filament/ApoderadoResources'), for: 'App\Filament\ApoderadoResources')
            ->discoverPages(in: app_path('Filament/Apoderado/Pages'), for: 'App\Filament\Apoderado\Pages')
            ->pages([
                Dashboard::class,
                ApoderadoNotificationsPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Apoderado/Widgets'), for: 'App\Filament\Apoderado\Widgets')
            ->widgets([
                AccountWidget::class,
                ApoderadoLinkedStudentsWidget::class,
                ApoderadoGpaStatWidget::class,
                ApoderadoPendingSubmissionsWidget::class,
                ApoderadoGradesWidget::class,
                ApoderadoUpcomingEvaluationsWidget::class,
                ApoderadoNotificationsWidget::class,
            ])
            ->renderHook(
                'panels::topbar.end',
                fn (): string => (string) view('components.language-switcher'),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                SetTenantFromAuth::class,
                EnsureGuardianRole::class,
            ]);
    }
}
