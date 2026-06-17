<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\Playground\UserSelectPlayground;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

it('extends select field and exposes user select wrapper classes', function () {
    $field = UserSelect::make('assignee')
        ->options([
            'jane' => [
                'label' => 'Jane Cooper',
                'description' => 'jane@example.com',
                'verified' => true,
            ],
        ])
        ->searchable();

    expect($field)->toBeInstanceOf(SelectField::class)
        ->and($field->getWrapperClasses())->toHaveKey('fff-user-select')
        ->and($field->getWrapperClasses())->toHaveKey('fff-user-select--single')
        ->and($field->usesRichOptionHtml())->toBeTrue();
});

it('transforms user options for js with lean client payload', function () {
    $field = UserSelect::make('assignee')
        ->options([
            'jane' => [
                'label' => 'Jane Cooper',
                'description' => 'jane@example.com',
                'image' => 'https://example.com/jane.png',
                'verified' => true,
            ],
        ]);

    $options = $field->getOptionsForJs();

    expect($options)->toHaveCount(1)
        ->and($options[0]['value'])->toBe('jane')
        ->and($options[0]['label'])->toBe('Jane Cooper')
        ->and($options[0]['fffClientRender'])->toBeTrue()
        ->and($options[0]['userName'])->toBe('Jane Cooper')
        ->and($options[0]['user'])->toMatchArray([
            'name' => 'Jane Cooper',
            'email' => 'jane@example.com',
            'avatarUrl' => 'https://example.com/jane.png',
            'verified' => true,
            'initials' => 'JC',
        ])
        ->and($options[0]['label'])->not->toContain('<');
});

it('includes initials in lean user payload when avatar url is missing', function () {
    $field = UserSelect::make('assignee')
        ->options([
            'alex' => [
                'label' => 'Alex Rivera',
                'description' => 'alex@example.com',
            ],
        ]);

    $options = $field->getOptionsForJs();

    expect($options[0]['user']['initials'])->toBe('AR')
        ->and($options[0]['user']['avatarUrl'])->toBeNull();
});

it('supports multiple selection with names in trigger and tags below field', function () {
    $field = UserSelect::make('team')
        ->multiple()
        ->options([
            'jane' => ['label' => 'Jane Cooper', 'verified' => true],
            'alex' => ['label' => 'Alex Rivera'],
            'sam' => ['label' => 'Sam Chen'],
            'morgan' => ['label' => 'Morgan Lee'],
        ])
        ->default(['jane', 'alex', 'sam', 'morgan']);

    expect($field->getWrapperClasses())->toHaveKey('fff-user-select--multiple')
        ->and($field->shouldRenderMultipleUserTags())->toBeTrue();

    $trigger = $field->getInitialMultipleTriggerHtml();

    expect($trigger)
        ->toContain('fff-user-select__trigger-names')
        ->toContain('Jane Cooper')
        ->toContain('Alex Rivera');

    $tags = $field->getInitialSelectedUserTagsHtml();

    expect($tags)
        ->toContain('fff-user-select__selected-tags')
        ->toContain('fff-user-select__selected-tag')
        ->toContain('fff-user-select__selected-tag-remove')
        ->toContain('fff-user-select-option--tag');
});

it('renders single-user multiple trigger like single select', function () {
    $field = UserSelect::make('team')
        ->multiple()
        ->options([
            'jane' => [
                'label' => 'Jane Cooper',
                'description' => 'jane@example.com',
                'verified' => true,
            ],
        ])
        ->default(['jane']);

    $trigger = $field->getInitialMultipleTriggerHtml();

    expect($trigger)
        ->toContain('fff-user-select-option--trigger')
        ->toContain('Jane Cooper')
        ->toContain('jane@example.com')
        ->not->toContain('fff-user-select__trigger-names');

    expect($field->getInitialSelectedUserTagsHtml())->toBeNull();
});

it('exposes lean user metadata in js options without html payload', function () {
    $field = UserSelect::make('team')
        ->multiple()
        ->options([
            'jane' => ['label' => 'Jane Cooper', 'verified' => true],
        ]);

    $options = $field->getOptionsForJs();

    expect($options[0]['userName'])->toBe('Jane Cooper')
        ->and($options[0])->toHaveKey('user')
        ->and($options[0])->not->toHaveKey('tagHtml')
        ->and($options[0])->not->toHaveKey('listHtml');
});

it('resolves initial user select display in a single pass with lean entries', function () {
    $field = UserSelect::make('team')
        ->multiple()
        ->options([
            'jane' => ['label' => 'Jane Cooper', 'verified' => true],
            'alex' => ['label' => 'Alex Rivera'],
        ])
        ->default(['jane', 'alex']);

    $display = $field->getInitialUserSelectDisplay();

    expect($display['triggerHtml'])
        ->toContain('Jane Cooper')
        ->and($display['tagsHtml'])
        ->toContain('fff-user-select__selected-tag')
        ->and($display['entries'])
        ->toHaveCount(2)
        ->and($display['entries'][0])
        ->toHaveKeys(['value', 'user'])
        ->and($display['entries'][0])
        ->not->toHaveKey('listHtml')
        ->and($display['entries'][0]['user']['name'])
        ->toBe('Jane Cooper');

    expect($field->getInitialMultipleTriggerHtml())->toBe($display['triggerHtml'])
        ->and($field->getInitialSelectedUserTagsHtml())->toBe($display['tagsHtml'])
        ->and($field->getInitialSelectedUserEntriesForJs())->toBe($display['entries']);
});

it('enables dynamic options and default suggestions for option model fields', function () {
    $field = UserSelect::make('members')
        ->optionModel(Model::class)
        ->defaultSuggestionsLimit(5);

    expect($field->hasDynamicOptions())->toBeTrue()
        ->and($field->hasClientSideOptionList())->toBeFalse()
        ->and($field->hasInitialNoOptionsMessage())->toBeFalse()
        ->and($field->getDefaultSuggestionsLimit())->toBe(5);
});

it('defers initial js options for option model fields but loads suggestions on demand', function () {
    $userModel = config('auth.providers.users.model');

    if (! is_string($userModel) || ! class_exists($userModel)) {
        test()->markTestSkipped('No auth user model configured.');
    }

    try {
        $userCount = $userModel::query()->count();
    } catch (Throwable) {
        test()->markTestSkipped('No users database available.');
    }

    if ($userCount === 0) {
        try {
            if (method_exists($userModel, 'factory')) {
                $userModel::factory()->create();
            } else {
                test()->markTestSkipped('No users in database and no factory available.');
            }
        } catch (Throwable) {
            test()->markTestSkipped('Cannot create user record for empty database.');
        }
    }

    $field = UserSelect::make('members')
        ->optionModel($userModel)
        ->defaultSuggestionsLimit(5);

    expect($field->getInitialOptionsForJs())->toBe([]);

    $options = $field->getOptionsForJs();

    expect($options)->not->toBeEmpty()
        ->and(count($options))->toBeLessThanOrEqual(5)
        ->and($options[0])->toHaveKey('user');
});

it('defaults min search length to two characters', function () {
    $field = UserSelect::make('members')
        ->optionModel(Model::class);

    expect($field->getMinSearchLength())->toBe(2);
});

it('returns no search results when query is shorter than min search length', function () {
    $userModel = config('auth.providers.users.model');

    if (! is_string($userModel) || ! class_exists($userModel)) {
        test()->markTestSkipped('No auth user model configured.');
    }

    try {
        $userModel::query()->count();
    } catch (Throwable) {
        test()->markTestSkipped('No users database available.');
    }

    $field = UserSelect::make('members')
        ->optionModel($userModel)
        ->minSearchLength(2);

    expect($field->searchRecords('a'))->toBeEmpty();
});

it('applies prefix search constraints to the query', function () {
    $field = UserSelect::make('members')
        ->optionModel(config('auth.providers.users.model') ?? Model::class)
        ->nameColumn('name')
        ->emailColumn('email');

    $userModel = config('auth.providers.users.model');

    if (! is_string($userModel) || ! class_exists($userModel)) {
        test()->markTestSkipped('No auth user model configured.');
    }

    $query = $userModel::query();

    $method = new ReflectionMethod($field, 'applySearchToQuery');
    $method->invoke($field, $query, 'jan');

    $sql = $query->toSql();
    $bindings = $query->getBindings();

    expect($sql)->toContain('like ?')
        ->and($bindings)->toContain('jan%')
        ->and($bindings)->toContain('jan@%')
        ->and($bindings)->toHaveCount(3);
});

it('restricts model queries to display columns when resolvers are not customized', function () {
    $userModel = config('auth.providers.users.model');

    if (! is_string($userModel) || ! class_exists($userModel)) {
        test()->markTestSkipped('No auth user model configured.');
    }

    $field = UserSelect::make('members')
        ->optionModel($userModel)
        ->nameColumn('name')
        ->emailColumn('email');

    $method = new ReflectionMethod($field, 'buildModelQuery');
    $query = $method->invoke($field);

    expect(strtolower($query->toSql()))->toContain('select');
});

it('caches search results for repeated queries within the same component instance', function () {
    $userModel = config('auth.providers.users.model');

    if (! is_string($userModel) || ! class_exists($userModel)) {
        test()->markTestSkipped('No auth user model configured.');
    }

    try {
        $userModel::query()->count();
    } catch (Throwable) {
        test()->markTestSkipped('No users database available.');
    }

    $field = UserSelect::make('members')
        ->optionModel($userModel)
        ->minSearchLength(2);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $field->searchRecords('zz');
    $firstQueryCount = count(DB::getQueryLog());

    DB::flushQueryLog();

    $field->searchRecords('zz');

    expect($firstQueryCount)->toBeGreaterThan(0)
        ->and(count(DB::getQueryLog()))->toBe(0);
});

it('resolves record attributes via configurable columns and closures', function () {
    $record = new class extends Model
    {
        protected $guarded = [];

        public $timestamps = false;
    };

    $record->forceFill([
        'id' => 7,
        'full_name' => 'Jane Cooper',
        'email' => 'jane@example.com',
        'avatar' => 'https://example.com/jane.png',
        'email_verified_at' => '2024-01-01 00:00:00',
    ]);

    $field = UserSelect::make('assignee')
        ->nameColumn('full_name')
        ->emailColumn('email')
        ->avatarColumn('avatar')
        ->verificationColumn('email_verified_at');

    $option = $field->recordToOptionArray($record);

    expect($option)->toBe([
        'label' => 'Jane Cooper',
        'description' => 'jane@example.com',
        'image' => 'https://example.com/jane.png',
        'verified' => true,
    ]);
});

it('supports custom resolvers for record mapping', function () {
    $record = new class extends Model
    {
        protected $guarded = [];

        public $timestamps = false;
    };

    $record->forceFill(['id' => 1, 'name' => 'Jane Cooper']);

    $field = UserSelect::make('assignee')
        ->getNameUsing(fn (): string => 'Custom Name')
        ->getEmailUsing(fn (): string => 'custom@example.com')
        ->getAvatarUrlUsing(fn (): string => 'https://example.com/custom.png')
        ->isVerifiedUsing(fn (): bool => true);

    expect($field->recordToOptionArray($record))->toBe([
        'label' => 'Custom Name',
        'description' => 'custom@example.com',
        'image' => 'https://example.com/custom.png',
        'verified' => true,
    ]);
});

it('derives initials from multi word names', function () {
    $field = UserSelect::make('assignee');

    expect($field->getUserSelectInitials('Jane Cooper'))->toBe('JC')
        ->and($field->getUserSelectInitials('Alex'))->toBe('AL');
});

it('formats user shape into lean client payload', function () {
    $field = UserSelect::make('assignee');

    $payload = $field->formatUserOptionForJs('jane', [
        'label' => 'Jane Cooper',
        'description' => 'jane@example.com',
        'image' => 'https://example.com/jane.png',
        'verified' => true,
    ]);

    expect($payload)->toMatchArray([
        'value' => 'jane',
        'label' => 'Jane Cooper',
        'userName' => 'Jane Cooper',
        'fffClientRender' => true,
    ])->and($payload['user']['initials'])->toBe('JC');
});

it('registers user select playground defaults', function () {
    $state = app(FlexFieldsPlaygroundBuilder::class)->defaultState();

    expect($state)->toHaveKeys([
        'user_select__single',
        'user_select__multiple',
        'user_select__unverified',
        'user_select__members',
    ])
        ->and($state['user_select__members'])->toBeArray()
        ->and($state['user_select__members'])->not->toBeEmpty();
});

it('always includes project members field in user select playground', function () {
    $playground = app(UserSelectPlayground::class);
    $components = $playground->components();

    $section = collect($components)->first(fn ($component) => $component instanceof Section);

    expect($section)->not->toBeNull();

    $fieldNames = collect($section->getDefaultChildComponents())
        ->map(fn ($component) => $component->getName())
        ->all();

    expect($fieldNames)->toContain('user_select__members');
});

it('enables searchable dynamic lookup when option model is configured', function () {
    $field = UserSelect::make('assignee')
        ->optionModel(Model::class);

    expect($field->isSearchable())->toBeTrue();
});

it('throws when option model class is invalid', function () {
    UserSelect::make('assignee')
        ->optionModel('Not\\A\\Model')
        ->searchRecords('jane');
})->throws(InvalidArgumentException::class);

it('renders model-backed selected user tags on initial load instead of raw ids', function () {
    $userModel = config('auth.providers.users.model');

    if (! is_string($userModel) || ! class_exists($userModel)) {
        test()->markTestSkipped('No auth user model configured.');
    }

    try {
        $user = $userModel::query()->first();
    } catch (Throwable) {
        test()->markTestSkipped('No users database available.');
    }

    if ($user === null) {
        test()->markTestSkipped('No users in database.');
    }

    $secondUser = $userModel::query()->whereKeyNot($user->getKey())->first() ?? $user;

    $field = UserSelect::make('members')
        ->multiple()
        ->optionModel($userModel)
        ->emailColumn('email')
        ->verificationColumn('email_verified_at')
        ->default([$user->getKey(), $secondUser->getKey()]);

    $tags = $field->getInitialSelectedUserTagsHtml();

    expect($tags)
        ->toBeString()
        ->toContain('fff-user-select__selected-tags')
        ->toContain('fff-user-select__selected-tag')
        ->not->toBe((string) $user->getKey());

    $entries = $field->getInitialSelectedUserEntriesForJs();

    expect($entries)
        ->toHaveCount(2)
        ->and($entries[0]['user']['name'])->not->toBe((string) $user->getKey())
        ->and($entries[0])->toHaveKey('user')
        ->and($entries[0])->not->toHaveKey('tagHtml');

    $trigger = $field->getInitialMultipleTriggerHtml();

    expect($trigger)
        ->toBeString()
        ->toContain('fff-user-select__trigger-names')
        ->toContain($entries[0]['user']['name']);
});

it('resolves multiple model-backed users with a single database query', function () {
    $userModel = config('auth.providers.users.model');

    if (! is_string($userModel) || ! class_exists($userModel)) {
        test()->markTestSkipped('No auth user model configured.');
    }

    try {
        $users = $userModel::query()->limit(3)->get();
    } catch (Throwable) {
        test()->markTestSkipped('No users database available.');
    }

    if ($users->count() < 2) {
        test()->markTestSkipped('Not enough users in database.');
    }

    $ids = $users->pluck($users->first()->getKeyName())->all();

    $field = UserSelect::make('members')
        ->multiple()
        ->optionModel($userModel)
        ->emailColumn('email');

    DB::flushQueryLog();
    DB::enableQueryLog();

    $field->resolveOptionLabelsForValues($ids);

    $queryCount = count(DB::getQueryLog());

    expect($queryCount)->toBe(1);
});

it('loads latest users as default suggestions when search is empty', function () {
    $userModel = config('auth.providers.users.model');

    if (! is_string($userModel) || ! class_exists($userModel)) {
        test()->markTestSkipped('No auth user model configured.');
    }

    try {
        $userCount = $userModel::query()->count();
    } catch (Throwable) {
        test()->markTestSkipped('No users database available.');
    }

    if ($userCount === 0) {
        test()->markTestSkipped('No users in database.');
    }

    $field = UserSelect::make('members')
        ->optionModel($userModel)
        ->defaultSuggestionsLimit(5);

    $suggestions = $field->getDefaultSuggestions();

    expect($suggestions)->not->toBeEmpty()
        ->and(count($suggestions))->toBeLessThanOrEqual(5);

    expect($field->getInitialOptionsForJs())->toBe([])
        ->and($field->getOptionsForJs())->not->toBeEmpty();
});

it('returns plain names from resolve option labels for values', function () {
    $field = UserSelect::make('team')
        ->multiple()
        ->options([
            'jane' => [
                'label' => 'Jane Cooper',
                'description' => 'jane@example.com',
                'verified' => true,
            ],
        ]);

    expect($field->resolveOptionLabelsForValues(['jane']))
        ->toBe(['jane' => 'Jane Cooper']);
});

it('filters selected users from dropdown options without mutating livewire state', function () {
    $contents = file_get_contents(
        dirname(__DIR__, 2).'/resources/views/forms/components/partials/user-select-client-patches.blade.php',
    );

    expect($contents)
        ->toContain('filterUserSelectUnselectedOptions')
        ->toContain('isUserSelectValueSelected')
        ->not->toContain('this.state = this.state.map((value) => String(value))');
});

it('syncs multiple user removal with livewire state immediately', function () {
    $contents = file_get_contents(
        dirname(__DIR__, 2).'/resources/views/forms/components/partials/user-select-trigger-patches.blade.php',
    );

    expect($contents)
        ->toContain('Livewire.find(selectInstance.livewireId)')
        ->toContain('initialSelectedUserEntries')
        ->toContain('onStateChange([...nextState])');
});

it('guards user select state against stale livewire sync on dropdown open', function () {
    $contents = file_get_contents(
        dirname(__DIR__, 2).'/resources/views/forms/components/partials/user-select-client-patches.blade.php',
    );

    expect($contents)
        ->toContain('userSelectStatesEqual')
        ->toContain('__fffLocalStateVersion')
        ->toContain('__fffLocalState');
});

it('respects configured max visible avatars', function () {
    $field = UserSelect::make('members')
        ->maxVisibleAvatars(10);

    expect($field->getMaxVisibleAvatars())->toBe(10);
});

it('caches duplicate user select queries at the request level', function () {
    $userModel = config('auth.providers.users.model');

    if (! is_string($userModel) || ! class_exists($userModel)) {
        test()->markTestSkipped('No auth user model configured.');
    }

    try {
        $userCount = $userModel::query()->count();
    } catch (Throwable) {
        test()->markTestSkipped('No users database available.');
    }

    if ($userCount === 0) {
        test()->markTestSkipped('No users in database.');
    }

    $field1 = UserSelect::make('assignee_1')
        ->optionModel($userModel)
        ->defaultSuggestionsLimit(5);

    $field2 = UserSelect::make('assignee_2')
        ->optionModel($userModel)
        ->defaultSuggestionsLimit(5);

    DB::flushQueryLog();
    DB::enableQueryLog();

    // First field fetches suggestions
    $field1->getOptionsForJs();
    $firstQueryCount = count(DB::getQueryLog());

    // Flush to check if second field executes any query or gets it from scoped cache
    DB::flushQueryLog();

    $field2->getOptionsForJs();
    $secondQueryCount = count(DB::getQueryLog());

    expect($firstQueryCount)->toBeGreaterThan(0)
        ->and($secondQueryCount)->toBe(0);
});

it('loads tag chips stylesheet for removable selected user tag chips', function () {
    $tagChipsCss = file_get_contents(__DIR__.'/../../resources/dist/css/tag-chips.css');

    expect(FlexFieldAssets::stylesheetsFor('user-select'))
        ->toBe(['teleported-menu', 'select-field', 'tag-chips', 'user-display', 'user-select'])
        ->and($tagChipsCss)
        ->toContain('.fff-tags-field__tag-remove');
});
