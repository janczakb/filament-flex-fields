<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Infolists\Components\FlexMediaImageEntry;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\FlexMediaImageColumn;
use Bjanczak\FilamentFlexFields\Support\Media\Presentation\NativeImageConfiguration;
use Illuminate\Database\Eloquent\Model;

it('preserves older native zero and single argument image methods', function (): void {
    $legacy = new class
    {
        public function getVisibility(): string
        {
            return 'private';
        }

        public function getImageUrl(?string $state = null): ?string
        {
            return $state === null ? null : '/images/'.$state;
        }
    };
    expect(NativeImageConfiguration::resolve($legacy, 'getVisibility', true, 'one.jpg', new class extends Model {}))->toBe('private')
        ->and(NativeImageConfiguration::resolve($legacy, 'getImageUrl', true, 'one.jpg', null))->toBe('/images/one.jpg');
});

it('passes the item and related record to the newer native contract without inventing missing state', function (): void {
    $record = new class extends Model {};
    $new = new class
    {
        public array $received = [];

        public function getVisibility(?string $state = null, ?Model $relatedRecord = null): string
        {
            $this->received[] = [func_num_args(), $state, $relatedRecord];

            return 'private';
        }
    };
    NativeImageConfiguration::resolve($new, 'getVisibility', true, 'one.jpg', $record);
    NativeImageConfiguration::resolve($new, 'getVisibility', false, null, null);
    expect($new->received)->toBe([[2, 'one.jpg', $record], [0, null, null]]);
});

class NativeImageParentProbe
{
    public function getImageUrl(?string $state = null, ?Model $relatedRecord = null): ?string
    {
        return 'parent:'.$state;
    }
}

it('delegates to the native parent implementation without reentering the FLEX override', function (): void {
    $component = new class extends NativeImageParentProbe
    {
        public function getImageUrl(?string $state = null, ?Model $relatedRecord = null): ?string
        {
            return NativeImageConfiguration::resolve($this, 'getImageUrl', true, $state, $relatedRecord, parent::class);
        }
    };
    expect($component->getImageUrl('one.jpg'))->toBe('parent:one.jpg');
});

it('preserves native public URLs and rejects unsafe private paths in real image surfaces', function (string $class): void {
    $component = $class::make('image')->visibility('public');
    expect($component->getImageUrl('https://example.test/image.jpg', new class extends Model {}))->toBe('https://example.test/image.jpg');
    $component->visibility('private')->disk('local');
    expect($component->getImageUrl('../private.jpg'))->toBeNull();
})->with([
    'table' => [FlexMediaImageColumn::class],
    'infolist' => [FlexMediaImageEntry::class],
]);
