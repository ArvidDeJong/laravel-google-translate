# API Reference

Complete API documentation for Livewire Google Translate.

## GoogleTranslateService

The main service class for translating content.

### Constructor

```php
public function __construct()
```

The service is automatically instantiated by Laravel's service container.

**Usage:**
```php
$translator = app(GoogleTranslateService::class);
// or
$translator = resolve(GoogleTranslateService::class);
```

### isAvailable()

Check if the Google Translate API is configured and available.

```php
public function isAvailable(): bool
```

**Returns:** `bool` - True if API key is configured, false otherwise

**Example:**
```php
if ($translator->isAvailable()) {
    // Proceed with translation
} else {
    // Handle missing API key
}
```

### translate()

Translate plain text from source to target language.

```php
public function translate(
    string $text,
    string $targetLocale,
    ?string $sourceLocale = null
): string
```

**Parameters:**
- `$text` (string) - The text to translate
- `$targetLocale` (string) - Target language code (e.g., 'en', 'de')
- `$sourceLocale` (string|null) - Source language code (optional, uses config default)

**Returns:** `string` - Translated text

**Throws:** `\Exception` - If API request fails

**Example:**
```php
$result = $translator->translate('Hallo wereld', 'en');
// Returns: "Hello world"

$result = $translator->translate('Hello', 'de', 'en');
// Returns: "Hallo"
```

### translateHtml()

Translate HTML content while preserving tags and structure.

```php
public function translateHtml(
    string $html,
    string $targetLocale,
    ?string $sourceLocale = null
): string
```

**Parameters:**
- `$html` (string) - HTML content to translate
- `$targetLocale` (string) - Target language code
- `$sourceLocale` (string|null) - Source language code (optional)

**Returns:** `string` - Translated HTML with preserved tags

**Example:**
```php
$html = '<p>Welkom bij <strong>Laravel</strong></p>';
$result = $translator->translateHtml($html, 'en');
// Returns: "<p>Welcome to <strong>Laravel</strong></p>"
```

### translateBatch()

Translate multiple texts in a single API call.

```php
public function translateBatch(
    array $texts,
    string $targetLocale,
    ?string $sourceLocale = null
): array
```

**Parameters:**
- `$texts` (array) - Array of strings to translate
- `$targetLocale` (string) - Target language code
- `$sourceLocale` (string|null) - Source language code (optional)

**Returns:** `array` - Array of translated strings in same order

**Example:**
```php
$texts = ['Hallo', 'Wereld', 'Welkom'];
$results = $translator->translateBatch($texts, 'en');
// Returns: ['Hello', 'World', 'Welcome']
```

### translateFields()

Translate an associative array of fields with HTML support.

```php
public function translateFields(
    array $fields,
    string $targetLocale,
    ?string $sourceLocale = null,
    array $htmlFields = []
): array
```

**Parameters:**
- `$fields` (array) - Associative array of field => value pairs
- `$targetLocale` (string) - Target language code
- `$sourceLocale` (string|null) - Source language code (optional)
- `$htmlFields` (array) - Array of field names that contain HTML

**Returns:** `array` - Associative array with translated values

**Example:**
```php
$fields = [
    'title' => 'Welkom',
    'content' => '<p>Dit is de inhoud</p>',
];

$results = $translator->translateFields($fields, 'en', 'nl', ['content']);
// Returns: [
//     'title' => 'Welcome',
//     'content' => '<p>This is the content</p>',
// ]
```

### getSourceLocale()

Get the configured default source locale.

```php
public function getSourceLocale(): string
```

**Returns:** `string` - Source locale code from config

**Example:**
```php
$locale = $translator->getSourceLocale();
// Returns: "nl" (or configured value)
```

### getTargetLocales()

Get the configured target locales.

```php
public function getTargetLocales(): array
```

**Returns:** `array` - Array of target locale codes

**Example:**
```php
$locales = $translator->getTargetLocales();
// Returns: ['en', 'de', 'fr']
```

## HasGoogleTranslate Trait

Trait for Eloquent models to add translation capabilities.

### Properties

#### $translatableFields

Define which model fields should be translated.

```php
protected array $translatableFields = [];
```

**Example:**
```php
protected array $translatableFields = [
    'title',
    'content',
    'description',
];
```

#### $htmlFields

Define which fields contain HTML content.

```php
protected array $htmlFields = [];
```

**Example:**
```php
protected array $htmlFields = [
    'content',
    'description',
];
```

### Methods

#### hasTranslation()

Check if a translation exists for a specific locale.

```php
public function hasTranslation(string $locale): bool
```

**Parameters:**
- `$locale` (string) - Language code to check

**Returns:** `bool` - True if translation exists

**Example:**
```php
if ($page->hasTranslation('en')) {
    // Translation exists
}
```

#### getTranslation()

Get the translation for a specific locale.

```php
public function getTranslation(string $locale): ?self
```

**Parameters:**
- `$locale` (string) - Language code

**Returns:** `Model|null` - Translation model or null if not found

**Example:**
```php
$englishPage = $dutchPage->getTranslation('en');
```

#### getAllTranslations()

Get all translations including the current model.

```php
public function getAllTranslations(): Collection
```

**Returns:** `Collection` - Collection of all translation models

**Example:**
```php
$translations = $page->getAllTranslations();
foreach ($translations as $translation) {
    echo $translation->locale . ': ' . $translation->title;
}
```

#### createTranslation()

Create a new translation for the model.

```php
public function createTranslation(
    string $targetLocale,
    array $additionalAttributes = []
): self
```

**Parameters:**
- `$targetLocale` (string) - Target language code
- `$additionalAttributes` (array) - Additional attributes to set on translation

**Returns:** `Model` - The created translation model

**Throws:** `\Exception` - If translation already exists or API fails

**Example:**
```php
$translation = $page->createTranslation('en', [
    'slug' => 'about-us',
    'active' => true,
]);
```

#### fillMissingTranslations()

Fill empty fields from a source translation.

```php
public function fillMissingTranslations(string $sourceLocale): array
```

**Parameters:**
- `$sourceLocale` (string) - Source language code to translate from

**Returns:** `array` - Array with 'translated' and 'errors' keys

**Example:**
```php
$result = $englishPage->fillMissingTranslations('nl');

// Returns:
// [
//     'translated' => ['title', 'content'],
//     'errors' => [],
// ]
```

#### getTranslatableFields()

Get the list of translatable fields.

```php
public function getTranslatableFields(): array
```

**Returns:** `array` - Array of field names

#### getHtmlFields()

Get the list of HTML fields.

```php
public function getHtmlFields(): array
```

**Returns:** `array` - Array of HTML field names

### Scopes

#### sourceItems()

Query scope to get only source items (no parent).

```php
public function scopeSourceItems(Builder $query): Builder
```

**Example:**
```php
$sourcePages = Page::sourceItems()->get();
```

#### localized()

Query scope to get items in a specific locale.

```php
public function scopeLocalized(Builder $query, ?string $locale = null): Builder
```

**Parameters:**
- `$locale` (string|null) - Locale code (uses app locale if null)

**Example:**
```php
$dutchPages = Page::localized('nl')->get();
$currentLocalePages = Page::localized()->get();
```

### Static Methods

#### getMissingTranslations()

Get source items that don't have a translation for the target locale.

```php
public static function getMissingTranslations(
    string $targetLocale,
    ?string $sourceLocale = null
): Collection
```

**Parameters:**
- `$targetLocale` (string) - Target locale to check for
- `$sourceLocale` (string|null) - Source locale (optional)

**Returns:** `Collection` - Collection of models missing translation

**Example:**
```php
$missing = Page::getMissingTranslations('en', 'nl');
foreach ($missing as $page) {
    $page->createTranslation('en');
}
```

## Configuration

Configuration file: `config/google-translate.php`

### api_key

Google Cloud Translation API key.

**Type:** `string`  
**Default:** `env('GOOGLE_TRANSLATE_API_KEY')`  
**Required:** Yes

### source_locale

Default source language for translations.

**Type:** `string`  
**Default:** `env('GOOGLE_TRANSLATE_SOURCE_LOCALE', 'nl')`  
**Required:** No

### target_locales

Array of target languages for translations.

**Type:** `array`  
**Default:** `explode(',', env('GOOGLE_TRANSLATE_TARGET_LOCALES', 'en'))`  
**Required:** No

## Language Codes

Supported language codes (ISO 639-1):

| Code | Language |
|------|----------|
| `af` | Afrikaans |
| `ar` | Arabic |
| `bg` | Bulgarian |
| `bn` | Bengali |
| `ca` | Catalan |
| `cs` | Czech |
| `da` | Danish |
| `de` | German |
| `el` | Greek |
| `en` | English |
| `es` | Spanish |
| `et` | Estonian |
| `fa` | Persian |
| `fi` | Finnish |
| `fr` | French |
| `gu` | Gujarati |
| `he` | Hebrew |
| `hi` | Hindi |
| `hr` | Croatian |
| `hu` | Hungarian |
| `id` | Indonesian |
| `it` | Italian |
| `ja` | Japanese |
| `kn` | Kannada |
| `ko` | Korean |
| `lt` | Lithuanian |
| `lv` | Latvian |
| `mk` | Macedonian |
| `ml` | Malayalam |
| `mr` | Marathi |
| `ne` | Nepali |
| `nl` | Dutch |
| `no` | Norwegian |
| `pa` | Punjabi |
| `pl` | Polish |
| `pt` | Portuguese |
| `ro` | Romanian |
| `ru` | Russian |
| `sk` | Slovak |
| `sl` | Slovenian |
| `so` | Somali |
| `sq` | Albanian |
| `sv` | Swedish |
| `sw` | Swahili |
| `ta` | Tamil |
| `te` | Telugu |
| `th` | Thai |
| `tl` | Filipino |
| `tr` | Turkish |
| `uk` | Ukrainian |
| `ur` | Urdu |
| `vi` | Vietnamese |
| `zh-CN` | Chinese (Simplified) |
| `zh-TW` | Chinese (Traditional) |

For the complete list, see [Google's supported languages](https://cloud.google.com/translate/docs/languages).

## HTTP Responses

### Success Response

```json
{
    "data": {
        "translations": [
            {
                "translatedText": "Hello world",
                "detectedSourceLanguage": "nl"
            }
        ]
    }
}
```

### Error Responses

#### 400 Bad Request
```json
{
    "error": {
        "code": 400,
        "message": "Invalid value for 'target'",
        "status": "INVALID_ARGUMENT"
    }
}
```

#### 403 Forbidden
```json
{
    "error": {
        "code": 403,
        "message": "API key not valid",
        "status": "PERMISSION_DENIED"
    }
}
```

#### 429 Too Many Requests
```json
{
    "error": {
        "code": 429,
        "message": "Quota exceeded",
        "status": "RESOURCE_EXHAUSTED"
    }
}
```

## Events

The package does not emit custom events, but you can listen to Eloquent events:

```php
class Page extends Model
{
    use HasGoogleTranslate;
    
    protected static function booted()
    {
        static::created(function ($page) {
            if ($page->locale === 'nl') {
                // Auto-create English translation
                $page->createTranslation('en');
            }
        });
    }
}
```

## Testing Helpers

### Mocking the Service

```php
use Darvis\LivewireGoogleTranslate\GoogleTranslateService;
use Mockery;

$mock = Mockery::mock(GoogleTranslateService::class);
$mock->shouldReceive('translate')
    ->with('Hello', 'nl')
    ->andReturn('Hallo');

$this->app->instance(GoogleTranslateService::class, $mock);
```

### Faking HTTP Requests

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'translation.googleapis.com/*' => Http::response([
        'data' => [
            'translations' => [
                ['translatedText' => 'Mocked translation']
            ]
        ]
    ], 200)
]);
```
