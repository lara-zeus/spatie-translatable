<?php

namespace Tests\Feature;

use Filament\Panel;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LaraZeus\SpatieTranslatable\SpatieTranslatableContentDriver;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Mockery;
use Spatie\Translatable\HasTranslations;

class DriverTranslatablePost extends Model
{
    use HasTranslations;

    protected $table = 'driver_posts';

    protected $guarded = [];

    public array $translatable = ['title'];
}

class DriverPlainPost extends Model
{
    protected $table = 'driver_plain_posts';

    protected $guarded = [];
}

class DriverCustomTranslatablePost extends Model
{
    protected $table = 'driver_plain_posts';

    protected $guarded = [];

    public function getTranslatableAttributes(): array
    {
        return ['title'];
    }
}

beforeEach(function () {
    Schema::create('driver_posts', function (Blueprint $table) {
        $table->id();
        $table->json('title')->nullable();
        $table->string('slug')->nullable();
        $table->timestamps();
    });

    Schema::create('driver_plain_posts', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
        $table->timestamps();
    });
});

it('checks whether an attribute is translatable', function () {
    $driver = new SpatieTranslatableContentDriver('en');

    expect($driver->isAttributeTranslatable(DriverTranslatablePost::class, 'title'))->toBeTrue()
        ->and($driver->isAttributeTranslatable(DriverTranslatablePost::class, 'slug'))->toBeFalse()
        ->and($driver->isAttributeTranslatable(DriverPlainPost::class, 'title'))->toBeFalse();
});

it('makes, sets locale on, and updates translatable and plain records', function () {
    $driver = new SpatieTranslatableContentDriver('ar');

    /** @var DriverTranslatablePost $record */
    $record = $driver->makeRecord(DriverTranslatablePost::class, [
        'title' => 'مرحبا',
        'slug' => 'hello',
    ]);

    expect($record->getLocale())->toBe('ar')
        ->and($record->getTranslation('title', 'ar'))->toBe('مرحبا')
        ->and($record->slug)->toBe('hello');

    $record->save();

    $plain = $driver->makeRecord(DriverPlainPost::class, ['title' => 'Plain']);
    expect($driver->setRecordLocale($plain))->toBe($plain);

    $driver->setRecordLocale($record);
    expect($record->getLocale())->toBe('ar');

    $driver->updateRecord($record, [
        'title' => 'تحديث',
        'slug' => 'updated',
    ]);

    expect($record->refresh()->getTranslation('title', 'ar'))->toBe('تحديث')
        ->and($record->slug)->toBe('updated');
});

it('gets record attributes to array respecting plugin useFallbackLocale', function () {
    $record = DriverTranslatablePost::create([
        'title' => ['en' => 'English Only'],
        'slug' => 'english-only',
    ]);

    $panel = Panel::make()
        ->id('driver-test')
        ->default()
        ->plugin(SpatieTranslatablePlugin::make()->useFallbackLocale(false));

    filament()->registerPanel($panel);
    filament()->setCurrentPanel($panel);

    $driver = new SpatieTranslatableContentDriver('ar');

    expect($driver->getRecordAttributesToArray($record)['title'])->toBe('');

    $panelWithFallback = Panel::make()
        ->id('driver-fallback-test')
        ->default()
        ->plugin(SpatieTranslatablePlugin::make()->useFallbackLocale(true));

    filament()->registerPanel($panelWithFallback);
    filament()->setCurrentPanel($panelWithFallback);

    expect($driver->getRecordAttributesToArray($record)['title'])->toBe('English Only');

    $panelWithoutPlugin = Panel::make()
        ->id('driver-no-plugin-test')
        ->default();

    filament()->registerPanel($panelWithoutPlugin);
    filament()->setCurrentPanel($panelWithoutPlugin);

    expect($driver->getRecordAttributesToArray($record)['title'])->toBe('');

    $plain = DriverPlainPost::create(['title' => 'Plain']);
    expect($driver->getRecordAttributesToArray($plain)['title'])->toBe('Plain');

    $custom = new DriverCustomTranslatablePost(['title' => 'Custom']);
    expect($driver->getRecordAttributesToArray($custom)['title'])->toBe('Custom');
});

it('applies search constraints for sqlite and pgsql drivers', function () {
    $driver = new SpatieTranslatableContentDriver('en');

    DriverTranslatablePost::create(['title' => ['en' => 'Find Me', 'ar' => 'لا']]);
    DriverTranslatablePost::create(['title' => ['en' => 'Other', 'ar' => 'نعم']]);

    $results = $driver->applySearchConstraintToQuery(
        DriverTranslatablePost::query(),
        'title',
        'Find',
        'where',
    )->get();

    expect($results)->toHaveCount(1);

    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->andReturn('pgsql');
    $grammar = new PostgresGrammar($connection);
    $connection->shouldReceive('getQueryGrammar')->andReturn($grammar);
    $connection->shouldReceive('getConfig')->andReturn(null);

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('getConnection')->andReturn($connection);
    $query->shouldReceive('where')->once()->with(
        Mockery::on(fn ($expr): bool => $expr instanceof Expression && str_contains((string) $expr->getValue($grammar), "->>'en'")),
        'like',
        '%test%'
    )->andReturnSelf();

    expect($driver->applySearchConstraintToQuery($query, 'title', 'test', 'where', false))->toBe($query);
});
