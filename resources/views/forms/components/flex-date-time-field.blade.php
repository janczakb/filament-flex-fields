@php
    use Bjanczak\FilamentFlexFields\Enums\DateTimeFieldMode;
    use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeSegmentHydrator;
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $alpine = $field->getAlpineConfiguration();
    $initialDisplay = $alpine['initialDisplay'];
    $hasError = filled($statePath) && $errors->has($statePath);
    $livewireKey = $getLivewireKey();
    $isRange = in_array($field->getMode()->value, ['dateRange', 'timeRange'], true);
    $isDateTime = $field->getMode()->value === 'dateTime';
    $showCalendar = $field->shouldShowCalendar();
    $showCalendarButton = $field->shouldShowCalendarButton();
    $showTimeUnderCalendar = ! $field->shouldHideTimeSection()
        && $field->getGranularity()->value !== 'day'
        && ($isRange || $isDateTime);
    $calendarTimeParts = $showTimeUnderCalendar
        ? DateTimeSegmentHydrator::segmentParts(
            DateTimeFieldMode::Time,
            $field->getGranularity(),
            $field->getHourCycle(),
            $field->shouldShowSeconds(),
        )
        : [];
    $viewSegments = $field->getViewSegments();
    $segmentParts = $viewSegments['parts'];
    $locale = $field->getLocale();
    $monthDisplay = $field->getMonthDisplay();
    $showTimezoneInSegments = $field->getMode() === DateTimeFieldMode::Time && ! $field->shouldHideTimeZone();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'flex-date-time-field'])
    <x-filament-flex-fields::lazy-alpine-mount :mount-immediately="$isDisabled || $isReadOnly" :wrap-slot="false">
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize(), $showCalendar, $field->getMode()->value])), 0, 64) }}"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flex-date-time-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="flexDateTimeFieldFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            disabled: @js($isDisabled),
            readOnly: @js($isReadOnly),
            initialState: @js($alpine['initialState']),
            initialDisplay: @js($initialDisplay),
            initialSegments: @js($viewSegments),
            @foreach (collect($alpine)->except(['initialState', 'initialDisplay', 'initialSegments']) as $key => $value)
                {{ $key }}: @js($value),
            @endforeach
        })"
        x-init="init()"
        x-on:click.outside="if ($refs.calendarMenu?.contains($event.target)) { return }; closeCalendar()"
        x-on:keydown.escape.window="closeCalendar()"
        @class([
            'fff-date-time-field',
            'fff-flex-text-input',
            'fff-date-time-field--'.$getSize(),
            'fff-flex-text-input--'.$getSize(),
            'fff-date-time-field--'.$getVariant(),
            'fff-flex-text-input--'.$getVariant(),
            'fff-date-time-field--'.$field->getMode()->value,
            'fff-date-time-field--show-seconds' => $field->shouldShowSeconds(),
            'fff-date-time-field--textual-month' => $monthDisplay->isTextual(),
            'fff-date-time-field--textual-month-'.$monthDisplay->value => $monthDisplay->isTextual(),
            'has-actions' => $showCalendarButton,
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'has-focus-outline' => $shouldShowFocusOutline(),
        ])
        x-bind:class="{ 'is-display-ready': displayReady }"
        x-bind:dir="calendarDirection"
        @if ($monthDisplay->isTextual())
            style="--fff-date-time-month-ch: {{ DateTimeSegmentHydrator::segmentMaxLength('month', $monthDisplay, $locale) }}"
        @endif
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div
            @class([
                'fff-date-time-field__shell fff-flex-text-input__shell',
                'is-invalid' => $hasError,
                'is-segment-invalid' => false,
            ])
            x-bind:class="{ 'is-segment-invalid': segmentInvalid || config.isInvalid }"
            x-ref="fieldShell"
        >
            <div class="fff-date-time-field__input-row">
                <div class="fff-date-time-field__segments" role="presentation">
                    @if ($isRange)
                        @foreach (['start', 'end'] as $rangeTarget)
                            @if ($rangeTarget === 'end')
                                <span class="fff-date-time-field__range-separator">{{ $field->getRangeSeparator() }}</span>
                            @endif

                            <div class="fff-date-time-field__range-group" data-range-target="{{ $rangeTarget }}">
                                @foreach ($segmentParts as $index => $part)
                                    @php
                                        $segmentValue = $viewSegments['range'][$rangeTarget][$part] ?? '';
                                        $separator = DateTimeSegmentHydrator::separatorAfter($part, $segmentParts, $locale);
                                    @endphp

                                    <input
                                        type="text"
                                        class="fff-date-time-field__segment"
                                        data-segment-part="{{ $part }}"
                                        @if ($part === 'month' && $monthDisplay->isTextual())
                                            data-month-display="{{ $monthDisplay->value }}"
                                        @endif
                                        value="{{ e($segmentValue) }}"
                                        placeholder="{{ DateTimeSegmentHydrator::segmentPlaceholder($part, $locale, $monthDisplay) }}"
                                        maxlength="{{ DateTimeSegmentHydrator::segmentMaxLength($part, $monthDisplay, $locale) }}"
                                        x-bind:maxlength="segmentMaxLength('{{ $part }}')"
                                        x-bind:placeholder="segmentPlaceholder('{{ $part }}')"
                                        x-bind:inputmode="segmentInputMode('{{ $part }}')"
                                        x-bind:value="rangeSegments.{{ $rangeTarget }}['{{ $part }}'] ?? ''"
                                        x-on:focus="onSegmentFocus({{ $index }}, '{{ $rangeTarget }}', $event)"
                                        x-on:blur="onSegmentBlur($event)"
                                        x-on:input="onSegmentInput('{{ $part }}', $event)"
                                        x-on:keydown="onSegmentKeydown('{{ $part }}', $event)"
                                        autocomplete="off"
                                        @disabled($isDisabled || $isReadOnly)
                                    />

                                    @if ($separator !== '')
                                        <span class="fff-date-time-field__separator">{{ $separator }}</span>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    @else
                        @foreach ($segmentParts as $index => $part)
                            @php
                                $segmentValue = $viewSegments['single'][$part] ?? '';
                                $separator = DateTimeSegmentHydrator::separatorAfter($part, $segmentParts, $locale);
                            @endphp

                            <input
                                type="text"
                                class="fff-date-time-field__segment"
                                data-segment-part="{{ $part }}"
                                @if ($part === 'month' && $monthDisplay->isTextual())
                                    data-month-display="{{ $monthDisplay->value }}"
                                @endif
                                value="{{ e($segmentValue) }}"
                                placeholder="{{ DateTimeSegmentHydrator::segmentPlaceholder($part, $locale, $monthDisplay) }}"
                                maxlength="{{ DateTimeSegmentHydrator::segmentMaxLength($part, $monthDisplay, $locale) }}"
                                x-bind:maxlength="segmentMaxLength('{{ $part }}')"
                                x-bind:placeholder="segmentPlaceholder('{{ $part }}')"
                                x-bind:inputmode="segmentInputMode('{{ $part }}')"
                                x-bind:value="segments['{{ $part }}'] ?? ''"
                                x-on:focus="onSegmentFocus({{ $index }}, null, $event)"
                                x-on:blur="onSegmentBlur($event)"
                                x-on:input="onSegmentInput('{{ $part }}', $event)"
                                x-on:keydown="onSegmentKeydown('{{ $part }}', $event)"
                                autocomplete="off"
                                @disabled($isDisabled || $isReadOnly)
                            />

                            @if ($separator !== '')
                                <span class="fff-date-time-field__separator">{{ $separator }}</span>
                            @endif
                        @endforeach
                    @endif

                    @if ($showTimezoneInSegments)
                        <span class="fff-date-time-field__timezone">{{ $field->getTimeZone() }}</span>
                    @endif
                </div>

                @if ($showCalendarButton)
                    <div class="fff-date-time-field__suffix">
                        <button
                            type="button"
                            class="fff-date-time-field__calendar-button"
                            x-ref="calendarTrigger"
                            x-on:click.stop="toggleCalendar()"
                            x-bind:aria-expanded="calendarOpen ? 'true' : 'false'"
                            aria-haspopup="dialog"
                            x-bind:aria-label="config.labels.calendar"
                            @disabled($isDisabled || $isReadOnly)
                        >
                            <x-filament::icon
                                :icon="GravityIcon::Calendar"
                                class="fff-date-time-field__calendar-icon"
                            />
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <p
            class="fff-date-time-field__segment-error"
            x-show="segmentInvalid || config.isInvalid"
            x-cloak
            role="alert"
            x-text="segmentValidationMessage()"
        ></p>

        @if ($showCalendar)
                <template x-teleport="body">
                    <div
                        @class([
                            'fff-date-time-field__calendar',
                            'fff-date-time-field__calendar--'.$getSize(),
                        ])
                        x-ref="calendarMenu"
                        x-show="calendarOpen"
                        x-cloak
                        x-transition.opacity.duration.150ms
                        x-bind:class="{ 'is-positioned': calendarReady }"
                        x-bind:dir="calendarDirection"
                        x-on:click.stop
                        role="dialog"
                        x-bind:aria-label="config.labels.calendar"
                    >
                        <div
                            class="fff-date-time-field__calendar-header"
                            x-bind:class="{ 'is-year-picker-open': yearPickerOpen || calendarViewMode === 'years' }"
                        >
                            <button
                                type="button"
                                class="fff-date-time-field__year-trigger"
                                x-on:click="onCalendarHeaderClick()"
                                x-bind:disabled="isCalendarHeaderDisabled"
                                x-bind:aria-expanded="(yearPickerOpen || calendarViewMode === 'years') ? 'true' : 'false'"
                            >
                                <span class="fff-date-time-field__year-trigger-heading" x-text="calendarHeaderLabel"></span>
                                <x-filament::icon
                                    :icon="GravityIcon::ChevronRight"
                                    class="fff-date-time-field__year-trigger-indicator"
                                    x-bind:class="{ 'is-open': yearPickerOpen || calendarViewMode === 'years' }"
                                />
                            </button>
                            <div class="fff-date-time-field__calendar-nav" x-show="showsCalendarNavigation">
                                <button type="button" class="fff-date-time-field__nav-button" x-on:click="previousMonth()" x-bind:aria-label="calendarViewMode === 'days' ? 'Previous month' : (calendarViewMode === 'months' ? 'Previous year' : 'Previous years')">
                                    <x-filament::icon
                                        :icon="GravityIcon::ChevronLeft"
                                        class="fff-date-time-field__nav-icon"
                                    />
                                </button>
                                <button type="button" class="fff-date-time-field__nav-button" x-on:click="nextMonth()" x-bind:aria-label="calendarViewMode === 'days' ? 'Next month' : (calendarViewMode === 'months' ? 'Next year' : 'Next years')">
                                    <x-filament::icon
                                        :icon="GravityIcon::ChevronRight"
                                        class="fff-date-time-field__nav-icon"
                                    />
                                </button>
                            </div>
                        </div>

                        <div class="fff-date-time-field__calendar-body" x-show="showsDayCalendarGrid">
                            <div class="fff-date-time-field__weekdays">
                                <template x-for="label in weekdayLabels" :key="label">
                                    <span class="fff-date-time-field__weekday" x-text="label"></span>
                                </template>
                            </div>

                            <div
                                class="fff-date-time-field__day-grid-wrap"
                                x-ref="calendarDayGrid"
                                x-bind:class="{ 'is-year-picker-open': yearPickerOpen }"
                            >
                                <div class="fff-date-time-field__grid">
                                    <template x-for="(week, weekIndex) in calendarWeeks" :key="'week-' + weekIndex">
                                        <div class="fff-date-time-field__week">
                                        <template x-for="(cell, dayIndex) in week" :key="'day-' + weekIndex + '-' + dayIndex">
                                            <button
                                                type="button"
                                                class="fff-date-time-field__day"
                                                x-bind:class="getDayCellClass(cell)"
                                                x-bind:disabled="isDateDisabled(resolveCalendarCell(cell))"
                                                x-on:mouseenter="if (isRange && rangeValue.start && ! rangeValue.end) { hoveredDate = resolveCalendarCell(cell) }"
                                                x-on:mouseleave="hoveredDate = null"
                                                x-on:click="selectDate(cell)"
                                            >
                                                <span class="fff-date-time-field__day-label" x-text="resolveCalendarCell(cell).day"></span>
                                                <span class="fff-date-time-field__today-dot" x-show="isToday(resolveCalendarCell(cell))"></span>
                                            </button>
                                        </template>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div
                                class="fff-date-time-field__year-overlay"
                                x-ref="calendarYearOverlay"
                                x-bind:data-open="yearPickerOpen && usesYearPickerOverlay ? 'true' : 'false'"
                                x-bind:style="yearPickerOverlayStyle"
                                tabindex="-1"
                            >
                                <div class="fff-date-time-field__year-scroll-inner">
                                    <template x-for="year in yearOptions" :key="'overlay-year-' + year">
                                        <button
                                            type="button"
                                            class="fff-date-time-field__picker-cell"
                                            x-bind:class="{ 'is-selected': isSelectedCalendarYear(year) }"
                                            x-bind:data-year="year"
                                            x-on:click="selectCalendarYear(year)"
                                            x-text="year"
                                        ></button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="fff-date-time-field__picker-grid" x-show="calendarViewMode === 'months'">
                            <template x-for="month in monthOptions" :key="'month-' + month.value">
                                <button
                                    type="button"
                                    class="fff-date-time-field__picker-cell"
                                    x-bind:class="{ 'is-selected': isSelectedCalendarMonth(month.value) }"
                                    x-on:click="selectCalendarMonth(month.value)"
                                    x-text="month.label"
                                ></button>
                            </template>
                        </div>

                        <div
                            class="fff-date-time-field__year-scroll"
                            x-ref="calendarYearGrid"
                            x-show="calendarViewMode === 'years' && ! usesYearPickerOverlay"
                            tabindex="-1"
                        >
                            <div class="fff-date-time-field__year-scroll-inner">
                                <template x-for="year in yearOptions" :key="'year-' + year">
                                    <button
                                        type="button"
                                        class="fff-date-time-field__picker-cell"
                                        x-bind:class="{ 'is-selected': isSelectedCalendarYear(year) }"
                                        x-bind:data-year="year"
                                        x-on:click="selectCalendarYear(year)"
                                        x-text="year"
                                    ></button>
                                </template>
                            </div>
                        </div>

                        @if (filled($calendarTimeParts))
                            <div class="fff-date-time-field__time-rows">
                                @if ($isRange)
                                    @foreach (['start', 'end'] as $timeTarget)
                                        <div class="fff-date-time-field__time-row">
                                            <span
                                                class="fff-date-time-field__time-label"
                                                x-text="config.labels.{{ $timeTarget === 'start' ? 'range_start' : 'range_end' }}"
                                            ></span>
                                            <div class="fff-date-time-field__time-segments" data-time-target="{{ $timeTarget }}">
                                                @foreach ($calendarTimeParts as $part)
                                                    @php
                                                        $timeSeparator = DateTimeSegmentHydrator::separatorAfter($part, $calendarTimeParts, $locale);
                                                    @endphp

                                                    <input
                                                        type="text"
                                                        class="fff-date-time-field__segment"
                                                        data-segment-part="{{ $part }}"
                                                        placeholder="{{ DateTimeSegmentHydrator::segmentPlaceholder($part, $locale) }}"
                                                        maxlength="{{ DateTimeSegmentHydrator::segmentMaxLength($part) }}"
                                                        x-bind:value="timeSegments.{{ $timeTarget }}['{{ $part }}'] ?? ''"
                                                        x-on:focus="onTimeSegmentFocus('{{ $timeTarget }}', '{{ $part }}', $event)"
                                                        x-on:blur="onTimeSegmentBlur('{{ $timeTarget }}', '{{ $part }}', $event)"
                                                        x-on:input="onTimeSegmentInput('{{ $timeTarget }}', '{{ $part }}', $event)"
                                                        x-on:keydown="onTimeSegmentKeydown('{{ $timeTarget }}', '{{ $part }}', $event)"
                                                        x-bind:inputmode="segmentInputMode('{{ $part }}')"
                                                        autocomplete="off"
                                                        @disabled($isDisabled || $isReadOnly)
                                                    />

                                                    @if ($timeSeparator !== '')
                                                        <span class="fff-date-time-field__separator">{{ $timeSeparator }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="fff-date-time-field__time-row">
                                        <span class="fff-date-time-field__time-label" x-text="config.labels.time"></span>
                                        <div class="fff-date-time-field__time-segments" data-time-target="single">
                                            @foreach ($calendarTimeParts as $part)
                                                @php
                                                    $timeSeparator = DateTimeSegmentHydrator::separatorAfter($part, $calendarTimeParts, $locale);
                                                @endphp

                                                <input
                                                    type="text"
                                                    class="fff-date-time-field__segment"
                                                    data-segment-part="{{ $part }}"
                                                    placeholder="{{ DateTimeSegmentHydrator::segmentPlaceholder($part, $locale) }}"
                                                    maxlength="{{ DateTimeSegmentHydrator::segmentMaxLength($part) }}"
                                                    x-bind:value="timeSegments.single['{{ $part }}'] ?? ''"
                                                    x-on:focus="onTimeSegmentFocus('single', '{{ $part }}', $event)"
                                                    x-on:blur="onTimeSegmentBlur('single', '{{ $part }}', $event)"
                                                    x-on:input="onTimeSegmentInput('single', '{{ $part }}', $event)"
                                                    x-on:keydown="onTimeSegmentKeydown('single', '{{ $part }}', $event)"
                                                    x-bind:inputmode="segmentInputMode('{{ $part }}')"
                                                    autocomplete="off"
                                                    @disabled($isDisabled || $isReadOnly)
                                                />

                                                @if ($timeSeparator !== '')
                                                    <span class="fff-date-time-field__separator">{{ $timeSeparator }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if ($isRange)
                                    <p
                                        class="fff-date-time-field__range-summary"
                                        x-show="calendarRangeSummary !== ''"
                                        x-text="calendarRangeSummary"
                                    ></p>
                                @endif
                            </div>
                        @endif
                    </div>
                </template>
            @endif
    </div>
    </x-filament-flex-fields::lazy-alpine-mount>
</x-dynamic-component>
