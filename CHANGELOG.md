# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

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

[Unreleased]: https://github.com/ArvidDeJong/laravel-google-translate/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/ArvidDeJong/laravel-google-translate/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/ArvidDeJong/laravel-google-translate/releases/tag/v1.0.1
