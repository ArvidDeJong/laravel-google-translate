# Quick Start Guide

Get up and running with Livewire Google Translate in minutes! This guide shows you the most common use cases.

## Prerequisites

Make sure you've completed the [Installation Guide](installation.md) before proceeding.

## Basic Translation

### Translate Simple Text

```php
use Darvis\LivewireGoogleTranslate\GoogleTranslateService;

$translator = app(GoogleTranslateService::class);

// Translate from Dutch to English
$result = $translator->translate('Hallo wereld', 'en');
// Output: "Hello world"

// Translate from English to German
$result = $translator->translate('Hello world', 'de', 'en');
// Output: "Hallo Welt"
```

### Translate HTML Content

```php
// HTML tags are preserved
$html = '<p>Welkom bij <strong>Laravel</strong></p>';
$result = $translator->translateHtml($html, 'en');
// Output: "<p>Welcome to <strong>Laravel</strong></p>"
```

### Batch Translation

```php
// Translate multiple texts at once
$texts = ['Hallo', 'Wereld', 'Welkom'];
$results = $translator->translateBatch($texts, 'en');
// Output: ['Hello', 'World', 'Welcome']
```

## Using with Models

### Step 1: Add the Trait

Add the `HasGoogleTranslate` trait to your model:

```php
<?php

namespace App\Models;

use Darvis\LivewireGoogleTranslate\Traits\HasGoogleTranslate;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasGoogleTranslate;

    protected $fillable = [
        'pid',
        'locale',
        'title',
        'content',
        'slug',
        'active',
    ];

    // Define which fields should be translated
    protected array $translatableFields = [
        'title',
        'content',
    ];

    // Define which fields contain HTML
    protected array $htmlFields = [
        'content',
    ];
}
```

### Step 2: Create a Translation

```php
// Get the Dutch source page
$dutchPage = Page::where('locale', 'nl')->first();

// Create an English translation
$englishPage = $dutchPage->createTranslation('en', [
    'slug' => 'about-us',
    'active' => true,
]);

// The title and content are automatically translated!
```

### Step 3: Check and Retrieve Translations

```php
// Check if a translation exists
if ($dutchPage->hasTranslation('en')) {
    $englishPage = $dutchPage->getTranslation('en');
}

// Get all translations
$allTranslations = $dutchPage->getAllTranslations();
// Returns collection: [dutch page, english page, german page, ...]
```

## Common Scenarios

### Scenario 1: Multi-language Blog

```php
// Create a blog post in Dutch
$post = Post::create([
    'locale' => 'nl',
    'title' => 'Mijn eerste blog post',
    'content' => '<p>Dit is de inhoud van mijn blog post.</p>',
    'slug' => 'mijn-eerste-blog-post',
    'published' => true,
]);

// Create translations for English, German, and French
foreach (['en', 'de', 'fr'] as $locale) {
    $post->createTranslation($locale, [
        'slug' => 'my-first-blog-post', // Customize per locale if needed
        'published' => true,
    ]);
}
```

### Scenario 2: Fill Missing Translations

```php
// You have an English page with some empty fields
$englishPage = Page::where('locale', 'en')->first();

// Fill empty fields from the Dutch source
$result = $englishPage->fillMissingTranslations('nl');

// Check what was translated
if (!empty($result['translated'])) {
    echo "Translated fields: " . implode(', ', $result['translated']);
}

if (!empty($result['errors'])) {
    echo "Errors: " . implode(', ', $result['errors']);
}
```

### Scenario 3: Find Missing Translations

```php
// Find all Dutch pages without English translation
$missingPages = Page::getMissingTranslations('en', 'nl');

// Create translations for all missing pages
foreach ($missingPages as $page) {
    $page->createTranslation('en', [
        'slug' => $page->slug,
        'active' => $page->active,
    ]);
}

echo "Created {$missingPages->count()} translations!";
```

### Scenario 4: Query Localized Content

```php
// Get all pages in the current locale
$pages = Page::localized()->get();

// Get all pages in a specific locale
$dutchPages = Page::localized('nl')->get();

// Get only source items (no translations)
$sourcePages = Page::sourceItems()->get();

// Combine scopes
$publishedDutchPages = Page::localized('nl')
    ->where('published', true)
    ->orderBy('created_at', 'desc')
    ->get();
```

## Controller Example

Here's a complete example of a controller using the package:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->get('locale', app()->getLocale());
        
        $pages = Page::localized($locale)
            ->where('active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('pages.index', compact('pages', 'locale'));
    }
    
    public function show(string $locale, string $slug)
    {
        $page = Page::where('locale', $locale)
            ->where('slug', $slug)
            ->where('active', true)
            ->firstOrFail();
        
        return view('pages.show', compact('page'));
    }
    
    public function createTranslation(Page $page, string $locale)
    {
        if ($page->hasTranslation($locale)) {
            return redirect()->back()->with('error', 'Translation already exists');
        }
        
        try {
            $translation = $page->createTranslation($locale, [
                'slug' => $page->slug,
                'active' => false, // Review before publishing
            ]);
            
            return redirect()
                ->route('pages.edit', $translation)
                ->with('success', 'Translation created successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to create translation: ' . $e->getMessage());
        }
    }
}
```

## Livewire Component Example

```php
<?php

namespace App\Livewire;

use App\Models\Page;
use Livewire\Component;

class PageTranslator extends Component
{
    public Page $page;
    public string $targetLocale = 'en';
    public array $availableLocales = ['en', 'de', 'fr', 'es'];
    
    public function createTranslation()
    {
        if ($this->page->hasTranslation($this->targetLocale)) {
            session()->flash('error', 'Translation already exists');
            return;
        }
        
        try {
            $translation = $this->page->createTranslation($this->targetLocale, [
                'slug' => $this->page->slug,
                'active' => false,
            ]);
            
            session()->flash('success', 'Translation created successfully');
            $this->redirect(route('pages.edit', $translation));
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create translation: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        return view('livewire.page-translator');
    }
}
```

## Testing Your Integration

```php
use Tests\TestCase;
use App\Models\Page;

class TranslationTest extends TestCase
{
    public function test_can_create_translation()
    {
        $page = Page::factory()->create([
            'locale' => 'nl',
            'title' => 'Test Pagina',
        ]);
        
        $translation = $page->createTranslation('en');
        
        $this->assertNotNull($translation);
        $this->assertEquals('en', $translation->locale);
        $this->assertEquals($page->id, $translation->pid);
        $this->assertNotEmpty($translation->title);
    }
}
```

## Next Steps

- [Basic Concepts](concepts.md) - Understand how the package works
- [Model Integration](models.md) - Deep dive into model features
- [Translation Service](translation-service.md) - Advanced translation options
- [Configuration](configuration.md) - Customize the package

## Tips for Beginners

1. **Start Simple**: Begin with basic text translation before moving to models
2. **Test with Small Data**: Test translations with a few records first
3. **Monitor API Usage**: Keep an eye on your Google Cloud quota
4. **Cache Translations**: Consider caching frequently accessed translations
5. **Review Translations**: Always review automated translations before publishing
6. **Handle Errors**: Wrap translation calls in try-catch blocks
7. **Use Queues**: For bulk translations, consider using Laravel queues

## Common Mistakes to Avoid

❌ **Don't**: Translate the same content multiple times
```php
// Bad: Translates every time
foreach ($pages as $page) {
    $page->createTranslation('en');
}
```

✅ **Do**: Check if translation exists first
```php
// Good: Only creates if needed
foreach ($pages as $page) {
    if (!$page->hasTranslation('en')) {
        $page->createTranslation('en');
    }
}
```

❌ **Don't**: Forget to handle errors
```php
// Bad: No error handling
$translation = $page->createTranslation('en');
```

✅ **Do**: Always handle potential errors
```php
// Good: Proper error handling
try {
    $translation = $page->createTranslation('en');
} catch (\Exception $e) {
    Log::error('Translation failed: ' . $e->getMessage());
    // Handle the error appropriately
}
```
