<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class DummyModel extends Model
{
    use HasTranslations;

    protected $table = 'dummy_models';

    protected $guarded = [];

    public $translatable = ['title'];
}
