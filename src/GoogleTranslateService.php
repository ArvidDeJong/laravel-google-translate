<?php

namespace Darvis\LaravelGoogleTranslate;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleTranslateService
{
    protected ?string $apiKey = null;

    protected string $baseUrl = 'https://translation.googleapis.com/language/translate/v2';

    public function __construct()
    {
        $this->apiKey = config('google-translate.api_key');
    }

    /**
     * Check if translation service is available
     */
    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Get the configured source locale
     */
    public function getSourceLocale(): string
    {
        return config('google-translate.source_locale', 'nl');
    }

    /**
     * Get the configured target locales
     */
    public function getTargetLocales(): array
    {
        return config('google-translate.target_locales', ['en']);
    }

    /**
     * Translate a single text string
     */
    public function translate(string $text, string $targetLocale, ?string $sourceLocale = null): ?string
    {
        if (! $this->isAvailable() || empty($text)) {
            return null;
        }

        $sourceLocale = $sourceLocale ?? $this->getSourceLocale();

        try {
            $response = Http::asForm()->post($this->baseUrl.'?key='.$this->apiKey, [
                'q' => $text,
                'source' => $sourceLocale,
                'target' => $targetLocale,
                'format' => 'text',
            ]);

            if ($response->successful()) {
                return $response->json('data.translations.0.translatedText');
            }

            Log::error('Google Translate failed: '.$response->body());

            return null;
        } catch (\Exception $e) {
            Log::error('Google Translate failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Translate HTML content while preserving tags
     */
    public function translateHtml(string $html, string $targetLocale, ?string $sourceLocale = null): ?string
    {
        if (! $this->isAvailable() || empty($html)) {
            return null;
        }

        $sourceLocale = $sourceLocale ?? $this->getSourceLocale();

        try {
            $response = Http::asForm()->post($this->baseUrl.'?key='.$this->apiKey, [
                'q' => $html,
                'source' => $sourceLocale,
                'target' => $targetLocale,
                'format' => 'html',
            ]);

            if ($response->successful()) {
                return $response->json('data.translations.0.translatedText');
            }

            Log::error('Google Translate HTML failed: '.$response->body());

            return null;
        } catch (\Exception $e) {
            Log::error('Google Translate HTML failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Translate multiple texts at once (batch)
     */
    public function translateBatch(array $texts, string $targetLocale, ?string $sourceLocale = null): array
    {
        if (! $this->isAvailable() || empty($texts)) {
            return [];
        }

        $sourceLocale = $sourceLocale ?? $this->getSourceLocale();

        try {
            $response = Http::asForm()->post($this->baseUrl.'?key='.$this->apiKey, [
                'q' => $texts,
                'source' => $sourceLocale,
                'target' => $targetLocale,
                'format' => 'text',
            ]);

            if ($response->successful()) {
                $translations = $response->json('data.translations');

                return array_map(fn ($t) => $t['translatedText'], $translations);
            }

            Log::error('Google Translate batch failed: '.$response->body());

            return [];
        } catch (\Exception $e) {
            Log::error('Google Translate batch failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Translate an array of fields, using HTML mode for specified fields
     */
    public function translateFields(array $fields, string $targetLocale, ?string $sourceLocale = null, array $htmlFields = []): array
    {
        $translated = [];

        foreach ($fields as $field => $value) {
            if (empty($value)) {
                continue;
            }

            if (in_array($field, $htmlFields)) {
                $translated[$field] = $this->translateHtml($value, $targetLocale, $sourceLocale);
            } else {
                $translated[$field] = $this->translate($value, $targetLocale, $sourceLocale);
            }
        }

        return array_filter($translated);
    }
}
