<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class DriverCustomTranslatablePost extends Model
{
    protected $table = 'driver_plain_posts';

    protected $guarded = [];

    public function getTranslatableAttributes(): array
    {
        return ['title'];
    }
}
