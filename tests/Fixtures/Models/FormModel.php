<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Translatable\HasTranslations;

class FormModel extends Model
{
    use HasTranslations;

    protected $table = 'forms';

    protected $guarded = [];

    public $translatable = ['name', 'description'];

    public function sections(): HasMany
    {
        return $this->hasMany(FormSection::class, 'form_id');
    }

    public function meta(): HasOne
    {
        return $this->hasOne(FormMeta::class, 'form_id');
    }

    public function setting(): HasOne
    {
        return $this->hasOne(FormSetting::class, 'form_id');
    }
}
