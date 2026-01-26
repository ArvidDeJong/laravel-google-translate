# Basic Concepts

This guide explains the core concepts behind Livewire Google Translate to help you understand how the package works.

## Overview

Livewire Google Translate provides two main components:

1. **Translation Service** - A service for translating text and HTML
2. **Model Trait** - Integration with Eloquent models for managing translations

## Translation Service

The `GoogleTranslateService` is the core of the package. It communicates with Google's Cloud Translation API to translate content.

### How It Works

```php
$translator = app(GoogleTranslateService::class);
$result = $translator->translate('Hello', 'nl');
```

**Process:**
1. Service validates the API key is configured
2. Sends request to Google Cloud Translation API
3. Receives translated text
4. Returns the result

### Key Features

- **Text Translation**: Plain text translation
- **HTML Translation**: Preserves HTML tags during translation
- **Batch Translation**: Translate multiple texts in one API call
- **Field Translation**: Translate arrays of fields with HTML support

## Model Translation System

The package uses a parent-child relationship to manage translations.

### The Parent-Child Model

```
┌─────────────────┐
│  Dutch Page     │  <- Source (pid = null)
│  locale: 'nl'   │
│  pid: null      │
└────────┬────────┘
         │
         ├─────────────────────┐
         │                     │
┌────────▼────────┐   ┌────────▼────────┐
│  English Page   │   │  German Page    │
│  locale: 'en'   │   │  locale: 'de'   │
│  pid: 1         │   │  pid: 1         │
└─────────────────┘   └─────────────────┘
```

**Key Points:**
- Source items have `pid = null`
- Translations have `pid` pointing to the source
- Each translation has a unique `locale`
- All share the same translatable content

### Database Structure

Required columns for translatable models:

```php
$table->id();
$table->foreignId('pid')->nullable()->constrained('pages')->nullOnDelete();
$table->string('locale', 5)->default('nl');
// ... your other columns
```

**Column Purposes:**
- `id`: Unique identifier for each record
- `pid`: Parent ID (null for source, source ID for translations)
- `locale`: Language code (e.g., 'nl', 'en', 'de')

## Translatable Fields

You define which fields should be translated in your model:

```php
protected array $translatableFields = [
    'title',
    'content',
    'description',
];
```

**What Happens:**
- Only these fields are translated
- Other fields must be provided manually
- Empty fields are skipped

## HTML Fields

Some fields contain HTML that should be preserved:

```php
protected array $htmlFields = [
    'content',
    'description',
];
```

**Behavior:**
- Fields in `htmlFields` use `translateHtml()`
- Other fields use `translate()`
- HTML tags remain intact

**Example:**
```php
// Input
'<p>Welkom bij <strong>Laravel</strong></p>'

// Output (English)
'<p>Welcome to <strong>Laravel</strong></p>'
```

## Translation Workflow

### Creating a New Translation

```php
$source = Page::find(1); // Dutch page
$translation = $source->createTranslation('en', [
    'slug' => 'about-us',
    'active' => true,
]);
```

**What Happens:**
1. Checks if translation already exists
2. Translates all `translatableFields`
3. Creates new record with:
   - Translated fields
   - Provided attributes (`slug`, `active`)
   - `pid` set to source ID
   - `locale` set to target locale

### Filling Missing Translations

```php
$translation = Page::where('locale', 'en')->first();
$result = $translation->fillMissingTranslations('nl');
```

**What Happens:**
1. Finds the source record (where `id = pid`)
2. Checks each translatable field
3. If field is empty, translates from source
4. Saves the updated record

**Use Case:** When you've manually created a translation but left some fields empty.

## Scopes

The package provides query scopes for filtering:

### sourceItems()

Returns only source records (no `pid`):

```php
Page::sourceItems()->get();
// Returns: All pages where pid IS NULL
```

### localized($locale)

Returns records in a specific locale:

```php
Page::localized('en')->get();
// Returns: All pages where locale = 'en'

Page::localized()->get();
// Returns: All pages where locale = app()->getLocale()
```

## API Communication

### Request Flow

```
Your App → GoogleTranslateService → Google Cloud API
                                            ↓
Your App ← GoogleTranslateService ← Translated Text
```

### API Request Format

```php
// Single translation
POST https://translation.googleapis.com/language/translate/v2
{
    "q": "Hello world",
    "target": "nl",
    "source": "en",
    "format": "text",
    "key": "YOUR_API_KEY"
}

// Response
{
    "data": {
        "translations": [
            {
                "translatedText": "Hallo wereld"
            }
        ]
    }
}
```

## Configuration

The package uses these configuration values:

```php
// config/google-translate.php
return [
    'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
    'source_locale' => env('GOOGLE_TRANSLATE_SOURCE_LOCALE', 'nl'),
    'target_locales' => explode(',', env('GOOGLE_TRANSLATE_TARGET_LOCALES', 'en')),
];
```

**Access in Code:**
```php
$service->getSourceLocale(); // Returns 'nl'
$service->getTargetLocales(); // Returns ['en', 'de', 'fr']
```

## Error Handling

The package throws exceptions for various error conditions:

```php
try {
    $translation = $page->createTranslation('en');
} catch (\Exception $e) {
    // Handle errors:
    // - API key not configured
    // - API request failed
    // - Translation already exists
    // - Network errors
}
```

**Common Errors:**
- `API key not configured`: Missing `GOOGLE_TRANSLATE_API_KEY`
- `Translation already exists`: Duplicate translation attempt
- `HTTP 400`: Invalid request (check API key, locale codes)
- `HTTP 403`: API key restrictions or quota exceeded

## Best Practices

### 1. Always Check Before Creating

```php
if (!$page->hasTranslation('en')) {
    $page->createTranslation('en');
}
```

### 2. Use Batch Translation for Multiple Items

```php
// Better: One API call
$texts = ['Hello', 'World', 'Welcome'];
$results = $translator->translateBatch($texts, 'nl');

// Avoid: Multiple API calls
foreach ($texts as $text) {
    $translator->translate($text, 'nl');
}
```

### 3. Handle Errors Gracefully

```php
try {
    $translation = $page->createTranslation('en');
} catch (\Exception $e) {
    Log::error('Translation failed', [
        'page_id' => $page->id,
        'locale' => 'en',
        'error' => $e->getMessage(),
    ]);
    
    // Notify admin or retry later
}
```

### 4. Use Scopes for Queries

```php
// Good: Use provided scopes
$pages = Page::localized('en')->get();

// Avoid: Manual where clauses
$pages = Page::where('locale', 'en')->get();
```

### 5. Index Your Database

```php
// In migration
$table->index(['locale', 'pid']);
$table->index('locale');
```

## Performance Considerations

### API Costs

Google Cloud Translation API charges per character:
- Monitor your usage in Google Cloud Console
- Use batch translation to reduce API calls
- Cache translations when possible

### Database Queries

```php
// Efficient: One query with scope
$pages = Page::localized('en')
    ->with('author')
    ->get();

// Inefficient: Multiple queries
$pages = Page::all()->filter(fn($p) => $p->locale === 'en');
```

### Caching Translations

```php
use Illuminate\Support\Facades\Cache;

$translation = Cache::remember("page.{$id}.{$locale}", 3600, function () use ($id, $locale) {
    return Page::where('id', $id)
        ->where('locale', $locale)
        ->first();
});
```

## Next Steps

- [Translation Service](translation-service.md) - Detailed service documentation
- [Model Integration](models.md) - Advanced model features
- [Configuration](configuration.md) - Configuration options
- [Troubleshooting](troubleshooting.md) - Common issues
