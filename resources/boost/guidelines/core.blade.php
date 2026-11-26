## Lara Zeus Spatie Translatable (`lara-zeus/spatie-translatable`)

Filament v4/v5 support for `spatie/laravel-translatable` (maintained fork of Filament's Spatie Laravel Translatable plugin with session locale persistence, full-locale validation, and translatable relationship support).

### Key Classes & Traits
- Plugin: `LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin` (ID `'spatie-translatable'`)
  - Methods: `defaultLocales(?array)`, `useFallbackLocale(bool = true)`, `validateAllLocales(bool = true)`, `persist(bool = true)`, `getLocaleLabelUsing(?Closure)`
- Content Driver: `LaraZeus\SpatieTranslatable\SpatieTranslatableContentDriver`
- Resource Trait: `LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable`
- Page Traits (`LaraZeus\SpatieTranslatable\Resources\Pages\*\Concerns\Translatable`):
  - `ListRecords\Concerns\Translatable`, `CreateRecord\Concerns\Translatable`, `EditRecord\Concerns\Translatable`, `ViewRecord\Concerns\Translatable`, `ManageRecords\Concerns\Translatable`
- RelationManager Trait: `LaraZeus\SpatieTranslatable\Resources\RelationManagers\Concerns\Translatable`
- Locale Switcher Action: `LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher` (`Tables\Actions\LocaleSwitcher` is deprecated)
- Supporting Traits: `HasActiveLocaleSwitcher`, `HasTranslatableRelationships`

### Rules & Pitfalls
- **Filament version**: `lara-zeus/spatie-translatable` requires `filament/filament: ^5.0` (v1.x was v4, v2.x is v5). For Filament v3, use `lara-zeus/translatable` (`Filament\` namespace) instead.
- **Namespace**: Always import from `LaraZeus\SpatieTranslatable\...`, never `Filament\SpatieLaravelTranslatablePlugin` or `Filament\Resources\Concerns\Translatable`.
- **Both Resource & Pages need traits**: Applying `Translatable` only to the Resource is not enough—each page (`ListRecords`, `CreateRecord`, `EditRecord`, `ViewRecord`, `ManageRecords`) and `RelationManager` must use its matching `Translatable` trait and include `LocaleSwitcher::make()`.
- **Deprecated action**: Use `LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher::make()` even in `RelationManager::table()->headerActions([...])`; `LaraZeus\SpatieTranslatable\Tables\Actions\LocaleSwitcher` is deprecated.
- **Model requirement**: Models must use `Spatie\Translatable\HasTranslations` and define `$translatable`; otherwise `Translatable::getTranslatableAttributes()` throws `RuntimeException`.
