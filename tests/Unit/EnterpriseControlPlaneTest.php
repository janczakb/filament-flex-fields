<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\Enterprise\FieldRbacMatrix;
use Bjanczak\FilamentFlexFields\Support\Enterprise\ObservabilityHooks;
use Bjanczak\FilamentFlexFields\Support\Enterprise\OemWhiteLabel;
use Bjanczak\FilamentFlexFields\Support\Enterprise\SchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\Enterprise\SiemBridge;
use Bjanczak\FilamentFlexFields\Support\Enterprise\TenantFieldPacks;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureOs;

beforeEach(function (): void {
    TenantFieldPacks::clear();
    FieldRbacMatrix::reset();
    SchemaRegistry::clear();
    ObservabilityHooks::clear();
    SiemBridge::clear();
    OemWhiteLabel::clear();
    MediaCaptureOs::registerVirusScanCallback(null);
});

it('isolates tenant field packs per tenant id', function (): void {
    TenantFieldPacks::registerPack('tenant-a', ['select', 'phone']);
    TenantFieldPacks::registerPack('tenant-b', ['rating', 'signature']);

    expect(TenantFieldPacks::packFor('tenant-a'))->toBe(['select', 'phone'])
        ->and(TenantFieldPacks::packFor('tenant-b'))->toBe(['rating', 'signature'])
        ->and(TenantFieldPacks::packFor('tenant-c'))->toBe([]);
});

it('denies field abilities when rbac matrix entries are set', function (): void {
    expect(FieldRbacMatrix::can('editor', FieldRbacMatrix::ABILITY_EDIT, 'select'))->toBeTrue();

    FieldRbacMatrix::deny('editor', FieldRbacMatrix::ABILITY_EDIT, 'select');

    expect(FieldRbacMatrix::can('editor', FieldRbacMatrix::ABILITY_EDIT, 'select'))->toBeFalse()
        ->and(FieldRbacMatrix::can('editor', FieldRbacMatrix::ABILITY_VIEW, 'select'))->toBeTrue();
});

it('rolls back schema registry to a prior version as a new live publish', function (): void {
    $schemaV1 = [
        'target' => 'App\\Models\\Lead',
        'fields' => [
            ['slug' => 'email', 'label' => 'Email', 'type' => 'single_line_text'],
        ],
    ];

    $schemaV2 = [
        'target' => 'App\\Models\\Lead',
        'fields' => [
            ['slug' => 'email', 'label' => 'Email', 'type' => 'single_line_text'],
            ['slug' => 'phone', 'label' => 'Phone', 'type' => 'phone'],
        ],
    ];

    $v1 = SchemaRegistry::publish('crm-lead', $schemaV1, 'admin@example.com');
    SchemaRegistry::publish('crm-lead', $schemaV2, 'admin@example.com', SchemaRegistry::STATE_LIVE);

    $rolledBack = SchemaRegistry::rollback('crm-lead', $v1);
    $versions = SchemaRegistry::versions('crm-lead');

    expect($v1)->toBe(1)
        ->and($rolledBack['version'])->toBe(3)
        ->and($rolledBack['state'])->toBe(SchemaRegistry::STATE_LIVE)
        ->and($rolledBack['schema'])->toBe($schemaV1)
        ->and($rolledBack['checksum'])->toBe(SchemaRegistry::checksum($schemaV1))
        ->and(count($versions))->toBe(3);
});

it('emits observability hook payloads to registered listeners', function (): void {
    $received = [];

    ObservabilityHooks::on(ObservabilityHooks::EVENT_SELECT_SEARCH, function (array $payload) use (&$received): void {
        $received[] = $payload;
    });

    ObservabilityHooks::emit(ObservabilityHooks::EVENT_SELECT_SEARCH, [
        'field' => 'country',
        'query' => 'pol',
    ]);

    expect($received)->toHaveCount(1)
        ->and($received[0])->toMatchArray(['field' => 'country', 'query' => 'pol'])
        ->and(ObservabilityHooks::listEvents())->toContain(
            ObservabilityHooks::EVENT_FIELD_MOUNT,
            ObservabilityHooks::EVENT_UPLOAD_FAIL,
            ObservabilityHooks::EVENT_OVERLAY_OPEN,
        )
        ->and(ObservabilityHooks::WINDOW_EVENT)->toBe('fff:observability');
});

it('no-ops observability emit when enterprise kill-switch is disabled', function (): void {
    config()->set('filament-flex-fields.enterprise.enabled', false);

    $received = [];

    ObservabilityHooks::on(ObservabilityHooks::EVENT_SELECT_SEARCH, function (array $payload) use (&$received): void {
        $received[] = $payload;
    });

    ObservabilityHooks::record(ObservabilityHooks::EVENT_SELECT_SEARCH, ['field' => 'country', 'query' => 'x']);

    expect(ObservabilityHooks::enabled())->toBeFalse()
        ->and($received)->toBe([]);

    config()->set('filament-flex-fields.enterprise.enabled', true);
});

it('records field.mount when SelectField hydrates', function (): void {
    $received = [];

    ObservabilityHooks::on(ObservabilityHooks::EVENT_FIELD_MOUNT, function (array $payload) use (&$received): void {
        $received[] = $payload;
    });

    $field = SelectField::make('country')
        ->options(['pl' => 'Poland']);

    $field->callAfterStateHydrated();

    expect($received)->toHaveCount(1)
        ->and($received[0])->toMatchArray([
            'field' => 'country',
            'type' => 'select',
        ]);
});

it('records select.search on SelectField dynamic search cache miss only', function (): void {
    $received = [];

    ObservabilityHooks::on(ObservabilityHooks::EVENT_SELECT_SEARCH, function (array $payload) use (&$received): void {
        $received[] = $payload;
    });

    $field = SelectField::make('country')
        ->searchable()
        ->getSearchResultsUsing(fn (string $search): array => [
            'pl' => 'Poland',
        ]);

    expect($field->getSearchResults('pol'))->toBe(['pl' => 'Poland']);
    expect($field->getSearchResults('pol'))->toBe(['pl' => 'Poland']);

    expect($received)->toHaveCount(1)
        ->and($received[0])->toMatchArray([
            'field' => 'country',
            'query' => 'pol',
            'source' => 'options',
        ]);
});

it('records upload.fail when FlexFileUpload virus scan rejects', function (): void {
    $received = [];

    ObservabilityHooks::on(ObservabilityHooks::EVENT_UPLOAD_FAIL, function (array $payload) use (&$received): void {
        $received[] = $payload;
    });

    MediaCaptureOs::registerVirusScanCallback(fn (string $path): bool => false);

    $field = FlexFileUpload::make('attachment')->disk('public');

    expect($field->passesMediaCaptureVirusScan('uploads/malware.exe'))->toBeFalse()
        ->and($received)->toHaveCount(1)
        ->and($received[0])->toMatchArray([
            'field' => 'attachment',
            'reason' => 'virus_scan',
            'path' => 'uploads/malware.exe',
        ]);

    MediaCaptureOs::registerVirusScanCallback(null);
});

it('skips upload.fail when enterprise kill-switch is disabled during virus reject', function (): void {
    config()->set('filament-flex-fields.enterprise.enabled', false);

    $received = [];

    ObservabilityHooks::on(ObservabilityHooks::EVENT_UPLOAD_FAIL, function (array $payload) use (&$received): void {
        $received[] = $payload;
    });

    MediaCaptureOs::registerVirusScanCallback(fn (string $path): bool => false);

    $field = FlexFileUpload::make('attachment')->disk('public');

    expect($field->passesMediaCaptureVirusScan('uploads/malware.exe'))->toBeFalse()
        ->and($received)->toBe([]);

    MediaCaptureOs::registerVirusScanCallback(null);
    config()->set('filament-flex-fields.enterprise.enabled', true);
});

it('defaults enterprise config to enabled on fresh install', function (): void {
    expect(config('filament-flex-fields.enterprise.enabled'))->toBeTrue()
        ->and(config('filament-flex-fields.enterprise.siem.driver'))->toBe('null');
});

it('forwards observability events through SiemBridge custom sink after boot', function (): void {
    $forwarded = [];

    SiemBridge::registerSink(function (string $event, array $envelope) use (&$forwarded): void {
        $forwarded[] = [$event, $envelope['source'] ?? null, $envelope['payload'] ?? []];
    });
    SiemBridge::boot();

    ObservabilityHooks::emit(ObservabilityHooks::EVENT_SELECT_SEARCH, [
        'field' => 'country',
        'query' => 'pl',
    ]);

    expect($forwarded)->toHaveCount(1)
        ->and($forwarded[0][0])->toBe(ObservabilityHooks::EVENT_SELECT_SEARCH)
        ->and($forwarded[0][1])->toBe('filament-flex-fields')
        ->and($forwarded[0][2])->toMatchArray(['field' => 'country', 'query' => 'pl']);
});

it('exposes oem white-label banner and wipe guide for reseller handoff', function (): void {
    OemWhiteLabel::licenseBannerSlotHtml('<p>ACME licensed</p>');
    OemWhiteLabel::tokenRenameMap(['--fff-accent' => '--acme-accent']);

    expect(OemWhiteLabel::licenseBannerSlotHtml())->toBe('<p>ACME licensed</p>')
        ->and(OemWhiteLabel::tokenRenameMap())->toBe(['--fff-accent' => '--acme-accent'])
        ->and(OemWhiteLabel::brandWipeGuideSteps())->not->toBeEmpty();
});
