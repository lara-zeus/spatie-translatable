<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class RmComment extends Model
{
    use HasTranslations;

    protected $table = 'rm_comments';

    protected $guarded = [];

    public array $translatable = ['body'];
}
