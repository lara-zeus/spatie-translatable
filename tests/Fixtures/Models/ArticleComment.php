<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ArticleComment extends Model
{
    use HasTranslations;

    protected $table = 'article_comments';

    protected $guarded = [];

    public array $translatable = ['body'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
