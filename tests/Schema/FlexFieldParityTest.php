<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Data\FlexFieldEntity;
use Bjanczak\FilamentFlexFields\Filament\Pages\FlexFieldManagementPage;
use Bjanczak\FilamentFlexFields\Filament\Schema\VisibilityRuleBuilder;
use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Support\Enterprise\SchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldEncryption;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldEntityDiscovery;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldEntityRegistry;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldValueCsvExchange;
use Bjanczak\FilamentFlexFields\Support\Schema\JsonFieldConditions;
use Illuminate\Auth\GenericUser;
use Livewire\Livewire;

describe('entity discovery and management page', function (): void {
    beforeEach(function (): void {
        SchemaRegistry::clear();
        app(FlexFieldEntityRegistry::class)->forgetCache();
    });

    it('registers manual entities in the registry', function (): void {
        $registry = app(FlexFieldEntityRegistry::class);
        $registry->register(new FlexFieldEntity(
            modelClass: 'App\\Models\\Lead',
            label: 'Leads',
        ));

        expect($registry->find('App\\Models\\Lead')?->label)->toBe('Leads');
    });

    it('discovers configured entities through the registry', function (): void {
        config([
            'filament-flex-fields.schema.entity_discovery.from_filament_resources' => false,
            'filament-flex-fields.schema.entities' => [
                'App\\Models\\Lead' => ['label' => 'Leads', 'sort' => 0],
                'App\\Models\\Contact' => ['label' => 'Contacts', 'sort' => 1],
            ],
        ]);

        app(FlexFieldEntityRegistry::class)->forgetCache();

        expect(app(FlexFieldEntityDiscovery::class)->isEmpty())->toBeFalse()
            ->and(app(FlexFieldEntityRegistry::class)->selectOptions())
            ->toHaveKeys(['App\\Models\\Lead', 'App\\Models\\Contact']);
    });

    it('returns an empty-state hint when discovery finds no entities', function (): void {
        config([
            'filament-flex-fields.schema.entity_discovery.from_filament_resources' => false,
            'filament-flex-fields.schema.entities' => [],
        ]);

        app(FlexFieldEntityRegistry::class)->forgetCache();

        $hint = app(FlexFieldEntityDiscovery::class)->emptyStateHint();

        expect(app(FlexFieldEntityDiscovery::class)->isEmpty())->toBeTrue()
            ->and($hint)->toContain('schema.entities');
    });

    it('renders the flex field studio management page', function (): void {
        $this->actingAs(new GenericUser([
            'id' => 1,
            'email' => 'admin@example.com',
        ]));

        Livewire::test(FlexFieldManagementPage::class)
            ->assertSuccessful()
            ->assertSee('Flex field studio');
    });

    it('shows registry publish state on the management page', function (): void {
        $group = FlexFieldGroup::factory()->create([
            'slug' => 'studio-registry',
            'name' => 'Studio registry group',
        ]);

        $group->publishToRegistry('admin@example.com', SchemaRegistry::STATE_LIVE);

        $this->actingAs(new GenericUser([
            'id' => 1,
            'email' => 'admin@example.com',
        ]));

        Livewire::test(FlexFieldManagementPage::class)
            ->assertSuccessful()
            ->assertSee('Studio registry group')
            ->assertSee('v1')
            ->assertSee('live');
    });
});

describe('visibility rule builder', function (): void {
    it('round-trips repeater state to json rules', function (): void {
        $stored = VisibilityRuleBuilder::repeaterStateToRules([
            [
                'source' => 'model',
                'field' => 'status',
                'operator' => 'equals',
                'value' => 'active',
            ],
        ]);

        expect($stored)->toBe([
            'and' => [[
                'source' => 'model',
                'field' => 'model.status',
                'operator' => 'equals',
                'value' => 'active',
            ]],
        ]);
    });

    it('evaluates model attribute conditions', function (): void {
        $rule = [
            'and' => [[
                'source' => 'model',
                'field' => 'model.status',
                'operator' => 'equals',
                'value' => 'active',
            ]],
        ];

        expect(JsonFieldConditions::evaluate($rule, fn (string $field): string => match ($field) {
            'status' => 'active',
            default => '',
        }))->toBeTrue();
    });
});

describe('csv value exchange', function (): void {
    it('exports and imports flex field values', function (): void {
        $definitions = [FlexFieldDefinition::fromArray([
            'slug' => 'company',
            'label' => 'Company',
            'type' => 'single_line_text',
        ])];

        $record = new stdClass;
        $record->id = 10;
        $record->flex_field_values = ['company' => 'Acme'];

        $csv = app(FlexFieldValueCsvExchange::class)->export($definitions, [$record]);
        $imported = app(FlexFieldValueCsvExchange::class)->import($csv, $definitions);

        expect($imported['10']['company'])->toBe('Acme');
    });
});

describe('field encryption', function (): void {
    it('encrypts and decrypts scalar values', function (): void {
        $encrypted = FlexFieldEncryption::encrypt('secret');
        $decrypted = FlexFieldEncryption::decrypt($encrypted);

        expect($decrypted)->toBe('secret')
            ->and(FlexFieldEncryption::isEncryptedPayload($encrypted))->toBeTrue();
    });
});
