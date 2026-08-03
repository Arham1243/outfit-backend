<?php

namespace App\Support;

use Illuminate\Http\Request;

final class LocaleResolver
{
    public static function resolveFromRequest(Request $request): string
    {
        $header = $request->header('Accept-Language');

        if (is_string($header) && $header !== '') {
            $resolved = self::resolveLocaleKey($header);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        return (string) config('locales.default', config('app.locale', 'en'));
    }

    public static function resolveLocaleKey(string $locale): ?string
    {
        $normalized = self::normalize($locale);

        if ($normalized === '') {
            return null;
        }

        if (self::isLoaded($normalized)) {
            return $normalized;
        }

        $base = strtolower(explode('-', $normalized)[0] ?? '');

        if ($base !== '' && self::isLoaded($base)) {
            return $base;
        }

        return null;
    }

    public static function isLoaded(string $locale): bool
    {
        return in_array($locale, self::loadedLocales(), true);
    }

    /**
     * @return list<string>
     */
    public static function loadedLocales(): array
    {
        return array_values(array_unique(array_filter(
            config('locales.loaded', ['en']),
            static fn (mixed $locale): bool => is_string($locale) && $locale !== ''
        )));
    }

    private static function normalize(string $locale): string
    {
        $locale = trim(explode(',', $locale)[0] ?? '');
        $locale = trim(explode(';', $locale)[0] ?? '');

        if ($locale === '') {
            return '';
        }

        $parts = explode('-', $locale);

        if (count($parts) === 1) {
            return strtolower($parts[0]);
        }

        return strtolower($parts[0]).'-'.strtoupper($parts[1]);
    }
}
