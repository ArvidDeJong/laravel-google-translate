---
title: "Testing"
nav_order: 7
description: "Test Laravel code that uses darvis/laravel-google-translate without calling Google: fake the HTTP client, set a test key and assert on rows and requests."
---

# Testing

Your tests must never reach Google: a real call costs money, needs a key and makes the suite slow. The package sends every request through Laravel's HTTP client, so you replace Google with [`Http::fake()`](https://laravel.com/docs/http-client#testing). The package has no fake of its own.

## The three things every test needs

1. **`Http::preventStrayRequests()`** makes every request that is not faked throw, so nothing leaves your machine.
2. **An API key**, any value. Without a key the package sends nothing and returns `null`, so your fake is never used.
3. **A fake for `translation.googleapis.com/*`** that answers in the shape of Google's response: `data.translations`, a list with one `translatedText` per text you sent.

## A complete example

This test uses the `Page` model from the [quick start](quickstart.md). It is written for [Pest](https://pestphp.com); the `Http::` and `config()` calls are the same in a PHPUnit test class.

`tests/Feature/TranslatePageTest.php`:

```php
<?php

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Any request that is not faked throws, so no test can reach Google.
    Http::preventStrayRequests();

    // Without a key the package sends nothing and returns null.
    config(['google-translate.api_key' => 'test-key']);
});

it('creates an English row for a Dutch page', function () {
    // Every text comes back as "[en] text", so the assertions do not depend on Google.
    Http::fake([
        'translation.googleapis.com/*' => function ($request) {
            $texts = (array) $request['q'];

            return Http::response(['data' => ['translations' => array_map(
                fn ($text) => ['translatedText' => "[{$request['target']}] {$text}"],
                $texts,
            )]]);
        },
    ]);

    $page = Page::create(['locale' => 'nl', 'title' => 'Over ons', 'content' => '<p>Wij zijn...</p>', 'slug' => 'over-ons']);

    $english = $page->createTranslation('en', ['slug' => 'about-us']);

    expect($english->pid)->toBe($page->id)
        ->and($english->locale)->toBe('en')
        ->and($english->title)->toBe('[en] Over ons')
        ->and($english->slug)->toBe('about-us');

    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => $request['q'] === '<p>Wij zijn...</p>' && $request['format'] === 'html');
});

it('creates no row when Google refuses the call', function () {
    Http::fake([
        'translation.googleapis.com/*' => Http::response(['error' => ['message' => 'denied']], 403),
    ]);

    $page = Page::create(['locale' => 'nl', 'title' => 'Over ons', 'slug' => 'over-ons']);

    expect($page->createTranslation('en', ['slug' => 'about-us']))->toBeNull()
        ->and(Page::count())->toBe(1);
});
```

The first test proves that your model is set up correctly: the translated row gets the right `pid`, `locale` and slug, and `content` is sent as HTML. Two requests are sent, one per translatable field. The second test proves that your code survives a refusal: the result is `null` and no row is added. Assert on `null`, not on an exception, because a failed API call never throws.

## A fixed answer is enough for one string

```php
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;
use Illuminate\Support\Facades\Http;

Http::fake([
    'translation.googleapis.com/*' => Http::response([
        'data' => ['translations' => [['translatedText' => 'Hello']]],
    ]),
]);

expect(app(GoogleTranslateService::class)->translate('Hallo', 'en'))->toBe('Hello');
```

For `translateBatch()` the fake has to return as many `translatedText` entries as you send texts. The closure in the complete example does that for you.

## What the request looks like

Inside `Http::assertSent()` you can check everything the package sends:

| Part | Value |
| --- | --- |
| URL | `https://translation.googleapis.com/language/translate/v2` |
| Method | `POST`, JSON body |
| Header | `X-goog-api-key` with the API key |
| `$request['q']` | the text, or a list of texts for a batch |
| `$request['source']` | the source locale |
| `$request['target']` | the target locale |
| `$request['format']` | `text` or `html` |

## A forgotten fake does not fail the test by itself

`Http::preventStrayRequests()` throws when a request is not faked, but the package catches that exception like every other failure. The request is still blocked, so it costs nothing. What you see is a `null` result and this line in the log:

```text
Google Translate failed: Attempted request to [https://translation.googleapis.com/language/translate/v2] without a matching fake.
```

So when a test gets `null` where you expected a translation, check the fake first.

## The key is read once

The service is a singleton and reads the key when it is first resolved. Set `google-translate.api_key` before your code touches the service, as `beforeEach()` does above. When you change the key later in the same test, call `app()->forgetInstance(GoogleTranslateService::class)` afterwards.

You can also set the key for the whole suite in `phpunit.xml`:

```xml
<env name="GOOGLE_TRANSLATE_API_KEY" value="test-key"/>
```

## Test that a job is dispatched, not what it translates

For code that dispatches a translation job, fake the queue and assert on the job. No HTTP fake is needed, because the job never runs:

```php
use App\Jobs\TranslatePage;
use Illuminate\Support\Facades\Queue;

Queue::fake();

TranslatePage::dispatch($page);

Queue::assertPushed(TranslatePage::class);
```
