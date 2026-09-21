---
title: Installation
nav_order: 2
description: "Installing darvis/laravel-google-translate, creating a Google Cloud Translation API key and setting the source and target locales."
---

# Installation

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- A Google Cloud project with billing enabled

## Install the package

```bash
composer require darvis/laravel-google-translate
```

The service provider is discovered automatically.

## Create an API key

1. Open the [Google Cloud console](https://console.cloud.google.com/) and pick or create a project.
2. Enable the **Cloud Translation API** under *APIs & Services → Library*.
3. Create a key under *APIs & Services → Credentials → Create credentials → API key*.
4. Restrict the key to the Cloud Translation API, and to the IP addresses of your servers when they are fixed.

## Configure

```env
GOOGLE_TRANSLATE_API_KEY=your-api-key
GOOGLE_TRANSLATE_SOURCE_LOCALE=nl
GOOGLE_TRANSLATE_TARGET_LOCALES=en,de,fr
```

| Variable | Config key | Default | Meaning |
| --- | --- | --- | --- |
| `GOOGLE_TRANSLATE_API_KEY` | `api_key` | none | Without a key nothing is translated and every method returns `null` or an empty array. |
| `GOOGLE_TRANSLATE_SOURCE_LOCALE` | `source_locale` | `nl` | The language your original content is written in. |
| `GOOGLE_TRANSLATE_TARGET_LOCALES` | `target_locales` | `en` | Comma separated. The package does not loop over them for you; `getTargetLocales()` returns the list so your code can. |

Publish the config file when you want to set the values in PHP:

```bash
php artisan vendor:publish --tag=google-translate-config
```

Run `php artisan config:clear` after changing `.env` on a server that caches its config.

## Check that it works

```php
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;

$translator = app(GoogleTranslateService::class);

$translator->isAvailable();              // true when an API key is set
$translator->translate('Hallo', 'en');   // "Hello"
```

`null` instead of a translation means the call failed; the reason is in `storage/logs/laravel.log`. See [Troubleshooting](troubleshooting.md).

## Laravel Boost

The package ships a [Laravel Boost](https://laravel.com/docs/boost) guideline and a skill in `resources/boost/`. Run `php artisan boost:install`, or `php artisan boost:update --discover` in a project that already uses Boost, and your AI assistant knows the API, the table layout and the pitfalls.

## Next steps

- [Quick start](quickstart.md)
- [Concepts](concepts.md)
