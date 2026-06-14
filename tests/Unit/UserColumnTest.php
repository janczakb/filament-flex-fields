<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\UserColumn;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\Playground\UserColumnPlayground;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;

function makeUserColumnTestRecord(array $attributes = []): Model
{
    return new class($attributes) extends Model
    {
        protected $guarded = [];

        public $exists = true;

        public function __construct(array $attributes = [])
        {
            parent::__construct($attributes);
        }

        public function getFilamentAvatarUrl(): ?string
        {
            return $this->attributes['avatar_url'] ?? null;
        }
    };
}

it('extends text column and formats a single user with rich layout', function () {
    $column = UserColumn::make('author')
        ->nameColumn('name')
        ->emailColumn('email')
        ->verificationColumn('email_verified_at');

    $user = makeUserColumnTestRecord([
        'id' => 1,
        'name' => 'Jane Cooper',
        'email' => 'jane@example.com',
        'email_verified_at' => '2024-01-01',
        'avatar_url' => 'https://example.com/jane.png',
    ]);

    $html = $column->formatUserDisplay($user);

    expect($html)
        ->toContain('fff-user-column--rich')
        ->toContain('fff-user-select-option--trigger')
        ->toContain('Jane Cooper')
        ->toContain('jane@example.com')
        ->toContain('fff-user-select__verified-badge')
        ->toContain('https://example.com/jane.png');
});

it('formats multiple users as an overlapping avatar stack with overflow', function () {
    $column = UserColumn::make('members')
        ->maxVisibleAvatars(4)
        ->stackedRing(2)
        ->stackedOverlap(10);

    $users = collect([
        makeUserColumnTestRecord(['id' => 1, 'name' => 'Jane Cooper', 'avatar_url' => 'https://example.com/1.png']),
        makeUserColumnTestRecord(['id' => 2, 'name' => 'Alex Rivera', 'avatar_url' => 'https://example.com/2.png']),
        makeUserColumnTestRecord(['id' => 3, 'name' => 'Sam Chen', 'avatar_url' => 'https://example.com/3.png']),
        makeUserColumnTestRecord(['id' => 4, 'name' => 'Morgan Lee', 'avatar_url' => 'https://example.com/4.png']),
        makeUserColumnTestRecord(['id' => 5, 'name' => 'Taylor Brooks', 'avatar_url' => 'https://example.com/5.png']),
        makeUserColumnTestRecord(['id' => 6, 'name' => 'Riley Park', 'avatar_url' => 'https://example.com/6.png']),
        makeUserColumnTestRecord(['id' => 7, 'name' => 'Casey Stone', 'avatar_url' => 'https://example.com/7.png']),
        makeUserColumnTestRecord(['id' => 8, 'name' => 'Jordan West', 'avatar_url' => 'https://example.com/8.png']),
    ]);

    $html = $column->formatUserDisplay($users);

    expect($html)
        ->toContain('fff-user-column--stacked')
        ->toContain('fff-user-column__avatar-stack')
        ->toContain('fff-user-select__avatar--stack')
        ->toContain('fff-user-column__avatar-stack-overflow')
        ->toContain('+4')
        ->not->toContain('fff-user-select-option--trigger');
});

it('uses initials when avatar url is missing in stack mode', function () {
    $column = UserColumn::make('members');

    $users = [
        makeUserColumnTestRecord(['id' => 1, 'name' => 'Jane Cooper']),
        makeUserColumnTestRecord(['id' => 2, 'name' => 'Alex Rivera']),
    ];

    $html = $column->formatUserDisplay($users);

    expect($html)
        ->toContain('fff-user-select__avatar-initials')
        ->toContain('JC')
        ->toContain('AR');
});

it('returns empty string for blank state', function () {
    $column = UserColumn::make('author');

    expect($column->formatUserDisplay(null))->toBe('')
        ->and($column->formatUserDisplay([]))->toBe('');
});

it('supports custom resolvers aligned with user select api', function () {
    $column = UserColumn::make('owner')
        ->getNameUsing(fn (Model $record): string => 'Custom '.$record->getAttribute('name'))
        ->getEmailUsing(fn (Model $record): ?string => strtoupper((string) $record->getAttribute('email')))
        ->isVerifiedUsing(fn (): bool => true)
        ->getAvatarUrlUsing(fn (): string => 'https://example.com/custom.png');

    $user = makeUserColumnTestRecord([
        'name' => 'Jane',
        'email' => 'jane@example.com',
    ]);

    $html = $column->formatUserDisplay($user);

    expect($html)
        ->toContain('Custom Jane')
        ->toContain('JANE@EXAMPLE.COM')
        ->toContain('https://example.com/custom.png')
        ->toContain('fff-user-select__verified-badge');
});

it('normalizes a single model from an array with one item as rich layout', function () {
    $column = UserColumn::make('author');

    $user = makeUserColumnTestRecord(['name' => 'Solo User', 'email' => 'solo@example.com']);

    $html = $column->formatUserDisplay([$user]);

    expect($html)
        ->toContain('fff-user-column--rich')
        ->toContain('Solo User');
});

it('registers user column playground section after user select', function () {
    $builder = app(FlexFieldsPlaygroundBuilder::class);
    $components = $builder->build();

    $sectionHeadings = collect($components)
        ->filter(fn ($component): bool => $component instanceof Section)
        ->map(fn (Section $section): string => (string) $section->getHeading())
        ->values()
        ->all();

    $userSelectIndex = array_search('UserSelect', $sectionHeadings, true);
    $userColumnIndex = array_search('UserColumn', $sectionHeadings, true);

    expect($userSelectIndex)->not->toBeFalse()
        ->and($userColumnIndex)->not->toBeFalse()
        ->and($userColumnIndex)->toBeGreaterThan($userSelectIndex);
});

it('renders user column playground demo rows with rich and stacked layouts', function () {
    $playground = app(UserColumnPlayground::class);
    $rows = (new ReflectionClass($playground))
        ->getMethod('demoRows')
        ->invoke($playground);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['author'])->toContain('fff-user-column--rich')
        ->and($rows[0]['author'])->toContain('Jane Cooper')
        ->and($rows[1]['members'])->toContain('fff-user-column--stacked')
        ->and($rows[1]['members'])->toContain('+4');
});
