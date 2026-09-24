# Laravel Google Translate

[![Latest version](https://img.shields.io/packagist/v/darvis/laravel-google-translate.svg)](https://packagist.org/packages/darvis/laravel-google-translate)
[![Tests](https://github.com/ArvidDeJong/laravel-google-translate/actions/workflows/tests.yml/badge.svg)](https://github.com/ArvidDeJong/laravel-google-translate/actions/workflows/tests.yml)
[![PHP version](https://img.shields.io/packagist/dependency-v/darvis/laravel-google-translate/php.svg)](https://packagist.org/packages/darvis/laravel-google-translate)
[![License](https://img.shields.io/packagist/l/darvis/laravel-google-translate.svg)](LICENSE)

Translate text, HTML and Eloquent models in Laravel with the Google Cloud Translation API (v2, with an API key). A translated model is stored as a new row in the same table, with `pid` pointing at the source row. The package does not translate your `lang/` files, and it does not cache or queue by itself.

## Features

- **Text, HTML and batches** - HTML keeps its tags, a batch is one request
- **Translatable models** - a trait for models with one row per locale, linked through a `pid` column
- **Nothing to catch** - a failed API call is logged and returns `null`, or `[]` for a batch; it never throws
- **No double costs for models** - an existing translation is returned, and only empty fields are filled
- **Testable** - every call goes through Laravel's HTTP client, so `Http::fake()` replaces Google
- **Safe with your key** - sent in a header, never in a URL that can end up in a log
- **Laravel Boost** - guideline and skill included, so an AI assistant in your app knows the API

## Requirements

- PHP 8.2 or higher
- Laravel 11, 12 or 13 (Laravel 13 itself needs PHP 8.3)
- A Google Cloud Translation API key, from a Google Cloud project with billing enabled

## Installation

```bash
composer require darvis/laravel-google-translate
```

```env
GOOGLE_TRANSLATE_API_KEY=your-api-key
GOOGLE_TRANSLATE_SOURCE_LOCALE=nl
GOOGLE_TRANSLATE_TARGET_LOCALES=en,de,fr
```

The [installation page](https://arviddejong.github.io/laravel-google-translate/installation.html) explains where the key comes from and how to check that it works.

## Quick start

```php
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;

$translator = app(GoogleTranslateService::class);

$translator->translate('Hallo wereld', 'en');            // for example "Hello world"
$translator->translateHtml('<p>Hallo</p>', 'en');        // for example "<p>Hello</p>"
$translator->translateBatch(['Hallo', 'Wereld'], 'en');  // for example ["Hello", "World"]
```

For a model, add a `locale` and a nullable `pid` column to the table and the trait to the model:

```php
use Darvis\LaravelGoogleTranslate\Traits\HasGoogleTranslate;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasGoogleTranslate;

    protected $fillable = ['pid', 'locale', 'title', 'content', 'slug'];

    protected $translatableFields = ['title', 'content'];

    protected $htmlFields = ['content'];
}

$english = $page->createTranslation('en', ['slug' => 'about-us']);   // a new row, or null
```

## Documentation

The full documentation lives on the [documentation site](https://arviddejong.github.io/laravel-google-translate/):

- [Installation](https://arviddejong.github.io/laravel-google-translate/installation.html): the package, the API key, the settings and a check that it works
- [Quick start](https://arviddejong.github.io/laravel-google-translate/quickstart.html): a translatable model from migration to translated row
- [Concepts](https://arviddejong.github.io/laravel-google-translate/concepts.html): the table layout, failed calls, the API key and the costs
- [API reference](https://arviddejong.github.io/laravel-google-translate/api-reference.html): every public method
- [CMS integration](https://arviddejong.github.io/laravel-google-translate/cms-integration.html): a Livewire screen with missing translations and a translate button
- [Testing](https://arviddejong.github.io/laravel-google-translate/testing.html): test your code without calling Google
- [Troubleshooting](https://arviddejong.github.io/laravel-google-translate/troubleshooting.html): `null` comes back, and the log lines that say why
- [FAQ](https://arviddejong.github.io/laravel-google-translate/faq.html): short answers

## Laravel Boost

The package ships a [Laravel Boost](https://laravel.com/docs/boost) guideline and a skill. Run `php artisan boost:install`, or `php artisan boost:update --discover` in a project that already uses Boost.

## Testing

The package's own suite never calls Google:

```bash
composer test      # Pest
composer lint      # Pint, check only; composer format fixes
composer analyse   # Larastan
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## Support the package

If darvis/laravel-google-translate saves you time, a star on [GitHub](https://github.com/ArvidDeJong/laravel-google-translate) or a favourite on [Packagist](https://packagist.org/packages/darvis/laravel-google-translate) helps other developers find it.

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md).

## Security

Please report a vulnerability privately, as described in [SECURITY](SECURITY.md), not in the issue tracker.

## License

The MIT License (MIT). See [LICENSE](LICENSE).
