---
title: "API reference"
nav_order: 5
description: "Every public method of GoogleTranslateService, the HasGoogleTranslate trait and GoogleTranslateConfig, with arguments and return values."
---

# API reference

## GoogleTranslateService

`Darvis\LaravelGoogleTranslate\GoogleTranslateService`, bound in the container as a singleton (one shared instance) and aliased as `google-translate`. Resolve it with `app(GoogleTranslateService::class)` or type hint it in a constructor or a job's `handle()` method. There is no facade.

A failed API call never throws; see [Concepts](concepts.md) for what a failure returns.

| Method | Returns | Notes |
| --- | --- | --- |
| `isAvailable()` | `bool` | `true` when an API key is set. It does not call Google, so a wrong key is still "available". |
| `getSourceLocale()` | `string` | `source_locale` from the config. |
| `getTargetLocales()` | `array<int, string>` | `target_locales` from the config, trimmed and without empty entries. |
| `translate(string $text, string $targetLocale, ?string $sourceLocale = null)` | `?string` | Plain text. `null` for empty input, without a key, on failure, and when the response holds no translation. |
| `translateHtml(string $html, string $targetLocale, ?string $sourceLocale = null)` | `?string` | Sent with `format` set to `html`, so the tags are kept. `null` in the same cases as `translate()`. |
| `translateBatch(array $texts, string $targetLocale, ?string $sourceLocale = null)` | `array<int, string>` | One request, same order as the input. The keys of the input are dropped. `[]` for empty input, without a key and on failure. |
| `translateFields(array $fields, string $targetLocale, ?string $sourceLocale = null, array $htmlFields = [])` | `array<string, string>` | One request per field; the fields named in `$htmlFields` go through `translateHtml()`. Empty and failed fields are left out. |

A `null` source locale means the one from the config.

## HasGoogleTranslate

`Darvis\LaravelGoogleTranslate\Traits\HasGoogleTranslate`, for a model with a `locale` and a nullable `pid` column.

| Method | Returns | Notes |
| --- | --- | --- |
| `getTranslatableFields()` | `array<int, string>` | `$translatableFields`, or `title`, `content`, `description`, `excerpt`. |
| `getHtmlFields()` | `array<int, string>` | `$htmlFields`, or `content`, `description`. |
| `hasTranslation(string $locale)` | `bool` | `true` for the model's own locale. |
| `getTranslation(string $locale)` | `?static` | The model itself for its own locale. |
| `getAllTranslations()` | `Collection` | The source row and all its translations, from any row of the group. |
| `createTranslation(string $targetLocale, array $additionalAttributes = [])` | `?static` | The existing row when there is one. `null` without an API key, when the source row has no translatable content, or when every field failed. Translated fields win over `$additionalAttributes`; `pid` and `locale` are always set by the trait. |
| `fillMissingTranslations(?string $sourceLocale = null)` | `array{translated, errors}` | Fills the empty fields of this row from the row in the source locale, and saves when something changed. |
| `getMissingTranslations(string $targetLocale, ?string $sourceLocale = null)` (static) | `Collection` | Rows without a `pid` in the source locale (from the config when `null`) that have no row in the target locale. Runs one query per source row. |
| `sourceItems()` (scope) | `Builder` | Rows without a `pid`. |
| `localized(?string $locale = null)` (scope) | `Builder` | Rows in the given locale, or the current app locale. |

The `errors` list of `fillMissingTranslations()` holds one of:

- `Google Translate API not available`: no API key.
- `Source translation not found`: there is no row in the source locale.
- `Translation of '<field>' failed`: the call for that field failed; the reason is in the log.

## GoogleTranslateConfig

`Darvis\LaravelGoogleTranslate\Support\GoogleTranslateConfig`, the one place that reads the config.

| Method | Returns |
| --- | --- |
| `apiKey()` | `?string`, `null` when the key is missing or empty |
| `sourceLocale()` | `string` |
| `targetLocales()` | `array<int, string>` |

## Extending the service

The service is not final. `$apiKey` and `$baseUrl` are protected, and `request()` is the one method that talks to Google, so a subclass can point at another endpoint or add a cache. Bind your subclass in a service provider:

`app/Providers/AppServiceProvider.php`, in `register()`:

```php
use App\Services\CachedTranslateService;
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;

$this->app->singleton(GoogleTranslateService::class, fn () => new CachedTranslateService);
```

`CachedTranslateService` is your own class that extends `GoogleTranslateService`; the package does not ship it. The trait and the `google-translate` alias resolve the same binding, so they use your subclass too.
