<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class DriverTranslatablePost extends Model
{
    use HasTranslations;

    protected $table = 'driver_posts';

    protected $guarded = [];

    public array $translatable = ['title'];
}
