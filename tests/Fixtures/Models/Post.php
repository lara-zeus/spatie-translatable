<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Translatable\HasTranslations;

class Post extends Model
{
    use HasTranslations;

    protected $guarded = [];

    public $translatable = ['title', 'body', 'cover'];

    public function seo(): HasOne
    {
        return $this->hasOne(Seo::class);
    }
}
