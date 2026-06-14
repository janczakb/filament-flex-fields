<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VideoField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\VideoSources;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

it('exposes video field configuration api', function () {
    $field = VideoField::make('clip')
        ->size('lg')
        ->ratio('21:9')
        ->fullWidth()
        ->poster('https://example.com/poster.jpg')
        ->title('The Studio')
        ->subtitle('Episode 1')
        ->controls()
        ->nativeControls(false)
        ->autoplay()
        ->loop()
        ->muted()
        ->fullscreenable()
        ->volumeControl()
        ->skipSeconds(15);

    expect($field->getSize())->toBe('lg')
        ->and($field->getRatio())->toBe('21:9')
        ->and($field->getAspectRatioStyle())->toBe('21 / 9')
        ->and($field->isFullWidth())->toBeTrue()
        ->and($field->getPoster())->toBe('https://example.com/poster.jpg')
        ->and($field->getTitle())->toBe('The Studio')
        ->and($field->getSubtitle())->toBe('Episode 1')
        ->and($field->shouldShowControls())->toBeTrue()
        ->and($field->shouldUseNativeControls())->toBeFalse()
        ->and($field->shouldAutoplay())->toBeTrue()
        ->and($field->shouldLoop())->toBeTrue()
        ->and($field->shouldStartMuted())->toBeTrue()
        ->and($field->isFullscreenable())->toBeTrue()
        ->and($field->autoHidesControls())->toBeTrue()
        ->and($field->isPictureInPictureable())->toBeFalse()
        ->and($field->hasVolumeControl())->toBeTrue()
        ->and($field->getSkipSeconds())->toBe(15);
});

it('supports picture in picture only for html5 videos when enabled', function () {
    $mp4 = 'https://example.com/video.mp4';
    $youtube = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';

    $field = VideoField::make('clip')->pictureInPictureable();

    expect($field->isPictureInPictureable())->toBeTrue()
        ->and($field->supportsPictureInPicture($mp4))->toBeTrue()
        ->and($field->supportsPictureInPicture($youtube))->toBeFalse();

    expect(VideoField::make('clip')->supportsPictureInPicture($mp4))->toBeFalse();
});

it('resolves video source from static src or state', function () {
    $field = VideoField::make('clip')->src('https://example.com/static.mp4');

    expect($field->resolveVideoSrc('https://example.com/state.mp4'))
        ->toBe('https://example.com/static.mp4');

    $dynamic = VideoField::make('clip');

    expect($dynamic->resolveVideoSrc('https://example.com/state.mp4'))
        ->toBe('https://example.com/state.mp4')
        ->and($dynamic->resolveVideoSrc(null))->toBeNull();
});

it('sanitizes unsafe video urls', function () {
    $field = VideoField::make('clip');

    expect($field->resolveVideoSrc('javascript:alert(1)'))->toBeNull()
        ->and(VideoField::make('clip')->src('javascript:alert(1)')->getSrc())->toBeNull()
        ->and(VideoField::make('clip')->poster('https://example.com/poster.jpg')->getPoster())->toBe('https://example.com/poster.jpg')
        ->and(VideoField::make('clip')->poster('data:text/html,test')->getPoster())->toBeNull();
});

it('rejects unsafe video state during validation', function () {
    $field = VideoField::make('clip');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('clip', 'javascript:alert(1)', function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.validation.media.invalid_url'));
});

it('supports auto ratio without aspect style', function () {
    $field = VideoField::make('clip')->ratio('auto');

    expect($field->getRatio())->toBeNull()
        ->and($field->getAspectRatioStyle())->toBeNull();
});

it('rejects invalid aspect ratios', function () {
    VideoField::make('clip')->ratio('bad:0')->getAspectRatioStyle();
})->throws(InvalidArgumentException::class);

it('includes wrapper classes for size', function () {
    $field = VideoField::make('clip')->size('sm');

    expect($field->getWrapperClasses())->toBe([
        'fff-video-field-field',
        'fff-video-field-field--sm',
    ]);
});

it('shows metadata only when title or subtitle is set', function () {
    $field = VideoField::make('clip')->label('Clip label');

    expect($field->hasMetadata())->toBeFalse()
        ->and($field->getTitle())->toBeNull();

    $field->title('Episode title');

    expect($field->hasMetadata())->toBeTrue();
});

it('builds video field from flex field definition', function () {
    $builder = new FlexFieldFormBuilder;
    $component = $builder->makeComponent(new FlexFieldDefinition(
        slug: 'clip',
        label: 'Clip',
        type: FieldType::Video,
        config: [
            'size' => 'md',
            'ratio' => '16:9',
            'full_width' => true,
            'poster' => 'https://example.com/poster.jpg',
        ],
    ));

    expect($component)->toBeInstanceOf(VideoField::class)
        ->and($component->getSize())->toBe('md')
        ->and($component->getRatio())->toBe('16:9')
        ->and($component->isFullWidth())->toBeTrue()
        ->and($component->getPoster())->toBe('https://example.com/poster.jpg');
});

it('auto hides controls by default and can disable auto hide', function () {
    expect(VideoField::make('clip')->autoHidesControls())->toBeTrue();

    $field = VideoField::make('clip')->autoHideControls(false);

    expect($field->autoHidesControls())->toBeFalse();
});

it('builds video field auto hide controls from flex field definition', function () {
    $builder = new FlexFieldFormBuilder;
    $component = $builder->makeComponent(new FlexFieldDefinition(
        slug: 'clip',
        label: 'Clip',
        type: FieldType::Video,
        config: [
            'auto_hide_controls' => false,
        ],
    ));

    expect($component)->toBeInstanceOf(VideoField::class)
        ->and($component->autoHidesControls())->toBeFalse();
});

it('registers video field playground variants', function () {
    $builder = app(FlexFieldsPlaygroundBuilder::class);
    $state = $builder->defaultState();

    expect($state)->toHaveKeys([
        'video__basic',
        'video__youtube',
        'video__fullwidth',
        'video__square',
        'video__poster_only',
    ]);
});

it('detects youtube urls without extra javascript', function () {
    expect(VideoSources::youtubeId('https://www.youtube.com/watch?v=aqz-KE-bpKQ'))->toBe('aqz-KE-bpKQ')
        ->and(VideoSources::youtubeId('https://youtu.be/aqz-KE-bpKQ'))->toBe('aqz-KE-bpKQ')
        ->and(VideoSources::isYoutube('https://example.com/video.mp4'))->toBeFalse();
});

it('switches video field to youtube embed mode for youtube urls', function () {
    $field = VideoField::make('clip');

    expect($field->resolveProvider('https://www.youtube.com/watch?v=aqz-KE-bpKQ'))
        ->toBe(VideoSources::PROVIDER_YOUTUBE)
        ->and($field->usesYoutubeEmbed('https://www.youtube.com/watch?v=aqz-KE-bpKQ'))->toBeTrue()
        ->and($field->resolveYoutubeEmbedUrl('https://www.youtube.com/watch?v=aqz-KE-bpKQ'))
        ->toBe('https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ?rel=0&modestbranding=1&playsinline=1');
});

it('keeps mp4 urls on html5 provider', function () {
    $field = VideoField::make('clip');
    $url = 'https://avtshare01.rz.tu-ilmenau.de/avt-vqdb-uhd-1/test_1/segments/bigbuck_bunny_8bit_15000kbps_1080p_60.0fps_h264.mp4';

    expect($field->resolveProvider($url))->toBe(VideoSources::PROVIDER_HTML5)
        ->and($field->usesYoutubeEmbed($url))->toBeFalse();
});

it('can disable youtube detection', function () {
    $field = VideoField::make('clip')->allowYoutube(false);

    expect($field->usesYoutubeEmbed('https://www.youtube.com/watch?v=aqz-KE-bpKQ'))->toBeFalse();
});

it('uses youtube custom controls by default for youtube urls', function () {
    $field = VideoField::make('clip');
    $url = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';

    expect($field->usesYoutubeCustomControls($url))->toBeTrue()
        ->and($field->usesYoutubeFacade($url))->toBeFalse();
});

it('falls back to youtube facade when native controls are enabled', function () {
    $field = VideoField::make('clip')->nativeControls();
    $url = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';

    expect($field->usesYoutubeCustomControls($url))->toBeFalse()
        ->and($field->usesYoutubeFacade($url))->toBeTrue()
        ->and($field->resolveYoutubeEmbedUrl($url))
        ->toBe('https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ?controls=1&rel=0&modestbranding=1&playsinline=1');
});

it('builds youtube iframe player vars with hidden youtube controls', function () {
    $field = VideoField::make('clip')->loop()->muted();
    $url = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';

    expect($field->getYoutubeIframePlayerVars($url))->toMatchArray([
        'controls' => 0,
        'disablekb' => 1,
        'fs' => 0,
        'enablejsapi' => 1,
        'loop' => 1,
        'mute' => 1,
        'playlist' => 'aqz-KE-bpKQ',
    ]);
});

it('supports default and compact controls layouts', function () {
    expect(VideoField::make('clip')->getControlsLayout())->toBe('default')
        ->and(VideoField::make('clip')->compactControls()->getControlsLayout())->toBe('compact')
        ->and(VideoField::make('clip')->controlsLayout('compact')->usesCompactControls())->toBeTrue()
        ->and(VideoField::make('clip')->compactControls(false)->getControlsLayout())->toBe('default');
});

it('rejects unsupported video controls layouts', function () {
    VideoField::make('clip')->controlsLayout('stacked')->getControlsLayout();
})->throws(InvalidArgumentException::class);

it('uses gravity ui icons for video controls by default', function () {
    $field = VideoField::make('clip');

    expect($field->getPlayIcon())->toBe(GravityIcon::PlayFill)
        ->and($field->getPauseIcon())->toBe(GravityIcon::PauseFill)
        ->and($field->getVolumeIcon())->toBe(GravityIcon::VolumeFill)
        ->and($field->getMuteIcon())->toBe(GravityIcon::VolumeSlashFill)
        ->and($field->getFullscreenIcon())->toBe(GravityIcon::ChevronsExpandUpRight)
        ->and($field->getExitFullscreenIcon())->toBe(GravityIcon::ChevronsCollapseUpRight)
        ->and($field->getPictureInPictureIcon())->toBe(GravityIcon::CopyPicture)
        ->and($field->getExitPictureInPictureIcon())->toBe(GravityIcon::ChevronsCollapseUpRight)
        ->and($field->getPlaceholderIcon())->toBe(GravityIcon::Video);
});

it('allows overriding video control icons', function () {
    $field = VideoField::make('clip')
        ->playIcon(Heroicon::OutlinedPlay)
        ->pauseIcon(Heroicon::OutlinedPause)
        ->volumeIcon(Heroicon::OutlinedSpeakerWave)
        ->muteIcon(Heroicon::OutlinedSpeakerXMark)
        ->fullscreenIcon(Heroicon::OutlinedArrowsPointingOut)
        ->exitFullscreenIcon(Heroicon::OutlinedArrowsPointingIn)
        ->pictureInPictureIcon(Heroicon::OutlinedRectangleStack)
        ->exitPictureInPictureIcon(Heroicon::OutlinedArrowDownLeft)
        ->placeholderIcon(Heroicon::OutlinedVideoCamera);

    expect($field->getPlayIcon())->toBe(Heroicon::OutlinedPlay)
        ->and($field->getPauseIcon())->toBe(Heroicon::OutlinedPause)
        ->and($field->getVolumeIcon())->toBe(Heroicon::OutlinedSpeakerWave)
        ->and($field->getMuteIcon())->toBe(Heroicon::OutlinedSpeakerXMark)
        ->and($field->getFullscreenIcon())->toBe(Heroicon::OutlinedArrowsPointingOut)
        ->and($field->getExitFullscreenIcon())->toBe(Heroicon::OutlinedArrowsPointingIn)
        ->and($field->getPictureInPictureIcon())->toBe(Heroicon::OutlinedRectangleStack)
        ->and($field->getExitPictureInPictureIcon())->toBe(Heroicon::OutlinedArrowDownLeft)
        ->and($field->getPlaceholderIcon())->toBe(Heroicon::OutlinedVideoCamera);
});

it('detects vimeo urls', function () {
    expect(VideoSources::vimeoId('https://vimeo.com/123456789'))->toBe('123456789')
        ->and(VideoSources::vimeoId('https://player.vimeo.com/video/123456789'))->toBe('123456789')
        ->and(VideoSources::isVimeo('https://example.com/video.mp4'))->toBeFalse();
});

it('switches video field to vimeo embed mode for vimeo urls', function () {
    $field = VideoField::make('clip');
    $url = 'https://vimeo.com/123456789';

    expect($field->resolveProvider($url))
        ->toBe(VideoSources::PROVIDER_VIMEO)
        ->and($field->usesVimeoEmbed($url))->toBeTrue()
        ->and($field->resolveVimeoEmbedUrl($url))
        ->toBe('https://player.vimeo.com/video/123456789');
});

it('supports vimeo embed config customization', function () {
    $field = VideoField::make('clip')->loop()->muted()->autoplay();
    $url = 'https://vimeo.com/123456789';

    expect($field->resolveVimeoEmbedUrl($url, true))
        ->toBe('https://player.vimeo.com/video/123456789?autoplay=1&loop=1&muted=1');
});

it('can disable vimeo detection', function () {
    $field = VideoField::make('clip')->allowVimeo(false);

    expect($field->usesVimeoEmbed('https://vimeo.com/123456789'))->toBeFalse();
});
