# Security Policy

## Supported Versions

We release patches for security vulnerabilities. Which versions are eligible for receiving such patches depends on the CVSS v3.0 Rating:

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability within this package, please send an email to Arvid de Jong at [info@arvid.nl](mailto:info@arvid.nl). All security vulnerabilities will be promptly addressed.

**Please do not report security vulnerabilities through public GitHub issues.**

### What to Include

When reporting a vulnerability, please include:

- Type of issue (e.g., buffer overflow, SQL injection, cross-site scripting, etc.)
- Full paths of source file(s) related to the manifestation of the issue
- The location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit it

### Response Timeline

- **Initial Response**: Within 48 hours of receiving your report
- **Status Update**: Within 7 days with an assessment of the vulnerability
- **Fix Timeline**: Critical vulnerabilities will be patched within 30 days
- **Public Disclosure**: After a fix is released and users have had time to update

## Security Best Practices

When using this package:

### API Key Security

1. **Never commit API keys** to version control
   ```env
   # .env file (never commit this)
   GOOGLE_TRANSLATE_API_KEY=your-secret-key
   ```

2. **Use environment variables** for sensitive data
   ```php
   // Good
   config('google-translate.api_key')
   
   // Bad - never hardcode
   $apiKey = 'AIzaSyC...';
   ```

3. **Restrict your API key** in Google Cloud Console:
   - Set application restrictions (HTTP referrers, IP addresses)
   - Set API restrictions (only Cloud Translation API)
   - Monitor usage regularly

### Input Validation

Always validate and sanitize user input before translation:

```php
// Validate input
$validated = $request->validate([
    'text' => 'required|string|max:5000',
    'locale' => 'required|string|in:en,nl,de,fr',
]);

// Then translate
$result = $translator->translate($validated['text'], $validated['locale']);
```

### Rate Limiting

Implement rate limiting to prevent abuse:

```php
use Illuminate\Support\Facades\RateLimiter;

if (RateLimiter::tooManyAttempts('translate:' . $user->id, 100)) {
    throw new Exception('Too many translation requests');
}

RateLimiter::hit('translate:' . $user->id);
```

### Error Handling

Never expose sensitive information in error messages:

```php
try {
    $result = $translator->translate($text, 'en');
} catch (\Exception $e) {
    // Good - log detailed error
    Log::error('Translation failed', [
        'error' => $e->getMessage(),
        'user_id' => $user->id,
    ]);
    
    // Good - show generic message to user
    return response()->json(['error' => 'Translation failed'], 500);
    
    // Bad - exposes details
    // return response()->json(['error' => $e->getMessage()], 500);
}
```

### Database Security

Protect translation data:

```php
// Use mass assignment protection
class Page extends Model
{
    protected $fillable = [
        'title',
        'content',
        // Only allow specific fields
    ];
    
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];
}
```

### HTTPS Only

Always use HTTPS in production to protect API keys in transit:

```php
// In production
if (!request()->secure() && app()->environment('production')) {
    return redirect()->secure(request()->getRequestUri());
}
```

## Known Security Considerations

### API Quota

- Monitor your Google Cloud quota to prevent unexpected charges
- Set up billing alerts in Google Cloud Console
- Implement application-level rate limiting

### Content Filtering

- The package does not filter or sanitize translated content
- Implement your own content filtering if needed
- Be aware that translations may contain unexpected content

### Data Privacy

- Translation requests are sent to Google's servers
- Ensure compliance with GDPR and other privacy regulations
- Consider data residency requirements for your use case
- Review Google's [Privacy Policy](https://policies.google.com/privacy)

## Updates and Patches

To stay secure:

1. **Keep the package updated**:
   ```bash
   composer update darvis/livewire-google-translate
   ```

2. **Monitor security advisories**:
   - Watch the [GitHub repository](https://github.com/darvis/livewire-google-translate)
   - Enable GitHub security alerts

3. **Review the CHANGELOG**:
   - Check [CHANGELOG.md](CHANGELOG.md) for security fixes

## Disclosure Policy

When we receive a security bug report, we will:

1. Confirm the problem and determine affected versions
2. Audit code to find any similar problems
3. Prepare fixes for all supported versions
4. Release new versions as soon as possible
5. Credit the reporter (unless they prefer to remain anonymous)

## Contact

For security concerns, contact:
- **Email**: [info@arvid.nl](mailto:info@arvid.nl)
- **Response Time**: Within 48 hours

For general questions, use:
- **GitHub Issues**: [Create an issue](https://github.com/darvis/livewire-google-translate/issues)

## Attribution

We appreciate responsible disclosure and will acknowledge security researchers who report vulnerabilities to us.
