<?php

namespace Darvis\LaravelGoogleTranslate;

use Darvis\LaravelGoogleTranslate\Support\GoogleTranslateConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleTranslateService
{
    protected ?string $apiKey = null;

    protected string $baseUrl = 'https://translation.googleapis.com/language/translate/v2';

    public function __construct()
    {
        $this->apiKey = GoogleTranslateConfig::apiKey();
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
        return GoogleTranslateConfig::sourceLocale();
    }

    /**
     * Get the configured target locales
     *
     * @return array<int, string>
     */
    public function getTargetLocales(): array
    {
        return GoogleTranslateConfig::targetLocales();
    }

    /**
     * Translate a single text string
     */
    public function translate(string $text, string $targetLocale, ?string $sourceLocale = null): ?string
    {
        if (! $this->isAvailable() || empty($text)) {
            return null;
        }

        $translations = $this->request($text, $targetLocale, $sourceLocale, 'text', 'Google Translate failed');

        return $this->firstTranslation($translations);
    }

    /**
     * Translate HTML content while preserving tags
     */
    public function translateHtml(string $html, string $targetLocale, ?string $sourceLocale = null): ?string
    {
        if (! $this->isAvailable() || empty($html)) {
            return null;
        }

        $translations = $this->request($html, $targetLocale, $sourceLocale, 'html', 'Google Translate HTML failed');

        return $this->firstTranslation($translations);
    }

    /**
     * Translate multiple texts at once (batch)
     *
     * @param  array<int, string>  $texts
     * @return array<int, string>
     */
    public function translateBatch(array $texts, string $targetLocale, ?string $sourceLocale = null): array
    {
        if (! $this->isAvailable() || empty($texts)) {
            return [];
        }

        return $this->request(array_values($texts), $targetLocale, $sourceLocale, 'text', 'Google Translate batch failed') ?? [];
    }

    /**
     * Translate an array of fields, using HTML mode for specified fields
     *
     * @param  array<string, mixed>  $fields
     * @param  array<int, string>  $htmlFields
     * @return array<string, string>
     */
    public function translateFields(array $fields, string $targetLocale, ?string $sourceLocale = null, array $htmlFields = []): array
    {
        $translated = [];

        foreach ($fields as $field => $value) {
            if (empty($value)) {
                continue;
            }

            if (in_array($field, $htmlFields)) {
                $translated[$field] = $this->translateHtml((string) $value, $targetLocale, $sourceLocale);
            } else {
                $translated[$field] = $this->translate((string) $value, $targetLocale, $sourceLocale);
            }
        }

        return array_filter($translated);
    }

    /**
     * The first translated text, or null when the call failed or came back without one.
     *
     * @param  array<int, string>|null  $translations
     */
    protected function firstTranslation(?array $translations): ?string
    {
        $first = $translations[0] ?? '';

        return $first === '' ? null : $first;
    }

    /**
     * Post one request to the Translation API and return the translated texts in order, or null
     * when the call failed. A failure is logged under the given prefix and never thrown.
     *
     * The key travels in the X-goog-api-key header, not in the URL: an HTTP client exception
     * quotes the URL, and that message ends up in the log.
     *
     * @param  string|array<int, string>  $query
     * @return array<int, string>|null
     */
    protected function request(string|array $query, string $targetLocale, ?string $sourceLocale, string $format, string $logPrefix): ?array
    {
        try {
            $response = Http::asJson()
                ->acceptJson()
                ->withHeaders(['X-goog-api-key' => (string) $this->apiKey])
                ->post($this->baseUrl, [
                    'q' => $query,
                    'source' => $sourceLocale ?? $this->getSourceLocale(),
                    'target' => $targetLocale,
                    'format' => $format,
                ]);

            if ($response->successful()) {
                $translations = $response->json('data.translations');

                if (! is_array($translations)) {
                    return [];
                }

                return array_values(array_map(
                    fn ($translation): string => (string) ($translation['translatedText'] ?? ''),
                    $translations,
                ));
            }

            Log::error($logPrefix.': '.$response->body());

            return null;
        } catch (\Exception $e) {
            Log::error($logPrefix.': '.$e->getMessage());

            return null;
        }
    }
}
