---
title: "Concepts"
nav_order: 4
description: "How darvis/laravel-google-translate stores translations as rows linked by pid, what happens when a call fails, and what a translation costs."
---

# Concepts

## One row per locale

The trait does not add a translations table or JSON columns. A translation is a row in the same table:

| id | pid | locale | title |
| --- | --- | --- | --- |
| 1 | `null` | nl | Over ons |
| 2 | 1 | en | About us |
| 3 | 1 | de | Über uns |

- The **source row** has no `pid`.
- A **translation** has the id of the source row in `pid`.
- Every row has its own slug, status and relations, so a translation is a full page of its own.
- The package adds no foreign key and no unique index; the migration is yours. The [quick start](quickstart.md) has an example.

`hasTranslation()`, `getTranslation()` and `getAllTranslations()` work from the source row and from any translation of it.

## Which fields are translated

```php
protected $translatableFields = ['title', 'content', 'excerpt'];

protected $htmlFields = ['content'];
```

Without `$translatableFields` the trait uses `title`, `content`, `description` and `excerpt`; without `$htmlFields` it uses `content` and `description`. Override `getTranslatableFields()` or `getHtmlFields()` when the list depends on the model.

A field in `$htmlFields` is sent with `format` set to `html`, so the tags survive. Everything else is sent as plain text.

## A failed API call never throws

The service catches every exception around the HTTP call, writes one line to the log with `Log::error()` and returns `null`, or `[]` for a batch.

| Situation | `translate()`, `translateHtml()` | `translateBatch()` | In the log |
| --- | --- | --- | --- |
| No API key | `null` | `[]` | nothing |
| Empty input | `null` | `[]` | nothing |
| HTTP 4xx or 5xx | `null` | `[]` | `Google Translate failed: ` and the response body |
| Connection error or timeout | `null` | `[]` | `Google Translate failed: ` and the message of the exception |
| HTTP 2xx without a translation in the body | `null` | `[]` | nothing |

The prefix is `Google Translate HTML failed: ` for `translateHtml()` and `Google Translate batch failed: ` for a batch.

The package sets no timeout and does not retry. Laravel's HTTP client default applies, which is 30 seconds.

What can still throw is everything around the API call: `createTranslation()` inserts a row, so a missing column value or a model without `$fillable` raises the usual Eloquent exception. See [Troubleshooting](troubleshooting.md).

So check the return value. `createTranslation()` returns `null` when there is no key or no field could be translated. When some fields fail, the translation is created with the fields that did succeed; `fillMissingTranslations()` fills the rest later.

## The API key

The key is sent in the `X-goog-api-key` header, only to `https://translation.googleapis.com/language/translate/v2`. It is never part of the URL, so an error message that quotes the URL does not leak it into the log.

The service reads the key when it is constructed. It is a singleton, so a key you change at runtime is only picked up after `app()->forgetInstance(GoogleTranslateService::class)`.

## Costs

The package adds no costs of its own. Google charges for the characters you send, for every target locale again; the current rates are on [Google's pricing page](https://cloud.google.com/translate/pricing). The trait avoids sending the same text twice:

- `createTranslation()` returns the existing row when the locale already exists.
- `fillMissingTranslations()` skips the fields that already have a value.
- `translateBatch()` sends many strings in one request; that saves round trips, not characters.

There is no cache. When you translate the same loose strings more than once, cache the result yourself.

## Translated HTML is external input

The source may be your own content, but the result comes from an external service. Sanitise translated HTML the way you sanitise the source before you render it unescaped.

## Reading the settings

`Darvis\LaravelGoogleTranslate\Support\GoogleTranslateConfig` is the one place that reads the config: `apiKey()`, `sourceLocale()` and `targetLocales()`. The service exposes the last two as `getSourceLocale()` and `getTargetLocales()`.
