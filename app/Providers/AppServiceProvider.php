<?php

namespace App\Providers;

use App\Models\School;
use App\Observers\SchoolObserver;
use App\Services\ExtendedTranslationLoader;
use App\Tenancy\TenantContext;
use Illuminate\Translation\Translator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, fn () => new TenantContext());

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
        $resourceLang = is_callable([$app, 'langPath']) ? (string) $app->langPath() : resource_path('lang');
        $langPaths = [];
        if (is_string($resourceLang)) {
            $langPaths[] = dirname($resourceLang);
            $langPaths[] = $resourceLang;
        }
        $paths = array_values(array_unique(array_merge($defaultPaths, $langPaths)));

        return new ExtendedTranslationLoader(
            $app['files'],
            $paths,
            storage_path('app/lang')
        );
    }
}
