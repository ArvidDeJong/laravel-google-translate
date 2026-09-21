---
title: "Quick start"
nav_order: 3
description: "A complete example: make a Page model translatable with a locale and pid column, call createTranslation(), then translate loose text and HTML."
---

# Quick start

Finish the [installation](installation.md) first. This page builds one complete example, a `Page` model with translations, and then shows the service for loose text.

## Translate a model, step by step

### 1. Give the table a `locale` and a `pid` column

`database/migrations/2026_01_01_000000_create_pages_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pid')->nullable()->constrained('pages')->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('slug');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
```

`locale` holds the language of the row. `pid` ("parent id") is empty on the original row and holds the id of the original on every translation. With `cascadeOnDelete()` the translations are deleted together with the original. Run `php artisan migrate`.

For a table that already exists, add the two columns in a new migration. `pid` must be nullable, and the rows you already have need a value in `locale`.

### 2. Add the trait to the model

`app/Models/Page.php`:

```php
<?php

namespace App\Models;

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

- `$translatableFields` lists the columns that are sent to Google.
- `$htmlFields` lists the ones that contain HTML, so the tags survive.
- `$fillable` must contain `pid`, `locale`, every translatable field and everything you pass as an extra attribute, because the trait stores the translation with `create()`. See [mass assignment](https://laravel.com/docs/eloquent#mass-assignment) in the Laravel docs.

### 3. Create a page and translate it

`routes/console.php`:

```php
use App\Models\Page;
use Illuminate\Support\Facades\Artisan;

Artisan::command('pages:translate {locale}', function (string $locale) {
    foreach (Page::getMissingTranslations($locale) as $page) {
        $translation = $page->createTranslation($locale, ['slug' => $page->slug.'-'.$locale]);

        $this->line($translation === null
            ? "Page {$page->id}: failed, see the log"
            : "Page {$page->id}: {$translation->title}");
    }
});
```

Create a source page once, for example in `php artisan tinker`:

```php
App\Models\Page::create(['locale' => 'nl', 'title' => 'Over ons', 'content' => '<p>Wij zijn...</p>', 'slug' => 'over-ons']);
```

Then run `php artisan pages:translate en`. `getMissingTranslations('en')` returns the source rows (rows without a `pid`) in the source locale that have no English row yet. `createTranslation()` sends `title` as text and `content` as HTML, and stores a new row with `locale` `en`, `pid` `1` and the slug `over-ons-en`. The command prints a line such as `Page 1: About us`.

Run it a second time and it prints nothing: every page has its English row, so nothing is sent to Google again.

The second argument of `createTranslation()` holds the values that are not translated, such as a slug, a status or a foreign key. Every column without a default value has to be in there, or the insert fails.

`createTranslation()` returns `null` when there is no API key, when the source row has nothing to translate, or when every field failed. It never throws for a failed API call, so check for `null`.

## Fill the gaps in an existing translation

```php
$english = $page->getTranslation('en');

$result = $english->fillMissingTranslations();

$result['translated'];   // for example ['content']
$result['errors'];       // for example ["Translation of 'content' failed"]
```

Only the fields that are empty on the translation and filled on the source row are sent. A field that already has a value is never overwritten. The row is saved when at least one field was filled.

## Find what is missing

```php
Page::getMissingTranslations('en');   // source rows without an English translation
Page::sourceItems()->get();           // rows without a pid
Page::localized()->get();             // rows in the current app locale
Page::localized('de')->get();         // rows in German
```

`sourceItems()` and `localized()` are [query scopes](https://laravel.com/docs/eloquent#local-scopes): you chain them like any other query method, for example `Page::sourceItems()->localized('nl')->paginate()`.

## Translate in a queued job

Every field is one HTTP request to Google, for every locale again. In a web request that makes the visitor wait, so translate in a [queued job](https://laravel.com/docs/queues).

`app/Jobs/TranslatePage.php`:

```php
<?php

namespace App\Jobs;

use App\Models\Page;
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TranslatePage implements ShouldQueue
{
    use Queueable;

    public function __construct(public Page $page) {}

    public function handle(GoogleTranslateService $translator): void
    {
        if ($this->page->pid !== null) {
            return; // a translation must not translate itself
        }

        foreach ($translator->getTargetLocales() as $locale) {
            $this->page->createTranslation($locale, [
                'slug' => $this->page->slug.'-'.$locale,
            ]);
        }
    }
}
```

Dispatch it with `TranslatePage::dispatch($page);`. The job makes one row for every locale in `GOOGLE_TRANSLATE_TARGET_LOCALES` and skips the locales that already exist.

## Translate loose text and HTML

You do not need a model. Resolve the service from the container, anywhere in your application:

```php
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;

$translator = app(GoogleTranslateService::class);

$translator->translate('Hallo wereld', 'en');        // source locale from the config
$translator->translate('Hello world', 'de', 'en');   // explicit source locale: English to German

$translator->translateHtml('<p>Welkom op <strong>onze site</strong></p>', 'en');
// for example "<p>Welcome to <strong>our site</strong></p>"
```

Use `translateHtml()` for anything with tags. `translate()` tells Google the input is plain text, so tags are not treated as markup.

### Several strings in one request

```php
$translator->translateBatch(['Home', 'Over ons', 'Contact'], 'en');
// for example ["Home", "About us", "Contact"]
```

The result has the same order as the input. A failed batch returns an empty array.

### An array of fields

```php
$translated = $translator->translateFields(
    ['title' => 'Over ons', 'content' => '<p>Wij zijn...</p>', 'excerpt' => ''],
    'en',
    'nl',
    ['content'],   // these fields are sent as HTML
);
// for example ['title' => 'About us', 'content' => '<p>We are...</p>']
```

Empty fields and fields that failed are left out of the result. Every field is a request of its own.

## Next steps

- [Concepts](concepts.md): what comes back when a call fails, and what a translation costs
- [Testing](testing.md): test this code without calling Google
- [CMS integration](cms-integration.md): a screen with a translate button
