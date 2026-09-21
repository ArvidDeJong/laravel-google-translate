---
title: CMS integration
nav_order: 6
description: "A Livewire screen for a CMS that lists the source rows without a translation per locale and translates them with one click, in a queued job."
---

# CMS integration

A common screen in a CMS: per model and per locale, which items still miss a translation, with a button to translate them. This page builds it with Livewire; the package itself does not need Livewire.

## The job

Translating takes a few hundred milliseconds per field, so the button dispatches a job instead of translating in the request.

```php
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
            // No API key, or every field failed. The reason is in the log.
            $this->fail('Translation to '.$this->locale.' failed for '.$this->model::class.' #'.$this->model->getKey());
        }
    }
}
```

## The component

```php
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
        $locales = app(GoogleTranslateService::class)->getTargetLocales();
        $missing = [];

        foreach ($this->models as $label => $model) {
            foreach ($locales as $locale) {
                $missing[$label][$locale] = $model::getMissingTranslations($locale);
            }
        }

        return $missing;
    }

    public function translate(string $label, int $id, string $locale): void
    {
        $model = $this->models[$label]::findOrFail($id);

        TranslateModel::dispatch($model, $locale);
    }

    public function translateAll(string $label, string $locale): void
    {
        foreach ($this->missing[$label][$locale] as $model) {
            TranslateModel::dispatch($model, $locale);
        }
    }

    public function render()
    {
        return view('livewire.translations.translations-overview');
    }
}
```

`getMissingTranslations()` runs one query per source row. That is fine for a few hundred rows; for more, paginate the source rows yourself.

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

## The route

```php
use App\Livewire\Translations\TranslationsOverview;

Route::get('/cms/translations', TranslationsOverview::class)->middleware(['auth']);
```

Put it behind your CMS authorisation: every click costs money.

## Translate on save

To translate new content without a click, dispatch the job from an observer or from the place where the editor publishes:

```php
foreach (app(GoogleTranslateService::class)->getTargetLocales() as $locale) {
    TranslateModel::dispatch($page, $locale);
}
```

Do this for source rows only (`$page->pid === null`), or a translation will translate itself again.
