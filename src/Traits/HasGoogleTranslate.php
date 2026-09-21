<?php

namespace Darvis\LaravelGoogleTranslate\Traits;

use Darvis\LaravelGoogleTranslate\GoogleTranslateService;
use Darvis\LaravelGoogleTranslate\Support\GoogleTranslateConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait for Eloquent models that support translations via pid column
 *
 * Requirements:
 * - Model must have 'locale' column
 * - Model must have 'pid' column (parent id for translations)
 * - Model must define $translatableFields array
 * - Model must define $htmlFields array (optional, for HTML content)
 *
 * @phpstan-require-extends Model
 *
 * @property int|string $id
 * @property int|string|null $pid
 * @property string $locale
 */
trait HasGoogleTranslate
{
    /**
     * Get the translatable fields for this model
     * Override this in your model to customize
     *
     * @return array<int, string>
     */
    public function getTranslatableFields(): array
    {
        return $this->translatableFields ?? [
            'title',
            'content',
            'description',
            'excerpt',
        ];
    }

    /**
     * Get the HTML fields that should use HTML translation mode
     * Override this in your model to customize
     *
     * @return array<int, string>
     */
    public function getHtmlFields(): array
    {
        return $this->htmlFields ?? [
            'content',
            'description',
        ];
    }

    /**
     * Check if this model has a translation in the given locale
     */
    public function hasTranslation(string $locale): bool
    {
        if ($this->locale === $locale) {
            return true;
        }

        return $this->translationGroup()
            ->where('locale', $locale)
            ->exists();
    }

    /**
     * Get the translation for the given locale
     */
    public function getTranslation(string $locale): ?static
    {
        if ($this->locale === $locale) {
            return $this;
        }

        return $this->translationGroup()
            ->where('locale', $locale)
            ->first();
    }

    /**
     * Get all translations for this model (including self)
     *
     * @return Collection<int, static>
     */
    public function getAllTranslations(): Collection
    {
        return $this->translationGroup()->get();
    }

    /**
     * Query for the source row and every translation of it. The source row has no pid, so a
     * lookup on pid alone never finds it from one of its translations.
     *
     * @return Builder<static>
     */
    protected function translationGroup()
    {
        $parentId = $this->pid ?? $this->id;

        return static::query()->where(function ($query) use ($parentId) {
            $query->where('id', $parentId)
                ->orWhere('pid', $parentId);
        });
    }

    /**
     * Create a new translation using Google Translate
     *
     * @param  array<string, mixed>  $additionalAttributes
     */
    public function createTranslation(string $targetLocale, array $additionalAttributes = []): ?static
    {
        $service = app(GoogleTranslateService::class);

        if (! $service->isAvailable()) {
            return null;
        }

        if ($this->hasTranslation($targetLocale)) {
            return $this->getTranslation($targetLocale);
        }

        // Get source data
        $sourceData = $this->only($this->getTranslatableFields());

        // Translate fields
        $translatedData = $service->translateFields(
            $sourceData,
            $targetLocale,
            $this->locale,
            $this->getHtmlFields()
        );

        if (empty($translatedData)) {
            return null;
        }

        // Merge with additional attributes and create
        $parentId = $this->pid ?? $this->id;

        return static::query()->create(array_merge(
            $additionalAttributes,
            $translatedData,
            [
                'pid' => $parentId,
                'locale' => $targetLocale,
            ]
        ));
    }

    /**
     * Fill empty translatable fields from source translation
     *
     * @return array{translated: array<int, string>, errors: array<int, string>}
     */
    public function fillMissingTranslations(?string $sourceLocale = null): array
    {
        $service = app(GoogleTranslateService::class);

        if (! $service->isAvailable()) {
            return ['translated' => [], 'errors' => ['Google Translate API not available']];
        }

        $sourceLocale = $sourceLocale ?? $service->getSourceLocale();

        // Get source model
        $sourceModel = $this->locale === $sourceLocale ? $this : $this->getTranslation($sourceLocale);

        if (! $sourceModel) {
            return ['translated' => [], 'errors' => ['Source translation not found']];
        }

        $translated = [];
        $errors = [];

        foreach ($this->getTranslatableFields() as $field) {
            // Skip if target already has content
            if (! empty($this->$field)) {
                continue;
            }

            // Skip if source has no content
            if (empty($sourceModel->$field)) {
                continue;
            }

            // Translate the field
            $value = $sourceModel->$field;
            $isHtmlField = in_array($field, $this->getHtmlFields());

            $translatedValue = $isHtmlField
                ? $service->translateHtml($value, $this->locale, $sourceLocale)
                : $service->translate($value, $this->locale, $sourceLocale);

            if ($translatedValue) {
                $this->$field = $translatedValue;
                $translated[] = $field;
            } else {
                $errors[] = "Translation of '{$field}' failed";
            }
        }

        if (! empty($translated)) {
            $this->save();
        }

        return ['translated' => $translated, 'errors' => $errors];
    }

    /**
     * Scope to get only source items (no pid)
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSourceItems($query)
    {
        return $query->whereNull('pid');
    }

    /**
     * Scope to get items in specific locale
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeLocalized($query, ?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return $query->where('locale', $locale);
    }

    /**
     * Get items missing translation for given locale
     *
     * @return Collection<int, static>
     */
    public static function getMissingTranslations(string $targetLocale, ?string $sourceLocale = null): Collection
    {
        $sourceLocale = $sourceLocale ?? GoogleTranslateConfig::sourceLocale();

        // Get all source items
        $sourceItems = static::query()->where('locale', $sourceLocale)
            ->whereNull('pid')
            ->get();

        // Filter to those without translation
        return $sourceItems->filter(function ($item) use ($targetLocale) {
            return ! $item->hasTranslation($targetLocale);
        });
    }
}
