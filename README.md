# Laravel Google Translate

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darvis/laravel-google-translate.svg?style=flat-square)](https://packagist.org/packages/darvis/laravel-google-translate)
[![PHP Version](https://img.shields.io/packagist/php-v/darvis/laravel-google-translate.svg?style=flat-square)](https://packagist.org/packages/darvis/laravel-google-translate)
[![Laravel Version](https://img.shields.io/badge/laravel-11.x%20%7C%2012.x%20%7C%2013.x-blue.svg?style=flat-square)](https://packagist.org/packages/darvis/laravel-google-translate)
[![License](https://img.shields.io/packagist/l/darvis/laravel-google-translate.svg?style=flat-square)](https://packagist.org/packages/darvis/laravel-google-translate)

Google Translate integration for Laravel with support for translatable Eloquent models.

## Features

- 🌍 **Simple Translation API** - Translate text and HTML content
- 📦 **Model Trait** - Easy integration with Eloquent models
- 🔄 **Batch Translation** - Translate multiple texts at once
- 📝 **HTML Support** - Preserve HTML tags during translation
- ⚙️ **Configurable** - Customize source and target locales

## Requirements

- PHP 8.2+
- Laravel 11.x, 12.x, or 13.x
- Google Cloud Translation API key

## Installation

Install the package via Composer:

```bash
composer require darvis/laravel-google-translate
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=google-translate-config
```

Add your Google Translate API key to your `.env` file:

```env
GOOGLE_TRANSLATE_API_KEY=your-api-key-here
GOOGLE_TRANSLATE_SOURCE_LOCALE=nl
GOOGLE_TRANSLATE_TARGET_LOCALES=en,de,fr
```

## Usage

### Basic Translation

```php
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;

$translator = app(GoogleTranslateService::class);

// Translate text
$translated = $translator->translate('Hallo wereld', 'en');
// Result: "Hello world"

// Translate HTML (preserves tags)
$translated = $translator->translateHtml('<p>Hallo <strong>wereld</strong></p>', 'en');
// Result: "<p>Hello <strong>world</strong></p>"

// Batch translate
$translations = $translator->translateBatch(['Hallo', 'Wereld'], 'en');
// Result: ['Hello', 'World']
```

### Model Integration

Add the `HasGoogleTranslate` trait to your Eloquent model:

```php
use Darvis\LaravelGoogleTranslate\Traits\HasGoogleTranslate;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasGoogleTranslate;

    protected $fillable = [
        'pid',
        'locale',
        'title',
        'content',
        'description',
        // ...
    ];

    // Define which fields should be translated
    protected array $translatableFields = [
        'title',
        'content',
        'description',
        'seo_title',
        'seo_description',
    ];

    // Define which fields contain HTML
    protected array $htmlFields = [
        'content',
        'description',
    ];
}
```

### Creating Translations

```php
$page = Page::find(1); // Dutch page

// Create English translation
$englishPage = $page->createTranslation('en', [
    'slug' => $page->slug,
    'active' => true,
]);

// Check if translation exists
if ($page->hasTranslation('en')) {
    $translation = $page->getTranslation('en');
}

// Get all translations
$allTranslations = $page->getAllTranslations();
```

### Filling Missing Translations

```php
$englishPage = Page::where('locale', 'en')->first();

// Fill empty fields from Dutch source
$result = $englishPage->fillMissingTranslations('nl');

// $result = [
//     'translated' => ['title', 'content'],
//     'errors' => [],
// ]
```

### Finding Missing Translations

```php
// Get all Dutch pages without English translation
$missing = Page::getMissingTranslations('en', 'nl');

foreach ($missing as $page) {
    $page->createTranslation('en');
}
```

### Scopes

```php
// Get only source items (no pid)
$sourcePages = Page::sourceItems()->get();

// Get items in current locale
$localizedPages = Page::localized()->get();

// Get items in specific locale
$dutchPages = Page::localized('nl')->get();
```

## Configuration

```php
// config/google-translate.php

return [
    // Your Google Cloud Translation API key
    'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),

    // Default source locale
    'source_locale' => env('GOOGLE_TRANSLATE_SOURCE_LOCALE', 'nl'),

    // Target locales (comma-separated in .env)
    'target_locales' => explode(',', env('GOOGLE_TRANSLATE_TARGET_LOCALES', 'en')),
];
```

## Database Structure

Your translatable models should have:

- `locale` column (string) - The language code (e.g., 'nl', 'en')
- `pid` column (nullable integer) - Parent ID pointing to the source item

Example migration:

```php
Schema::create('pages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pid')->nullable()->constrained('pages')->nullOnDelete();
    $table->string('locale', 5)->default('nl');
    $table->string('title');
    $table->text('content')->nullable();
    // ...
    $table->timestamps();
    
    $table->index(['locale', 'pid']);
});
```

## API Reference

### GoogleTranslateService

| Method | Description |
|--------|-------------|
| `isAvailable()` | Check if API key is configured |
| `translate($text, $targetLocale, $sourceLocale)` | Translate plain text |
| `translateHtml($html, $targetLocale, $sourceLocale)` | Translate HTML content |
| `translateBatch($texts, $targetLocale, $sourceLocale)` | Translate multiple texts |
| `translateFields($fields, $targetLocale, $sourceLocale, $htmlFields)` | Translate array of fields |
| `getSourceLocale()` | Get configured source locale |
| `getTargetLocales()` | Get configured target locales |

### HasGoogleTranslate Trait

| Method | Description |
|--------|-------------|
| `hasTranslation($locale)` | Check if translation exists |
| `getTranslation($locale)` | Get translation for locale |
| `getAllTranslations()` | Get all translations including self |
| `createTranslation($locale, $attributes)` | Create new translation |
| `fillMissingTranslations($sourceLocale)` | Fill empty fields from source |
| `getTranslatableFields()` | Get list of translatable fields |
| `getHtmlFields()` | Get list of HTML fields |

### Scopes

| Scope | Description |
|-------|-------------|
| `sourceItems()` | Only items without pid (originals) |
| `localized($locale)` | Items in specific locale |

### Static Methods

| Method | Description |
|--------|-------------|
| `getMissingTranslations($targetLocale, $sourceLocale)` | Get items missing translation |

## CMS Integration

For a complete guide on building a Translation Check Dashboard for your CMS, see the [CMS Integration Guide](docs/cms-integration.md).

Key features:
- Overview of missing translations per module
- Bulk translation with one click
- Individual item translation
- API status monitoring
- Progress tracking per locale

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Author

- **Arvid de Jong** - [info@arvid.nl](mailto:info@arvid.nl)
