<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class EmptyTranslatableArticle extends Model
{
    use HasTranslations;

    protected $table = 'articles';

    protected $guarded = [];

    public array $translatable = [];
}
