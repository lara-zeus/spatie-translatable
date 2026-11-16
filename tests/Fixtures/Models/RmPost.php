<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class RmPost extends Model
{
    use HasTranslations;

    protected $table = 'rm_posts';

    protected $guarded = [];

    public array $translatable = ['title'];

    public function comments(): HasMany
    {
        return $this->hasMany(RmComment::class, 'post_id');
    }
}
