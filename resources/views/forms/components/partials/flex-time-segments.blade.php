@props([
    'size' => 'md',
    'variant' => 'primary',
    'value' => '',
    'minuteStep' => 15,
    'hourCycle' => 24,
    'minValue' => null,
    'maxValue' => null,
    'minValueExpression' => null,
    'maxValueExpression' => null,
    'disabled' => false,
    'readOnly' => false,
    'interactive' => true,
    'live' => true,
    'skipScriptLoad' => false,
    'getValueExpression' => null,
    'setValueExpression' => null,
    'initialValueExpression' => null,
    'hourPlaceholder' => null,
    'minutePlaceholder' => null,
    'ariaLabel' => null,
])

@php
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeSegmentHydrator;
    use Filament\Support\Facades\FilamentAsset;

    $segments = DateTimeSegmentHydrator::segmentsFromScheduleTime($value);
    $isStatic = ! $live && ! $interactive;
    $isLocked = $disabled || $readOnly || ($live && ! $interactive);
    $resolvedLocale = app()->getLocale();
    $hourPlaceholder = filled($hourPlaceholder)
        ? $hourPlaceholder
        : DateTimeSegmentHydrator::segmentPlaceholder('hour', $resolvedLocale);
    $minutePlaceholder = filled($minutePlaceholder)
        ? $minutePlaceholder
        : DateTimeSegmentHydrator::segmentPlaceholder('minute', $resolvedLocale);
    $hasValue = filled($segments['hour']) && filled($segments['minute']);
    $displayLabel = $hasValue
        ? ($hourCycle === 12
            ? sprintf(
                '%s : %s %s',
                str_pad((string) (((int) $segments['hour']) % 12 ?: 12), 2, '0', STR_PAD_LEFT),
                $segments['minute'],
                (int) $segments['hour'] >= 12 ? 'PM' : 'AM',
            )
            : "{$segments['hour']} : {$segments['minute']}")
        : "{$hourPlaceholder} : {$minutePlaceholder}";
@endphp

<div
    @class([
        'fff-flex-time-segments',
        'fff-flex-text-input',
        'fff-flex-text-input--'.$size,
        'fff-flex-text-input--'.$variant,
        'is-static' => $isStatic,
        'is-disabled' => $isLocked && ! $isStatic,
    ])
    @if ($live)
        @if (! $skipScriptLoad)
            x-load
            x-load-src="{{ FilamentAsset::getAlpineComponentSrc('flex-time-segments', FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        @endif
        x-data="flexTimeSegmentsComponent({
            getValue: {!! $getValueExpression ?? 'null' !!},
            setValue: {!! $setValueExpression ?? 'null' !!},
            minuteStep: {{ (int) $minuteStep }},
            hourCycle: {{ (int) $hourCycle }},
            minValue: @if (filled($minValueExpression))
                {!! $minValueExpression !!}
            @else
                @js(filled($minValue) ? $minValue : null)
            @endif,
            maxValue: @if (filled($maxValueExpression))
                {!! $maxValueExpression !!}
            @else
                @js(filled($maxValue) ? $maxValue : null)
            @endif,
            disabled: @js($isLocked),
            @if (filled($initialValueExpression))
                initialValue: {!! $initialValueExpression !!},
            @else
                initialValue: @js($value),
            @endif
            hourPlaceholder: @js($hourPlaceholder),
            minutePlaceholder: @js($minutePlaceholder),
            immediateDisplayReady: @js($skipScriptLoad),
        })"
        x-init="init()"
        x-on:click.outside="if ($refs.timeMenu?.contains($event.target)) { return }; closeMenu()"
        x-on:keydown.escape.window="closeMenu()"
    @endif
    @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
>
    <div @class([
        'fff-flex-text-input__shell',
        'fff-schedule-field__time-shell',
        'fff-flex-time-segments__shell',
    ])>
        @if ($live)
            <button
                type="button"
                class="fff-flex-time-segments__trigger"
                x-ref="timeTrigger"
                x-on:click.stop="toggleMenu()"
                x-bind:aria-expanded="menuOpen ? 'true' : 'false'"
                aria-haspopup="listbox"
                x-bind:disabled="isLocked"
            >
                <span class="fff-flex-time-segments__value">
                    <span
                        @class([
                            'fff-flex-time-segments__ssr-label',
                            'is-placeholder' => ! $hasValue,
                        ])
                        x-bind:class="{ 'is-replaced': componentReady }"
                    >{{ e($displayLabel) }}</span>
                    <span
                        class="fff-flex-time-segments__live-label"
                        x-bind:class="{
                            'is-ready': componentReady,
                            'is-placeholder': showPlaceholderStyle,
                        }"
                        x-text="displayLabel"
                    ></span>
                </span>

                <x-filament::icon
                    icon="heroicon-m-chevron-up-down"
                    class="fff-flex-time-segments__chevron"
                />
            </button>

            <template x-teleport="body">
                <div
                    class="fff-flex-time-segments__menu fff-teleported-menu"
                    x-ref="timeMenu"
                    x-show="menuOpen"
                    x-cloak
                    x-bind:class="{ 'is-positioned': menuReady }"
                    x-on:click.stop
                    role="listbox"
                    @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
                >
                    <div
                        class="fff-flex-time-segments__columns"
                        x-bind:class="{ 'fff-flex-time-segments__columns--12h': uses12HourCycle }"
                    >
                        <div
                            class="fff-flex-time-segments__column"
                            role="presentation"
                            aria-label="{{ $hourPlaceholder }}"
                        >
                            <template x-for="hour in hourOptions()" x-bind:key="`hour-${hour}`">
                                <button
                                    type="button"
                                    class="fff-flex-time-segments__option"
                                    role="option"
                                    x-bind:class="{ 'is-selected': selectedHour() === hour }"
                                    x-bind:data-selected="selectedHour() === hour ? 'true' : 'false'"
                                    x-bind:aria-selected="selectedHour() === hour ? 'true' : 'false'"
                                    x-bind:disabled="! isHourAllowed(hour)"
                                    x-on:click="selectHour(hour)"
                                    x-text="hour"
                                ></button>
                            </template>
                        </div>

                        <div
                            class="fff-flex-time-segments__column fff-flex-time-segments__column--minutes"
                            role="presentation"
                            aria-label="{{ $minutePlaceholder }}"
                        >
                            <template x-for="minute in minuteOptions()" x-bind:key="`minute-${minute}`">
                                <button
                                    type="button"
                                    class="fff-flex-time-segments__option"
                                    role="option"
                                    x-bind:class="{ 'is-selected': selectedMinute() === minute }"
                                    x-bind:data-selected="selectedMinute() === minute ? 'true' : 'false'"
                                    x-bind:aria-selected="selectedMinute() === minute ? 'true' : 'false'"
                                    x-bind:disabled="! isMinuteAllowed(minute)"
                                    x-on:click="selectMinute(minute)"
                                    x-text="minute"
                                ></button>
                            </template>
                        </div>

                        <template x-if="uses12HourCycle">
                            <div
                                class="fff-flex-time-segments__column fff-flex-time-segments__column--period"
                                role="presentation"
                                aria-label="AM/PM"
                            >
                                <template x-for="period in dayPeriodOptions()" x-bind:key="`period-${period}`">
                                    <button
                                        type="button"
                                        class="fff-flex-time-segments__option"
                                        role="option"
                                        x-bind:class="{ 'is-selected': selectedDayPeriod() === period }"
                                        x-bind:data-selected="selectedDayPeriod() === period ? 'true' : 'false'"
                                        x-bind:aria-selected="selectedDayPeriod() === period ? 'true' : 'false'"
                                        x-on:click="selectDayPeriod(period)"
                                        x-text="period"
                                    ></button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        @else
            <button
                type="button"
                class="fff-flex-time-segments__trigger"
                tabindex="-1"
                aria-hidden="true"
                @disabled(true)
            >
                <span @class([
                    'fff-flex-time-segments__value',
                    'is-placeholder' => ! $hasValue,
                ])>{{ e($displayLabel) }}</span>

                <x-filament::icon
                    icon="heroicon-m-chevron-up-down"
                    class="fff-flex-time-segments__chevron"
                />
            </button>
        @endif
    </div>
</div>
