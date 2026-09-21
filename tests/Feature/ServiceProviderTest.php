<?php

use Darvis\LaravelGoogleTranslate\GoogleTranslateService;
use Darvis\LaravelGoogleTranslate\GoogleTranslateServiceProvider;
use Illuminate\Support\ServiceProvider;

it('registers the service as a singleton', function () {
    expect(app(GoogleTranslateService::class))->toBe(app(GoogleTranslateService::class));
});

it('registers the google-translate alias', function () {
    expect(app('google-translate'))->toBeInstanceOf(GoogleTranslateService::class);
});

it('merges the package config', function () {
    expect(config('google-translate.source_locale'))->toBe('nl')
        ->and(config('google-translate.target_locales'))->toBe(['en']);
});

it('offers the config file for publishing', function () {
    // Never run vendor:publish here: it writes into the Testbench app.
    $paths = ServiceProvider::pathsToPublish(GoogleTranslateServiceProvider::class, 'google-translate-config');

    expect($paths)->toHaveCount(1)
        ->and(array_key_first($paths))->toEndWith('config/google-translate.php')
        ->and(array_values($paths)[0])->toBe(config_path('google-translate.php'));
});
