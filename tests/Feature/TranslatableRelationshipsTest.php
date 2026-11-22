<?php

use Filament\Panel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Tests\Fixtures\Models\FormModel;
use Tests\Fixtures\Resources\FormModelResource;
use Tests\Fixtures\Resources\Pages\CreateFormModel;
use Tests\Fixtures\Resources\Pages\EditFormModel;
use Tests\Fixtures\Resources\Pages\ViewFormModel;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Schema::create('forms', function (Blueprint $table) {
        $table->id();
        $table->json('name')->nullable();
        $table->json('description')->nullable();
        $table->string('slug')->nullable();
        $table->timestamps();
    });

    Schema::create('form_sections', function (Blueprint $table) {
        $table->id();
        $table->foreignId('form_id');
        $table->json('name')->nullable();
        $table->unsignedInteger('ordering')->default(1);
        $table->timestamps();
    });

    Schema::create('form_fields', function (Blueprint $table) {
        $table->id();
        $table->foreignId('form_section_id');
        $table->json('name')->nullable();
        $table->string('type')->nullable();
        $table->unsignedInteger('ordering')->default(1);
        $table->timestamps();
    });

    Schema::create('form_metas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('form_id');
        $table->json('meta_title')->nullable();
        $table->string('robots')->nullable();
        $table->timestamps();
    });

    Schema::create('form_settings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('form_id');
        $table->string('theme')->nullable();
        $table->timestamps();
    });

    $panel = Panel::make()
        ->id('test')
        ->default()
        ->resources([FormModelResource::class])
        ->plugin(SpatieTranslatablePlugin::make()->defaultLocales(['ar', 'en', 'ckb']));

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);
});

it('creates a record with translatable nested repeaters and singular relationships across three locales', function () {
    livewire(CreateFormModel::class)
        ->assertSet('activeLocale', 'ar')
        ->set('data.name', 'استمارة')
        ->set('data.description', 'وصف الاستمارة')
        ->set('data.slug', 'contact-form')
        ->set('data.meta.meta_title', 'عنوان ميتا')
        ->set('data.meta.robots', 'index,follow')
        ->set('data.setting.theme', 'dark')
        ->set('data.sections', [
            'sec-1' => [
                'name' => 'القسم الأول',
                'fields' => [
                    'fld-1' => [
                        'name' => 'الاسم الكامل',
                        'type' => 'text',
                    ],
                ],
            ],
        ])
        ->set('activeLocale', 'en')
        ->assertSet('data.name', null)
        ->assertSet('data.slug', 'contact-form')
        ->assertSet('data.meta.meta_title', null)
        ->assertSet('data.meta.robots', 'index,follow')
        ->assertSet('data.setting.theme', 'dark')
        ->assertSet('data.sections.sec-1.name', null)
        ->assertSet('data.sections.sec-1.fields.fld-1.name', null)
        ->assertSet('data.sections.sec-1.fields.fld-1.type', 'text')
        ->set('data.name', 'Contact Form')
        ->set('data.description', 'Form description')
        ->set('data.meta.meta_title', 'Meta Title EN')
        ->set('data.sections.sec-1.name', 'First Section')
        ->set('data.sections.sec-1.fields.fld-1.name', 'Full Name')
        ->set('activeLocale', 'ckb')
        ->assertSet('data.sections.sec-1.name', null)
        ->assertSet('data.sections.sec-1.fields.fld-1.name', null)
        ->assertSet('data.sections.sec-1.fields.fld-1.type', 'text')
        ->set('data.name', 'فۆرمی پەیوەندی')
        ->set('data.description', 'وەسفی فۆرم')
        ->set('data.meta.meta_title', 'سەردێڕی مێتا')
        ->set('data.sections.sec-1.name', 'بەشی یەکەم')
        ->set('data.sections.sec-1.fields.fld-1.name', 'ناوی تەواو')
        ->call('create')
        ->assertHasNoErrors();

    $form = FormModel::with(['sections.fields', 'meta', 'setting'])->sole();

    expect($form->getTranslations('name'))->toEqualCanonicalizing([
        'ar' => 'استمارة',
        'en' => 'Contact Form',
        'ckb' => 'فۆرمی پەیوەندی',
    ])->and($form->slug)->toBe('contact-form')
        ->and($form->meta->getTranslations('meta_title'))->toEqualCanonicalizing([
            'ar' => 'عنوان ميتا',
            'en' => 'Meta Title EN',
            'ckb' => 'سەردێڕی مێتا',
        ])->and($form->meta->robots)->toBe('index,follow')
        ->and($form->setting->theme)->toBe('dark');

    $section = $form->sections->sole();
    expect($section->getTranslations('name'))->toEqualCanonicalizing([
        'ar' => 'القسم الأول',
        'en' => 'First Section',
        'ckb' => 'بەشی یەکەم',
    ]);

    $field = $section->fields->sole();
    expect($field->getTranslations('name'))->toEqualCanonicalizing([
        'ar' => 'الاسم الكامل',
        'en' => 'Full Name',
        'ckb' => 'ناوی تەواو',
    ])->and($field->type)->toBe('text');
});

it('switches locales and saves edits on existing translatable nested repeaters without overwriting untouched locales', function () {
    $form = FormModel::create([
        'name' => ['ar' => 'استمارة', 'en' => 'Contact Form', 'ckb' => 'فۆرمی پەیوەندی'],
        'slug' => 'contact',
    ]);
    $form->meta()->create([
        'meta_title' => ['ar' => 'ميتا', 'en' => 'Meta EN', 'ckb' => 'مێتا'],
        'robots' => 'noindex',
    ]);
    $form->setting()->create(['theme' => 'light']);

    $section = $form->sections()->create([
        'name' => ['ar' => 'القسم ١', 'en' => 'Section 1', 'ckb' => 'بەشی ١'],
        'ordering' => 1,
    ]);
    $field = $section->fields()->create([
        'name' => ['ar' => 'الاسم', 'en' => 'Name', 'ckb' => 'ناو'],
        'type' => 'email',
        'ordering' => 1,
    ]);

    $secKey = "record-{$section->getKey()}";
    $fldKey = "record-{$field->getKey()}";

    livewire(EditFormModel::class, ['record' => $form->getKey()])
        ->assertSet('activeLocale', 'ar')
        ->assertSet('data.name', 'استمارة')
        ->assertSet('data.meta.meta_title', 'ميتا')
        ->assertSet("data.sections.{$secKey}.name", 'القسم ١')
        ->assertSet("data.sections.{$secKey}.fields.{$fldKey}.name", 'الاسم')
        ->assertSet("data.sections.{$secKey}.fields.{$fldKey}.type", 'email')
        ->set("data.sections.{$secKey}.name", 'القسم ١ المعدل')
        ->set("data.sections.{$secKey}.fields.{$fldKey}.name", 'الاسم المعدل')
        ->set('data.setting.theme', 'ocean')
        ->set('activeLocale', 'en')
        ->assertSet('data.name', 'Contact Form')
        ->assertSet('data.meta.meta_title', 'Meta EN')
        ->assertSet('data.setting.theme', 'ocean')
        ->assertSet("data.sections.{$secKey}.name", 'Section 1')
        ->assertSet("data.sections.{$secKey}.fields.{$fldKey}.name", 'Name')
        ->assertSet("data.sections.{$secKey}.fields.{$fldKey}.type", 'email')
        ->set("data.sections.{$secKey}.name", 'Section 1 Updated')
        ->set("data.sections.{$secKey}.fields.{$fldKey}.name", 'Email Address')
        ->set('data.meta.meta_title', 'Meta EN Updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($form->refresh()->meta->getTranslations('meta_title'))->toEqualCanonicalizing([
        'ar' => 'ميتا',
        'en' => 'Meta EN Updated',
        'ckb' => 'مێتا',
    ])->and($form->setting->theme)->toBe('ocean');

    expect($section->refresh()->getTranslations('name'))->toEqualCanonicalizing([
        'ar' => 'القسم ١ المعدل',
        'en' => 'Section 1 Updated',
        'ckb' => 'بەشی ١',
    ]);

    expect($field->refresh()->getTranslations('name'))->toEqualCanonicalizing([
        'ar' => 'الاسم المعدل',
        'en' => 'Email Address',
        'ckb' => 'ناو',
    ])->and($field->type)->toBe('email');
});

it('adds a new repeater item on edit, translates it across locales, saves, and keeps locale switching working after save', function () {
    $form = FormModel::create([
        'name' => ['ar' => 'استمارة', 'en' => 'Form'],
    ]);
    $existingSection = $form->sections()->create([
        'name' => ['ar' => 'قديم', 'en' => 'Existing'],
        'ordering' => 1,
    ]);

    $existingKey = "record-{$existingSection->getKey()}";

    $page = livewire(EditFormModel::class, ['record' => $form->getKey()])
        ->assertSet('activeLocale', 'ar')
        ->set('data.sections.new-sec', [
            'name' => 'قسم جديد',
            'fields' => [
                'new-fld' => [
                    'name' => 'حقل جديد',
                    'type' => 'textarea',
                ],
            ],
        ])
        ->set('activeLocale', 'en')
        ->assertSet("data.sections.{$existingKey}.name", 'Existing')
        ->assertSet('data.sections.new-sec.name', null)
        ->assertSet('data.sections.new-sec.fields.new-fld.name', null)
        ->assertSet('data.sections.new-sec.fields.new-fld.type', 'textarea')
        ->set('data.sections.new-sec.name', 'New Section')
        ->set('data.sections.new-sec.fields.new-fld.name', 'New Field')
        ->call('save')
        ->assertHasNoErrors();

    $sections = $form->refresh()->sections()->orderBy('ordering')->with('fields')->get();
    expect($sections)->toHaveCount(2);

    $newSection = $sections[1];
    $newField = $newSection->fields->sole();

    expect($newSection->getTranslations('name'))->toEqualCanonicalizing([
        'ar' => 'قسم جديد',
        'en' => 'New Section',
    ])->and($newField->getTranslations('name'))->toEqualCanonicalizing([
        'ar' => 'حقل جديد',
        'en' => 'New Field',
    ])->and($newField->type)->toBe('textarea');

    // Switching back to 'ar' on the same Edit page after save uses remapped record-{id} keys.
    $newSecKey = "record-{$newSection->getKey()}";
    $newFldKey = "record-{$newField->getKey()}";

    $page->set('activeLocale', 'ar')
        ->assertSet("data.sections.{$newSecKey}.name", 'قسم جديد')
        ->assertSet("data.sections.{$newSecKey}.fields.{$newFldKey}.name", 'حقل جديد');
});

it('does not resurrect a deleted repeater item when switching locales', function () {
    $form = FormModel::create([
        'name' => ['ar' => 'استمارة', 'en' => 'Form'],
    ]);
    $sec1 = $form->sections()->create(['name' => ['ar' => 'أول', 'en' => 'First'], 'ordering' => 1]);
    $sec2 = $form->sections()->create(['name' => ['ar' => 'ثاني', 'en' => 'Second'], 'ordering' => 2]);

    $key1 = "record-{$sec1->getKey()}";
    $key2 = "record-{$sec2->getKey()}";

    livewire(EditFormModel::class, ['record' => $form->getKey()])
        ->set('data.sections', [
            $key1 => [
                'name' => 'أول',
                'fields' => [],
            ],
        ])
        ->set('activeLocale', 'en')
        ->assertSet("data.sections.{$key1}.name", 'First')
        ->assertSet("data.sections.{$key2}", null)
        ->call('save')
        ->assertHasNoErrors();

    expect($form->refresh()->sections)->toHaveCount(1)
        ->and($form->sections->sole()->getKey())->toBe($sec1->getKey());
});

it('does not save non-translatable relationships on locale switch', function () {
    $form = FormModel::create(['name' => ['ar' => 'استمارة', 'en' => 'Form']]);
    $form->setting()->create(['theme' => 'original']);

    $page = livewire(EditFormModel::class, ['record' => $form->getKey()])
        ->set('data.setting.theme', 'updated')
        ->set('activeLocale', 'en');

    expect($form->setting()->value('theme'))->toBe('original');

    $page->assertSet('data.setting.theme', 'updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($form->setting()->value('theme'))->toBe('updated');
});

it('validates required fields inside translatable repeaters on create when validateAllLocales is enabled', function () {
    filament()->getCurrentPanel()->plugin(
        SpatieTranslatablePlugin::make()
            ->defaultLocales(['ar', 'en'])
            ->validateAllLocales(true)
    );

    livewire(CreateFormModel::class)
        ->assertSet('activeLocale', 'ar')
        ->set('data.name', 'استمارة')
        ->set('data.sections', [
            'sec-1' => [
                'name' => 'القسم الأول',
                'fields' => [
                    'fld-1' => [
                        'name' => 'الاسم',
                        'type' => 'text',
                    ],
                ],
            ],
        ])
        ->call('create')
        ->assertHasErrors([
            'data.name' => 'required',
            'data.sections.sec-1.name' => 'required',
            'data.sections.sec-1.fields.fld-1.name' => 'required',
        ])
        ->assertSet('activeLocale', 'en')
        ->set('data.name', 'Form EN')
        ->set('data.sections.sec-1.name', 'Section 1 EN')
        ->set('data.sections.sec-1.fields.fld-1.name', 'Name EN')
        ->call('create')
        ->assertHasNoErrors();

    $form = FormModel::with('sections.fields')->sole();
    expect($form->sections->sole()->getTranslations('name'))->toEqualCanonicalizing([
        'ar' => 'القسم الأول',
        'en' => 'Section 1 EN',
    ])->and($form->sections->sole()->fields->sole()->getTranslations('name'))->toEqualCanonicalizing([
        'ar' => 'الاسم',
        'en' => 'Name EN',
    ]);
});

it('validates newly added repeater item across locales on edit when validateAllLocales is enabled and recovers cleanly', function () {
    filament()->getCurrentPanel()->plugin(
        SpatieTranslatablePlugin::make()
            ->defaultLocales(['ar', 'en'])
            ->validateAllLocales(true)
    );

    $form = FormModel::create(['name' => ['ar' => 'استمارة', 'en' => 'Form']]);

    livewire(EditFormModel::class, ['record' => $form->getKey()])
        ->assertSet('activeLocale', 'ar')
        ->set('data.sections.new-sec', [
            'name' => 'قسم جديد',
            'fields' => [],
        ])
        ->call('save')
        ->assertHasErrors(['data.sections.new-sec.name' => 'required'])
        ->assertSet('activeLocale', 'en')
        ->assertSet('data.sections.new-sec.name', null)
        ->set('data.sections.new-sec.name', 'New Section EN')
        ->call('save')
        ->assertHasNoErrors();

    expect($form->refresh()->sections->sole()->getTranslations('name'))->toEqualCanonicalizing([
        'ar' => 'قسم جديد',
        'en' => 'New Section EN',
    ]);
});

it('allows adding a new repeater item in one locale on edit when validateAllLocales is false without copying it to other locales', function () {
    $form = FormModel::create(['name' => ['ar' => 'استمارة', 'en' => 'Form']]);

    livewire(EditFormModel::class, ['record' => $form->getKey()])
        ->assertSet('activeLocale', 'ar')
        ->set('data.sections.new-sec', [
            'name' => 'قسم عربي فقط',
            'fields' => [],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect($form->refresh()->sections->sole()->getTranslations('name'))->toBe([
        'ar' => 'قسم عربي فقط',
    ]);
});

it('respects useFallbackLocale when preloading translatable relationship translations on edit', function () {
    config()->set('app.fallback_locale', 'en');

    filament()->getCurrentPanel()->plugin(
        SpatieTranslatablePlugin::make()
            ->defaultLocales(['en', 'ar'])
            ->useFallbackLocale(false)
    );

    $form = FormModel::create(['name' => ['en' => 'Form EN']]);
    $section = $form->sections()->create(['name' => ['en' => 'Section EN'], 'ordering' => 1]);
    $secKey = "record-{$section->getKey()}";

    livewire(EditFormModel::class, ['record' => $form->getKey()])
        ->assertSet('activeLocale', 'en')
        ->assertSet("data.sections.{$secKey}.name", 'Section EN')
        ->set('activeLocale', 'ar')
        ->assertSet("data.sections.{$secKey}.name", '');

    filament()->getCurrentPanel()->plugin(
        SpatieTranslatablePlugin::make()
            ->defaultLocales(['en', 'ar'])
            ->useFallbackLocale(true)
    );

    livewire(EditFormModel::class, ['record' => $form->getKey()])
        ->assertSet('activeLocale', 'en')
        ->set('activeLocale', 'ar')
        ->assertSet("data.sections.{$secKey}.name", 'Section EN');
});

it('switches locales on form-based ViewRecord with translatable nested repeaters', function () {
    $form = FormModel::create(['name' => ['ar' => 'استمارة', 'en' => 'Form']]);
    $section = $form->sections()->create(['name' => ['ar' => 'القسم ١', 'en' => 'Section 1'], 'ordering' => 1]);
    $field = $section->fields()->create(['name' => ['ar' => 'الاسم', 'en' => 'Name'], 'type' => 'text', 'ordering' => 1]);

    $secKey = "record-{$section->getKey()}";
    $fldKey = "record-{$field->getKey()}";

    livewire(ViewFormModel::class, ['record' => $form->getKey()])
        ->assertSet('activeLocale', 'ar')
        ->assertSet("data.sections.{$secKey}.name", 'القسم ١')
        ->assertSet("data.sections.{$secKey}.fields.{$fldKey}.name", 'الاسم')
        ->set('activeLocale', 'en')
        ->assertSet("data.sections.{$secKey}.name", 'Section 1')
        ->assertSet("data.sections.{$secKey}.fields.{$fldKey}.name", 'Name')
        ->set('activeLocale', 'ar')
        ->assertSet("data.sections.{$secKey}.name", 'القسم ١')
        ->assertSet("data.sections.{$secKey}.fields.{$fldKey}.name", 'الاسم');
});
