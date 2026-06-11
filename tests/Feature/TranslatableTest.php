<?php

namespace Tests\Unit;

use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Spatie\Translatable\HasTranslations;

class DummyModel extends Model
{
    use HasTranslations;

    protected $table = 'dummy_models';

    protected $guarded = [];

    public $translatable = ['title'];
}

class DummyResource extends Resource
{
    protected static ?string $model = DummyModel::class;

    public static function getTranslatableAttributes(): array
    {
        return ['title'];
    }

    public static function getTranslatableLocales(): array
    {
        return ['en', 'ar'];
    }

    public static function getDefaultTranslatableLocale(): string
    {
        return 'en';
    }
}

class CreateDummyModel extends CreateRecord
{
    use Translatable;

    protected static string $resource = DummyResource::class;

    public function getFormSchema(): array
    {
        return [
            TextInput::make('title')->required(),
        ];
    }

    // Helper to expose the protected method for testing
    public function testHandleRecordCreation(array $data)
    {
        return $this->handleRecordCreation($data);
    }

    public function getModel(): string
    {
        return DummyModel::class;
    }
}

beforeEach(function () {
    Schema::create('dummy_models', function (Blueprint $table) {
        $table->id();
        $table->json('title')->nullable();
        $table->timestamps();
    });

    // Create a mock panel for the test
    $panel = Panel::make()
        ->id('test')
        ->default()
        ->plugin(SpatieTranslatablePlugin::make()->validateAllLocales(true));

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);
});

it('validates required fields across all locales during record creation', function () {
    $component = new CreateDummyModel;
    $component->activeLocale = 'en';

    // Simulate setting data for 'en'
    $component->data = [
        'title' => 'English Title',
    ];

    // Form setup mock
    $component->form = new class($component->data)
    {
        public $data;

        public function __construct($data)
        {
            $this->data = $data;
        }

        public function validate()
        {
            throw ValidationException::withMessages(['title' => 'Title is required']);
        }

        public function getState()
        {
            return $this->data;
        }

        public function fill($data)
        {
            $this->data = $data;
        }
    };

    // Simulate other locale data with missing required field
    $component->otherLocaleData = [
        'ar' => [
            'title' => null, // Missing required field!
        ],
    ];

    $exceptionThrown = false;

    try {
        $component->testHandleRecordCreation($component->data);
    } catch (ValidationException $e) {
        $exceptionThrown = true;
        // Assert that the active locale switched to the failing one
        expect($component->activeLocale)->toBe('ar');
    }

    expect($exceptionThrown)->toBeTrue('ValidationException was not thrown for missing required field in AR locale');
});

it('does not throw if validateAllLocales is false and locale is untouched', function () {
    // Disable it for this test
    filament()->getCurrentPanel()->plugin(SpatieTranslatablePlugin::make()->validateAllLocales(false));

    $component = new CreateDummyModel;
    $component->activeLocale = 'en';

    // Simulate setting data for 'en'
    $component->data = [
        'title' => 'English Title',
    ];

    // Form setup mock
    $component->form = new class($component->data)
    {
        public $data;

        public function __construct($data)
        {
            $this->data = $data;
        }

        public function validate()
        {
            throw ValidationException::withMessages(['title' => 'Title is required']);
        }

        public function getState()
        {
            return $this->data;
        }

        public function fill($data)
        {
            $this->data = $data;
        }
    };

    // Simulate untouched other locale data (not present in otherLocaleData)
    $component->otherLocaleData = [];

    $exceptionThrown = false;

    try {
        $component->testHandleRecordCreation($component->data);
    } catch (ValidationException $e) {
        $exceptionThrown = true;
    }

    expect($exceptionThrown)->toBeFalse('ValidationException was unexpectedly thrown when validateAllLocales is false and other locale untouched');
});
