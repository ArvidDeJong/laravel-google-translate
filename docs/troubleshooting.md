# Troubleshooting

Common issues and their solutions when using Livewire Google Translate.

## Installation Issues

### API Key Not Configured

**Error:**
```
API key not configured
```

**Solutions:**

1. Check your `.env` file:
   ```env
   GOOGLE_TRANSLATE_API_KEY=your-api-key-here
   ```

2. Clear config cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

3. Restart your development server

4. Verify the key is loaded:
   ```php
   dd(config('google-translate.api_key'));
   ```

### API Key Not Valid

**Error:**
```
HTTP 400: API key not valid
```

**Solutions:**

1. Verify your API key in Google Cloud Console
2. Check for extra spaces in `.env` file
3. Ensure Cloud Translation API is enabled
4. Check API key restrictions match your setup

### Package Not Found

**Error:**
```
Class 'Darvis\LivewireGoogleTranslate\GoogleTranslateService' not found
```

**Solutions:**

1. Run composer dump-autoload:
   ```bash
   composer dump-autoload
   ```

2. Verify installation:
   ```bash
   composer show darvis/livewire-google-translate
   ```

3. Reinstall if needed:
   ```bash
   composer remove darvis/livewire-google-translate
   composer require darvis/livewire-google-translate
   ```

## Translation Issues

### Empty Translation Results

**Problem:** Translations return empty strings

**Causes & Solutions:**

1. **Empty source text**
   ```php
   // Check source is not empty
   if (empty($text)) {
       // Handle empty case
   }
   ```

2. **API quota exceeded**
   - Check Google Cloud Console quota
   - Upgrade your plan if needed

3. **Network issues**
   ```php
   try {
       $result = $translator->translate($text, 'en');
   } catch (\Exception $e) {
       Log::error('Translation failed: ' . $e->getMessage());
   }
   ```

### HTML Tags Not Preserved

**Problem:** HTML tags are removed or broken

**Solution:** Use `translateHtml()` instead of `translate()`:

```php
// Wrong
$result = $translator->translate('<p>Hello</p>', 'nl');

// Correct
$result = $translator->translateHtml('<p>Hello</p>', 'nl');
```

For models, add fields to `$htmlFields`:

```php
protected array $htmlFields = [
    'content',
    'description',
];
```

### Translation Already Exists

**Error:**
```
Translation for locale 'en' already exists
```

**Solution:** Check before creating:

```php
if (!$page->hasTranslation('en')) {
    $page->createTranslation('en');
} else {
    // Update existing translation
    $existing = $page->getTranslation('en');
    $existing->update([...]);
}
```

## Model Issues

### Trait Not Working

**Problem:** Model methods not available

**Solutions:**

1. Verify trait is imported:
   ```php
   use Darvis\LivewireGoogleTranslate\Traits\HasGoogleTranslate;
   
   class Page extends Model
   {
       use HasGoogleTranslate;
   }
   ```

2. Clear compiled files:
   ```bash
   php artisan clear-compiled
   composer dump-autoload
   ```

### Missing Columns

**Error:**
```
SQLSTATE[42S22]: Column not found: 'locale'
```

**Solution:** Add required columns to your migration:

```php
Schema::table('pages', function (Blueprint $table) {
    $table->foreignId('pid')->nullable()->constrained('pages')->nullOnDelete();
    $table->string('locale', 5)->default('nl');
    $table->index(['locale', 'pid']);
});
```

Run the migration:
```bash
php artisan migrate
```

### Translatable Fields Not Set

**Problem:** No fields are translated

**Solution:** Define `$translatableFields` in your model:

```php
protected array $translatableFields = [
    'title',
    'content',
    'description',
];
```

### Foreign Key Constraint Error

**Error:**
```
SQLSTATE[23000]: Integrity constraint violation
```

**Solutions:**

1. Ensure parent record exists:
   ```php
   $source = Page::find($pid);
   if (!$source) {
       throw new \Exception('Source page not found');
   }
   ```

2. Use `nullOnDelete()` in migration:
   ```php
   $table->foreignId('pid')
       ->nullable()
       ->constrained('pages')
       ->nullOnDelete();
   ```

## API Issues

### Quota Exceeded

**Error:**
```
HTTP 429: Quota exceeded
```

**Solutions:**

1. Check usage in Google Cloud Console
2. Implement rate limiting:
   ```php
   use Illuminate\Support\Facades\RateLimiter;
   
   if (RateLimiter::tooManyAttempts('translate', 100)) {
       throw new \Exception('Too many translation requests');
   }
   
   RateLimiter::hit('translate');
   $result = $translator->translate($text, 'en');
   ```

3. Use queues for bulk translations:
   ```php
   dispatch(new TranslatePageJob($page, 'en'));
   ```

4. Upgrade your Google Cloud plan

### API Request Timeout

**Error:**
```
cURL error 28: Operation timed out
```

**Solutions:**

1. Check your internet connection
2. Increase timeout in HTTP client
3. Retry failed requests:
   ```php
   $maxRetries = 3;
   $attempt = 0;
   
   while ($attempt < $maxRetries) {
       try {
           return $translator->translate($text, 'en');
       } catch (\Exception $e) {
           $attempt++;
           if ($attempt >= $maxRetries) {
               throw $e;
           }
           sleep(1);
       }
   }
   ```

### Invalid Locale Code

**Error:**
```
HTTP 400: Invalid value for 'target'
```

**Solution:** Use valid ISO 639-1 language codes:

```php
// Valid codes
'en', 'nl', 'de', 'fr', 'es', 'it', 'pt', 'ru', 'ja', 'zh'

// Invalid codes
'eng', 'dutch', 'english'
```

See [Google's supported languages](https://cloud.google.com/translate/docs/languages).

## Performance Issues

### Slow Translation

**Problem:** Translations take too long

**Solutions:**

1. **Use batch translation:**
   ```php
   // Slow: Multiple API calls
   foreach ($texts as $text) {
       $translator->translate($text, 'en');
   }
   
   // Fast: One API call
   $results = $translator->translateBatch($texts, 'en');
   ```

2. **Use queues:**
   ```php
   // Create job
   php artisan make:job TranslateContentJob
   
   // Dispatch job
   TranslateContentJob::dispatch($page, 'en');
   ```

3. **Cache results:**
   ```php
   $cacheKey = "translation.{$text}.{$locale}";
   $result = Cache::remember($cacheKey, 3600, function () use ($text, $locale) {
       return $translator->translate($text, $locale);
   });
   ```

### Memory Issues

**Problem:** Out of memory when translating many items

**Solutions:**

1. **Use chunking:**
   ```php
   Page::sourceItems()->chunk(100, function ($pages) {
       foreach ($pages as $page) {
           $page->createTranslation('en');
       }
   });
   ```

2. **Use cursor:**
   ```php
   foreach (Page::sourceItems()->cursor() as $page) {
       $page->createTranslation('en');
   }
   ```

3. **Process in queue:**
   ```php
   Page::sourceItems()->each(function ($page) {
       TranslatePageJob::dispatch($page, 'en');
   });
   ```

## Database Issues

### Duplicate Translations

**Problem:** Multiple translations for same locale

**Solution:** Add unique constraint:

```php
Schema::table('pages', function (Blueprint $table) {
    $table->unique(['pid', 'locale']);
});
```

### Orphaned Translations

**Problem:** Translations exist but source is deleted

**Solution:** Use `nullOnDelete()` or `cascadeOnDelete()`:

```php
// Option 1: Set pid to null when source deleted
$table->foreignId('pid')
    ->nullable()
    ->constrained('pages')
    ->nullOnDelete();

// Option 2: Delete translations when source deleted
$table->foreignId('pid')
    ->nullable()
    ->constrained('pages')
    ->cascadeOnDelete();
```

Clean up existing orphans:
```php
Page::whereNotNull('pid')
    ->whereNotExists(function ($query) {
        $query->select('id')
            ->from('pages as parent')
            ->whereColumn('parent.id', 'pages.pid');
    })
    ->delete();
```

## Testing Issues

### Tests Failing

**Problem:** Tests fail with API errors

**Solution:** Mock the translation service:

```php
use Darvis\LivewireGoogleTranslate\GoogleTranslateService;
use Mockery;

public function test_translation()
{
    $mock = Mockery::mock(GoogleTranslateService::class);
    $mock->shouldReceive('translate')
        ->andReturn('Mocked translation');
    
    $this->app->instance(GoogleTranslateService::class, $mock);
    
    // Your test code
}
```

Or use a fake API key in `.env.testing`:
```env
GOOGLE_TRANSLATE_API_KEY=fake-key-for-testing
```

## Getting Help

If you're still experiencing issues:

1. **Check the documentation:**
   - [Installation Guide](installation.md)
   - [Quick Start](quickstart.md)
   - [API Reference](api-reference.md)

2. **Search existing issues:**
   - [GitHub Issues](https://github.com/darvis/livewire-google-translate/issues)

3. **Create a new issue:**
   - Include error messages
   - Provide code examples
   - Mention PHP, Laravel, and package versions

4. **Contact support:**
   - Email: [info@arvid.nl](mailto:info@arvid.nl)

## Debug Mode

Enable detailed error logging:

```php
// In your code
try {
    $result = $translator->translate($text, 'en');
} catch (\Exception $e) {
    Log::error('Translation error', [
        'text' => $text,
        'locale' => 'en',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    throw $e;
}
```

Check logs:
```bash
tail -f storage/logs/laravel.log
```
