# Contributing to Livewire Google Translate

Thank you for considering contributing to this project! We appreciate all contributions, whether they are bug fixes, new features, documentation improvements, or suggestions.

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Documentation](#documentation)

## Code of Conduct

This project follows a code of conduct. By participating, you are expected to uphold this code:

- Be respectful and inclusive
- Accept constructive criticism
- Focus on what is best for the community
- Show empathy towards other community members

## How Can I Contribute?

### 🐛 Reporting Bugs

If you find a bug, please create an issue with:

- A clear title and description
- Steps to reproduce the bug
- Expected behavior vs. actual behavior
- Your PHP, Laravel, and package versions
- Code examples if possible

### 💡 Feature Requests

For new features:

- Check if it already exists or has been proposed
- Clearly describe what you want and why
- Provide examples of how it would work
- Be open to discussion about the implementation

### 📝 Improving Documentation

Documentation is just as important as code:

- Improve existing documentation
- Add examples
- Fix typos
- Translate documentation

## Development Setup

### Requirements

- PHP 8.2 or higher
- Composer
- Git

### Installation

1. **Fork the project** on GitHub

2. **Clone your fork**
   ```bash
   git clone https://github.com/your-username/livewire-google-translate.git
   cd livewire-google-translate
   ```

3. **Install dependencies**
   ```bash
   composer install
   ```

4. **Create a new branch**
   ```bash
   git checkout -b feature/my-new-feature
   ```

5. **Copy .env.example to .env** (if present)
   ```bash
   cp .env.example .env
   ```

## Pull Request Process

1. **Update your fork** with the latest changes
   ```bash
   git checkout main
   git pull upstream main
   ```

2. **Make your changes** in a feature branch

3. **Write or update tests** for your changes
   ```bash
   composer test
   ```

4. **Ensure all tests pass**
   ```bash
   composer test
   ```

5. **Commit your changes** with clear commit messages
   ```bash
   git commit -m "feat: add new feature"
   ```

   Use conventional commits:
   - `feat:` for new features
   - `fix:` for bug fixes
   - `docs:` for documentation
   - `test:` for tests
   - `refactor:` for code refactoring
   - `style:` for code formatting
   - `chore:` for maintenance

6. **Push to your fork**
   ```bash
   git push origin feature/my-new-feature
   ```

7. **Open a Pull Request** on GitHub with:
   - Clear title and description
   - Reference to related issues
   - Screenshots if applicable
   - List of changes

## Coding Standards

### PHP Standards

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style
- Use type hints wherever possible
- Write clear, descriptive names
- Keep functions small and focused

### Code Formatting

We use PHP CS Fixer for consistent code formatting:

```bash
composer format
```

### Example of good code:

```php
<?php

namespace Darvis\LivewireGoogleTranslate;

use Illuminate\Support\Facades\Http;

class GoogleTranslateService
{
    public function translate(string $text, string $targetLocale, ?string $sourceLocale = null): string
    {
        if (empty($text)) {
            return '';
        }

        $sourceLocale ??= $this->getSourceLocale();

        // Implementation...
    }
}
```

## Testing

### Running Tests

```bash
# All tests
composer test

# With coverage
composer test-coverage

# Specific test
vendor/bin/pest tests/Unit/GoogleTranslateServiceTest.php
```

### Writing Tests

We use [Pest](https://pestphp.com/) for testing:

```php
<?php

use Darvis\LivewireGoogleTranslate\GoogleTranslateService;

it('can translate text', function () {
    $service = new GoogleTranslateService();
    
    $result = $service->translate('Hello', 'nl');
    
    expect($result)->toBe('Hallo');
});

it('returns empty string for empty input', function () {
    $service = new GoogleTranslateService();
    
    $result = $service->translate('', 'en');
    
    expect($result)->toBe('');
});
```

### Test Guidelines

- Write tests for all new features
- Update tests when making changes
- Test edge cases and error scenarios
- Use clear test descriptions
- Keep tests simple and focused

## Documentation

### README Updates

When adding features:

- Update README.md with examples
- Add to the features list
- Update the API reference if needed

### Code Documentation

```php
/**
 * Translate text to the target language.
 *
 * @param string $text The text to translate
 * @param string $targetLocale The target language code (e.g., 'en', 'de')
 * @param string|null $sourceLocale The source language code (optional)
 * @return string The translated text
 * @throws \Exception If the API is not available
 */
public function translate(string $text, string $targetLocale, ?string $sourceLocale = null): string
{
    // ...
}
```

## Questions?

Have questions? You can:

- Open an issue for discussion
- Contact us at [info@arvid.nl](mailto:info@arvid.nl)

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

**Thank you for your contribution! 🎉**
