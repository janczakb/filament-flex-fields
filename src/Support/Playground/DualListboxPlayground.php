<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\DualListboxField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class DualListboxPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'dual_listbox__basic' => ['read', 'write'],
            'dual_listbox__permissions' => ['users.view', 'posts.edit'],
            'dual_listbox__flat' => ['tailwind'],
            'dual_listbox__sm' => ['draft'],
            'dual_listbox__lg' => ['published'],
            'dual_listbox__disabled' => ['read'],
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        $statusOptions = [
            'draft' => 'Draft',
            'reviewing' => 'Reviewing',
            'published' => 'Published',
            'archived' => 'Archived',
        ];

        $techOptions = [
            'tailwind' => 'Tailwind CSS',
            'alpine' => 'Alpine.js',
            'laravel' => 'Laravel',
            'livewire' => 'Livewire',
            'react' => 'React',
            'vue' => 'Vue',
        ];

        $permissionOptions = [
            'users.view' => [
                'label' => 'View users',
                'description' => 'Browse the user directory',
            ],
            'users.create' => [
                'label' => 'Create users',
                'description' => 'Invite and register accounts',
            ],
            'users.delete' => [
                'label' => 'Delete users',
                'description' => 'Remove accounts permanently',
            ],
            'posts.view' => [
                'label' => 'View posts',
                'description' => 'Read published content',
            ],
            'posts.edit' => [
                'label' => 'Edit posts',
                'description' => 'Update existing articles',
            ],
            'posts.publish' => [
                'label' => 'Publish posts',
                'description' => 'Make content public',
            ],
            'billing.view' => [
                'label' => 'View billing',
                'description' => 'See invoices and plans',
            ],
            'billing.manage' => [
                'label' => 'Manage billing',
                'description' => 'Change subscription settings',
            ],
        ];

        return [
            Section::make('Dual Listbox')
                ->description('SaaS-style transfer list with search, reorder and compact panels.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    DualListboxField::make('dual_listbox__permissions')
                        ->label('Permissions')
                        ->helperText('Assign capabilities. Double-click moves an item. Hold Cmd/Ctrl for multi-select.')
                        ->options($permissionOptions)
                        ->default(['users.view', 'posts.edit'])
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            DualListboxField::make('dual_listbox__basic')
                                ->label('Access levels')
                                ->options([
                                    'read' => 'Read access',
                                    'write' => 'Write access',
                                    'delete' => 'Delete access',
                                    'admin' => 'Administration',
                                ])
                                ->default(['read', 'write']),
                            DualListboxField::make('dual_listbox__flat')
                                ->label('Flat variant')
                                ->variant('flat')
                                ->options($techOptions),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            DualListboxField::make('dual_listbox__sm')
                                ->label('Small')
                                ->size('sm')
                                ->options($statusOptions),
                            DualListboxField::make('dual_listbox__lg')
                                ->label('Large')
                                ->size('lg')
                                ->options($statusOptions),
                            DualListboxField::make('dual_listbox__disabled')
                                ->label('Disabled')
                                ->disabled()
                                ->options($statusOptions),
                        ]),
                ]),
        ];
    }
}
