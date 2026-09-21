# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository. The conventions shared by every darvis package (language, releases, CI, docs site, Boost guidelines, public API policy) are in [../CLAUDE.md](../CLAUDE.md); this file only holds what is specific to this package.

## Package overview

`darvis/laravel-google-translate` is a Laravel package (PHP 8.2+, Laravel 11/12/13) that translates text, HTML and Eloquent models with the Google Cloud Translation API (v2, API key).

- Namespace: `Darvis\LaravelGoogleTranslate\` → `src/`
- Service provider auto-registered via `extra.laravel.providers` in [composer.json](composer.json)
- Config key: `google-translate`

## Architecture

- [GoogleTranslateConfig](src/Support/GoogleTranslateConfig.php) is the only place that reads the package config; don't call `config('google-translate.…')` elsewhere. `tests/Feature/ConfigAccessorTest.php` walks `src/`, `resources/` and `routes/` for it.
- [GoogleTranslateService](src/GoogleTranslateService.php) is a singleton that reads the API key in its constructor. `request()` is the one method that talks to Google; the public methods only guard the input and pick the log prefix.
- [HasGoogleTranslate](src/Traits/HasGoogleTranslate.php) stores a translation as a row in the same table: the source row has no `pid`, a translation has the source id in `pid`. `translationGroup()` is the one query for "the source row and its translations"; a lookup on `pid` alone never finds the source from a translation, which was the 1.0 bug in `fillMissingTranslations()`.
- The trait is only used by host app models, so PHPStan would skip it as unused. `tests/Fixtures/Article.php` uses it and is in the PHPStan paths for that reason; don't remove it from `phpstan.neon.dist`.

## Conventions

- Nothing throws. A failed call is logged and returns `null`, or `[]` for a batch. Host apps rely on that: a translation that fails must never break the page that asked for it. Don't introduce an exception within 1.x.
- The API key goes in the `X-goog-api-key` header, never in the URL. The package logs the message of an HTTP client exception, and that message quotes the URL. `tests/Feature/GoogleTranslateServiceTest.php` pins both.
- Keep the public API compatible within 1.x: the list is in `CONTRIBUTING.md`. It includes the wording of the log lines (`Google Translate failed: …`), because host apps alert on them, and the protected `$apiKey` and `$baseUrl`, because the service is not final.
- Don't add native parameter or return types to the trait's scope methods; host app models may override them. Types go in the docblock.
- Never call Google from a test. `TestCase` calls `Http::preventStrayRequests()`; use `fakeGoogle()` or your own `Http::fake()`.
