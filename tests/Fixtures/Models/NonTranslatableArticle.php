<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class NonTranslatableArticle extends Model
{
    protected $table = 'articles';

    protected $guarded = [];
}
