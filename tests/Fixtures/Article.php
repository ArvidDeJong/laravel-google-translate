<?php

namespace Darvis\LaravelGoogleTranslate\Tests\Fixtures;

use Darvis\LaravelGoogleTranslate\Traits\HasGoogleTranslate;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $pid
 * @property string $locale
 * @property string|null $title
 * @property string|null $content
 * @property string|null $slug
 */
class Article extends Model
{
    use HasGoogleTranslate;

    protected $guarded = [];

    /** @var array<int, string> */
    protected $translatableFields = ['title', 'content'];

    /** @var array<int, string> */
    protected $htmlFields = ['content'];
}
