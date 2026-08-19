<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        $available = array_keys((array) config('app.available_locales', []));

        if (! \in_array($locale, $available, true)) {
            $locale = (string) config('app.locale', 'en');
        }

        $sessionStarted = session()->isStarted();
        if ($sessionStarted) {
            session([SetLocale::SESSION_KEY => $locale]);
        }

        $user = $request->user();
        if ($user && method_exists($user, 'setLocale')) {
            try {
                if (($user->locale ?? null) !== $locale) {
                    $user->setLocale($locale);
                }
            } catch (\Throwable) {
                // ignore
            }
        }

        return redirect()
            ->back(fallback: route('filament.admin.pages.dashboard'))
            ->withCookie(cookie()->forever(SetLocale::COOKIE_KEY, $locale));
    }
}
