@php
    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
    $isVertical = $isVertical();
    $pipsMode = $getPipsMode();
    $livewireKey = $getLivewireKey();
    $isDisabled = $isDisabled();
    $size = $getSize();
    $variant = $getVariant();
    $showValue = $shouldShowValue();
    $prefix = $getDisplayPrefix();
    $suffix = $getDisplaySuffix();
    $trackLabel = $getTrackLabel();
    $hideThumbUntilInteraction = $shouldHideThumbUntilInteraction();
    $valuePosition = $getValuePosition();
    $decimalPlaces = $getDecimalPlaces();
    $initialFillSegments = $getInitialFillSegments();
    $initialNormalizedValues = $getNormalizedStateValues();
    $initialValueRatios = $getInitialValueRatios();
    $isRange = $isRangeState();
    $initialDisplayValue = $formatDisplayValue();
    $hasTooltipsEnabled = $hasTooltips();
    $initialLiveValues = array_map(
        fn (float $value): string => $formatDisplayValue($value),
        $getNormalizedStateValues(),
    );
    $minDisplayValue = $formatDisplayValue($getMinValue());
    $maxDisplayValue = $formatDisplayValue($getMaxValue());
    $fillColor = $getFillColor();
    $color = $getColor();
    $showScale = $hasTooltipsEnabled && ! $isVertical;
    $fillColorStyle = filled($fillColor) ? '--fff-flex-slider-accent: '.$fillColor.';' : null;
    $showFooter = $showValue && ! $isRange && ! $hasTooltipsEnabled && ! filled($pipsMode);
    $showHeaderLabel = filled($trackLabel) && ($showScale || ! $showFooter);
    $showStepDots = $field->shouldShowStepDots();
    $stepDotRatios = $field->getStepDotRatios();
    $shouldRenderServerPips = $field->shouldRenderServerPips();
    $serverRenderedPips = $field->getServerRenderedPips();
    $hasSingleFillThumb = collect($initialFillSegments)->contains(
        fn (array $segment): bool => $segment['type'] === 'from-min',
    );
    $hasRangeFillThumbs = collect($initialFillSegments)->contains(
        fn (array $segment): bool => $segment['type'] === 'between',
    );
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    :inline-label-vertical-alignment="\Filament\Support\Enums\VerticalAlignment::Center"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'flex-slider'])
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flex-slider', package: \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="flexSliderFormComponent({
            arePipsStepped: @js($arePipsStepped()),
            autoFill: @js($shouldAutoFill()),
            behavior: @js($getBehaviorForJs()),
            decimalPlaces: @js($decimalPlaces),
            fillTrack: @js($getFillTrack()),
            hideThumbUntilInteraction: @js($hideThumbUntilInteraction),
            initialDisplayValue: @js($initialDisplayValue),
            initialFillSegments: @js($initialFillSegments),
            initialLiveValues: @js($initialLiveValues),
            initialNormalizedValues: @js($initialNormalizedValues),
            initialValueRatios: @js($initialValueRatios),
            isDisabled: @js($isDisabled),
            isRtl: @js($isRtl()),
            isVertical: @js($isVertical),
            maxDifference: @js($getMaxDifference()),
            minDifference: @js($getMinDifference()),
            maxValue: @js($getMaxValue()),
            minValue: @js($getMinValue()),
            nonLinearPoints: @js($getNonLinearPoints()),
            pipsDensity: @js($getPipsDensity()),
            pipsFilter: @js($getPipsFilterForJs()),
            pipsFormatter: @js($getPipsFormatterForJs()),
            pipsMode: @js($pipsMode),
            pipsValues: @js($getPipsValues()),
            serverRenderedPips: @js($shouldRenderServerPips),
            prefix: @js($prefix),
            rangePadding: @js($getRangePadding()),
            showValue: @js($showValue),
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            step: @js($getStep()),
            suffix: @js($suffix),
            tooltips: @js($getTooltipsForJs()),
            trackLabel: @js($trackLabel),
            valuePosition: @js($valuePosition),
            hasTooltips: @js($hasTooltipsEnabled),
        })"
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $size, $variant, $hideThumbUntilInteraction])), 0, 64) }}"
        @class([
            'fff-flex-slider',
            'fff-flex-slider--' . $size,
            'fff-flex-slider--' . $variant,
            'fff-flex-slider--hide-thumb-until-interaction' => $hideThumbUntilInteraction,
            'fff-flex-slider--has-pips' => filled($pipsMode),
            'fff-flex-slider--has-server-pips' => $shouldRenderServerPips,
            'fff-flex-slider--has-step-dots' => $showStepDots,
            'fff-flex-slider--has-tooltips' => $hasTooltipsEnabled,
            'fff-flex-slider--has-footer' => $showFooter,
            'fff-flex-slider--vertical' => $isVertical,
            'fi-color-' . $color => filled($color) && blank($fillColor),
            'is-disabled' => $isDisabled,
            'is-range' => $isRange,
            'is-ready' => true,
        ])
        @style($fillColorStyle)
        x-bind:class="{
            'is-dragging': isDragging,
            'is-range': isRange,
            'is-thumb-hovered': isThumbHovered,
        }"
        x-on:mouseenter="isHovered = true"
        x-on:mouseleave="isHovered = false; isThumbHovered = false"
        x-on:mousemove="updateThumbHover($event)"
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div class="fff-flex-slider__control">
            @if ($showHeaderLabel)
                <div class="fff-flex-slider__header-label">{{ $trackLabel }}</div>
            @endif

            <div class="fff-flex-slider__track-stack">
                <div class="fff-flex-slider__rail is-ready" x-ref="track">
                    <div class="fff-flex-slider__track-shell" aria-hidden="true">
                        <div class="fff-flex-slider__track-bg"></div>

                        @foreach ($initialFillSegments as $index => $segment)
                            <div
                                @class([
                                    'fff-flex-slider__fill',
                                    $field->fillSegmentModifierClass($segment),
                                ])
                                x-bind:class="fillSegmentClass({{ $index }})"
                                style="{{ $field->fillSegmentVariables($segment) }}"
                                x-bind:style="fillSegmentStyle({{ $index }})"
                            ></div>
                        @endforeach

                        @if ($showStepDots)
                            <div class="fff-flex-slider__step-dots" aria-hidden="true">
                                @foreach ($stepDotRatios as $ratio)
                                    <span
                                        class="fff-flex-slider__step-dot"
                                        style="{{ $field->stepDotStyle($ratio) }}"
                                    ></span>
                                @endforeach
                            </div>
                        @endif

                        @if ($hasSingleFillThumb)
                            <div
                                @class([
                                    'fff-flex-slider__thumb',
                                    'fff-flex-slider__thumb--on-track',
                                    'fff-flex-slider__thumb--single',
                                    'is-visible' => ! $hideThumbUntilInteraction,
                                ])
                                style="{{ $field->thumbChromeVariables($initialNormalizedValues[0] ?? $getMinValue()) }}"
                                x-bind:style="valueRatioStyle(0)"
                                x-bind:class="{
                                    'is-visible': ! hideThumbUntilInteraction || isHovered || isDragging,
                                    'is-dragging': isDragging,
                                }"
                                aria-hidden="true"
                            >
                                @if ($hasTooltipsEnabled)
                                    <span
                                        class="fff-flex-slider__tooltip"
                                        x-text="liveValues[0]"
                                    >{{ $initialLiveValues[0] ?? '' }}</span>
                                @endif
                            </div>
                        @endif

                        @if ($hasRangeFillThumbs)
                            <div
                                @class([
                                    'fff-flex-slider__thumb',
                                    'fff-flex-slider__thumb--on-track',
                                    'fff-flex-slider__thumb--range',
                                    'fff-flex-slider__thumb--leading',
                                    'is-visible',
                                ])
                                style="{{ $field->thumbChromeVariables($initialNormalizedValues[0] ?? $getMinValue()) }}"
                                x-bind:style="valueRatioStyle(0)"
                                x-bind:class="{ 'is-dragging': isDragging }"
                                aria-hidden="true"
                            >
                                @if ($hasTooltipsEnabled)
                                    <span
                                        class="fff-flex-slider__tooltip"
                                        x-text="liveValues[0]"
                                    >{{ $initialLiveValues[0] ?? '' }}</span>
                                @endif
                            </div>

                            <div
                                @class([
                                    'fff-flex-slider__thumb',
                                    'fff-flex-slider__thumb--on-track',
                                    'fff-flex-slider__thumb--range',
                                    'fff-flex-slider__thumb--trailing',
                                    'is-visible',
                                ])
                                style="{{ $field->thumbChromeVariables($initialNormalizedValues[1] ?? $getMaxValue()) }}"
                                x-bind:style="valueRatioStyle(1)"
                                x-bind:class="{ 'is-dragging': isDragging }"
                                aria-hidden="true"
                            >
                                @if ($hasTooltipsEnabled)
                                    <span
                                        class="fff-flex-slider__tooltip"
                                        x-text="liveValues[1]"
                                    >{{ $initialLiveValues[1] ?? '' }}</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div
                        x-ref="sliderHost"
                        wire:ignore
                        {{
                            $attributes
                                ->merge([
                                    'id' => $getId(),
                                ], escape: false)
                                ->merge($getExtraAttributes(), escape: false)
                                ->class(['fff-flex-slider__host'])
                        }}
                    ></div>
                </div>
            </div>

            @if ($shouldRenderServerPips)
                <div class="fff-flex-slider__pips" aria-hidden="true">
                    @foreach ($serverRenderedPips as $pip)
                        <div
                            class="fff-flex-slider__pip"
                            style="{{ $field->pipStyle($pip['ratio']) }}"
                        >
                            <span @class([
                                'fff-flex-slider__pip-marker',
                                'fff-flex-slider__pip-marker--large' => $pip['size'] === 'large',
                                'fff-flex-slider__pip-marker--sub' => $pip['size'] === 'sub',
                                'fff-flex-slider__pip-marker--normal' => $pip['size'] === 'normal',
                            ])></span>
                            @if (filled($pip['label']))
                                <span class="fff-flex-slider__pip-value">{{ $pip['label'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($showScale && ! $isVertical)
                <div class="fff-flex-slider__scale" aria-hidden="true">
                    <span class="fff-flex-slider__scale-min">{{ $minDisplayValue }}</span>
                    <span class="fff-flex-slider__scale-max">{{ $maxDisplayValue }}</span>
                </div>
            @endif

            @if ($showFooter)
                <div class="fff-flex-slider__footer">
                    <span class="fff-flex-slider__footer-label">{{ $trackLabel }}</span>

                    <span
                        @class([
                            'fff-flex-slider__footer-value',
                            'fff-flex-slider__footer-value--' . $valuePosition => $valuePosition !== 'end',
                        ])
                        x-text="liveDisplayValue"
                    >{{ $initialDisplayValue }}</span>
                </div>
            @endif

            @if ($showValue && $isRange)
                <div class="fff-flex-slider__handle-values">
                    @foreach ($initialNormalizedValues as $index => $value)
                        <span
                            class="fff-flex-slider__handle-value"
                            style="{{ $field->thumbChromeVariables($value) }}"
                            x-bind:style="valueRatioStyle({{ $index }})"
                            x-text="liveValues[{{ $index }}]"
                        >{{ $initialLiveValues[$index] ?? '' }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-dynamic-component>
