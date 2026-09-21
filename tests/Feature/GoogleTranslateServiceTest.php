<?php

use Darvis\LaravelGoogleTranslate\GoogleTranslateService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->service = new GoogleTranslateService;
});

it('is available with an api key and not without one', function () {
    expect($this->service->isAvailable())->toBeTrue();

    config(['google-translate.api_key' => '']);

    expect((new GoogleTranslateService)->isAvailable())->toBeFalse();
});

it('returns the locales from the config', function () {
    config(['google-translate.target_locales' => ['en', 'de', 'fr']]);

    expect($this->service->getSourceLocale())->toBe('nl')
        ->and($this->service->getTargetLocales())->toBe(['en', 'de', 'fr']);
});

it('translates text', function () {
    $this->fakeGoogle();

    expect($this->service->translate('Hallo wereld', 'en'))->toBe('[en] Hallo wereld');

    Http::assertSent(fn ($request) => $request['q'] === 'Hallo wereld'
        && $request['source'] === 'nl'
        && $request['target'] === 'en'
        && $request['format'] === 'text');
});

it('translates html in html mode', function () {
    $this->fakeGoogle();

    expect($this->service->translateHtml('<p>Hallo</p>', 'en', 'de'))->toBe('[en] <p>Hallo</p>');

    Http::assertSent(fn ($request) => $request['format'] === 'html' && $request['source'] === 'de');
});

it('translates a batch in one request and keeps the order', function () {
    $this->fakeGoogle();

    $results = $this->service->translateBatch(['Hallo', 'Wereld', 'Welkom'], 'en');

    expect($results)->toBe(['[en] Hallo', '[en] Wereld', '[en] Welkom']);

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->isJson() && $request['q'] === ['Hallo', 'Wereld', 'Welkom']);
});

it('translates fields, the html fields in html mode, and skips the empty ones', function () {
    $this->fakeGoogle();

    $results = $this->service->translateFields(
        ['title' => 'Hallo', 'content' => '<p>Wereld</p>', 'excerpt' => ''],
        'en',
        'nl',
        ['content'],
    );

    expect($results)->toBe(['title' => '[en] Hallo', 'content' => '[en] <p>Wereld</p>']);

    Http::assertSent(fn ($request) => $request['q'] === '<p>Wereld</p>' && $request['format'] === 'html');
});

it('sends the api key in a header and never in the url', function () {
    $this->fakeGoogle();

    $this->service->translate('Hallo', 'en');

    Http::assertSent(fn ($request) => $request->hasHeader('X-goog-api-key', 'test-api-key')
        && ! str_contains($request->url(), 'test-api-key')
        && ! str_contains($request->url(), 'key='));
});

it('returns null for empty input without calling the api', function () {
    Http::fake();

    expect($this->service->translate('', 'en'))->toBeNull()
        ->and($this->service->translateHtml('', 'en'))->toBeNull()
        ->and($this->service->translateBatch([], 'en'))->toBe([]);

    Http::assertNothingSent();
});

it('returns null without an api key, without calling the api', function () {
    Http::fake();
    config(['google-translate.api_key' => null]);

    $service = new GoogleTranslateService;

    expect($service->translate('Hallo', 'en'))->toBeNull()
        ->and($service->translateBatch(['Hallo'], 'en'))->toBe([]);

    Http::assertNothingSent();
});

it('logs a failed response and returns null', function () {
    Http::fake(['translation.googleapis.com/*' => Http::response(['error' => ['message' => 'API key not valid']], 400)]);
    Log::spy();

    expect($this->service->translate('Hallo', 'en'))->toBeNull()
        ->and($this->service->translateHtml('<p>Hallo</p>', 'en'))->toBeNull()
        ->and($this->service->translateBatch(['Hallo'], 'en'))->toBe([]);

    Log::shouldHaveReceived('error')->withArgs(fn ($message) => str_starts_with($message, 'Google Translate failed: ') && str_contains($message, 'API key not valid'))->once();
    Log::shouldHaveReceived('error')->withArgs(fn ($message) => str_starts_with($message, 'Google Translate HTML failed: '))->once();
    Log::shouldHaveReceived('error')->withArgs(fn ($message) => str_starts_with($message, 'Google Translate batch failed: '))->once();
});

it('logs a connection error without the api key and returns null', function () {
    Http::fake(fn ($request) => throw new ConnectionException('cURL error 28: Operation timed out for '.$request->url()));
    Log::spy();

    expect($this->service->translate('Hallo', 'en'))->toBeNull();

    Log::shouldHaveReceived('error')->withArgs(fn ($message) => str_contains($message, 'cURL error 28') && ! str_contains($message, 'test-api-key'))->once();
});

it('returns null when a successful response has no translation', function () {
    Http::fake(['translation.googleapis.com/*' => Http::response(['data' => []])]);

    expect($this->service->translate('Hallo', 'en'))->toBeNull()
        ->and($this->service->translateBatch(['Hallo'], 'en'))->toBe([]);
});
