<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Support\Assets\InitialSchemaLayout;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class InitialLayoutPost extends Model {}
class InitialLayoutComment extends Model {}

function initialLayoutPost(): InitialLayoutPost
{
    $post = (new InitialLayoutPost)->forceFill(['id' => 1]);
    $post->setRelation('comments', collect([
        (new InitialLayoutComment)->forceFill(['id' => 2, 'body' => 'Published comment', 'is_published' => true]),
        (new InitialLayoutComment)->forceFill(['id' => 3, 'body' => 'Private comment', 'is_published' => false]),
    ]));

    return $post;
}

it('does not evaluate native entry visibility during explicit layout preparation', function (): void {
    $calls = [];
    $repeatable = RepeatableEntry::make('comments')->schema([
        TextEntry::make('body')->visible(function (InitialLayoutComment $record) use (&$calls): bool {
            $calls[] = $record->id;

            return $record->is_published;
        }),
    ]);
    $schema = Schema::make(new TestableTranslatableForm)->model(initialLayoutPost())->components([$repeatable]);
    InitialSchemaLayout::prepare($schema);
    expect($calls)->toBe([]);
    foreach ($repeatable->getChildSchemas() as $item) {
        $item->getComponents();
    }
    expect($calls)->toBe([2, 3]);
});

it('renders native-only repeatables with typed item visibility without a FLEX panel', function (bool $hidden): void {
    $entry = TextEntry::make('body');
    if ($hidden) {
        $entry->hidden(fn (InitialLayoutComment $record): bool => ! $record->is_published);
    } else {
        $entry->visible(fn (InitialLayoutComment $record): bool => $record->is_published);
    }
    $schema = Schema::make(new TestableTranslatableForm)->model(initialLayoutPost())
        ->components([RepeatableEntry::make('comments')->schema([$entry])]);
    $html = $schema->toHtml();
    expect($html)->toContain('Published comment')->not->toContain('Private comment', 'data-fff-initial-layout');
})->with([false, true]);

it('prepares actual item tabs without invoking the repeatable template visibility', function (): void {
    $seen = [];
    $repeatable = RepeatableEntry::make('comments')->schema([
        Tabs::make('Item tabs')->tabs([
            Tab::make('Overview')->schema([
                FlexTextInput::make('body')->visible(function (InitialLayoutComment $record) use (&$seen): bool {
                    $seen[] = $record->id;

                    return $record->is_published;
                }),
            ]),
            Tab::make('Later')->schema(fn (): array => throw new RuntimeException('Dormant schema was evaluated')),
        ]),
    ]);
    $schema = Schema::make(new TestableTranslatableForm)->model(initialLayoutPost())->components([$repeatable]);
    InitialSchemaLayout::prepare($schema);
    $items = $repeatable->getChildSchemas();
    $firstTabs = $items[0]->getComponents()[0];
    expect($firstTabs->getExtraAttributes()['data-fff-initial-layout'] ?? null)->toBe('native-tabs')
        ->and($seen)->not->toContain(1);
});

it('does not evaluate unused builder block templates', function (): void {
    $owner = new TestableTranslatableForm;
    $schema = Schema::make($owner)->statePath('data')->model(initialLayoutPost())->components([
        Builder::make('blocks')->blocks([
            Block::make('used')->schema([FlexTextInput::make('title')]),
            Block::make('unused')->schema(fn (): array => throw new RuntimeException('Unused block template evaluated')),
        ]),
    ]);
    $schema->fill(['blocks' => [['type' => 'used', 'data' => ['title' => 'Live block']]]]);
    InitialSchemaLayout::prepare($schema);
    expect($owner->data['blocks'])->toHaveCount(1);
});

it('uses actual repeater item state while preparing item tabs', function (): void {
    $owner = new TestableTranslatableForm;
    $seen = [];
    $repeater = Repeater::make('items')->schema([
        Tabs::make('Repeated tabs')->tabs([
            Tab::make('Live')->schema([FlexTextInput::make('name')->visible(function ($get) use (&$seen): bool {
                $seen[] = $get('name');

                return true;
            })]),
            Tab::make('Dormant')->schema(fn (): array => throw new RuntimeException('Dormant repeater tab evaluated')),
        ]),
    ]);
    $schema = Schema::make($owner)->statePath('data')->components([$repeater]);
    $schema->getComponents(withHidden: true);
    $owner->data = ['items' => ['alpha' => ['name' => 'Alpha'], 'beta' => ['name' => 'Beta']]];
    $seen = [];
    InitialSchemaLayout::prepare($schema);
    expect(array_values(array_unique($seen)))->toBe(['Alpha', 'Beta']);
});

it('does not add a schema-wide visibility walk to native-only rendering', function (): void {
    $calls = 0;
    $schema = Schema::make(new TestableTranslatableForm)->model(initialLayoutPost())->components([
        TextEntry::make('id')->visible(function (InitialLayoutPost $record) use (&$calls): bool {
            $calls++;

            return true;
        }),
    ]);
    $schema->toHtml();
    // Native rendering caches visibility; FLEX must not add another root-wide pass.
    expect($calls)->toBe(1);
});

it('keeps native item visibility out of layout discovery inside native tabs', function (): void {
    $calls = [];
    $repeatable = RepeatableEntry::make('comments')->schema([
        TextEntry::make('body')->visible(function (InitialLayoutComment $record) use (&$calls): bool {
            $calls[] = $record->id;

            return $record->is_published;
        }),
    ]);
    $tabs = Tabs::make('Native only')->tabs([Tab::make('Comments')->schema([$repeatable])]);
    $schema = Schema::make(new TestableTranslatableForm)->model(initialLayoutPost())->components([$tabs]);
    InitialSchemaLayout::prepare($schema);
    expect($calls)->toBe([])
        ->and($tabs->getExtraAttributes())->not->toHaveKey('data-fff-initial-layout');
    // Older Filament tab Blade views require a bound Livewire rendering context.
    foreach ($repeatable->getChildSchemas() as $item) {
        $item->getComponents();
    }
    expect($calls)->toBe([2, 3]);
});

it('prepares the enclosing tab around repeatable FLEX children with actual item records', function (): void {
    $seen = [];
    $repeatable = RepeatableEntry::make('comments')->schema([
        FlexTextInput::make('body')->visible(function (InitialLayoutComment $record) use (&$seen): bool {
            $seen[] = $record->id;

            return $record->is_published;
        }),
    ]);
    $tabs = Tabs::make('Containing tabs')->tabs([Tab::make('Comments')->schema([$repeatable])]);
    Schema::make(new TestableTranslatableForm)->model(initialLayoutPost())->components([$tabs])->getComponents();
    expect($tabs->getExtraAttributes()['data-fff-initial-layout'] ?? null)->toBe('native-tabs')
        ->and($seen)->toContain(2)->not->toContain(1);
});

it('keeps sibling cloned tabs independent when only one item contains visible FLEX', function (): void {
    $repeatable = RepeatableEntry::make('comments')->schema([
        Tabs::make('Per item')->tabs([Tab::make('Overview')->schema([
            FlexTextInput::make('body')->visible(fn (InitialLayoutComment $record): bool => $record->is_published),
        ])]),
    ]);
    Schema::make(new TestableTranslatableForm)->model(initialLayoutPost())->components([$repeatable])->getComponents();
    [$first, $second] = $repeatable->getChildSchemas();
    $firstTabs = $first->getComponents()[0];
    $secondTabs = $second->getComponents()[0];
    expect($firstTabs->getExtraAttributes()['data-fff-initial-layout'] ?? null)->toBe('native-tabs')
        ->and($secondTabs->getExtraAttributes())->not->toHaveKey('data-fff-initial-layout');
});

it('preserves explicit preparation if an integration replaces the tab attributes callback', function (): void {
    $tabs = Tabs::make('Custom attributes')->tabs([Tab::make('Live')->schema([FlexTextInput::make('title')])])
        ->extraAttributes(['data-custom' => 'preserved']);
    $schema = Schema::make(new TestableTranslatableForm)->components([$tabs]);
    InitialSchemaLayout::prepare($schema);
    expect($tabs->getExtraAttributes())->toMatchArray(['data-custom' => 'preserved', 'data-fff-initial-layout' => 'native-tabs']);
});

it('does not prepare Livewire fragments or client-persisted tabs', function (bool $livewire): void {
    if ($livewire) {
        request()->headers->set('X-Livewire', 'true');
    }
    $tabs = Tabs::make('Client managed')->persistTab(! $livewire)
        ->tabs([Tab::make('Live')->schema([FlexTextInput::make('title')])]);
    Schema::make(new TestableTranslatableForm)->components([$tabs])->getComponents();
    try {
        expect($tabs->getExtraAttributes())->not->toHaveKey('data-fff-initial-layout');
    } finally {
        request()->headers->remove('X-Livewire');
    }
})->with([false, true]);

it('leaves application exceptions visible and restores the preparation guard for retry', function (): void {
    $fail = true;
    $tabs = Tabs::make('Retry')->tabs([Tab::make('Live')->schema([
        FlexTextInput::make('title')->visible(function () use (&$fail): bool {
            if ($fail) {
                throw new RuntimeException('Application visibility error');
            }

            return true;
        }),
    ])]);
    Schema::make(new TestableTranslatableForm)->components([$tabs])->getComponents();
    expect(fn () => $tabs->getExtraAttributes())->toThrow(RuntimeException::class, 'Application visibility error');
    $fail = false;
    expect($tabs->getExtraAttributes()['data-fff-initial-layout'] ?? null)->toBe('native-tabs');
});
