---
title: "Troubleshooting"
nav_order: 8
description: "Symptom, cause and fix for darvis/laravel-google-translate: null instead of a translation, the literal log lines, a missing row, a missing pid and broken HTML."
---

# Troubleshooting

A failed API call never throws, so a problem shows up as `null`, an empty array or a missing row. Work through the first two sections in order: first the key, then the log.

## isAvailable() returns false

```php
app(\Darvis\LaravelGoogleTranslate\GoogleTranslateService::class)->isAvailable();   // false
```

**Cause.** The package sees no API key. Nothing is sent to Google and nothing is logged.

**Fix.**

1. Check that `.env` has `GOOGLE_TRANSLATE_API_KEY=...` with a value.
2. Run `php artisan config:clear`. With a cached config (`php artisan config:cache`) a change in `.env` is ignored until you clear or rebuild the cache.
3. If you published `config/google-translate.php`, check that `api_key` still reads `env('GOOGLE_TRANSLATE_API_KEY')`. A published file that is out of date wins over the package default.
4. In a long running process (a queue worker, Octane), restart it: `php artisan queue:restart`. The service reads the key once, when it is first used.

## translate() returns null and isAvailable() is true

**Cause.** One of three things: the input was empty, the call failed, or Google answered without a translation.

**Fix.** Look in your log (`storage/logs/laravel.log` by default) for a line at level `error` that starts with one of these prefixes:

```text
Google Translate failed: ...
Google Translate HTML failed: ...
Google Translate batch failed: ...
```

The first is written by `translate()`, the second by `translateHtml()`, the third by `translateBatch()`. After the prefix comes the response body from Google (JSON with an `error` object), or the message of the exception when Google could not be reached.

| What follows the prefix | Cause | Fix |
| --- | --- | --- |
| A JSON body that starts with `{"error":` | Google refused the request. The `code`, `status` and `message` fields in the body say why. | Read the `message`. The things to check on your side: the key is the right one and still exists, the Cloud Translation API is enabled for its project, billing is enabled, the restrictions on the key allow your server, and `source` and `target` are language codes from [Google's language list](https://cloud.google.com/translate/docs/languages), such as `en` and not `en_US`. |
| A JSON body with `"code": 403` | [Google's troubleshooting page](https://cloud.google.com/translate/troubleshooting) names a billing problem or a quota as the cause of an HTTP 403: the message is `Daily Limit Exceeded` for the daily limit and `User Rate Limit Exceeded` for the characters per minute quota. | Check billing and the quotas of the project. When you hit the per minute quota, translate in queued jobs and [rate limit the queue](https://laravel.com/docs/queues#rate-limiting). |
| `cURL error 28: ...` | Timeout. The package sets no timeout, so Laravel's default of 30 seconds applies. | Try again; the package does not retry. In a job, let the queue retry. |
| Another `cURL error ...` | The server cannot reach `translation.googleapis.com`, for example because of DNS, a firewall or a proxy. | Fix the outgoing connection of the server. |
| `Attempted request to [https://translation.googleapis.com/language/translate/v2] without a matching fake.` | You are in a test with `Http::preventStrayRequests()` and no fake for Google. | Add the fake, see [Testing](testing.md). |

**No line in the log at all.** Then no failure happened:

- The text was empty. Empty input returns `null` without a call.
- Google answered with HTTP 2xx but without a translation in the body. That returns `null` and logs nothing.
- Your log level hides `error` lines, or the log goes to another channel. Check `LOG_CHANNEL` and `LOG_LEVEL`.

The messages inside the JSON body are written by Google, not by this package, and can change. The prefixes are part of the package and stay the same within 1.x.

## createTranslation() returns null

**Cause and fix.**

- No API key: see [isAvailable() returns false](#isavailable-returns-false).
- None of the translatable fields has a value on the source row. Check `$translatableFields` against your columns: without that property the trait uses `title`, `content`, `description` and `excerpt`.
- Every field failed: see the log, as above.

## createTranslation() throws a MassAssignmentException

**Cause.** The model has no `$fillable` at all. The translation is stored with `create()`, and Laravel refuses that on a fully guarded model.

**Fix.** Add `pid`, `locale`, the translatable fields and everything you pass as additional attributes to `$fillable`:

```php
protected $fillable = ['pid', 'locale', 'title', 'content', 'slug'];
```

## The translation is created but has no pid, or a field is missing

**Cause.** The model has a `$fillable`, but `pid` or another attribute is not in it. Laravel drops attributes that are not fillable without an error, so the new row looks like a second source row.

**Fix.** Add the missing attribute to `$fillable`. To find this kind of mistake during development, call `Model::preventSilentlyDiscardingAttributes()` in the `boot()` method of `app/Providers/AppServiceProvider.php`; Laravel then throws instead of dropping the attribute.

## createTranslation() throws a QueryException about a missing value

**Cause.** A column without a default value, for example `slug` or `status`, is not among the translatable fields, so the insert has no value for it. The database reports a NOT NULL violation or a missing default value; the exact text depends on your database.

**Fix.** Pass it as an additional attribute:

```php
$page->createTranslation('en', ['slug' => 'about-us', 'status' => 'draft']);
```

## The database says the locale or pid column does not exist

**Cause.** The migration that adds `locale` and `pid` has not run. The package has no migration of its own; the columns are yours.

**Fix.** Add them as in the [quick start](quickstart.md) and run `php artisan migrate`.

## The HTML comes back broken

**Cause.** The text went through `translate()`, which sends it as plain text. For a model: the field is missing from `$htmlFields`.

**Fix.** Use `translateHtml()`, or add the field to `$htmlFields`. Without that property the trait treats `content` and `description` as HTML.

## fillMissingTranslations() reports an error

The `errors` list holds one or more of these texts:

| Error | Cause | Fix |
| --- | --- | --- |
| `Google Translate API not available` | No API key. | See [isAvailable() returns false](#isavailable-returns-false). |
| `Source translation not found` | There is no row in the source locale within this group of translations. | Pass the locale the source is actually written in: `$row->fillMissingTranslations('de')`. Before 1.1.0 this error also came back for every translation row; upgrade to 1.1.0 or later. |
| `Translation of 'content' failed` (with the name of your field) | The call for that field failed. | See the log, as above. |

## fillMissingTranslations() does not update a changed source

**Cause.** It only fills fields that are empty on the translation. A field that has a value is never overwritten.

**Fix.** Empty the field on the translation first, then call `fillMissingTranslations()` again.

## The same text is translated twice

**Cause.** `createTranslation()` and `fillMissingTranslations()` never send a field that is already translated, but loose calls to `translate()` are not cached.

**Fix.** Cache them yourself:

```php
use Illuminate\Support\Facades\Cache;

$translated = Cache::rememberForever(
    'translation.'.md5($text).'.'.$locale,
    fn () => $translator->translate($text, $locale),
);
```

A failed call returns `null`. `Cache::rememberForever()` treats a cached `null` as a miss and runs the closure again, so a failure is tried again the next time.

## A test gets null, or a test reaches Google

See [Testing](testing.md). In short: set `google-translate.api_key` to any value, call `Http::preventStrayRequests()` and fake `translation.googleapis.com/*`.

## Still stuck

Open an [issue](https://github.com/ArvidDeJong/laravel-google-translate/issues/new/choose) with the log line, without your API key.
