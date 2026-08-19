<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SESSION_KEY = 'app.locale';

    public const COOKIE_KEY = 'app_locale';

    protected bool $shouldSendCookie = false;

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        if ($locale && $this->isAvailable($locale)) {
            App::setLocale($locale);

            if (session()->isStarted()) {
                session([self::SESSION_KEY => $locale]);
            }
        }

        $response = $next($request);

        if (
            $locale
            && $this->isAvailable($locale)
            && (
                $this->shouldSendCookie
                || ((string) $request->cookies->get(self::COOKIE_KEY, '') !== $locale)
            )
        ) {
            $response->headers->setCookie(
                cookie()->forever(self::COOKIE_KEY, $locale)
            );
        }

        return $response;
    }

    protected function resolveLocale(Request $request): ?string
    {
        if ($request->filled('lang')) {
            $candidate = (string) $request->input('lang');
            if ($this->isAvailable($candidate)) {
                $this->persistToSession($candidate);
                $this->persistToUser($candidate);
                $this->shouldSendCookie = true;

                return $candidate;
            }
        }

        if (session()->isStarted() && $request->session()->has(self::SESSION_KEY)) {
            $candidate = (string) $request->session()->get(self::SESSION_KEY);
            if ($this->isAvailable($candidate)) {
                return $candidate;
            }
        }

        if ($request->cookies->has(self::COOKIE_KEY)) {
            $candidate = (string) $request->cookies->get(self::COOKIE_KEY);
            if ($this->isAvailable($candidate)) {
                $this->persistToSession($candidate);

                return $candidate;
            }
        }

        /** @var User|null $user */
        $user = Auth::user();
        if ($user && method_exists($user, 'preferredLocale')) {
            $preferred = $user->preferredLocale();
            if ($preferred && $this->isAvailable($preferred)) {
                $this->persistToSession($preferred);
                $this->shouldSendCookie = true;

                return $preferred;
            }
        }

        $browser = $request->getPreferredLanguage(array_keys($this->availableLocales()));
        if ($browser && $this->isAvailable($browser)) {
            $this->persistToSession($browser);
            $this->shouldSendCookie = true;

            return $browser;
        }

        $default = config('app.locale');
        if ($default && $this->isAvailable((string) $default)) {
            return (string) $default;
        }

        return null;
    }

    protected function persistToSession(string $locale): void
    {
        if (session()->isStarted()) {
            session([self::SESSION_KEY => $locale]);
        }
    }

    protected function persistToUser(string $locale): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'setLocale')) {
            return;
        }

        try {
            if (($user->locale ?? null) !== $locale) {
                $user->setLocale($locale);
            }
        } catch (\Throwable) {
            // ignore - locale is still tracked in session/cookie
        }
    }

    protected function isAvailable(string $locale): bool
    {
        return \array_key_exists($locale, $this->availableLocales());
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function availableLocales(): array
    {
        return (array) config('app.available_locales', []);
    }
}
