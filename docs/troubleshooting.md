---
title: Troubleshooting
nav_order: 7
description: "Why a translation comes back as null, how to read the log lines of darvis/laravel-google-translate, and the usual causes on the Google side."
---

# Troubleshooting

The package never throws, so a problem shows up as `null`, an empty array or a missing row. Start with the log.

## Nothing comes back

```php
$translator = app(\Darvis\LaravelGoogleTranslate\GoogleTranslateService::class);

$translator->isAvailable();   // false: there is no API key
```

**`isAvailable()` is false.** `GOOGLE_TRANSLATE_API_KEY` is missing or empty. After changing `.env`, run `php artisan config:clear`; with a cached config the old value stays in use.

**`isAvailable()` is true and the result is still `null`.** The call failed. Look for one of these lines in `storage/logs/laravel.log`:

```text
Google Translate failed: ...
Google Translate HTML failed: ...
Google Translate batch failed: ...
```

What follows the prefix is the response body from Google, or the message of the HTTP client.

| In the log | Cause |
| --- | --- |
| `API key not valid` | The key is wrong, or it was deleted in the Google Cloud console. |
| `Cloud Translation API has not been used in project ... or it is disabled` | Enable the Cloud Translation API for the project the key belongs to. |
| `Requests from referer ... are blocked` or `The provided API key has an IP address restriction` | The restriction on the key does not match your server. Use an IP restriction for server side calls, not an HTTP referrer. |
| `This API method requires billing to be enabled` | Enable billing on the Google Cloud project. |
| `User Rate Limit Exceeded` or HTTP 429 | You send too much at once. Translate in queued jobs and rate limit the queue. |
| `Invalid Value` for `target` or `source` | The locale is not a language code Google knows, for example `en_US` instead of `en`. |
| `cURL error 28` | Timeout. Try again; the package does not retry by itself. |

**The text is empty.** Empty input returns `null` without a call, and nothing is logged.

## The HTML comes back broken

The text went through `translate()` instead of `translateHtml()`. For a model, the field is missing from `$htmlFields`.

## createTranslation() returns null

- No API key: see above.
- None of the translatable fields has a value on the source row.
- Every field failed: see the log.

## createTranslation() throws a MassAssignmentException

The translation is made with `create()`. Add `pid`, `locale`, the translatable fields and everything you pass as additional attributes to `$fillable`.

## createTranslation() throws a database error about a missing value

A column without a default is not among the translatable fields. Pass it as an additional attribute:

```php
$page->createTranslation('en', ['slug' => 'about-us', 'status' => 'draft']);
```

## fillMissingTranslations() reports "Source translation not found"

There is no row in the source locale within this group of translations. Pass the locale the source is actually written in: `$row->fillMissingTranslations('de')`.

Before 1.1.0 this error also came back for every translation row, because the source row was not found from a translation. Upgrade to 1.1.0 or later.

## The same text is translated twice

`createTranslation()` and `fillMissingTranslations()` never send a field twice, but loose calls to `translate()` are not cached. Cache them yourself:

```php
$translated = Cache::rememberForever(
    'translation.'.md5($text).'.'.$locale,
    fn () => $translator->translate($text, $locale),
);
```

A failed call returns `null`, and the cache treats a stored `null` as a miss, so a failure is tried again next time.

## Tests call Google

Fake the HTTP client and block everything that is not faked:

```php
Http::preventStrayRequests();

Http::fake([
    'translation.googleapis.com/*' => Http::response([
        'data' => ['translations' => [['translatedText' => 'Hello']]],
    ]),
]);
```

Set `google-translate.api_key` to any value in the test; without a key nothing is sent and every result is `null`.

## Still stuck

Open an [issue](https://github.com/ArvidDeJong/laravel-google-translate/issues/new/choose) with the log line, without your API key.
