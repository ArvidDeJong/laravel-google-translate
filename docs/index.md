---
title: Home
nav_order: 1
description: "Translate text, HTML and Eloquent models in Laravel with the Google Cloud Translation API: one service, one trait, one row per locale."
permalink: /
---

# Laravel Google Translate

`darvis/laravel-google-translate` translates text, HTML and Eloquent models with the Google Cloud Translation API.

- **A service** for a string, a piece of HTML, a batch of strings or an array of fields.
- **A trait** for Eloquent models that keep one row per locale, linked through a `pid` column.
- **No exceptions to catch**: a failed call is logged and returns `null`, so a translation that fails never breaks the page that asked for it.

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- A Google Cloud Translation API key

## Install

```bash
composer require darvis/laravel-google-translate
```

```env
GOOGLE_TRANSLATE_API_KEY=your-api-key
GOOGLE_TRANSLATE_SOURCE_LOCALE=nl
GOOGLE_TRANSLATE_TARGET_LOCALES=en,de,fr
```

## In short

```php
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;

$translator = app(GoogleTranslateService::class);

$translator->translate('Hallo wereld', 'en');            // "Hello world"
$translator->translateHtml('<p>Hallo</p>', 'en');        // "<p>Hello</p>"
$translator->translateBatch(['Hallo', 'Wereld'], 'en');  // ["Hello", "World"]

$english = $page->createTranslation('en');               // a new row, or null
```

## Pages

- [Installation](installation.md): the package, the API key and the config file
- [Quick start](quickstart.md): the service and the trait in a few examples
- [Concepts](concepts.md): the table layout, what happens on a failure, what it costs
- [API reference](api-reference.md): every public method
- [CMS integration](cms-integration.md): an overview of missing translations with a translate button
- [Troubleshooting](troubleshooting.md): nothing comes back, and other common problems
- [FAQ](faq.md)

## Links

- [Source on GitHub](https://github.com/ArvidDeJong/laravel-google-translate)
- [Packagist](https://packagist.org/packages/darvis/laravel-google-translate)
- [Changelog](https://github.com/ArvidDeJong/laravel-google-translate/blob/main/CHANGELOG.md)
- [Report an issue](https://github.com/ArvidDeJong/laravel-google-translate/issues)
