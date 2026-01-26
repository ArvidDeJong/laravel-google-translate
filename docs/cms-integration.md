# CMS Integration Guide

This guide shows how to build a complete Translation Check Dashboard for your CMS using Livewire and FluxUI.

## Overview

A Translation Check Dashboard provides:
- Overview of missing translations per module
- Bulk translation functionality
- Individual item translation
- API status monitoring

## Prerequisites

- Livewire 4.x
- FluxUI (optional, for styling)
- All models must have the `HasGoogleTranslate` trait

## Step 1: Add Trait to All Models

Every model that needs translation support must use the `HasGoogleTranslate` trait:

```php
<?php

namespace App\Models;

use Darvis\LivewireGoogleTranslate\Traits\HasGoogleTranslate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasGoogleTranslate, SoftDeletes;

    protected array $translatableFields = [
        'title',
        'title_2',
        'title_3',
        'seo_title',
        'seo_description',
        'summary',
        'excerpt',
        'content',
    ];

    protected array $htmlFields = [
        'content',
        'summary',
    ];

    protected $fillable = [
        'pid',
        'locale',
        'active',
        'title',
        'content',
        // ... other fields
    ];
}
```

### Common Model Configurations

**Content Pages:**
```php
protected array $translatableFields = [
    'title',
    'seo_title',
    'seo_description',
    'excerpt',
    'content',
    'description',
];

protected array $htmlFields = [
    'content',
    'description',
];
```

**Simple Items (Categories, Tags):**
```php
protected array $translatableFields = [
    'title',
    'description',
];

protected array $htmlFields = [
    'description',
];
```

**SEO-only Items:**
```php
protected array $translatableFields = [
    'title',
    'seo_title',
    'seo_description',
];

protected array $htmlFields = [];
```

## Step 2: Create the Translation Check Component

```php
<?php

namespace App\Livewire\Translation;

use App\Models\Page;
use App\Models\Project;
use App\Models\Knowledgebase;
// ... import all your translatable models
use Darvis\LivewireGoogleTranslate\GoogleTranslateService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.cms')]
class TranslationCheck extends Component
{
    public string $sourceLocale;
    public array $targetLocales;
    public bool $isTranslating = false;

    public function mount()
    {
        $service = app(GoogleTranslateService::class);
        $this->sourceLocale = $service->getSourceLocale();
        $this->targetLocales = $service->getTargetLocales();
    }

    /**
     * Define all translatable models with their configuration
     */
    protected array $translatableModels = [
        'Page' => [
            'model' => Page::class,
            'label' => 'Pages',
            'route' => 'cms.pages.edit',
            'titleField' => 'title',
        ],
        'Project' => [
            'model' => Project::class,
            'label' => 'Projects',
            'route' => 'cms.projects.edit',
            'titleField' => 'title',
        ],
        // Add more models as needed
    ];

    /**
     * Check if Google Translate API is available
     */
    #[Computed]
    public function apiAvailable(): bool
    {
        return app(GoogleTranslateService::class)->isAvailable();
    }

    /**
     * Get translation status for all modules
     */
    #[Computed]
    public function translationStatus(): array
    {
        $status = [];

        foreach ($this->translatableModels as $key => $config) {
            $modelClass = $config['model'];

            // Get all source items (no pid = original items)
            $sourceItems = $modelClass::where('locale', $this->sourceLocale)
                ->whereNull('pid')
                ->get();

            $totalSource = $sourceItems->count();
            $missingByLocale = [];

            foreach ($this->targetLocales as $targetLocale) {
                $missing = [];

                foreach ($sourceItems as $item) {
                    $hasTranslation = $modelClass::where('pid', $item->id)
                        ->where('locale', $targetLocale)
                        ->exists();

                    if (!$hasTranslation) {
                        $missing[] = [
                            'id' => $item->id,
                            'title' => $item->{$config['titleField']} ?? 'No title',
                            'route' => $config['route'],
                        ];
                    }
                }

                $missingByLocale[$targetLocale] = $missing;
            }

            $status[$key] = [
                'label' => $config['label'],
                'totalSource' => $totalSource,
                'missingByLocale' => $missingByLocale,
            ];
        }

        return $status;
    }

    /**
     * Get total missing translations per locale
     */
    #[Computed]
    public function totalMissing(): array
    {
        $totals = [];

        foreach ($this->targetLocales as $locale) {
            $totals[$locale] = 0;
            foreach ($this->translationStatus as $module) {
                $totals[$locale] += count($module['missingByLocale'][$locale] ?? []);
            }
        }

        return $totals;
    }

    /**
     * Translate a single item
     */
    public function translateItem(string $moduleKey, int $itemId, string $targetLocale): void
    {
        if (!$this->apiAvailable) {
            Flux::toast('Google Translate API not configured', variant: 'danger');
            return;
        }

        $config = $this->translatableModels[$moduleKey] ?? null;
        if (!$config) {
            Flux::toast('Module not found', variant: 'danger');
            return;
        }

        $modelClass = $config['model'];
        $sourceItem = $modelClass::find($itemId);

        if (!$sourceItem || !method_exists($sourceItem, 'createTranslation')) {
            Flux::toast('Item not found or does not support translation', variant: 'danger');
            return;
        }

        $translation = $sourceItem->createTranslation($targetLocale, [
            'active' => false, // Review before publishing
        ]);

        if ($translation) {
            Flux::toast("Translation created for {$sourceItem->{$config['titleField']}}", variant: 'success');
            unset($this->translationStatus);
            unset($this->totalMissing);
        } else {
            Flux::toast('Translation failed', variant: 'danger');
        }
    }

    /**
     * Translate all missing items for a module
     */
    public function translateAllForModule(string $moduleKey, string $targetLocale): void
    {
        if (!$this->apiAvailable) {
            Flux::toast('Google Translate API not configured', variant: 'danger');
            return;
        }

        $config = $this->translatableModels[$moduleKey] ?? null;
        if (!$config) return;

        $this->isTranslating = true;
        $modelClass = $config['model'];
        
        $sourceItems = $modelClass::where('locale', $this->sourceLocale)
            ->whereNull('pid')
            ->get();

        $translated = 0;
        $failed = 0;

        foreach ($sourceItems as $sourceItem) {
            $hasTranslation = $modelClass::where('pid', $sourceItem->id)
                ->where('locale', $targetLocale)
                ->exists();

            if ($hasTranslation) continue;

            if (!method_exists($sourceItem, 'createTranslation')) {
                $failed++;
                continue;
            }

            $translation = $sourceItem->createTranslation($targetLocale, [
                'active' => false,
            ]);

            $translation ? $translated++ : $failed++;
        }

        $this->isTranslating = false;
        unset($this->translationStatus);
        unset($this->totalMissing);

        if ($translated > 0) {
            Flux::toast("{$translated} translations created" . ($failed > 0 ? ", {$failed} failed" : ''), variant: 'success');
        } elseif ($failed > 0) {
            Flux::toast("{$failed} translations failed", variant: 'danger');
        }
    }

    public function render()
    {
        return view('livewire.translation.translation-check');
    }
}
```

## Step 3: Create the Blade View

```blade
<div class="space-y-6">
    {{-- API Status Warning --}}
    @if(!$this->apiAvailable)
        <div class="rounded-lg border border-amber-500 bg-amber-50 p-4">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h3 class="font-medium">Google Translate API not configured</h3>
                    <p class="text-sm">Add <code>GOOGLE_TRANSLATE_API_KEY</code> to your .env file.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Summary --}}
    <div class="rounded-lg border p-6">
        <h2 class="mb-4 text-lg font-semibold">Summary</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($this->targetLocales as $locale)
                <div class="rounded-lg border p-4">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">{{ strtoupper($locale) }}</span>
                        @if($this->totalMissing[$locale] > 0)
                            <span class="rounded bg-red-100 px-2 py-1 text-sm text-red-800">
                                {{ $this->totalMissing[$locale] }} missing
                            </span>
                        @else
                            <span class="rounded bg-green-100 px-2 py-1 text-sm text-green-800">
                                Complete
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Per Module --}}
    @foreach($this->translationStatus as $moduleKey => $module)
        <div class="rounded-lg border p-6">
            <h3 class="mb-4 text-lg font-semibold">{{ $module['label'] }}</h3>
            <p class="mb-4 text-sm text-gray-500">
                {{ $module['totalSource'] }} items in {{ strtoupper($sourceLocale) }}
            </p>

            @foreach($this->targetLocales as $locale)
                @php
                    $missingItems = $module['missingByLocale'][$locale] ?? [];
                    $missingCount = count($missingItems);
                @endphp

                <div class="mb-4">
                    <div class="mb-2 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ strtoupper($locale) }}</span>
                            @if($this->apiAvailable && $missingCount > 0)
                                <button 
                                    wire:click="translateAllForModule('{{ $moduleKey }}', '{{ $locale }}')"
                                    wire:loading.attr="disabled"
                                    class="rounded bg-purple-600 px-3 py-1 text-sm text-white hover:bg-purple-700">
                                    Translate All
                                </button>
                            @endif
                        </div>
                        <span class="text-sm">
                            {{ $module['totalSource'] - $missingCount }}/{{ $module['totalSource'] }} translated
                        </span>
                    </div>

                    @if($missingCount > 0)
                        <div class="rounded border">
                            <table class="w-full text-sm">
                                <tbody>
                                    @foreach($missingItems as $item)
                                        <tr class="border-b last:border-0">
                                            <td class="px-3 py-2 text-gray-500">#{{ $item['id'] }}</td>
                                            <td class="px-3 py-2">{{ $item['title'] }}</td>
                                            <td class="px-3 py-2 text-right">
                                                @if($this->apiAvailable)
                                                    <button 
                                                        wire:click="translateItem('{{ $moduleKey }}', {{ $item['id'] }}, '{{ $locale }}')"
                                                        class="rounded bg-purple-600 px-2 py-1 text-white hover:bg-purple-700">
                                                        Translate
                                                    </button>
                                                @endif
                                                <a href="{{ route($item['route'], $item['id']) }}" 
                                                   class="rounded bg-yellow-600 px-2 py-1 text-white hover:bg-yellow-700">
                                                    Edit
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach
</div>
```

## Step 4: Add Route

```php
// routes/cms.php
Route::livewire('/translations', 'translation.translation-check')
    ->name('translations.check');
```

## Important Notes

### 1. New Translations are Inactive

Translations are created with `active => false` by default. This allows content editors to review before publishing:

```php
$translation = $sourceItem->createTranslation($targetLocale, [
    'active' => false, // Always review before publishing
]);
```

### 2. Clear Computed Cache After Changes

After creating translations, clear the computed property cache:

```php
unset($this->translationStatus);
unset($this->totalMissing);
```

### 3. Check for Trait Before Translating

Always verify the model has the trait:

```php
if (!method_exists($sourceItem, 'createTranslation')) {
    // Model doesn't have HasGoogleTranslate trait
    return;
}
```

### 4. Handle API Failures Gracefully

The `createTranslation()` method returns `null` if translation fails:

```php
$translation = $sourceItem->createTranslation($targetLocale);

if ($translation) {
    // Success
} else {
    // Failed - API error, empty content, etc.
}
```

## Performance Tips

### 1. Use Queues for Bulk Translation

For large datasets, dispatch jobs instead of translating synchronously:

```php
foreach ($sourceItems as $item) {
    TranslateItemJob::dispatch($item, $targetLocale);
}
```

### 2. Batch API Calls

The trait already uses `translateFields()` which batches field translations, but for multiple items consider using the batch API directly.

### 3. Cache Translation Status

For large datasets, cache the translation status:

```php
#[Computed]
public function translationStatus(): array
{
    return Cache::remember('translation-status', 300, function () {
        // ... compute status
    });
}
```

## Troubleshooting

### "All translations failed"

1. Check if models have the `HasGoogleTranslate` trait
2. Verify `$translatableFields` is defined
3. Check API key is valid
4. Check source items have content to translate

### Translations are empty

1. Verify `$translatableFields` includes the correct fields
2. Check source items have non-empty content
3. Review Google Translate API response in logs

### API quota exceeded

1. Implement rate limiting
2. Use queues with delays
3. Cache translations
4. Consider batch operations

## Next Steps

- [API Reference](api-reference.md) - Complete method documentation
- [Troubleshooting](troubleshooting.md) - Common issues and solutions
