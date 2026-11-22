<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class FormField extends Model
{
    use HasTranslations;

    protected $table = 'form_fields';

    protected $guarded = [];

    public $translatable = ['name'];
}
