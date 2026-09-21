---
name: laravel-google-translate-development
description: Work with darvis/laravel-google-translate. Use it to translate text, HTML or Eloquent models with Google Cloud Translation, set up the locale and pid columns, handle a translation that comes back as null, and test code that translates without calling Google.
---

# darvis/laravel-google-translate development

## When to use this skill

Use this skill when code translates content in an application that has `darvis/laravel-google-translate` installed, when a model needs translations per locale, when a translation comes back as `null` or a translated row is missing, or when you write tests around translated content.

## How a call runs

1. The service is a singleton that reads the API key once, when it is constructed.
2. Every method first checks the key and the input. Without a key or with empty input it returns `null` (or `[]`) and sends nothing.
3. The request is a JSON POST to `https://translation.googleapis.com/language/translate/v2`, with the key in the `X-goog-api-key` header and `q`, `source`, `target` and `format` (`text` or `html`) in the body.
4. Any failure is caught, logged with `Log::error()` and turned into `null` or `[]`. Nothing throws.

| Situation | `translate()` / `translateHtml()` | `translateBatch()` | Log line |
| --- | --- | --- | --- |
| No API key | `null` | `[]` | none |
| Empty input | `null` | `[]` | none |
| HTTP 4xx or 5xx | `null` | `[]` | `Google Translate failed: <response body>` |
| Connection error | `null` | `[]` | `Google Translate failed: <message>` |

The prefix is `Google Translate HTML failed` for `translateHtml()` and `Google Translate batch failed` for a batch.

## The table layout

Translations are rows in the same table, not a separate table and not JSON columns.

```php
Schema::create('pages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pid')->nullable()->constrained('pages')->cascadeOnDelete();
    $table->string('locale', 5)->index();
    $table->string('title');
    $table->text('content')->nullable();
    $table->string('slug');
    $table->timestamps();
});
```

- Source row: `pid` is null. Translation: `pid` is the id of the source row.
- A unique index on `slug` has to include `locale`, or the translation needs its own slug.
- `hasTranslation()`, `getTranslation()` and `getAllTranslations()` work from the source row and from a translation.

## The model

```php
use Darvis\LaravelGoogleTranslate\Traits\HasGoogleTranslate;

class Page extends Model
{
    use HasGoogleTranslate;

    protected $fillable = ['pid', 'locale', 'title', 'content', 'slug'];

    protected $translatableFields = ['title', 'content'];

    protected $htmlFields = ['content'];
}
```

Pitfalls:

- `createTranslation()` uses `create()`. A field that is not fillable is silently dropped, and with `Model::preventSilentlyDiscardingAttributes()` it throws.
- A column without a default that is not translatable (slug, status, a foreign key) must be passed as the second argument, or the insert fails.
- A field with tags that is missing from `$htmlFields` is sent as plain text and comes back with broken markup.
- Without `$translatableFields` the trait uses `title`, `content`, `description` and `excerpt`; columns that don't exist are simply not sent.

## Creating and completing translations

```php
$english = $page->createTranslation('en', ['slug' => 'about-us']);

if ($english === null) {
    // No key, no translatable content, or every field failed.
}

$result = $english->fillMissingTranslations();
// ['translated' => ['excerpt'], 'errors' => ["Translation of 'content' failed"]]
```

- `createTranslation()` returns the existing row when the locale already exists; it never translates twice.
- When only some fields fail, the row is created with the fields that succeeded. Run `fillMissingTranslations()` later for the rest.
- `fillMissingTranslations()` never overwrites a field that has a value. To translate a changed source again, empty the field on the translation first.

## Translate in a job, source rows only

Every field is one HTTP request to Google, and Google charges for the characters you send, for every locale again.

```php
public function handle(GoogleTranslateService $translator): void
{
    if ($this->page->pid !== null) {
        return; // a translation must not translate itself
    }

    foreach ($translator->getTargetLocales() as $locale) {
        $this->page->createTranslation($locale, ['slug' => $this->page->slug.'-'.$locale]);
    }
}
```

`getTargetLocales()` returns the list from `GOOGLE_TRANSLATE_TARGET_LOCALES`; the package never loops over it by itself.

## Finding what is missing

```php
Page::getMissingTranslations('en');   // one query per source row, paginate large tables
Page::sourceItems()->get();
Page::localized()->get();             // the current app locale
```

## Settings

Read them through `Darvis\LaravelGoogleTranslate\Support\GoogleTranslateConfig`: `apiKey()` (null when empty), `sourceLocale()` and `targetLocales()`. A key changed at runtime is only used after `app()->forgetInstance(GoogleTranslateService::class)`.

## Testing

Never call Google from a test.

```php
use Illuminate\Support\Facades\Http;

Http::preventStrayRequests();
config(['google-translate.api_key' => 'test-key']);

Http::fake([
    'translation.googleapis.com/*' => function ($request) {
        $texts = (array) $request['q'];

        return Http::response(['data' => ['translations' => array_map(
            fn ($text) => ['translatedText' => "[{$request['target']}] {$text}"],
            $texts,
        )]]);
    },
]);

$translation = $page->createTranslation('en', ['slug' => 'about-us']);

expect($translation->title)->toBe('[en] Over ons');
```

- Set the key before the service is resolved, or call `app()->forgetInstance(GoogleTranslateService::class)` after changing it.
- A forgotten fake does not throw: the package catches the stray request exception like any other failure, returns `null` and logs `Google Translate failed: Attempted request to [...] without a matching fake.`
- To test the failure path, return `Http::response(['error' => 'denied'], 403)` and assert on `null`, not on an exception.
- Assert on the request with `Http::assertSent(fn ($request) => $request['format'] === 'html')`.
