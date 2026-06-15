<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\UserColumn;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\Playground\UserColumnPlayground;
use Bjanczak\FilamentFlexFields\Support\UserColumnRenderCache;
use Bjanczak\FilamentFlexFields\Support\UserColumnSharedStackCache;
use Bjanczak\FilamentFlexFields\Support\UserColumnStackState;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

function makeUserColumnProjectModel(): Model
{
    return new class extends Model
    {
        protected $table = 'projects';

        public function members(): BelongsToMany
        {
            return $this->belongsToMany(Model::class, 'project_user', 'project_id', 'user_id');
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

it('detects multi-user state so filament does not comma-join rich layouts', function () {
    $column = UserColumn::make('members');

    $method = new ReflectionMethod(UserColumn::class, 'stateContainsMultipleUsers');

    $users = collect([
        makeUserColumnTestRecord(['id' => 1, 'name' => 'Jane Cooper']),
        makeUserColumnTestRecord(['id' => 2, 'name' => 'Alex Rivera']),
    ]);

    expect($method->invoke($column, $users))->toBeTrue()
        ->and($method->invoke($column, makeUserColumnTestRecord(['id' => 1])))->toBeFalse()
        ->and($column->formatUserDisplay($users))
        ->toContain('fff-user-column--stacked')
        ->not->toContain('fff-user-column--rich');
});

it('normalizes stack state wrapper objects for multi-user rendering', function () {
    $column = UserColumn::make('members');

    $users = collect([
        makeUserColumnTestRecord(['id' => 1, 'name' => 'Jane Cooper']),
        makeUserColumnTestRecord(['id' => 2, 'name' => 'Alex Rivera']),
    ]);

    $html = $column->formatUserDisplay(new UserColumnStackState($users));

    expect($html)
        ->toContain('fff-user-column--stacked')
        ->not->toContain('fff-user-select-option--trigger');
});

it('caches identical stack renders within the same request', function () {
    UserColumnRenderCache::flush();

    $column = UserColumn::make('members');

    $users = [
        makeUserColumnTestRecord(['id' => 1, 'name' => 'Jane Cooper']),
        makeUserColumnTestRecord(['id' => 2, 'name' => 'Alex Rivera']),
    ];

    $column->formatUserDisplay($users);
    $column->formatUserDisplay($users);

    expect(UserColumnRenderCache::entries())->toHaveCount(1);
});

it('eager loads direct relationship column names to avoid n plus one queries', function () {
    $column = UserColumn::make('members');
    $query = makeUserColumnProjectModel()->newQuery();

    $column->applyEagerLoading($query);

    expect($query->getEagerLoads())->toHaveKey('members');
});

it('supports explicit eager load relationships via fluent api', function () {
    $column = UserColumn::make('team_preview')
        ->eagerLoad(['members', 'owner']);
    $query = makeUserColumnProjectModel()->newQuery();

    $column->applyEagerLoading($query);

    expect($query->getEagerLoads())
        ->toHaveKey('members')
        ->toHaveKey('owner');
});

it('caches shared stack resolver results by key', function () {
    UserColumnSharedStackCache::flush();

    $calls = 0;
    $resolver = function () use (&$calls) {
        $calls++;

        return ['members'];
    };

    UserColumnSharedStackCache::remember('team-preview', $resolver);
    UserColumnSharedStackCache::remember('team-preview', $resolver);

    expect($calls)->toBe(1)
        ->and(UserColumnSharedStackCache::entries())->toHaveCount(1);
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

it('queues user column stylesheets for the playground page styles before hook', function () {
    config()->set('filament-flex-fields.playground.enabled', true);

    $stylesPartial = file_get_contents(__DIR__.'/../../resources/views/partials/playground-page-stylesheets.blade.php');
    $assetsPartial = file_get_contents(__DIR__.'/../../resources/views/partials/playground-assets.blade.php');
    $themePartial = file_get_contents(__DIR__.'/../../resources/views/partials/playground-theme.blade.php');

    expect($stylesPartial)
        ->toContain('playgroundStylesheetHrefForRequest()')
        ->toContain('rel="stylesheet"')
        ->toContain('data-fff-playground-bundle')
        ->and($assetsPartial)
        ->toContain('fffPrefetchPlaygroundBundle')
        ->toContain('fffEnsurePlaygroundBundle')
        ->and($themePartial)
        ->toContain('window.FffPlaygroundTheme')
        ->toContain('syncFilamentTheme')
        ->toContain("localStorage.setItem('theme'")
        ->toContain('alpine:init');

    app()->instance('request', Illuminate\Http\Request::create('/admin/flex-fields-playground/user-column', 'GET'));

    expect(FlexFieldAssets::playgroundStylesheetHrefForRequest())
        ->toBe(FlexFieldAssets::playgroundBundleHrefForSlug('user-column'));
});

it('does not load stylesheets from table column blade partials', function () {
    $richBlade = file_get_contents(__DIR__.'/../../resources/views/tables/columns/user-column-rich.blade.php');
    $stackBlade = file_get_contents(__DIR__.'/../../resources/views/tables/columns/user-column-stack.blade.php');
    $demoBlade = file_get_contents(__DIR__.'/../../resources/views/partials/playground/user-column-demo.blade.php');

    expect($richBlade)->not->toContain('load-stylesheet')
        ->and($stackBlade)->not->toContain('load-stylesheet')
        ->and($demoBlade)->not->toContain('load-stylesheet');
});

it('registers table column stylesheets during column setup', function () {
    FlexFieldStylesheetQueue::reset();

    UserColumn::make('author');

    expect(FlexFieldStylesheetQueue::registered())
        ->toBe(['user-display', 'user-column']);
});

it('loads user display stylesheet for shared avatar primitives', function () {
    $userDisplayCss = file_get_contents(__DIR__.'/../../resources/dist/css/user-display.css');

    expect(FlexFieldAssets::stylesheetsFor('user-column'))
        ->toBe(['user-display', 'user-column'])
        ->and($userDisplayCss)
        ->toContain('.fff-user-select__avatar');
});

it('styles user column playground table for dark mode in the playground bundle', function () {
    $playgroundCss = file_get_contents(__DIR__.'/../../resources/dist/css/playground.css');

    expect($playgroundCss)
        ->toMatch('/\.dark\s+\.fff-user-column-playground__table-wrap[\s\S]*zinc-900/');
});
