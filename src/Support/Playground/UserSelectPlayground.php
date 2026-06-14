<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class UserSelectPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'user_select__single' => 'jane',
            'user_select__multiple' => ['jane', 'alex', 'sam', 'morgan', 'riley'],
            'user_select__unverified' => 'alex',
            'user_select__members' => $this->defaultMemberIds(),
        ];
    }

    /**
     * @return list<int|string>
     */
    protected function defaultMemberIds(): array
    {
        $userModel = $this->resolveUserModelClass();

        if ($userModel === null) {
            return ['jane', 'alex', 'sam'];
        }

        try {
            $ids = $userModel::query()
                ->orderBy($userModel::make()->getKeyName())
                ->limit(3)
                ->pluck($userModel::make()->getKeyName())
                ->all();

            return $ids !== [] ? $ids : ['jane', 'alex', 'sam'];
        } catch (Throwable) {
            return ['jane', 'alex', 'sam'];
        }
    }

    protected function resolveUserModelClass(): ?string
    {
        $userModel = config('auth.providers.users.model');

        if (! is_string($userModel) || ! class_exists($userModel) || ! is_subclass_of($userModel, Model::class)) {
            return null;
        }

        return $userModel;
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        $users = $this->mockUserOptions();

        return [
            Section::make('UserSelect')
                ->description('Generic user picker with avatars, email secondary line, verification badge, and names/tags multi trigger.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    UserSelect::make('user_select__single')
                        ->label('Assignee')
                        ->helperText('Single select with avatar, name, email, and verified badge.')
                        ->options($users)
                        ->searchable()
                        ->placeholder('Select a user'),
                    UserSelect::make('user_select__multiple')
                        ->label('Team members')
                        ->helperText('Multi select: 1 user looks like single; 2+ shows names in trigger and removable tags below.')
                        ->options($users)
                        ->multiple()
                        ->searchable()
                        ->maxVisibleAvatars(5),
                    UserSelect::make('user_select__unverified')
                        ->label('Reviewer')
                        ->helperText('Initials fallback when no avatar URL is provided.')
                        ->options($users)
                        ->searchable(),
                    ...$this->membersFieldComponents(),
                ]),
        ];
    }

    /**
     * BelongsToMany — pivot (live DB users; on a resource use relationship() instead).
     *
     * @return list<Component>
     */
    protected function membersFieldComponents(): array
    {
        $userModel = $this->resolveUserModelClass();

        if ($userModel === null) {
            return [
                UserSelect::make('user_select__members')
                    ->label('Project members')
                    ->helperText(
                        'Static mock users — optionModel() demo needs a configured User model and database. '
                        .'See the UserColumn section below for read-only table examples.'
                    )
                    ->options($this->mockUserOptions())
                    ->multiple()
                    ->searchable()
                    ->maxVisibleAvatars(5),
            ];
        }

        return [
            UserSelect::make('user_select__members')
                ->label('Project members')
                ->helperText(
                    'BelongsToMany — pivot: UserSelect::make(\'members\')->relationship(\'members\', \'name\')->multiple()->emailColumn(\'email\'). '
                    .'Playground uses optionModel() because this form has no parent record.'
                )
                ->optionModel($userModel)
                ->multiple()
                ->emailColumn('email')
                ->verificationColumn('email_verified_at')
                ->getAvatarUrlUsing(function (Model $record): ?string {
                    if (method_exists($record, 'getFilamentAvatarUrl')) {
                        return $record->getFilamentAvatarUrl();
                    }

                    return null;
                })
                ->searchable()
                ->defaultSuggestionsLimit(5)
                ->maxVisibleAvatars(5),
        ];
    }

    /**
     * @return array<string, array{label: string, description: string, image?: string, verified: bool}>
     */
    protected function mockUserOptions(): array
    {
        return [
            'jane' => [
                'label' => 'Jane Cooper',
                'description' => 'jane.cooper@example.com',
                'image' => 'https://ui-avatars.com/api/?name=Jane+Cooper&background=3b82f6&color=fff&size=128',
                'verified' => true,
            ],
            'alex' => [
                'label' => 'Alex Rivera',
                'description' => 'alex.rivera@example.com',
                'verified' => false,
            ],
            'sam' => [
                'label' => 'Sam Chen',
                'description' => 'sam.chen@example.com',
                'image' => 'https://ui-avatars.com/api/?name=Sam+Chen&background=10b981&color=fff&size=128',
                'verified' => true,
            ],
            'morgan' => [
                'label' => 'Morgan Lee',
                'description' => 'morgan.lee@example.com',
                'verified' => true,
            ],
            'riley' => [
                'label' => 'Riley Brooks',
                'description' => 'riley.brooks@example.com',
                'image' => 'https://ui-avatars.com/api/?name=Riley+Brooks&background=f59e0b&color=fff&size=128',
                'verified' => false,
            ],
            'casey' => [
                'label' => 'Casey Jordan',
                'description' => 'casey.jordan@example.com',
                'verified' => true,
            ],
        ];
    }
}
