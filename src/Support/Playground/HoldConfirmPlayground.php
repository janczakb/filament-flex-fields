<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Actions\Action;
use Bjanczak\FilamentFlexFields\Support\Admin\HoldConfirmEnterprise;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;

class HoldConfirmPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Hold confirm')
                ->description('Press and hold until the progress fill completes — same HoldConfirmAction used on Filament actions (`->holdConfirm()`).')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    View::make('filament-flex-fields::partials.playground.hold-confirm-demo')
                        ->viewData([
                            'bulkHoldMs' => HoldConfirmEnterprise::bulkHoldMs(),
                            'requiresAuditReason' => HoldConfirmEnterprise::requiresAuditReason(),
                            'keyboard' => HoldConfirmEnterprise::keyboardContract(),
                        ]),
                    Actions::make([
                        Action::make('holdConfirmUpdate')
                            ->label('Hold to update')
                            ->color('primary')
                            ->holdConfirm(2000)
                            ->action(fn () => Notification::make()
                                ->title('Settings updated')
                                ->success()
                                ->send()),
                        Action::make('holdConfirmDeleteFast')
                            ->label('Hold to delete (800ms)')
                            ->icon(GravityIcon::TrashBin)
                            ->color('danger')
                            ->holdConfirm(800)
                            ->action(fn () => Notification::make()
                                ->title('Fast hold confirmed')
                                ->danger()
                                ->send()),
                        Action::make('holdConfirmDeleteSlow')
                            ->label('Hold to delete (4s, left)')
                            ->icon(GravityIcon::TrashBin)
                            ->color('danger')
                            ->holdConfirm(4000, 'left')
                            ->action(fn () => Notification::make()
                                ->title('Slow hold confirmed')
                                ->danger()
                                ->send()),
                        Action::make('holdConfirmBulk')
                            ->label('Hold to delete selected (bulk '.HoldConfirmEnterprise::bulkHoldMs().'ms)')
                            ->icon(GravityIcon::TrashBin)
                            ->color('danger')
                            ->holdConfirm(HoldConfirmEnterprise::bulkHoldMs())
                            ->action(fn () => Notification::make()
                                ->title('Bulk delete would fire')
                                ->danger()
                                ->send()),
                    ]),
                    PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Actions\Action;

Action::make('delete')
    ->label('Hold to delete')
    ->color('danger')
    ->holdConfirm(2000)
    ->action(fn () => /* ... */);

// Duration + fill direction
Action::make('purge')
    ->holdConfirm(4000, 'left');
PHP),
                ]),
        ];
    }
}
