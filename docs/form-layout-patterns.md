# Form layout patterns

[← Powrót do spisu treści](../README.md)


Recipes for modern forms **without** heavy Filament `Section` / `Grid` / `Fieldset` chrome. See **Modern form layouts** in Flex Fields Playground.

### 1. Tabbed editor — `SegmentTabs` + `CoverCard`

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\CoverCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardStack;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;

ItemCardStack::make()
    ->stackGap('lg')
    ->extraAttributes(['class' => 'fff-form-layout fff-form-layout--wide'])
    ->schema([
        CoverCard::make()
            ->backgroundImage('https://cdn.example.com/hero.jpg')
            ->ratio('21:9')
            ->fullWidth()
            ->topTitle('New listing'),
        SegmentTabs::make('Listing')
            ->variant('ghost')
            ->fullWidth()
            ->tabs([
                SegmentTab::make('Details')->schema([
                    FlexTextInput::make('name')->label('Name'),
                    FlexTextareaField::make('description')->label('Description'),
                ]),
                SegmentTab::make('Location')->schema([
                    CountryField::make('country'),
                    PhoneField::make('phone'),
                ]),
            ]),
    ]);
```

### 2. iOS settings — `ItemCardGroup`

```php
ItemCardGroup::make('Publishing')
    ->headerStyle('outside')
    ->variant('secondary')
    ->separated()
    ->schema([
        ItemCard::make('Public listing')
            ->icon(GravityIcon::Eye)
            ->schema([SwitchField::make('public')->inline()->size('sm')]),
        ItemCard::make('Notifications')
            ->icon(GravityIcon::Bell)
            ->schema([
                SelectField::make('channel')
                    ->options(['email' => 'Email', 'push' => 'Push'])
                    ->variant('item-card')
                    ->hiddenLabel(),
            ]),
    ]);
```

### 3. Two-column profile cards — `ItemCardStack` grid

```php
ItemCardStack::make()
    ->columns(['default' => 1, 'sm' => 2])
    ->extraAttributes(['class' => 'fff-form-layout fff-form-layout--grid'])
    ->schema([
        ItemCard::make('Profile')
            ->extraAttributes(['class' => 'item-card--form-panel'])
            ->variant('outline')->standalone()
            ->columns(1)
            ->schema([/* fields */]),
        ItemCard::make('Contact')
            ->extraAttributes(['class' => 'item-card--form-panel'])
            ->variant('outline')->standalone()
            ->columns(1)
            ->schema([/* fields */]),
        ItemCard::make('Visibility')
            ->extraAttributes(['class' => 'item-card--form-panel'])
            ->columnSpanFull()
            ->schema([ChoiceCards::make('status')->gridColumns(3)->options([...])]),
    ]);
```

### Layout CSS helpers

| Class | Purpose |
|-------|---------|
| `fff-form-layout` | Constrains width (`max-width: 42rem`) for single-column forms |
| `fff-form-layout--wide` | Removes width cap (banners, tabbed editors) |
| `fff-form-layout--grid` | Enables two-column `ItemCardStack` grid from `640px` |
| `item-card--form-panel` | Vertical field layout inside `ItemCard` |

---

