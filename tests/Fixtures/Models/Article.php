<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Article extends Model
{
    use HasTranslations;

    protected $table = 'articles';

    protected $guarded = [];

    public array $translatable = ['title'];

    public function comments(): HasMany
    {
        return $this->hasMany(ArticleComment::class);
    }
}
