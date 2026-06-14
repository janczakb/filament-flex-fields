<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Pages;

use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
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

        $this->form->fill(method_exists($playground, 'defaultState') ? $playground->defaultState() : []);
    }

    public function form(Schema $schema): Schema
    {
        $definition = static::resolveDefinition();

        if ($definition === null) {
            return $schema->components([])->statePath('data');
        }

        /** @var object{components(): list<Component>} $playground */
        $playground = app($definition['playground']);

        return $schema
            ->components($playground->components())
            ->statePath('data');
    }

    public function getPlaygroundSlug(): ?string
    {
        return Filament::getCurrentPageConfigurationKey();
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
            ->title('Validation passed')
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
        return [
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
        return FlexFieldsPlaygroundRegistry::find(Filament::getCurrentPageConfigurationKey());
    }
}
