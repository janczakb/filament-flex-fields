@php
    use Filament\Support\Enums\IconSize;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $videoSrc = $field->resolveVideoSrc($getState());
    $usesYoutube = $field->usesYoutubeEmbed($getState());
    $usesYoutubeCustomControls = $field->usesYoutubeCustomControls($getState());
    $usesYoutubeFacade = $field->usesYoutubeFacade($getState());
    $youtubeId = $field->resolveYoutubeId($getState());
    $youtubeEmbedUrl = $field->resolveYoutubeEmbedUrl($getState());
    $youtubeThumbnail = $field->resolveYoutubeThumbnail($getState());
    $usesVimeo = $field->usesVimeoEmbed($getState());
    $vimeoId = $field->resolveVimeoId($getState());
    $vimeoEmbedUrl = $field->resolveVimeoEmbedUrl($getState());
    $poster = $getPoster();
    $title = $getTitle();
    $subtitle = $getSubtitle();
    $hasMetadata = $field->hasMetadata();
    $aspectRatioStyle = $getAspectRatioStyle();
    $livewireKey = $getLivewireKey();
    $showCustomControls = ($shouldShowControls() && ! $shouldUseNativeControls())
        && (filled($videoSrc) && ! $usesYoutube && ! $usesVimeo || $usesYoutubeCustomControls);
    $controlsLayout = $getControlsLayout();
    $usesCompactControls = $usesCompactControls();
    $showPictureInPictureControl = $isPictureInPictureable() && ! $usesYoutube && ! $usesVimeo && filled($videoSrc);
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'video-field'])
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize(), $getRatio(), $getSrc(), $getPoster(), $usesYoutube, $usesYoutubeCustomControls, $usesVimeo])), 0, 64) }}"
        @class([
            'fff-video-field',
            'fff-video-field--'.$getSize(),
            'is-full-width' => $isFullWidth(),
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'is-youtube' => $usesYoutube,
            'is-youtube-custom' => $usesYoutubeCustomControls,
            'is-vimeo' => $usesVimeo,
            'has-native-controls' => ($usesYoutube && $usesYoutubeFacade) || $usesVimeo || (! $usesYoutube && $shouldUseNativeControls()),
            'has-custom-controls' => $showCustomControls,
            'has-metadata' => $hasMetadata,
            'is-controls-compact' => $usesCompactControls,
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        @if ($usesYoutubeFacade)
            <div
                class="fff-video-field__frame"
                x-ref="frame"
                x-data="{
                    active: @js($shouldAutoplay()),
                    isFullscreen: false,
                    embedUrl: @js($youtubeEmbedUrl),
                    activate() {
                        if (@js($isDisabled || $isReadOnly)) {
                            return;
                        }

                        this.active = true;
                    },
                    toggleFullscreen() {
                        if (! @js($isFullscreenable())) {
                            return;
                        }

                        const frame = this.$refs.frame;

                        if (! document.fullscreenElement) {
                            frame.requestFullscreen?.().catch(() => {});
                        } else {
                            document.exitFullscreen?.().catch(() => {});
                        }
                    },
                    init() {
                        this.$watch('active', (value) => {
                            if (value && this.embedUrl && ! this.embedUrl.includes('autoplay=1')) {
                                this.embedUrl = this.embedUrl + (this.embedUrl.includes('?') ? '&' : '?') + 'autoplay=1';
                            }
                        });

                        document.addEventListener('fullscreenchange', () => {
                            this.isFullscreen = document.fullscreenElement === this.$refs.frame;
                        });
                    },
                }"
                x-init="init()"
                @if ($aspectRatioStyle)
                    style="aspect-ratio: {{ $aspectRatioStyle }};"
                @endif
            >
                <template x-if="! active">
                    <div class="fff-video-field__youtube-facade">
                        <img
                            class="fff-video-field__video"
                            src="{{ e($youtubeThumbnail) }}"
                            alt=""
                            decoding="async"
                        />
                        <div class="fff-video-field__scrim" aria-hidden="true"></div>

                        <div class="fff-video-field__ui">
                            <div class="fff-video-field__dock">
                                @if ($hasMetadata)
                                    <div class="fff-video-field__meta">
                                        @if (filled($subtitle))
                                            <span class="fff-video-field__meta-kicker">{{ $subtitle }}</span>
                                        @endif
                                        @if (filled($title))
                                            <span class="fff-video-field__meta-title">{{ $title }}</span>
                                        @endif
                                    </div>
                                @endif

                                <div class="fff-video-field__toolbar">
                                    <div class="fff-video-field__toolbar-start">
                                        <button
                                            type="button"
                                            class="fff-video-field__pill"
                                            x-on:click="activate()"
                                            aria-label="{{ __('filament-flex-fields::default.video.play') }}"
                                            @disabled($isDisabled || $isReadOnly)
                                        >
                                            {{ \Filament\Support\generate_icon_html($field->getPlayIcon(), size: IconSize::ExtraSmall) }}
                                            <span>{{ __('filament-flex-fields::default.video.play') }}</span>
                                        </button>
                                    </div>

                                    @if ($isFullscreenable())
                                        <div class="fff-video-field__toolbar-end">
                                            <button
                                                type="button"
                                                class="fff-video-field__glass-btn"
                                                x-on:click="toggleFullscreen()"
                                                x-bind:aria-label="isFullscreen ? @js(__('filament-flex-fields::default.video.exit_fullscreen')) : @js(__('filament-flex-fields::default.video.fullscreen'))"
                                            >
                                                <span x-show="! isFullscreen" aria-hidden="true">
                                                    {{ \Filament\Support\generate_icon_html($field->getFullscreenIcon(), size: IconSize::Small) }}
                                                </span>
                                                <span x-show="isFullscreen" aria-hidden="true">
                                                    {{ \Filament\Support\generate_icon_html($field->getExitFullscreenIcon(), size: IconSize::Small) }}
                                                </span>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <iframe
                    class="fff-video-field__youtube-iframe"
                    x-show="active"
                    x-bind:src="active ? embedUrl : null"
                    title="{{ filled($title) ? e($title) : e($getLabel()) }}"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                    loading="lazy"
                ></iframe>
            </div>
        @elseif ($usesVimeo)
            <div
                class="fff-video-field__frame"
                @if ($aspectRatioStyle)
                    style="aspect-ratio: {{ $aspectRatioStyle }};"
                @endif
            >
                <iframe
                    class="fff-video-field__vimeo-iframe"
                    src="{{ $vimeoEmbedUrl }}"
                    title="{{ filled($title) ? e($title) : e($getLabel()) }}"
                    allow="autoplay; fullscreen; picture-in-picture"
                    allowfullscreen
                    loading="lazy"
                ></iframe>
            </div>
        @else
            <div
                class="fff-video-field__player"
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('video-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
                x-data="videoFieldFormComponent({
                    state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                    staticSrc: @js($getSrc()),
                    provider: @js($field->resolveProvider($getState())),
                    youtubeId: @js($youtubeId),
                    youtubeNoCookie: @js($shouldUseYoutubeNoCookie()),
                    youtubePlayerVars: @js($field->getYoutubeIframePlayerVars($getState(), $shouldAutoplay())),
                    youtubeThumbnail: @js($youtubeThumbnail),
                    poster: @js($poster),
                    title: @js($title),
                    subtitle: @js($subtitle),
                    showControls: @js($shouldShowControls()),
                    nativeControls: @js($shouldUseNativeControls()),
                    autoplay: @js($shouldAutoplay()),
                    loop: @js($shouldLoop()),
                    muted: @js($shouldStartMuted()),
                    playsInline: @js($shouldPlayInline()),
                    readOnly: @js($isDisabled || $isReadOnly),
                    fullscreenable: @js($isFullscreenable()),
                    autoHideControls: @js($autoHidesControls()),
                    pictureInPictureable: @js($isPictureInPictureable()),
                    volumeControl: @js($hasVolumeControl()),
                    labels: {
                        play: @js(__('filament-flex-fields::default.video.play')),
                        pause: @js(__('filament-flex-fields::default.video.pause')),
                    },
                })"
                x-bind:class="{ 'is-hydrated': uiTransitionsEnabled }"
                x-init="init()"
                x-on:mousemove="onFrameMove()"
                x-on:mouseleave="onFrameLeave()"
            >
                <div
                    class="fff-video-field__frame"
                    x-ref="frame"
                    x-bind:class="{ 'is-picture-in-picture': isPictureInPicture }"
                    @if ($aspectRatioStyle)
                        style="aspect-ratio: {{ $aspectRatioStyle }};"
                    @endif
                >
                    @if ($usesYoutubeCustomControls)
                        <template x-if="facadeActive">
                            <div class="fff-video-field__youtube-facade">
                                <img
                                    class="fff-video-field__video"
                                    src="{{ e($youtubeThumbnail) }}"
                                    alt=""
                                    decoding="async"
                                />
                            </div>
                        </template>

                        <div
                            class="fff-video-field__youtube-player"
                            x-ref="youtubePlayer"
                            x-show="! facadeActive"
                        ></div>
                    @elseif (filled($videoSrc))
                        <video
                            class="fff-video-field__video"
                            x-ref="video"
                            src="{{ e($videoSrc) }}"
                            x-bind:src="videoSrc"
                            @if ($poster)
                                poster="{{ e($poster) }}"
                            @endif
                            x-bind:poster="poster || null"
                            preload="metadata"
                            @if ($shouldLoop()) loop @endif
                            @if ($shouldStartMuted()) muted @endif
                            @if ($shouldPlayInline()) playsinline @endif
                            @if ($shouldUseNativeControls()) controls @endif
                            x-bind:muted="muted"
                        ></video>

                        @if ($showPictureInPictureControl)
                            <div
                                class="fff-video-field__pip-placeholder"
                                x-show="isPictureInPicture"
                                x-cloak
                                aria-hidden="true"
                            >
                                @if (filled($poster))
                                    <img
                                        class="fff-video-field__pip-placeholder-image"
                                        src="{{ e($poster) }}"
                                        alt=""
                                        decoding="async"
                                    />
                                @else
                                    <div class="fff-video-field__pip-placeholder-fallback"></div>
                                @endif
                            </div>
                        @endif
                    @else
                        <div class="fff-video-field__empty" aria-hidden="true">
                            @if ($poster)
                                <img
                                    class="fff-video-field__placeholder"
                                    src="{{ e($poster) }}"
                                    alt=""
                                    decoding="async"
                                />
                            @else
                                <div class="fff-video-field__placeholder fff-video-field__placeholder--fallback" aria-hidden="true">
                                    {{ \Filament\Support\generate_icon_html($field->getPlaceholderIcon(), size: IconSize::Large) }}
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($showCustomControls)
                        @include($usesCompactControls
                            ? 'filament-flex-fields::forms.components.partials.video-field-controls-compact'
                            : 'filament-flex-fields::forms.components.partials.video-field-controls')
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-dynamic-component>
