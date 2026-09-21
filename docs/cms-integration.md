---
title: "CMS integration"
nav_order: 6
description: "Build a Livewire screen for your CMS that lists the source rows without a translation per locale and translates them in a queued job with one click."
---

# CMS integration

A common screen in a CMS: per model and per locale, which items still miss a translation, with a button to translate them. This page builds that screen with [Livewire](https://livewire.laravel.com).

## What you need first

- The package does not require Livewire and ships no screens. Install Livewire yourself with `composer require livewire/livewire`. The component below works with Livewire 3 and Livewire 4.
- A Livewire layout, because the component is shown as a full page. See [Pages](https://livewire.laravel.com/docs/pages) in the Livewire docs.
- A running [queue worker](https://laravel.com/docs/queues), because the buttons dispatch jobs.
- Models that use the trait, as in the [quick start](quickstart.md). The example uses `App\Models\Page` and `App\Models\Post`, each with a `title` and a `slug` column.

## The job

Every field is one HTTP request to Google, so the button dispatches a job instead of translating inside the request.

`app/Jobs/TranslateModel.php`:

```php
<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class TranslateModel implements ShouldQueue
{
    use Queueable;

    public function __construct(public Model $model, public string $locale) {}

    public function handle(): void
    {
        $translation = $this->model->createTranslation($this->locale, [
            'slug' => Str::slug($this->model->title).'-'.$this->locale,
        ]);

        if ($translation === null) {
            // No API key, nothing to translate, or every field failed. The reason is in the log.
            $this->fail('Translation to '.$this->locale.' failed for '.$this->model::class.' #'.$this->model->getKey());
        }
    }
}
```

The job creates the translated row. When `createTranslation()` returns `null`, `fail()` marks the job as failed, so it shows up in `php artisan queue:failed` instead of disappearing.

## The component

`app/Livewire/Translations/TranslationsOverview.php`:

```php
<?php

namespace App\Livewire\Translations;

use App\Jobs\TranslateModel;
use App\Models\Page;
use App\Models\Post;
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TranslationsOverview extends Component
{
    /** @var array<string, class-string> */
    protected array $models = [
        'Pages' => Page::class,
        'Posts' => Post::class,
    ];

    #[Computed]
    public function available(): bool
    {
        return app(GoogleTranslateService::class)->isAvailable();
    }

    /**
     * @return array<string, array<string, \Illuminate\Support\Collection>>
     */
    #[Computed]
    public function missing(): array
    {
        $missing = [];

        foreach ($this->models as $label => $model) {
            foreach ($this->locales() as $locale) {
                $missing[$label][$locale] = $model::getMissingTranslations($locale);
            }
        }

        return $missing;
    }

    public function translate(string $label, int $id, string $locale): void
    {
        $this->guard($label, $locale);

        $model = $this->models[$label]::sourceItems()->findOrFail($id);

        TranslateModel::dispatch($model, $locale);
    }

    public function translateAll(string $label, string $locale): void
    {
        $this->guard($label, $locale);

        foreach ($this->missing[$label][$locale] as $model) {
            TranslateModel::dispatch($model, $locale);
        }
    }

    public function render()
    {
        return view('livewire.translations.translations-overview');
    }

    /**
     * @return array<int, string>
     */
    protected function locales(): array
    {
        return app(GoogleTranslateService::class)->getTargetLocales();
    }

    /**
     * The label and the locale come from the browser, so check them against the lists.
     */
    protected function guard(string $label, string $locale): void
    {
        abort_unless(isset($this->models[$label]) && in_array($locale, $this->locales(), true), 404);
    }
}
```

`missing()` asks every model for its source rows without a translation, per locale from `GOOGLE_TRANSLATE_TARGET_LOCALES`. The arguments of `translate()` and `translateAll()` come from the browser, so `guard()` only accepts a label and a locale from your own lists, and `sourceItems()` makes sure a translation row is never translated again.

`getMissingTranslations()` runs one query per source row. For a large table, paginate the source rows yourself and call `hasTranslation()` per row.

## The view

`resources/views/livewire/translations/translations-overview.blade.php`:

{% raw %}
```blade
<div>
    @unless ($this->available)
        <p class="text-red-600">GOOGLE_TRANSLATE_API_KEY is not set, so nothing can be translated.</p>
    @endunless

    @foreach ($this->missing as $label => $locales)
        <h2>{{ $label }}</h2>

        @foreach ($locales as $locale => $items)
            <h3>
                {{ strtoupper($locale) }}: {{ $items->count() }} missing

                @if ($items->isNotEmpty())
                    <button wire:click="translateAll('{{ $label }}', '{{ $locale }}')" @disabled(! $this->available)>
                        Translate all
                    </button>
                @endif
            </h3>

            <ul>
                @foreach ($items as $item)
                    <li wire:key="{{ $label }}-{{ $locale }}-{{ $item->id }}">
                        #{{ $item->id }} {{ $item->title }}
                        <button wire:click="translate('{{ $label }}', {{ $item->id }}, '{{ $locale }}')" @disabled(! $this->available)>
                            Translate
                        </button>
                    </li>
                @endforeach
            </ul>
        @endforeach
    @endforeach
</div>
```
{% endraw %}

The list is computed when the component renders. A row disappears from it once the queue worker has created the translation and the page is rendered again.

## The route

`routes/web.php`:

```php
use App\Livewire\Translations\TranslationsOverview;
use Illuminate\Support\Facades\Route;

Route::get('/cms/translations', TranslationsOverview::class)->middleware(['auth']);
```

This form works in Livewire 3 and in Livewire 4. Livewire 4 recommends its own `Route::livewire('/cms/translations', TranslationsOverview::class)`, which does not exist in Livewire 3.

Put the route behind the authorisation of your CMS. `auth` (the [middleware](https://laravel.com/docs/middleware) that requires a logged in user) is the minimum; every click sends text to Google on your account.

## Translate on save

To translate new content without a click, dispatch the job from an [observer](https://laravel.com/docs/eloquent#observers) or from the place where the editor publishes:

```php
use App\Jobs\TranslateModel;
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;

if ($page->pid === null) {
    foreach (app(GoogleTranslateService::class)->getTargetLocales() as $locale) {
        TranslateModel::dispatch($page, $locale);
    }
}
```

The check on `pid` limits this to source rows. Without it, a saved translation is sent to Google as well, to fill the locales that are still missing, and you get a translation of a translation.
