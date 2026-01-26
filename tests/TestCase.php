<?php

namespace Darvis\LivewireGoogleTranslate\Tests;

use Darvis\LivewireGoogleTranslate\GoogleTranslateServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            GoogleTranslateServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('google-translate.api_key', 'test-api-key');
        config()->set('google-translate.source_locale', 'nl');
        config()->set('google-translate.target_locales', ['en', 'de', 'fr']);
    }
}
