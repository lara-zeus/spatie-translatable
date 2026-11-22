<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class FormMeta extends Model
{
    use HasTranslations;

    protected $table = 'form_metas';

    protected $guarded = [];

    public $translatable = ['meta_title'];
}
