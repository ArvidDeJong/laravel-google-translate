<?php

use Darvis\LaravelGoogleTranslate\Support\GoogleTranslateConfig;

/**
 * GoogleTranslateConfig is the one place that reads the package config. These tests guard the two
 * things that go wrong once a default is written down twice: an accessor that disagrees with the
 * config file, and a caller that reaches past the accessor and keeps its own stale fallback.
 */
function googleTranslateRoot(string $path = ''): string
{
    return dirname(__DIR__, 2).($path === '' ? '' : '/'.$path);
}

test('the accessors return the values the config file ships', function () {
    $config = require googleTranslateRoot('config/google-translate.php');

    config(['google-translate.api_key' => $config['api_key']]);

    expect(GoogleTranslateConfig::apiKey())->toBeNull()
        ->and(GoogleTranslateConfig::sourceLocale())->toBe($config['source_locale'])
        ->and(GoogleTranslateConfig::targetLocales())->toBe($config['target_locales']);
});

test('the target locales accept a list or a comma separated string, and drop the blanks', function () {
    config(['google-translate.target_locales' => ['en', ' de ', '']]);
    expect(GoogleTranslateConfig::targetLocales())->toBe(['en', 'de']);

    config(['google-translate.target_locales' => 'en, fr,']);
    expect(GoogleTranslateConfig::targetLocales())->toBe(['en', 'fr']);

    config(['google-translate.target_locales' => null]);
    expect(GoogleTranslateConfig::targetLocales())->toBe(['en']);
});

test('an empty api key counts as no api key', function () {
    config(['google-translate.api_key' => '']);
    expect(GoogleTranslateConfig::apiKey())->toBeNull();

    config(['google-translate.api_key' => 'test-key']);
    expect(GoogleTranslateConfig::apiKey())->toBe('test-key');
});

test('nothing outside the accessor reads the package config', function () {
    $offenders = [];

    foreach (['src', 'resources', 'routes'] as $directory) {
        $path = googleTranslateRoot($directory);

        if (! is_dir($path)) {
            continue;
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace(googleTranslateRoot().'/', '', $file->getPathname());

            // The accessor is where the reading happens, and the Boost guideline quotes the call
            // it tells you not to write.
            if (str_ends_with($relative, 'GoogleTranslateConfig.php') || str_starts_with($relative, 'resources/boost/')) {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());

            if (preg_match("/(config\\(|Config::get\\()['\"]google-translate\\./", $contents)) {
                $offenders[] = $relative;
            }
        }
    }

    expect($offenders)->toBe([], 'these read the config directly instead of through GoogleTranslateConfig');
});

test('the config keys are in alphabetical order, at every level', function () {
    $walk = function (array $config, string $trail) use (&$walk): void {
        $keys = array_keys($config);

        if ($keys !== array_filter($keys, 'is_string')) {
            return;
        }

        $sorted = $keys;
        sort($sorted);

        expect($keys)->toBe($sorted, "the keys in '{$trail}' are not in alphabetical order");

        foreach ($config as $key => $value) {
            if (is_array($value) && $value !== []) {
                $walk($value, $trail.'.'.$key);
            }
        }
    };

    $walk(require googleTranslateRoot('config/google-translate.php'), 'google-translate');
});
