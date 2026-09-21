---
title: Quick start
nav_order: 3
description: "The translation service and the HasGoogleTranslate trait in a few examples: text, HTML, batches, model translations and queued jobs."
---

# Quick start

Finish the [installation](installation.md) first.

## Translate text

```php
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;

$translator = app(GoogleTranslateService::class);

$translator->translate('Hallo wereld', 'en');        // source locale from the config
$translator->translate('Hello world', 'de', 'en');   // explicit source locale
```

## Translate HTML

```php
$translator->translateHtml('<p>Welkom op <strong>onze site</strong></p>', 'en');
// "<p>Welcome to <strong>our site</strong></p>"
```

Use `translateHtml()` for anything with tags. `translate()` sends the text as plain text, and Google then translates or escapes the markup.

## Translate several strings in one request

```php
$translator->translateBatch(['Home', 'Over ons', 'Contact'], 'en');
// ["Home", "About us", "Contact"]
```

The result has the same order as the input. A failed batch returns an empty array.

## Translate an array of fields

```php
$translated = $translator->translateFields(
    ['title' => 'Over ons', 'content' => '<p>Wij zijn...</p>', 'excerpt' => ''],
    'en',
    'nl',
    ['content'],   // these fields are sent as HTML
);
// ['title' => 'About us', 'content' => '<p>We are...</p>']
```

Empty fields and fields that failed are left out of the result.

## Translate a model

Add a `locale` and a nullable `pid` column to the table:

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

Add the trait and list the fields:

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
```

`pid`, `locale` and every translatable field have to be fillable, because the translation is made with `create()`.

```php
$page = Page::create(['locale' => 'nl', 'title' => 'Over ons', 'content' => '<p>Wij zijn...</p>', 'slug' => 'over-ons']);

$english = $page->createTranslation('en', ['slug' => 'about-us']);

if ($english === null) {
    // no API key, or every field failed; the reason is in the log
}
```

The second argument holds the values that are not translated, such as a slug, a status or a foreign key.

## Fill the gaps in an existing translation

```php
$result = $english->fillMissingTranslations();

$result['translated'];   // ['content']
$result['errors'];       // ["Translation of 'excerpt' failed"]
```

Only the fields that are empty on the translation and filled on the source are sent.

## Find what is missing

```php
Page::getMissingTranslations('en');   // source rows without an English translation
Page::sourceItems()->get();           // rows without a pid
Page::localized()->get();             // rows in the current app locale
Page::localized('de')->get();
```

## Translate in a queued job

A call takes a few hundred milliseconds per field and per locale. Translate in a job when you do more than one:

```php
class TranslatePage implements ShouldQueue
{
    use Queueable;

    public function __construct(public Page $page) {}

    public function handle(GoogleTranslateService $translator): void
    {
        foreach ($translator->getTargetLocales() as $locale) {
            $this->page->createTranslation($locale);
        }
    }
}
```

## Next steps

- [Concepts](concepts.md)
- [API reference](api-reference.md)
