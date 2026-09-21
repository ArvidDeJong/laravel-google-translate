<?php

namespace Darvis\LaravelGoogleTranslate\Support;

/**
 * The one place that reads the package config. Callers ask this class, so a default is written
 * once and a caller cannot quietly disagree with config/google-translate.php about what it is.
 */
final class GoogleTranslateConfig
{
    /**
     * The Google Cloud Translation API key, or null when it is not set or empty.
     */
    public static function apiKey(): ?string
    {
        $key = config('google-translate.api_key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    /**
     * Locale the original content is written in.
     */
    public static function sourceLocale(): string
    {
        return (string) config('google-translate.source_locale', 'nl');
    }

    /**
     * Locales to translate to, without empty entries.
     *
     * @return array<int, string>
     */
    public static function targetLocales(): array
    {
        $locales = config('google-translate.target_locales', ['en']);

        if (is_string($locales)) {
            $locales = explode(',', $locales);
        }

        if (! is_array($locales)) {
            return ['en'];
        }

        return array_values(array_filter(
            array_map(fn ($locale): string => trim((string) $locale), $locales),
            fn (string $locale): bool => $locale !== '',
        ));
    }
}
