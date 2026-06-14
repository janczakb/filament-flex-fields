<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\UserColumn;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Illuminate\Database\Eloquent\Model;

class UserColumnPlayground
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
            Section::make('UserColumn')
                ->description('Read-only table column with the same user visuals as UserSelect — rich layout for one user, overlapping avatar stack with +N overflow for many.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    View::make('filament-flex-fields::partials.playground.user-column-demo')
                        ->viewData([
                            'rows' => $this->demoRows(),
                        ]),
                ]),
        ];
    }

    /**
     * @return list<array{project: string, author: string, members: string}>
     */
    protected function demoRows(): array
    {
        $authorColumn = UserColumn::make('author')
            ->nameColumn('name')
            ->emailColumn('email')
            ->verificationColumn('email_verified_at')
            ->getAvatarUrlUsing(fn (Model $record): ?string => $record->getAttribute('avatar_url'));

        $membersColumn = UserColumn::make('members')
            ->nameColumn('name')
            ->maxVisibleAvatars(4)
            ->stackedRing(2)
            ->stackedOverlap(10)
            ->getAvatarUrlUsing(fn (Model $record): ?string => $record->getAttribute('avatar_url'));

        return [
            [
                'project' => 'Harbor redesign',
                'author' => $authorColumn->formatUserDisplay($this->makeMockUser([
                    'name' => 'Jane Cooper',
                    'email' => 'jane.cooper@example.com',
                    'email_verified_at' => '2024-01-01',
                    'avatar_url' => 'https://ui-avatars.com/api/?name=Jane+Cooper&background=3b82f6&color=fff&size=128',
                ])),
                'members' => $membersColumn->formatUserDisplay([
                    $this->makeMockUser(['name' => 'Jane Cooper', 'avatar_url' => 'https://ui-avatars.com/api/?name=Jane+Cooper&background=3b82f6&color=fff&size=128']),
                    $this->makeMockUser(['name' => 'Alex Rivera', 'avatar_url' => 'https://ui-avatars.com/api/?name=Alex+Rivera&background=6366f1&color=fff&size=128']),
                ]),
            ],
            [
                'project' => 'Fleet analytics',
                'author' => $authorColumn->formatUserDisplay($this->makeMockUser([
                    'name' => 'Alex Rivera',
                    'email' => 'alex.rivera@example.com',
                    'email_verified_at' => null,
                ])),
                'members' => $membersColumn->formatUserDisplay(collect([
                    $this->makeMockUser(['id' => 1, 'name' => 'Jane Cooper', 'avatar_url' => 'https://ui-avatars.com/api/?name=Jane+Cooper&background=3b82f6&color=fff&size=128']),
                    $this->makeMockUser(['id' => 2, 'name' => 'Alex Rivera', 'avatar_url' => 'https://ui-avatars.com/api/?name=Alex+Rivera&background=6366f1&color=fff&size=128']),
                    $this->makeMockUser(['id' => 3, 'name' => 'Sam Chen', 'avatar_url' => 'https://ui-avatars.com/api/?name=Sam+Chen&background=10b981&color=fff&size=128']),
                    $this->makeMockUser(['id' => 4, 'name' => 'Morgan Lee', 'avatar_url' => 'https://ui-avatars.com/api/?name=Morgan+Lee&background=f59e0b&color=fff&size=128']),
                    $this->makeMockUser(['id' => 5, 'name' => 'Taylor Brooks', 'avatar_url' => 'https://ui-avatars.com/api/?name=Taylor+Brooks&background=ef4444&color=fff&size=128']),
                    $this->makeMockUser(['id' => 6, 'name' => 'Riley Park']),
                    $this->makeMockUser(['id' => 7, 'name' => 'Casey Stone']),
                    $this->makeMockUser(['id' => 8, 'name' => 'Jordan West']),
                ])),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeMockUser(array $attributes = []): Model
    {
        return new class($attributes) extends Model
        {
            protected $guarded = [];

            public $exists = true;

            public function __construct(array $attributes = [])
            {
                parent::__construct($attributes);
            }
        };
    }
}
