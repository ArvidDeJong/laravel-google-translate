# Contributing

Contributions are welcome: bug reports, fixes, documentation and ideas.

## Before you start

- **Bugs:** open an [issue](https://github.com/ArvidDeJong/laravel-google-translate/issues/new/choose) with the steps to reproduce and the log line. Leave your API key out.
- **Features:** open an issue first. This package stays small on purpose, so let's agree a feature fits before you build it.
- **Security issues:** don't open an issue; see [SECURITY.md](SECURITY.md).

## Development

```bash
git clone https://github.com/ArvidDeJong/laravel-google-translate.git
cd laravel-google-translate
composer install

composer test      # Pest
composer lint      # Pint, check only (composer format fixes)
composer analyse   # Larastan, level 8
```

CI runs the tests on PHP 8.2 to 8.4 with Laravel 11, 12 and 13, on the lowest and the latest dependencies.

## Pull requests

- Add or update tests for every change in behaviour. Never call Google from a test; the test case blocks every request that is not faked.
- Keep the public API compatible within 1.x: the public methods of `GoogleTranslateService` and their return values (`null` or `[]` on failure, never an exception), its protected `$apiKey` and `$baseUrl`, the public methods and scopes of `HasGoogleTranslate`, the `$translatableFields` and `$htmlFields` properties, the shape of the `fillMissingTranslations()` result and the wording of the log lines.
- Don't add return types to the trait's scope methods; host app models may override them.
- Read settings through `Support\GoogleTranslateConfig`, never with `config('google-translate.…')`.
- Write code, comments and messages in English.
- Update `docs/`, `CHANGELOG.md` (under `Unreleased`) and `resources/boost/` when users will notice the change.
- The documentation in `docs/` is also the website. Don't write `{{ }}` or `{% %}` there outside a raw block; Jekyll would render it.

## Code of conduct

This project follows the [Contributor Covenant](CODE_OF_CONDUCT.md).
