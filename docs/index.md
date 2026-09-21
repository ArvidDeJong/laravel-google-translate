---
title: "Home"
nav_order: 1
description: "Translate text, HTML and Eloquent models in Laravel with the Google Cloud Translation API: one service, one trait, one row per locale."
permalink: /
---

# Laravel Google Translate

`darvis/laravel-google-translate` sends text, HTML or the fields of an Eloquent model to the Google Cloud Translation API and gives you the translation back. For a model it stores the translation as a new row in the same table, linked to the original through a `pid` column.

## Who it is for

You have a Laravel application with content in one language, and you want a machine translation of it in other languages: pages in a CMS, products, news items, or loose strings.

- **A service** for a string, a piece of HTML, a batch of strings or an array of fields.
- **A trait** (a piece of code you add to a model with `use`) for Eloquent models that keep one row per locale.
- **A failed API call never throws.** It is logged and returns `null`, so you check the return value instead of catching an exception.

## What it does not do

- It does not translate the language files in `lang/`, and it does not replace `__()`.
- It does not cache: the same loose string sent twice is translated twice.
- It does not queue, retry or loop over your target locales. Your code decides when and to which locale it translates.
- It does not add a translations table or JSON columns; a translation is a full row of its own.
- It does not detect the source language. It always sends a source locale, from the config or from your call.
- It uses the Cloud Translation API v2 ("Basic") with an API key, not v3 with a service account.

## Requirements

- PHP 8.2 or higher
- Laravel 11, 12 or 13 (Laravel 13 itself needs PHP 8.3)
- A Google Cloud Translation API key, from a Google Cloud project with billing enabled

## Install

```bash
composer require darvis/laravel-google-translate
```

```env
GOOGLE_TRANSLATE_API_KEY=your-api-key
GOOGLE_TRANSLATE_SOURCE_LOCALE=nl
```

[Installation](installation.md) explains where the key comes from and how to check that it works.

## In short

```php
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;

$translator = app(GoogleTranslateService::class);

$translator->translate('Hallo wereld', 'en');            // for example "Hello world"
$translator->translateHtml('<p>Hallo</p>', 'en');        // for example "<p>Hello</p>"
$translator->translateBatch(['Hallo', 'Wereld'], 'en');  // for example ["Hello", "World"]

$english = $page->createTranslation('en');               // a new row, or null
```

## Pages

- [Installation](installation.md): the package, the API key, the settings, and a check that it works
- [Quick start](quickstart.md): a translatable `Page` model from migration to translated row, then the service methods
- [Concepts](concepts.md): the table layout, what comes back when a call fails, the API key and the costs
- [API reference](api-reference.md): every public method with its arguments and return value
- [CMS integration](cms-integration.md): a Livewire screen that lists missing translations with a translate button
- [Testing](testing.md): test your own code without calling Google
- [Troubleshooting](troubleshooting.md): `null` comes back, a row is missing, and the log lines that tell you why
- [FAQ](faq.md): short answers to the questions people ask first

## Links

- [Source on GitHub](https://github.com/ArvidDeJong/laravel-google-translate)
- [Packagist](https://packagist.org/packages/darvis/laravel-google-translate)
- [Changelog](https://github.com/ArvidDeJong/laravel-google-translate/blob/main/CHANGELOG.md)
- [Report an issue](https://github.com/ArvidDeJong/laravel-google-translate/issues)
