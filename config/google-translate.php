<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Translate API Key
    |--------------------------------------------------------------------------
    |
    | Your Google Cloud Translation API key. You can get one from the
    | Google Cloud Console: https://console.cloud.google.com/
    |
    */
    'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Source Locale
    |--------------------------------------------------------------------------
    |
    | The default source locale for translations. This is the language
    | your original content is written in.
    |
    */
    'source_locale' => env('GOOGLE_TRANSLATE_SOURCE_LOCALE', 'nl'),

    /*
    |--------------------------------------------------------------------------
    | Target Locales
    |--------------------------------------------------------------------------
    |
    | The locales you want to translate your content to.
    |
    */
    'target_locales' => explode(',', env('GOOGLE_TRANSLATE_TARGET_LOCALES', 'en')),
];
