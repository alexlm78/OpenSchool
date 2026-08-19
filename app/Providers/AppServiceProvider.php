<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\School;
use App\Models\Submission;
use App\Models\User;
use App\Observers\SchoolObserver;
use App\Policies\EnrollmentPolicy;
use App\Policies\EvaluationPolicy;
use App\Policies\GradePolicy;
use App\Policies\NotificationPolicy;
use App\Policies\SubmissionPolicy;
use App\Services\ExtendedTranslationLoader;
use App\Tenancy\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, fn () => new TenantContext);

        $this->app->extend('translation.loader', function ($defaultLoader, $app) {
            return $this->createExtendedLoader($app);
        });

        $this->app->extend('translator', function ($defaultTranslator, $app) {
            $loader = $app['translation.loader'];
            $locale = (string) $app['config']['app.locale'];
            $fallback = (string) $app['config']['app.fallback_locale'];

            if ($defaultTranslator instanceof Translator) {
                $locale = $defaultTranslator->getLocale();
                $fallback = $defaultTranslator->getFallback();
            }

            $translator = new Translator($loader, $locale);
            $translator->setFallback($fallback);

            return $translator;
        });
    }

    public function boot(): void
    {
        School::observe(SchoolObserver::class);

        $this->registerPolicies();
        $this->configureRateLimiters();

        // Ensure that if translation services were already resolved by the time
        // register() ran (some deferred providers materialize late), we forcibly
        // swap the in-container instances to the ExtendedTranslationLoader.
        $loader = $this->createExtendedLoader($this->app);

        if ($this->app->resolved('translation.loader')) {
            $this->app->instance('translation.loader', $loader);
        }

        if ($this->app->resolved('translator')) {
            $previous = $this->app['translator'];
            $locale = $previous instanceof Translator ? $previous->getLocale() : (string) config('app.locale');
            $fallback = $previous instanceof Translator ? $previous->getFallbackLocale() : (string) config('app.fallback_locale');

            $translator = new Translator($loader, $locale);
            $translator->setFallback($fallback);
            $this->app->instance('translator', $translator);
        }
    }

    protected function createExtendedLoader($app): ExtendedTranslationLoader
    {
        $defaultPaths = (array) $app['config']['view.paths'];
        $resourceLang = \is_callable([$app, 'langPath']) ? (string) $app->langPath() : resource_path('lang');
        $langPaths = [];
        if (\is_string($resourceLang)) {
            $langPaths[] = \dirname($resourceLang);
            $langPaths[] = $resourceLang;
        }
        $paths = array_values(array_unique(array_merge($defaultPaths, $langPaths)));

        return new ExtendedTranslationLoader(
            $app['files'],
            $paths,
            storage_path('app/lang')
        );
    }

    private function registerPolicies(): void
    {
        Gate::policy(Enrollment::class, EnrollmentPolicy::class);
        Gate::policy(Evaluation::class, EvaluationPolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);
        Gate::policy(Grade::class, GradePolicy::class);
        Gate::policy(DatabaseNotification::class, NotificationPolicy::class);
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('api', static function (Request $request): Limit {
            /** @var User|null $user */
            $user = $request->user();

            return Limit::perMinute(120)->by($user?->getKey() !== null
                ? 'u-'.$user->getKey()
                : 'ip-'.$request->ip(),
            );
        });

        RateLimiter::for('api.auth', static function (Request $request): Limit {
            return Limit::perMinute(10)
                ->by('login-'.strtolower((string) $request->input('email', '')).'|ip-'.$request->ip())
                ->response(static fn () => response()->json([
                    'message' => 'Demasiados intentos de inicio de sesión. Inténtalo más tarde.',
                ], 429));
        });
    }
}
