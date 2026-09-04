<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Concerns\HasFlexFields;
use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Data\FlexFieldSchema;
use Bjanczak\FilamentFlexFields\Support\FlexFieldSchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldEncryption;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldValueCsvExchange;
use Illuminate\Database\Eloquent\Model;

class FlexFieldSchemaTestModel extends Model
{
    use HasFlexFields;

    protected $guarded = [];

    public $timestamps = false;

    protected $table = 'flex_field_schema_test_models';
}

beforeEach(function (): void {
    app(FlexFieldSchemaRegistry::class)->register(
        FlexFieldSchema::make('schema-test-model', FlexFieldSchemaTestModel::class)
            ->fields([
                ['slug' => 'company', 'label' => 'Company', 'type' => 'single_line_text', 'sort' => 0],
                ['slug' => 'active', 'label' => 'Active', 'type' => 'toggle', 'sort' => 1],
                ['slug' => 'budget', 'label' => 'Budget', 'type' => 'currency', 'sort' => 2],
                ['slug' => 'secret', 'label' => 'Secret', 'type' => 'single_line_text', 'sort' => 3, 'is_encrypted' => true],
            ]),
    );
});

it('persists encrypted flex field values and decrypts on read', function (): void {
    $model = new FlexFieldSchemaTestModel;
    $model->setFlexFieldValue('secret', 'top-secret');

    $stored = $model->getAttribute('flex_field_values')['secret'] ?? null;

    expect($stored)->not->toBe('top-secret')
        ->and(FlexFieldEncryption::isEncryptedPayload($stored))->toBeTrue()
        ->and($model->getFlexFieldValue('secret'))->toBe('top-secret');
});

it('round-trips csv values for toggle and text fields', function (): void {
    $definitions = collect([
        FlexFieldDefinition::fromArray(['slug' => 'company', 'label' => 'Company', 'type' => 'single_line_text']),
        FlexFieldDefinition::fromArray(['slug' => 'active', 'label' => 'Active', 'type' => 'toggle']),
        FlexFieldDefinition::fromArray(['slug' => 'budget', 'label' => 'Budget', 'type' => 'currency']),
    ]);

    $record = new FlexFieldSchemaTestModel;
    $record->id = 42;
    $record->flex_field_values = [
        'company' => 'Acme',
        'active' => true,
        'budget' => 1200.5,
    ];

    $exchange = app(FlexFieldValueCsvExchange::class);
    $csv = $exchange->export($definitions, [$record]);
    $imported = $exchange->import($csv, $definitions);

    expect($imported['42']['company'])->toBe('Acme')
        ->and($imported['42']['active'])->toBeTrue()
        ->and($imported['42']['budget'])->toBe('1200.5');
});
