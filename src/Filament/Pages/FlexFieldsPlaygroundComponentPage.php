<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Pages;

use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
use Bjanczak\FilamentFlexFields\Support\Playground\Contracts\PlaygroundWithPersistence;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexFieldsPlaygroundStore;
use Bjanczak\FilamentFlexFields\Support\Playground\PlaygroundCodeSnippet;
use Bjanczak\FilamentFlexFields\Support\Wow\CommandPaletteCatalog;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class FlexFieldsPlaygroundComponentPage extends Page implements HasForms
{
    use InteractsWithForms;

    /**
     * @var class-string<Cluster>|null
     */
    protected static ?string $cluster = FlexFieldsPlaygroundCluster::class;

    protected string $view = 'filament-flex-fields::pages.flex-fields-playground-component';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return FlexFieldsPlaygroundRegistry::isEnabled()
            && auth()->check()
            && filled(static::resolveDefinition());
    }

    public function getTitle(): string|Htmlable
    {
        return static::resolveDefinition()['label'] ?? static::$title ?? 'Flex Fields Playground';
    }

    public static function getNavigationLabel(): string
    {
        return static::resolveDefinition()['label'] ?? parent::getNavigationLabel();
    }

    public function mount(): void
    {
        $definition = static::resolveDefinition();

        if ($definition === null) {
            abort(404);
        }

        /** @var object{components(): list<Component>, defaultState?: (): array<string, mixed>} $playground */
        $playground = app($definition['playground']);

        $state = method_exists($playground, 'defaultState') ? $playground->defaultState() : [];

        if ($playground instanceof PlaygroundWithPersistence) {
            $stored = app(FlexFieldsPlaygroundStore::class)->get($playground->playgroundSlug());

            if ($stored !== null) {
                $keys = $playground->persistedStateKeys();

                foreach ($stored as $key => $value) {
                    if ($keys === null || in_array($key, $keys, true) || $key === '_meta') {
                        $state[$key] = $value;
                    }
                }
            }
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $definition = static::resolveDefinition();

        if ($definition === null) {
            return $schema->components([])->statePath('data');
        }

        /** @var object{components(): list<Component>} $playground */
        $playground = app($definition['playground']);

        $components = $playground->components();

        if (! PlaygroundCodeSnippet::playgroundDeclaresSnippet($definition['playground'])) {
            $components[] = PlaygroundCodeSnippet::forHub(
                (string) ($definition['slug'] ?? $this->getPlaygroundSlug() ?? 'hub'),
                $definition['playground'],
            );
        }

        return $schema
            ->components($components)
            ->statePath('data');
    }

    public function getPlaygroundSlug(): ?string
    {
        return Filament::getCurrentPageConfigurationKey() ?? static::resolveSlugFromRequest();
    }

    /**
     * ⌘K palette entries with resolved playground URLs.
     *
     * Built in PHP because Livewire compiles Blade as a closure — `use`
     * statements inside `@php` are a parse error.
     *
     * @return list<array{id: string, label: string, playground_slug: string|null, kind?: string, url: string|null}>
     */
    public function commandPaletteEntries(): array
    {
        return array_values(array_map(
            static function (array $entry): array {
                $slug = $entry['playground_slug'] ?? null;

                return [
                    ...$entry,
                    'url' => filled($slug)
                        ? static::getUrl(configuration: $slug)
                        : null,
                ];
            },
            CommandPaletteCatalog::all(),
        ));
    }

    /**
     * @return array<NavigationItem>
     */
    public function getSubNavigation(): array
    {
        return app(FlexFieldsPlaygroundCluster::class)->getSubNavigation();
    }

    public function dumpState(): void
    {
        Notification::make()
            ->title('Current form state')
            ->body('<pre class="text-xs overflow-x-auto whitespace-pre-wrap">'.e(json_encode($this->form->getState(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)).'</pre>')
            ->success()
            ->persistent()
            ->send();
    }

    public function resetState(): void
    {
        $this->mount();

        Notification::make()
            ->title('Playground reset')
            ->success()
            ->send();
    }

    public function validateForm(): void
    {
        $this->form->validate();

        Notification::make()
            ->title(__('filament-flex-fields::default.flex_fields_playground.validation_passed'))
            ->success()
            ->send();
    }

    public function savePlaygroundState(): void
    {
        $definition = static::resolveDefinition();

        if ($definition === null) {
            return;
        }

        /** @var object $playground */
        $playground = app($definition['playground']);

        if (! $playground instanceof PlaygroundWithPersistence) {
            return;
        }

        $this->form->validate();

        $state = $this->form->getState();
        $keys = $playground->persistedStateKeys();
        $toStore = [];

        foreach ($state as $key => $value) {
            if ($keys === null || in_array($key, $keys, true)) {
                $toStore[$key] = $value;
            }
        }

        if (method_exists($playground, 'sealPersistedState')) {
            $toStore = $playground->sealPersistedState($toStore);
        }

        app(FlexFieldsPlaygroundStore::class)->put($playground->playgroundSlug(), $toStore);

        $sealedAt = is_array($toStore['_meta'] ?? null)
            ? ($toStore['_meta']['sealed_at'] ?? null)
            : null;

        Notification::make()
            ->title(__('filament-flex-fields::default.flex_fields_playground.saved'))
            ->body($sealedAt ? __('filament-flex-fields::default.flex_fields_playground.saved_sealed_at', ['at' => $sealedAt]) : null)
            ->success()
            ->send();
    }

    public function verifyVerificationCodeDemo(string $code): void
    {
        Notification::make()
            ->title('Verification code received')
            ->body($code)
            ->success()
            ->send();
    }

    public function resendVerificationCodeDemo(): void
    {
        Notification::make()
            ->title('Verification code resent')
            ->body('A new code was sent to a****@gmail.com')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        $definition = static::resolveDefinition();

        if ($definition !== null) {
            $playground = app($definition['playground']);

            if ($playground instanceof PlaygroundWithPersistence) {
                $actions[] = Action::make('savePlaygroundState')
                    ->label(__('filament-flex-fields::default.flex_fields_playground.save'))
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->color('primary')
                    ->action('savePlaygroundState');
            }
        }

        return [
            ...$actions,
            Action::make('validateForm')
                ->label('Validate')
                ->icon('heroicon-o-shield-check')
                ->color('gray')
                ->action('validateForm'),
            Action::make('dumpState')
                ->label('Dump JSON')
                ->icon('heroicon-o-code-bracket')
                ->color('gray')
                ->action('dumpState')
                ->modalWidth(Width::ExtraLarge),
            Action::make('resetState')
                ->label('Reset defaults')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->action('resetState'),
        ];
    }

    /**
     * @return array{label: string, playground: class-string, sort: int}|null
     */
    protected static function resolveDefinition(): ?array
    {
        $slug = Filament::getCurrentPageConfigurationKey() ?? static::resolveSlugFromRequest();

        if (blank($slug)) {
            return null;
        }

        return FlexFieldsPlaygroundRegistry::find($slug);
    }

    protected static function resolveSlugFromRequest(): ?string
    {
        if (preg_match('#flex-fields-playground/([^/]+)#', request()->path(), $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
