# Installation Guide

This guide will walk you through installing and setting up the Laravel Google Translate package in your Laravel application.

## Requirements

Before installing, make sure your system meets these requirements:

- **PHP**: 8.2 or higher
- **Laravel**: 11.x, 12.x, or 13.x
- **Composer**: Latest version recommended
- **Google Cloud Account**: For API access

## Step 1: Install via Composer

Install the package using Composer:

```bash
composer require darvis/laravel-google-translate
```

## Step 2: Publish Configuration

Publish the configuration file to customize settings:

```bash
php artisan vendor:publish --tag=google-translate-config
```

This creates a `config/google-translate.php` file in your application.

## Step 3: Get Google Translate API Key

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the **Cloud Translation API**:
   - Navigate to "APIs & Services" > "Library"
   - Search for "Cloud Translation API"
   - Click "Enable"
4. Create credentials:
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "API Key"
   - Copy your API key

### Restricting Your API Key (Recommended)

For security, restrict your API key:

1. Click on your API key in the credentials list
2. Under "API restrictions", select "Restrict key"
3. Choose "Cloud Translation API"
4. Under "Application restrictions", set appropriate restrictions
5. Save changes

## Step 4: Configure Environment Variables

Add your Google Translate API key to your `.env` file:

```env
GOOGLE_TRANSLATE_API_KEY=your-api-key-here
GOOGLE_TRANSLATE_SOURCE_LOCALE=nl
GOOGLE_TRANSLATE_TARGET_LOCALES=en,de,fr
```

### Configuration Options

- `GOOGLE_TRANSLATE_API_KEY`: Your Google Cloud Translation API key (required)
- `GOOGLE_TRANSLATE_SOURCE_LOCALE`: Default source language (default: `nl`)
- `GOOGLE_TRANSLATE_TARGET_LOCALES`: Comma-separated list of target languages (default: `en`)

## Step 5: Set Up Database (Optional)

If you plan to use translatable models, you need to add translation columns to your database tables.

### Example Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pid')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('locale', 5)->default('nl');
            $table->string('title');
            $table->text('content')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Add index for better performance
            $table->index(['locale', 'pid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
```

### Required Columns

For translatable models, your tables should have:

- `locale` (string): Language code (e.g., 'nl', 'en', 'de')
- `pid` (nullable integer): Parent ID pointing to the source item

## Step 6: Verify Installation

Test that everything is working:

```php
use Darvis\LaravelGoogleTranslate\GoogleTranslateService;

$translator = app(GoogleTranslateService::class);

if ($translator->isAvailable()) {
    echo "Google Translate is configured correctly!";
} else {
    echo "API key is missing or invalid.";
}
```

## Troubleshooting

### "API key not configured" Error

Make sure you've:
1. Added `GOOGLE_TRANSLATE_API_KEY` to your `.env` file
2. Cleared your config cache: `php artisan config:clear`
3. Restarted your development server

### "API key not valid" Error

Verify that:
1. Your API key is correct (no extra spaces)
2. The Cloud Translation API is enabled in Google Cloud Console
3. Your API key has the correct restrictions

### "Quota exceeded" Error

Check your Google Cloud Console:
1. Go to "APIs & Services" > "Dashboard"
2. Click on "Cloud Translation API"
3. Check your quota usage
4. Consider upgrading your plan if needed

## Next Steps

Now that you've installed the package, learn how to use it:

- [Quick Start Guide](quickstart.md) - Get started with basic examples
- [Basic Concepts](concepts.md) - Understand the core concepts
- [Translation Service](translation-service.md) - Learn about the translation service
- [Model Integration](models.md) - Use with Eloquent models

## Uninstalling

To remove the package:

```bash
# Remove the package
composer remove darvis/laravel-google-translate

# Remove the config file
rm config/google-translate.php

# Remove environment variables from .env
# GOOGLE_TRANSLATE_API_KEY=...
# GOOGLE_TRANSLATE_SOURCE_LOCALE=...
# GOOGLE_TRANSLATE_TARGET_LOCALES=...
```
