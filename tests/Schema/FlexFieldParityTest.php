<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldEntity;
use Bjanczak\FilamentFlexFields\Filament\Pages\FlexFieldManagementPage;
use Bjanczak\FilamentFlexFields\Filament\Schema\VisibilityRuleBuilder;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldEntityRegistry;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldEncryption;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldValueCsvExchange;
use Bjanczak\FilamentFlexFields\Support\Schema\JsonFieldConditions;
use Livewire\Livewire;

describe('entity discovery and management page', function (): void {
    beforeEach(function (): void {
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

    it('renders the flex field studio management page', function (): void {
        $this->actingAs(new \Illuminate\Auth\GenericUser([
            'id' => 1,
            'email' => 'admin@example.com',
        ]));

        Livewire::test(FlexFieldManagementPage::class)
            ->assertSuccessful()
            ->assertSee('Flex field studio');
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
        $definitions = [\Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition::fromArray([
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
