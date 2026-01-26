<?php

namespace Darvis\LaravelGoogleTranslate\Traits;

use Darvis\LaravelGoogleTranslate\GoogleTranslateService;

/**
 * Trait for Eloquent models that support translations via pid column
 *
 * Requirements:
 * - Model must have 'locale' column
 * - Model must have 'pid' column (parent id for translations)
 * - Model must define $translatableFields array
 * - Model must define $htmlFields array (optional, for HTML content)
 */
trait HasGoogleTranslate
{
    /**
     * Get the translatable fields for this model
     * Override this in your model to customize
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

        $parentId = $this->pid ?? $this->id;

        return static::where('pid', $parentId)
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

        $parentId = $this->pid ?? $this->id;

        return static::where('pid', $parentId)
            ->where('locale', $locale)
            ->first();
    }

    /**
     * Get all translations for this model (including self)
     */
    public function getAllTranslations(): \Illuminate\Database\Eloquent\Collection
    {
        $parentId = $this->pid ?? $this->id;

        return static::where(function ($query) use ($parentId) {
            $query->where('id', $parentId)
                ->orWhere('pid', $parentId);
        })->get();
    }

    /**
     * Create a new translation using Google Translate
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

        return static::create(array_merge(
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
     */
    public function scopeSourceItems($query)
    {
        return $query->whereNull('pid');
    }

    /**
     * Scope to get items in specific locale
     */
    public function scopeLocalized($query, ?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        return $query->where('locale', $locale);
    }

    /**
     * Get items missing translation for given locale
     */
    public static function getMissingTranslations(string $targetLocale, ?string $sourceLocale = null): \Illuminate\Database\Eloquent\Collection
    {
        $sourceLocale = $sourceLocale ?? config('google-translate.source_locale', 'nl');

        // Get all source items
        $sourceItems = static::where('locale', $sourceLocale)
            ->whereNull('pid')
            ->get();

        // Filter to those without translation
        return $sourceItems->filter(function ($item) use ($targetLocale) {
            return ! $item->hasTranslation($targetLocale);
        });
    }
}
