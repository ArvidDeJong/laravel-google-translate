<?php

namespace Darvis\LaravelGoogleTranslate\Tests;

use Darvis\LaravelGoogleTranslate\GoogleTranslateServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // A forgotten fake fails the test instead of calling Google.
        Http::preventStrayRequests();

        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pid')->nullable()->index();
            $table->string('locale', 5)->index();
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app): array
    {
        return [
            GoogleTranslateServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // The package defaults have no API key, and without one nothing is translated.
        $app['config']->set('google-translate.api_key', 'test-api-key');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Fake the Translation API: every text comes back as "[target] text".
     */
    protected function fakeGoogle(): void
    {
        Http::fake([
            'translation.googleapis.com/*' => function ($request) {
                $data = $request->data();
                $texts = (array) $data['q'];

                return Http::response(['data' => ['translations' => array_map(
                    fn ($text) => ['translatedText' => "[{$data['target']}] {$text}"],
                    $texts,
                )]]);
            },
        ]);
    }
}
