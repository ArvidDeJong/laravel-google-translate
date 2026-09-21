# Laravel Google Translate

[![Latest version](https://img.shields.io/packagist/v/darvis/laravel-google-translate.svg)](https://packagist.org/packages/darvis/laravel-google-translate)
[![Tests](https://github.com/ArvidDeJong/laravel-google-translate/actions/workflows/tests.yml/badge.svg)](https://github.com/ArvidDeJong/laravel-google-translate/actions/workflows/tests.yml)
[![PHP version](https://img.shields.io/packagist/dependency-v/darvis/laravel-google-translate/php.svg)](https://packagist.org/packages/darvis/laravel-google-translate)
[![License](https://img.shields.io/packagist/l/darvis/laravel-google-translate.svg)](LICENSE)

Translate text, HTML and Eloquent models in Laravel with the Google Cloud Translation API.

## Features

- **Text, HTML and batches** - HTML keeps its tags, a batch is one request
- **Translatable models** - a trait for models with one row per locale, linked through a `pid` column
- **Nothing to catch** - a failed call is logged and returns `null`, it never breaks the page
- **No double costs** - an existing translation is returned, and only empty fields are filled
- **Safe with your key** - sent in a header, never in a URL that can end up in a log
- **Laravel Boost** - guideline and skill included, so an AI assistant in your app knows the API

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- A Google Cloud Translation API key

## Installation

```bash
composer require darvis/laravel-google-translate
```

```env
GOOGLE_TRANSLATE_API_KEY=your-api-key
GOOGLE_TRANSLATE_SOURCE_LOCALE=nl
GOOGLE_TRANSLATE_TARGET_LOCALES=en,de,fr
```

## Quick start

```php
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;

$translator = app(GoogleTranslateService::class);

$translator->translate('Hallo wereld', 'en');            // "Hello world"
$translator->translateHtml('<p>Hallo</p>', 'en');        // "<p>Hello</p>"
$translator->translateBatch(['Hallo', 'Wereld'], 'en');  // ["Hello", "World"]
```

For a model, add a `locale` and a nullable `pid` column and the trait:

```php
use Darvis\LaravelGoogleTranslate\Traits\HasGoogleTranslate;

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

- [Installation](https://arviddejong.github.io/laravel-google-translate/installation.html): the package, the API key and the config
- [Quick start](https://arviddejong.github.io/laravel-google-translate/quickstart.html): the service and the trait in examples
- [Concepts](https://arviddejong.github.io/laravel-google-translate/concepts.html): the table layout, failures and costs
- [API reference](https://arviddejong.github.io/laravel-google-translate/api-reference.html)
- [CMS integration](https://arviddejong.github.io/laravel-google-translate/cms-integration.html): an overview of missing translations
- [Troubleshooting](https://arviddejong.github.io/laravel-google-translate/troubleshooting.html)

## Testing

```bash
composer test      # Pest
composer lint      # Pint, check only; composer format fixes
composer analyse   # Larastan
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md).

## Security

Please report a vulnerability privately, as described in [SECURITY](SECURITY.md), not in the issue tracker.

## License

The MIT License (MIT). See [LICENSE](LICENSE).
