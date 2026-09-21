<?php

use Darvis\LaravelGoogleTranslate\GoogleTranslateService;
use Darvis\LaravelGoogleTranslate\Tests\Fixtures\Article;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->source = Article::create([
        'locale' => 'nl',
        'title' => 'Hallo wereld',
        'content' => '<p>Welkom</p>',
        'slug' => 'hallo-wereld',
    ]);
});

it('creates a translation row linked to the source through pid', function () {
    $this->fakeGoogle();

    $translation = $this->source->createTranslation('en', ['slug' => 'hello-world']);

    expect($translation)->not->toBeNull()
        ->and($translation->pid)->toBe($this->source->id)
        ->and($translation->locale)->toBe('en')
        ->and($translation->title)->toBe('[en] Hallo wereld')
        ->and($translation->content)->toBe('[en] <p>Welkom</p>')
        ->and($translation->slug)->toBe('hello-world');
});

it('translates the html fields in html mode and the others as text', function () {
    $this->fakeGoogle();

    $this->source->createTranslation('en');

    Http::assertSent(fn ($request) => $request['q'] === 'Hallo wereld' && $request['format'] === 'text');
    Http::assertSent(fn ($request) => $request['q'] === '<p>Welkom</p>' && $request['format'] === 'html');
});

it('returns the existing translation instead of creating a second one', function () {
    $this->fakeGoogle();

    $first = $this->source->createTranslation('en');
    $second = $this->source->createTranslation('en');

    expect($second->id)->toBe($first->id)
        ->and(Article::where('locale', 'en')->count())->toBe(1);
});

it('creates nothing without an api key', function () {
    config(['google-translate.api_key' => null]);
    app()->forgetInstance(GoogleTranslateService::class);

    expect($this->source->createTranslation('en'))->toBeNull()
        ->and(Article::count())->toBe(1);
});

it('creates nothing when every translation fails', function () {
    Http::fake(['translation.googleapis.com/*' => Http::response(['error' => 'denied'], 403)]);

    expect($this->source->createTranslation('en'))->toBeNull()
        ->and(Article::count())->toBe(1);
});

it('finds translations from the source and from a translation', function () {
    $english = Article::create(['pid' => $this->source->id, 'locale' => 'en', 'title' => 'Hello world']);

    expect($this->source->hasTranslation('en'))->toBeTrue()
        ->and($this->source->hasTranslation('de'))->toBeFalse()
        ->and($this->source->getTranslation('en')->id)->toBe($english->id)
        ->and($this->source->getTranslation('nl')->id)->toBe($this->source->id)
        ->and($english->hasTranslation('nl'))->toBeTrue()
        ->and($english->getTranslation('nl')->id)->toBe($this->source->id)
        ->and($english->getAllTranslations())->toHaveCount(2);
});

it('fills the empty fields of a translation from the source', function () {
    $this->fakeGoogle();

    $english = Article::create(['pid' => $this->source->id, 'locale' => 'en', 'title' => 'Hello world']);

    $result = $english->fillMissingTranslations();

    expect($result['errors'])->toBe([])
        ->and($result['translated'])->toBe(['content'])
        ->and($english->fresh()->title)->toBe('Hello world')
        ->and($english->fresh()->content)->toBe('[en] <p>Welkom</p>');
});

it('reports the fields that could not be translated', function () {
    Http::fake(['translation.googleapis.com/*' => Http::response(['error' => 'denied'], 403)]);

    $english = Article::create(['pid' => $this->source->id, 'locale' => 'en', 'title' => 'Hello world']);

    $result = $english->fillMissingTranslations();

    expect($result['translated'])->toBe([])
        ->and($result['errors'])->toBe(["Translation of 'content' failed"]);
});

it('lists the source items that miss a translation', function () {
    $other = Article::create(['locale' => 'nl', 'title' => 'Tweede']);
    Article::create(['pid' => $this->source->id, 'locale' => 'en', 'title' => 'Hello world']);

    $missing = Article::getMissingTranslations('en');

    expect($missing->pluck('id')->all())->toBe([$other->id]);
});

it('scopes to source items and to a locale', function () {
    Article::create(['pid' => $this->source->id, 'locale' => 'en', 'title' => 'Hello world']);

    app()->setLocale('en');

    expect(Article::sourceItems()->count())->toBe(1)
        ->and(Article::localized('nl')->count())->toBe(1)
        ->and(Article::localized()->first()->title)->toBe('Hello world');
});
