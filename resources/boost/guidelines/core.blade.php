## darvis/laravel-google-translate

Translates text, HTML and Eloquent models with the Google Cloud Translation API. Translations of a model are rows in the same table: the source row has no `pid`, a translation has the id of the source row in `pid` and its own `locale`.

- Resolve `Darvis\LaravelGoogleTranslate\GoogleTranslateService` from the container (it is a singleton, alias `google-translate`). Don't call the Translation API yourself.
- `translate($text, $targetLocale, $sourceLocale = null)` for plain text, `translateHtml()` for anything with tags, `translateBatch(array $texts, ...)` for many strings in one request, `translateFields(array $fields, $target, $source, array $htmlFields)` for an array of fields.
- **Nothing throws.** A failed call is logged (`Google Translate failed: ...`) and returns `null`, or `[]` for a batch. Always check the return value; don't wrap calls in try/catch expecting an exception.
- Without `GOOGLE_TRANSLATE_API_KEY` every method returns `null` or `[]` without calling Google. `isAvailable()` only tells you a key is set, not that it is valid.
- Models use the `Darvis\LaravelGoogleTranslate\Traits\HasGoogleTranslate` trait and need a `locale` column and a nullable `pid` column. List the fields in `$translatableFields`, and the ones with HTML in `$htmlFields`.
- `$model->createTranslation('en', ['slug' => 'about-us'])` returns the new row, the existing row when that locale already exists, or `null`. It uses `create()`, so `pid`, `locale`, the translatable fields and the additional attributes must be fillable, and every column without a default must be passed as an additional attribute.
- `$translation->fillMissingTranslations()` fills only the empty fields and returns `['translated' => [...], 'errors' => [...]]`.
- `Model::getMissingTranslations('en')` runs one query per source row; paginate for large tables. Scopes: `sourceItems()` and `localized($locale = null)`.
- Every call costs money per character. Translate in a queued job, and only for source rows (`pid` is null), or a translation translates itself again.
- Read settings through `Darvis\LaravelGoogleTranslate\Support\GoogleTranslateConfig` (`apiKey()`, `sourceLocale()`, `targetLocales()`), not with `config()`.
- In tests, never call Google: `Http::preventStrayRequests()`, fake `translation.googleapis.com/*` and set `google-translate.api_key` to any value.

@verbatim
<code-snippet name="Translate a page into every target locale in a queued job" lang="php">
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;

public function handle(GoogleTranslateService $translator): void
{
    foreach ($translator->getTargetLocales() as $locale) {
        $translation = $this->page->createTranslation($locale, [
            'slug' => $this->page->slug.'-'.$locale,
        ]);

        if ($translation === null) {
            // No API key, or every field failed. The reason is in the log.
            report(new RuntimeException("Translating page {$this->page->id} to {$locale} failed."));
        }
    }
}
</code-snippet>
@endverbatim
