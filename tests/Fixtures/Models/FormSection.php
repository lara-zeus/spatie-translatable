<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class FormSection extends Model
{
    use HasTranslations;

    protected $table = 'form_sections';

    protected $guarded = [];

    public $translatable = ['name'];

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_section_id');
    }
}
