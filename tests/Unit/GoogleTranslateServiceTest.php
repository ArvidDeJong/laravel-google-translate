<?php

use Darvis\LivewireGoogleTranslate\GoogleTranslateService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new GoogleTranslateService();
});

it('can check if service is available', function () {
    expect($this->service->isAvailable())->toBeTrue();
});

it('returns source locale from config', function () {
    expect($this->service->getSourceLocale())->toBe('nl');
});

it('returns target locales from config', function () {
    $locales = $this->service->getTargetLocales();
    
    expect($locales)->toBeArray()
        ->and($locales)->toContain('en', 'de', 'fr');
});

it('can translate text', function () {
    Http::fake([
        'translation.googleapis.com/*' => Http::response([
            'data' => [
                'translations' => [
                    ['translatedText' => 'Hello world']
                ]
            ]
        ], 200)
    ]);
    
    $result = $this->service->translate('Hallo wereld', 'en');
    
    expect($result)->toBe('Hello world');
});

it('returns empty string for empty input', function () {
    $result = $this->service->translate('', 'en');
    
    expect($result)->toBe('');
});

it('can translate html content', function () {
    Http::fake([
        'translation.googleapis.com/*' => Http::response([
            'data' => [
                'translations' => [
                    ['translatedText' => '<p>Hello <strong>world</strong></p>']
                ]
            ]
        ], 200)
    ]);
    
    $result = $this->service->translateHtml('<p>Hallo <strong>wereld</strong></p>', 'en');
    
    expect($result)->toBe('<p>Hello <strong>world</strong></p>');
});

it('can translate batch of texts', function () {
    Http::fake([
        'translation.googleapis.com/*' => Http::response([
            'data' => [
                'translations' => [
                    ['translatedText' => 'Hello'],
                    ['translatedText' => 'World'],
                    ['translatedText' => 'Welcome']
                ]
            ]
        ], 200)
    ]);
    
    $results = $this->service->translateBatch(['Hallo', 'Wereld', 'Welkom'], 'en');
    
    expect($results)->toBeArray()
        ->and($results)->toHaveCount(3)
        ->and($results[0])->toBe('Hello')
        ->and($results[1])->toBe('World')
        ->and($results[2])->toBe('Welcome');
});

it('can translate fields with html support', function () {
    Http::fake([
        'translation.googleapis.com/*' => Http::response([
            'data' => [
                'translations' => [
                    ['translatedText' => 'Hello'],
                    ['translatedText' => '<p>World</p>']
                ]
            ]
        ], 200)
    ]);
    
    $fields = [
        'title' => 'Hallo',
        'content' => '<p>Wereld</p>'
    ];
    
    $results = $this->service->translateFields($fields, 'en', 'nl', ['content']);
    
    expect($results)->toBeArray()
        ->and($results['title'])->toBe('Hello')
        ->and($results['content'])->toBe('<p>World</p>');
});
