---
title: "Installation"
nav_order: 2
description: "Install darvis/laravel-google-translate step by step: composer require, create a Google Cloud Translation API key, set the locales and check that it works."
---

# Installation

## Requirements

- PHP 8.2 or higher
- Laravel 11, 12 or 13 (Laravel 13 itself needs PHP 8.3)
- A Google Cloud Translation API key, from a Google Cloud project with billing enabled

The package needs nothing else: no migration of its own, no queue, no Livewire.

## 1. Install the package

```bash
composer require darvis/laravel-google-translate
```

Laravel discovers the service provider by itself; there is nothing to register.

## 2. Create an API key at Google

The package talks to the Cloud Translation API v2 ("Basic") with an API key. You create that key in the Google Cloud console:

1. Open the [Google Cloud console](https://console.cloud.google.com/) and pick or create a project.
2. Make sure billing is enabled for the project. Google requires it for Cloud Translation; see [Google's setup guide](https://cloud.google.com/translate/docs/setup).
3. Enable the **Cloud Translation API** for that project.
4. Go to the **Credentials** page, click **Create credentials** and choose **API key**.
5. Restrict the key. Under *API restrictions*, allow only the Cloud Translation API. Under *Application restrictions*, allow only the IP addresses of your servers when those are fixed. Google explains both in [Manage API keys](https://cloud.google.com/docs/authentication/api-keys).

The calls come from your server, not from a browser, and the package sends no `Referer` header. So when you add an application restriction, choose IP addresses, not websites (HTTP referrers).

## 3. Put the key and the locales in `.env`

```env
GOOGLE_TRANSLATE_API_KEY=your-api-key
GOOGLE_TRANSLATE_SOURCE_LOCALE=nl
GOOGLE_TRANSLATE_TARGET_LOCALES=en,de,fr
```

| Variable | Config key | Default | Meaning |
| --- | --- | --- | --- |
| `GOOGLE_TRANSLATE_API_KEY` | `google-translate.api_key` | none | Without a key nothing is sent to Google, and every method returns `null` or an empty array. |
| `GOOGLE_TRANSLATE_SOURCE_LOCALE` | `google-translate.source_locale` | `nl` | The language your original content is written in. Set it to `en` when your content is English. |
| `GOOGLE_TRANSLATE_TARGET_LOCALES` | `google-translate.target_locales` | `en` | Comma separated. The package does not loop over them for you; `getTargetLocales()` returns the list so your code can. |

Use the language codes Google knows, such as `en`, `de` or `fr`, not `en_US`. Google publishes the [list of supported languages](https://cloud.google.com/translate/docs/languages).

On a server that caches its config, run `php artisan config:clear` (or `php artisan config:cache` again) after you change `.env`. Until then the old values stay in use.

## 4. Optional: publish the config file

You only need this when you want to set the values in PHP instead of in `.env`:

```bash
php artisan vendor:publish --tag=google-translate-config
```

This writes `config/google-translate.php` with the three keys from the table above.

## Check that it works

Open `php artisan tinker` and first run the check that costs nothing:

```php
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;

$translator = app(GoogleTranslateService::class);

$translator->isAvailable();
// true
```

`isAvailable()` only looks at the config. It sends nothing to Google, so it is free, and it tells you whether the package can see your key.

- `true`: the key is set. Go on to the real call.
- `false`: `GOOGLE_TRANSLATE_API_KEY` is missing or empty, or the config is cached. See [isAvailable() returns false](troubleshooting.md#isavailable-returns-false).

Then make one real call. This sends five characters to Google:

```php
$translator->translate('Hallo', 'en', 'nl');
// a string such as "Hello"
```

- A translated string: everything works.
- `null`: Google refused the call or could not be reached. The reason is in your log (`storage/logs/laravel.log` by default) on a line that starts with `Google Translate failed: `. See [Troubleshooting](troubleshooting.md).

`isAvailable()` returning `true` does not prove that the key is valid; only a real call does.

## Laravel Boost

The package ships a [Laravel Boost](https://laravel.com/docs/boost) guideline and a skill in `resources/boost/`. Run `php artisan boost:install`, or `php artisan boost:update --discover` in a project that already uses Boost, and the AI assistant in your project knows the API, the table layout and the pitfalls.

## Next steps

- [Quick start](quickstart.md): make a model translatable
- [Testing](testing.md): keep your test suite away from Google
