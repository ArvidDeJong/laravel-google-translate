<?php

namespace Darvis\LaravelGoogleTranslate;

use Illuminate\Support\ServiceProvider;

class GoogleTranslateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/google-translate.php' => config_path('google-translate.php'),
        ], 'google-translate-config');
    }

    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/google-translate.php',
            'google-translate'
        );

        // Register the service
        $this->app->singleton(GoogleTranslateService::class, function ($app) {
            return new GoogleTranslateService;
        });

        // Register alias
        $this->app->alias(GoogleTranslateService::class, 'google-translate');
    }
}
