<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Media;

use Bjanczak\FilamentFlexFields\Support\Media\FlexMedia;
use Bjanczak\FilamentFlexFields\Support\Media\Presentation\NativeImageConfiguration;
use Bjanczak\FilamentFlexFields\Support\Media\Storage\MediaAddress;
use Illuminate\Database\Eloquent\Model;
use Throwable;

trait UsesPrivateMediaUrls
{
    public function getImageUrl(?string $state = null, ?Model $relatedRecord = null): ?string
    {
        $hasState = func_num_args() > 0;
        $visibility = NativeImageConfiguration::resolve($this, 'getVisibility', $hasState, $state, $relatedRecord);
        if ($visibility !== 'private') {
            return NativeImageConfiguration::resolve($this, 'getImageUrl', $hasState, $state, $relatedRecord, parent::class);
        }
        if ($state === null || $state === '') {
            return null;
        }
        try {
            MediaAddress::path($state);

            return FlexMedia::signedUrl(NativeImageConfiguration::resolve($this, 'getDiskName', $hasState, $state, $relatedRecord) ?? throw new \LogicException('Private images require a resolved disk name.'), $state, ['visibility' => 'private']);
        } catch (Throwable) {
            return null;
        }
    }
}
