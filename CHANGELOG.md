# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.1.1] - 2026-09-21

### Added
- Documentation: a [Testing](https://arviddejong.github.io/laravel-google-translate/testing.html) page
  with a complete example test, the shape of the request and the response to fake, and the two traps
  (the key is read once; a forgotten fake does not fail the test by itself).
- Documentation: the home page says who the package is for and what it does not do, the installation
  page has numbered steps and a "Check that it works" section that starts with the free
  `isAvailable()` check, and the quick start is one complete example with file names and imports.
- The docs guard test checks that every link between pages resolves and that the home page links to
  every page.

### Fixed
- The docs and the FAQ said `Http::preventStrayRequests()` makes a forgotten fake fail the test. It
  blocks the request, but the package catches that exception like every other failure: the result is
  `null` and the log has `Google Translate failed: Attempted request to [...] without a matching fake.`
- The troubleshooting table quoted error texts of Google's API as if they were fixed ("API key not
  valid", "Invalid Value", referrer, IP and billing messages, HTTP 429 for the rate limit). Those are
  Google's words and the package cannot guarantee them. The page now quotes only the package's own
  log prefixes and the two messages Google documents for an HTTP 403 (`Daily Limit Exceeded`,
  `User Rate Limit Exceeded`), and describes the other causes.
- The docs said a `null` result with a key always has a line in the log. A 2xx response without a
  translation in the body returns `null` (or `[]`) and logs nothing.
- The docs said `createTranslation()` throws a `MassAssignmentException` when an attribute is not
  fillable. That only happens on a model without any `$fillable`; with a `$fillable` that misses
  `pid`, Laravel drops it silently and the new row looks like a second source row.
- The docs gave "a few hundred milliseconds per field" as the duration of a call. The package cannot
  know that; what it does is one HTTP request per field and per locale, with Laravel's default
  timeout of 30 seconds and no retry.
- The `fillMissingTranslations()` example reported an `excerpt` field the example model does not
  have, and the queued job example missed its namespace and imports.
- The CMS example accepted any model label and any locale from the browser, and could translate a
  translation row. It now checks both against its own lists and only loads source rows. The page
  also says that Livewire has to be installed by the host app, and that `Route::get()` with a
  component class works in Livewire 3 and 4 while `Route::livewire()` exists only in Livewire 4.
- The requirements differed per page. They now read the same everywhere: PHP 8.2 or higher,
  Laravel 11, 12 or 13 (Laravel 13 itself needs PHP 8.3), and an API key from a Google Cloud project
  with billing enabled.

## [1.1.0] - 2026-09-21

### Added
- `Darvis\LaravelGoogleTranslate\Support\GoogleTranslateConfig`, the one place that reads the package
  config, with `apiKey()`, `sourceLocale()` and `targetLocales()`. A test fails the build on a direct
  `config('google-translate.…')` read.
- A documentation site at https://arviddejong.github.io/laravel-google-translate/ with an FAQ and an
  `llms.txt`, and a Laravel Boost guideline and skill in `resources/boost/`.
- Tests for the trait on a real table, for failed and empty responses, for the service provider and
  for the docs site. The suite had one file with eight tests, two of which failed.
- The tooling of the other darvis packages: Larastan level 8, the `test`, `lint`, `format` and
  `analyse` composer scripts, issue forms, a code of conduct and a `.gitattributes` that keeps
  development files out of the dist archive.

### Fixed
- **`fillMissingTranslations()` never worked on a translation.** The source row has no `pid`, and the
  lookup only searched on `pid`, so from a translation the source was never found and the method
  always answered `Source translation not found`. `hasTranslation()` and `getTranslation()` had the
  same blind spot for the source locale. They now search the source row and its translations.
- The documentation described exceptions the package never throws. A failed call is logged and
  returns `null`, or an empty array for a batch; the docs now say so.
- Links in `composer.json`, the README and the docs pointed at `github.com/darvis`, which is not
  where this package lives.

### Security
- The API key is sent in the `X-goog-api-key` header instead of in the URL. An HTTP client exception
  quotes the URL, and the package logs that message, so a timeout wrote the key to the log. Rotate
  your key if your log files are shared or shipped to an external service.

### Changed
- Requests are sent as JSON, the format Google documents. A batch used to be form encoded as
  `q[0]=…&q[1]=…`. If your tests inspect the request body of a faked call, read it with
  `$request['q']` as before; a test that matched on `?key=` in the URL has to look at the header.
- `getTargetLocales()` trims the locales and drops empty ones, so `GOOGLE_TRANSLATE_TARGET_LOCALES=en, de`
  gives `['en', 'de']` instead of `['en', ' de']`.
- `illuminate/http` is now a declared dependency; it was used but not required.
- CI calls the shared workflow in `ArvidDeJong/.github`, which adds PHP 8.4 and the `prefer-lowest`
  column that checks the version constraints in `composer.json`. `minimum-stability` is `stable`.
- The composer scripts `test-coverage` and `format-test` are replaced by `lint` and `analyse`, the
  same names as in the other darvis packages.
- Security reports go through GitHub private vulnerability reporting, see `SECURITY.md`.

## [1.0.1] - 2026-03-30

### Added
- First stable release
- Complete documentation
- Test suite with Pest
- Laravel 11.x, 12.x, and 13.x support
- PHP 8.2+ support
- Initial package release
- Google Translate integration for Laravel
- `GoogleTranslateService` for translating text and HTML content
- `HasGoogleTranslate` trait for Eloquent models
- Batch translation support
- HTML tag preservation during translation
- Model scopes for localized content
- Configurable source and target locales
- Automatic filling of missing translations

[Unreleased]: https://github.com/ArvidDeJong/laravel-google-translate/compare/v1.1.1...HEAD
[1.1.1]: https://github.com/ArvidDeJong/laravel-google-translate/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/ArvidDeJong/laravel-google-translate/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/ArvidDeJong/laravel-google-translate/releases/tag/v1.0.1
