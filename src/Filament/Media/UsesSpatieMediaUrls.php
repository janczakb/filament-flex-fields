<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Media;

use Bjanczak\FilamentFlexFields\Support\Media\Presentation\NativeImageConfiguration;
use Bjanczak\FilamentFlexFields\Support\Media\Spatie\SpatieMediaUrls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait UsesSpatieMediaUrls
{
    public function getImageUrl(?string $state = null, ?Model $relatedRecord = null): ?string
    {
        if ($state === null || ! in_array($state, $this->getState(), true)) {
            return null;
        }
        // Native getState applies collection/allCollections/filter/order. Never bypass it by UUID.
        foreach ($this->mediaPresentationRecords() as $record) {
            $collection = $record->getRelationValue('media');
            if (! $collection instanceof Collection) {
                continue;
            }
            $media = $collection->first(fn (mixed $item): bool => $item instanceof Media && $item->uuid === $state);
            if ($media instanceof Media) {
                $minutes = config('filament.temporary_file_url_expiry_minutes', 30);
                $minutes = is_int($minutes) && $minutes > 0 && $minutes <= 10080 ? $minutes : 30;

                $visibility = NativeImageConfiguration::resolve($this, 'getVisibility', true, $state, $relatedRecord) ?? throw new \LogicException('Media images require resolved visibility.');
                $conversion = NativeImageConfiguration::resolve($this, 'getConversion', true, $state, $relatedRecord);

                return app(SpatieMediaUrls::class)->forMedia($media, $visibility, now()->addMinutes($minutes), $conversion ?? '');
            }
        }

        return null;
    }

    /** @return list<Model> */
    protected function mediaPresentationRecords(): array
    {
        $record = $this->getRecord();
        if (! $record instanceof Model) {
            return [];
        }
        $related = $this->mediaRelationshipRecords($record);

        return array_values(array_filter(Arr::wrap($related), fn (mixed $item): bool => $item instanceof Model));
    }

    abstract protected function mediaRelationshipRecords(Model $record): mixed;
}
