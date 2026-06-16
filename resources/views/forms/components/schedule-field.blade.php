@php
    use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
    use Filament\Support\View\Components\ToggleComponent;
    use Filament\Support\Facades\FilamentAsset;
    use Illuminate\Support\Arr;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $hasError = filled($statePath) && $errors->has($statePath);
    $livewireKey = $getLivewireKey();
    $config = $field->getAlpineConfiguration();
    $initialState = $field->normalizeState($getState());
    $days = $field->getDays();
    $timezones = $field->showsTimezoneSelector() ? $field->getTimezoneOptionsForJs() : [];
    $selectedTimezoneId = $initialState['timezone'] ?? $field->getDefaultTimezoneIdentifier();
    $selectedTimezone = $selectedTimezoneId
        ? collect($timezones)->firstWhere('id', $selectedTimezoneId)
        : null;
    $switchOnColorClasses = Arr::toCssClasses(\Filament\Support\get_component_color_classes(ToggleComponent::class, 'primary'));
    $switchOffColorClasses = Arr::toCssClasses(\Filament\Support\get_component_color_classes(ToggleComponent::class, 'gray'));
    $switchOnColorClassList = array_values(array_filter(explode(' ', $switchOnColorClasses)));
    $switchOffColorClassList = array_values(array_filter(explode(' ', $switchOffColorClasses)));
    $switchToggleColorClassList = array_values(array_unique([...$switchOnColorClassList, ...$switchOffColorClassList]));
    $switchToggleColorClassBinding = implode(', ', array_map(
        fn (string $class): string => sprintf(
            '%s: Boolean(state) ? %s : %s',
            json_encode($class),
            in_array($class, $switchOnColorClassList, true) ? 'true' : 'false',
            in_array($class, $switchOffColorClassList, true) ? 'true' : 'false',
        ),
        $switchToggleColorClassList,
    ));
    $size = $getSize();
    $timezoneSize = 'md';
    $showTimezone = $field->showsTimezoneSelector();
    $labels = $config['labels'] ?? [];
    $timeStep = $field->getTimeStep();
    $minSlots = $field->getMinSlots();
    $maxSlots = $field->getMaxSlots();
    $copySourceDay = $field->getCopySourceDay();
    $workdays = $field->getWorkdays();
    $allowCopyToWeekdays = $field->shouldAllowCopyToWeekdays();
    $openStatusLabel = $labels['open'] ?? __('filament-flex-fields::default.schedule.open');
    $closedStatusLabel = $labels['closed'] ?? __('filament-flex-fields::default.schedule.closed');
    $statusLabelMinCh = max(mb_strlen($openStatusLabel), mb_strlen($closedStatusLabel));
    $copyToWeekdaysLabel = $labels['copyToWeekdays'] ?? __('filament-flex-fields::default.schedule.copy_to_weekdays');
    $hasCopyToWeekdaysColumn = $allowCopyToWeekdays
        && collect($workdays)
            ->contains(fn (string $weekday): bool => $weekday !== $copySourceDay && in_array($weekday, $days, true));
    $copyColumnMinCh = mb_strlen($copyToWeekdaysLabel) + 3;
    $slotLabel = $labels['slot'] ?? __('filament-flex-fields::default.schedule.slot');
    $breakLabel = $labels['break'] ?? __('filament-flex-fields::default.schedule.break');
    $lockedDays = $field->getLockedDays();
    $slotLabelWidthCh = max(
        mb_strlen($slotLabel.' '.$maxSlots),
        mb_strlen($breakLabel),
    ) + 1;
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'schedule-field'])

    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $size, $getVariant(), $days, $field->showsTimezoneSelector()])), 0, 64) }}"
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('schedule-field', FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="scheduleFieldFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            disabled: @js($isDisabled),
            readOnly: @js($isReadOnly),
            initialState: @js($initialState),
            ...@js($config),
        })"
        x-init="init()"
        x-on:click.outside="if ($refs.timezoneMenu?.contains($event.target)) { return }; closeMenu()"
        x-on:keydown.escape.window="closeMenu()"
        @class([
            'fff-schedule-field',
            'fff-schedule-field--'.$size,
            'fff-schedule-field--'.$getVariant(),
            'fff-schedule-field--has-copy-column' => $hasCopyToWeekdaysColumn,
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'has-focus-outline' => $shouldShowFocusOutline(),
            'has-error' => $hasError,
        ])
        x-bind:class="{ 'is-day-animated': dayAnimationsEnabled }"
        style="--fff-schedule-status-min-ch: {{ $statusLabelMinCh }}; --fff-schedule-copy-min-ch: {{ $copyColumnMinCh }}; --fff-schedule-slot-label-w: calc({{ $slotLabelWidthCh }} * 1ch + 1.75rem);"
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        @if ($showTimezone)
            <div class="fff-schedule-field__timezone">
                <label class="fff-schedule-field__timezone-label">{{ $labels['timezone'] ?? __('filament-flex-fields::default.schedule.timezone') }}</label>

                <div
                    @class([
                        'fff-timezone-field',
                        'fff-flex-text-input',
                        'fff-timezone-field--'.$timezoneSize,
                        'fff-flex-text-input--'.$timezoneSize,
                        'fff-timezone-field--'.$getVariant(),
                        'fff-flex-text-input--'.$getVariant(),
                    ])
                >
                    <div @class([
                        'fff-timezone-field__shell fff-flex-text-input__shell',
                        'is-invalid' => $hasError,
                    ])>
                        <button
                            type="button"
                            class="fff-timezone-field__trigger"
                            x-ref="timezoneTrigger"
                            x-on:click.stop="toggleMenu()"
                            x-bind:aria-expanded="menuOpen ? 'true' : 'false'"
                            aria-haspopup="listbox"
                            x-bind:aria-label="labels.timezone"
                            x-bind:disabled="! isInteractive"
                        >
                            <span class="fff-timezone-field__icon-wrap" aria-hidden="true">
                                <x-filament::icon
                                    :icon="GravityIcon::Globe"
                                    class="fff-timezone-field__icon"
                                />
                            </span>

                            <span class="fff-timezone-field__label">
                                <span
                                    @class([
                                        'fff-timezone-field__ssr-label',
                                        'is-placeholder' => ! $selectedTimezone,
                                    ])
                                    x-bind:class="{ 'is-replaced': displayReady }"
                                >
                                    @if ($selectedTimezone)
                                        {{ e($selectedTimezone['label']) }}
                                    @else
                                        {{ e(__('filament-flex-fields::default.schedule.timezone_placeholder')) }}
                                    @endif
                                </span>
                                <span
                                    class="fff-timezone-field__live-label"
                                    x-bind:class="{ 'is-ready': displayReady }"
                                    x-show="! isTimezoneEmpty"
                                    x-text="selectedTimezone?.label"
                                ></span>
                                <span
                                    class="fff-timezone-field__live-placeholder"
                                    x-bind:class="{ 'is-ready': displayReady }"
                                    x-show="isTimezoneEmpty"
                                    x-text="timezonePlaceholder"
                                ></span>
                            </span>

                            <span class="fff-timezone-field__offset" x-show="showOffset && ! isTimezoneEmpty">
                                <span
                                    class="fff-timezone-field__ssr-meta"
                                    x-bind:class="{ 'is-replaced': displayReady }"
                                >
                                    @if ($selectedTimezone)
                                        {{ e($selectedTimezone['offset']) }}
                                    @endif
                                </span>
                                <span
                                    class="fff-timezone-field__live-meta"
                                    x-bind:class="{ 'is-ready': displayReady }"
                                    x-text="selectedTimezone?.offset"
                                ></span>
                            </span>

                            <x-filament::icon
                                icon="heroicon-m-chevron-up-down"
                                class="fff-timezone-field__chevron"
                            />
                        </button>

                        <template x-teleport="body">
                            <div
                                @class([
                                    'fff-timezone-field__menu',
                                    'fff-teleported-menu',
                                    'fff-timezone-field__menu--'.$timezoneSize,
                                    'fff-teleported-menu--'.$timezoneSize,
                                ])
                                x-ref="timezoneMenu"
                                x-show="menuOpen"
                                x-cloak
                                x-bind:class="{ 'is-positioned': menuReady }"
                                x-on:click.stop
                                role="listbox"
                                x-bind:aria-label="labels.timezone"
                            >
                                <div class="fff-timezone-field__search-wrap fff-teleported-menu__search-wrap" x-show="searchable">
                                    <input
                                        type="search"
                                        class="fff-timezone-field__search fff-teleported-menu__search"
                                        x-model="timezoneSearch"
                                        x-ref="timezoneSearch"
                                        x-bind:placeholder="searchPlaceholder"
                                        x-on:keydown.stop
                                    />
                                </div>

                                <ul
                                    class="fff-timezone-field__list"
                                    x-on:scroll.passive="onTimezoneListScroll($event)"
                                >
                                    <li
                                        x-show="usesVirtualScroll"
                                        class="fff-timezone-field__virtual-spacer"
                                        :style="`height: ${virtualSpacerTop}px`"
                                        aria-hidden="true"
                                    ></li>
                                    <template x-for="timezone in visibleTimezones" :key="timezone.id">
                                        <li>
                                            <button
                                                type="button"
                                                class="fff-timezone-field__option"
                                                x-on:click="selectTimezone(timezone.id)"
                                                x-bind:class="{ 'is-selected': timezone.id === getTimezoneValue() }"
                                                role="option"
                                                x-bind:aria-selected="timezone.id === getTimezoneValue() ? 'true' : 'false'"
                                            >
                                                <span class="fff-timezone-field__option-icon-wrap" aria-hidden="true">
                                                    <x-filament::icon
                                                        :icon="GravityIcon::Clock"
                                                        class="fff-timezone-field__option-icon"
                                                    />
                                                </span>
                                                <span class="fff-timezone-field__option-name" x-text="timezone.label"></span>
                                                <span
                                                    class="fff-timezone-field__option-offset"
                                                    x-show="showOffset"
                                                    x-text="timezone.offset"
                                                ></span>
                                            </button>
                                        </li>
                                    </template>
                                    <li
                                        x-show="usesVirtualScroll"
                                        class="fff-timezone-field__virtual-spacer"
                                        :style="`height: ${virtualSpacerBottom}px`"
                                        aria-hidden="true"
                                    ></li>
                                </ul>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        @endif

        <div class="fff-schedule-field__days">
            @foreach ($days as $day)
                @php
                    $dayState = $initialState['days'][$day] ?? ['enabled' => false, 'slots' => []];
                    $isDayEnabledInitial = (bool) ($dayState['enabled'] ?? false);
                    $daySlotsInitial = is_array($dayState['slots'] ?? null) ? $dayState['slots'] : [];
                    $canAddSlotInitial = count($daySlotsInitial) < $maxSlots;
                    $canRemoveSlotInitial = ! $isDisabled
                        && ! $isReadOnly
                        && (! $isDayEnabledInitial || count($daySlotsInitial) > $minSlots);
                    $isCopySourceDay = $day === $copySourceDay;
                    $dayToggleColorClassBinding = str_replace(
                        'Boolean(state)',
                        'isDayEnabled('.json_encode($day).')',
                        $switchToggleColorClassBinding,
                    );
                @endphp
                <div
                    @class([
                        'fff-schedule-field__day',
                        'is-open' => $isDayEnabledInitial,
                    ])
                    wire:key="{{ $statePath }}-day-{{ $day }}"
                    x-bind:class="{ 'is-open': isDayEnabled(@js($day)), 'has-day-error': dayValidationError(@js($day)) }"
                >
                    <div class="fff-schedule-field__day-header">
                        <span class="fff-schedule-field__day-label">{{ __("filament-flex-fields::default.schedule.days.{$day}") }}</span>

                        <span class="fff-schedule-field__day-status">
                            <span
                                class="fff-schedule-field__ssr-status"
                                x-bind:class="{ 'is-replaced': displayReady }"
                            >
                                {{ $isDayEnabledInitial ? $openStatusLabel : $closedStatusLabel }}
                            </span>
                            <span
                                class="fff-schedule-field__live-status"
                                x-bind:class="{ 'is-ready': displayReady }"
                                x-text="isDayEnabled(@js($day)) ? labels.open : labels.closed"
                            ></span>
                        </span>

                        <div class="fff-schedule-field__day-header-spacer" aria-hidden="true"></div>

                        @if ($hasCopyToWeekdaysColumn)
                            <div @class([
                                'fff-schedule-field__copy-slot',
                                'fff-schedule-field__copy-slot--active' => $isCopySourceDay,
                            ])>
                                @if ($isCopySourceDay)
                                    <button
                                        type="button"
                                        class="fff-schedule-field__copy-btn"
                                        title="{{ $copyToWeekdaysLabel }}"
                                        x-bind:class="{ 'is-hidden': ! shouldShowCopyButton(@js($day)) }"
                                        x-on:click="copySourceToWeekdays()"
                                        x-bind:disabled="! isInteractive"
                                        x-bind:aria-label="labels.copyToWeekdays"
                                    >
                                        <x-filament::icon :icon="GravityIcon::Copy" class="h-3.5 w-3.5" />
                                        <span class="fff-schedule-field__copy-btn-label">{{ $copyToWeekdaysLabel }}</span>
                                    </button>
                                @endif
                            </div>
                        @endif

                        @if (! $isReadOnly)
                            @if (in_array($day, $lockedDays, true))
                                <span
                                    class="fff-schedule-field__day-lock"
                                    title="{{ __('filament-flex-fields::default.schedule.day_locked') }}"
                                >
                                    <x-filament::icon :icon="GravityIcon::Lock" class="fff-schedule-field__day-lock-icon" />
                                </span>
                            @else
                                <div
                                    @class([
                                        'fff-switch',
                                        'fff-switch--sm',
                                        'fff-switch--inline',
                                        'fff-schedule-field__day-switch',
                                        'is-disabled' => $isDisabled,
                                    ])
                                >
                                    <button
                                        type="button"
                                        @class([
                                            'fff-switch__control',
                                            $isDayEnabledInitial ? $switchOnColorClasses : $switchOffColorClasses,
                                        ])
                                        role="switch"
                                        aria-label="{{ __('filament-flex-fields::default.schedule.toggle_day', ['day' => __("filament-flex-fields::default.schedule.days.{$day}")]) }}"
                                        aria-checked="{{ $isDayEnabledInitial ? 'true' : 'false' }}"
                                        data-checked="{{ $isDayEnabledInitial ? 'true' : 'false' }}"
                                        x-bind:aria-checked="isDayEnabled(@js($day)) ? 'true' : 'false'"
                                        x-bind:data-checked="isDayEnabled(@js($day)) ? 'true' : 'false'"
                                        x-bind:class="{ {{ $dayToggleColorClassBinding }} }"
                                        x-on:click="toggleDay(@js($day))"
                                        x-bind:disabled="! isInteractive"
                                    >
                                        <span class="fff-switch__thumb"></span>
                                    </button>
                                </div>
                            @endif
                        @endif
                    </div>

                    @if ($isDayEnabledInitial)
                        <div
                            class="fff-schedule-field__day-body-ssr"
                            x-bind:class="{ 'is-replaced': displayReady }"
                        >
                            <div class="fff-schedule-field__slots">
                                @php
                                    $workSlotCounter = 0;
                                @endphp
                                @foreach ($daySlotsInitial as $slotIndex => $slot)
                                    @php
                                        $isBreakSlotInitial = ($slot['type'] ?? 'slot') === 'break';

                                        if (! $isBreakSlotInitial) {
                                            $workSlotCounter++;
                                        }

                                        $slotEntryLabelInitial = $isBreakSlotInitial
                                            ? $breakLabel
                                            : $slotLabel.' '.$workSlotCounter;
                                    @endphp
                                    <div @class([
                                        'fff-schedule-field__slot',
                                        'fff-schedule-field__slot--break' => $isBreakSlotInitial,
                                    ])>
                                        <span class="fff-schedule-field__slot-label">
                                            <span @class([
                                                'fff-schedule-field__slot-label-icon',
                                                'fff-schedule-field__slot-label-icon--break' => $isBreakSlotInitial,
                                                'fff-schedule-field__slot-label-icon--slot' => ! $isBreakSlotInitial,
                                            ])>
                                                <x-filament::icon
                                                    :icon="$isBreakSlotInitial ? GravityIcon::Clock : GravityIcon::Briefcase"
                                                    class="fff-schedule-field__slot-icon"
                                                />
                                            </span>
                                            <span class="fff-schedule-field__slot-label-text">{{ $slotEntryLabelInitial }}</span>
                                        </span>

                                        <div class="fff-schedule-field__time-fields">
                                            <div class="fff-schedule-field__time-group">
                                                <label class="fff-schedule-field__time-label">{{ $labels['from'] ?? __('filament-flex-fields::default.schedule.from') }}</label>
                                                @include('filament-flex-fields::forms.components.partials.flex-time-segments', [
                                                    'size' => $size,
                                                    'variant' => $getVariant(),
                                                    'value' => $slot['from'] ?? '',
                                                    'minuteStep' => $timeStep,
                                                    'disabled' => $isDisabled,
                                                    'readOnly' => $isReadOnly,
                                                    'interactive' => false,
                                                    'live' => false,
                                                ])
                                            </div>

                                            <div class="fff-schedule-field__time-group">
                                                <label class="fff-schedule-field__time-label">{{ $labels['to'] ?? __('filament-flex-fields::default.schedule.to') }}</label>
                                                @include('filament-flex-fields::forms.components.partials.flex-time-segments', [
                                                    'size' => $size,
                                                    'variant' => $getVariant(),
                                                    'value' => $slot['to'] ?? '',
                                                    'minuteStep' => $timeStep,
                                                    'disabled' => $isDisabled,
                                                    'readOnly' => $isReadOnly,
                                                    'interactive' => false,
                                                    'live' => false,
                                                ])
                                            </div>
                                        </div>

                                        @if ($canRemoveSlotInitial)
                                            <div class="fff-schedule-field__slot-actions">
                                                <button
                                                    type="button"
                                                    class="fff-schedule-field__icon-btn"
                                                    tabindex="-1"
                                                    aria-hidden="true"
                                                    @disabled($isDisabled || $isReadOnly)
                                                >
                                                    <x-filament::icon :icon="GravityIcon::TrashBin" class="h-4 w-4" />
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="fff-schedule-field__slot-toolbar">
                                <button
                                    type="button"
                                    class="fff-schedule-field__action-btn"
                                    tabindex="-1"
                                    aria-hidden="true"
                                    @disabled($isDisabled || $isReadOnly || ! $canAddSlotInitial)
                                >
                                    <x-filament::icon :icon="GravityIcon::Plus" class="h-3.5 w-3.5" />
                                    <span>{{ $labels['addSlot'] ?? __('filament-flex-fields::default.schedule.add_slot') }}</span>
                                </button>

                                <button
                                    type="button"
                                    class="fff-schedule-field__action-btn"
                                    tabindex="-1"
                                    aria-hidden="true"
                                    @disabled($isDisabled || $isReadOnly || ! $canAddSlotInitial)
                                >
                                    <x-filament::icon :icon="GravityIcon::Clock" class="h-3.5 w-3.5" />
                                    <span>{{ $labels['addBreak'] ?? __('filament-flex-fields::default.schedule.add_break') }}</span>
                                </button>
                            </div>
                        </div>
                    @endif

                    <div
                        class="fff-schedule-field__day-body-live"
                        x-bind:class="{ 'is-ready': displayReady }"
                    >
                        <div
                            @class([
                                'fff-schedule-field__day-collapse',
                                'is-expanded' => $isDayEnabledInitial,
                            ])
                            x-bind:class="{ 'is-expanded': isDayEnabled(@js($day)) }"
                            x-bind:aria-hidden="! isDayEnabled(@js($day))"
                        >
                            <div class="fff-schedule-field__day-collapse-inner">
                                <div class="fff-schedule-field__slots">
                                    <template x-for="(slot, slotIndex) in daySlots(@js($day))" x-bind:key="`${@js($day)}-${slotIndex}`">
                                        <div
                                            class="fff-schedule-field__slot"
                                            x-bind:class="{
                                                'is-invalid': slotIsInvalid(@js($day), slotIndex),
                                                'fff-schedule-field__slot--break': isBreakSlot(slot),
                                            }"
                                        >
                                    <span class="fff-schedule-field__slot-label">
                                        <span
                                            class="fff-schedule-field__slot-label-icon"
                                            x-bind:class="isBreakSlot(slot) ? 'fff-schedule-field__slot-label-icon--break' : 'fff-schedule-field__slot-label-icon--slot'"
                                        >
                                            <span x-show="! isBreakSlot(slot)" x-cloak>
                                                <x-filament::icon :icon="GravityIcon::Briefcase" class="fff-schedule-field__slot-icon" />
                                            </span>
                                            <span x-show="isBreakSlot(slot)" x-cloak>
                                                <x-filament::icon :icon="GravityIcon::Clock" class="fff-schedule-field__slot-icon" />
                                            </span>
                                        </span>
                                        <span class="fff-schedule-field__slot-label-text" x-text="slotEntryLabel(@js($day), slotIndex)"></span>
                                    </span>

                                    <template x-if="flexTimeSegmentsReady">
                                        <div class="fff-schedule-field__time-fields">
                                            <div class="fff-schedule-field__time-group">
                                                <label class="fff-schedule-field__time-label" x-text="labels.from"></label>
                                                @include('filament-flex-fields::forms.components.partials.flex-time-segments', [
                                                    'size' => $size,
                                                    'variant' => $getVariant(),
                                                    'value' => '',
                                                    'minuteStep' => $timeStep,
                                                    'disabled' => $isDisabled,
                                                    'readOnly' => $isReadOnly,
                                                    'live' => true,
                                                    'skipScriptLoad' => true,
                                                    'getValueExpression' => "() => (daySlots('{$day}')[slotIndex] ?? {}).from ?? ''",
                                                    'setValueExpression' => "(value) => updateSlotTime('{$day}', slotIndex, 'from', value)",
                                                    'initialValueExpression' => '(daySlots(\''.$day.'\')[slotIndex] ?? {}).from ?? \'\'',
                                                    'maxValueExpression' => '(daySlots(\''.$day.'\')[slotIndex] ?? {}).to || null',
                                                    'ariaLabel' => $labels['from'] ?? __('filament-flex-fields::default.schedule.from'),
                                                ])
                                            </div>

                                            <div class="fff-schedule-field__time-group">
                                                <label class="fff-schedule-field__time-label" x-text="labels.to"></label>
                                                @include('filament-flex-fields::forms.components.partials.flex-time-segments', [
                                                    'size' => $size,
                                                    'variant' => $getVariant(),
                                                    'value' => '',
                                                    'minuteStep' => $timeStep,
                                                    'disabled' => $isDisabled,
                                                    'readOnly' => $isReadOnly,
                                                    'live' => true,
                                                    'skipScriptLoad' => true,
                                                    'getValueExpression' => "() => (daySlots('{$day}')[slotIndex] ?? {}).to ?? ''",
                                                    'setValueExpression' => "(value) => updateSlotTime('{$day}', slotIndex, 'to', value)",
                                                    'initialValueExpression' => '(daySlots(\''.$day.'\')[slotIndex] ?? {}).to ?? \'\'',
                                                    'minValueExpression' => '(daySlots(\''.$day.'\')[slotIndex] ?? {}).from || null',
                                                    'ariaLabel' => $labels['to'] ?? __('filament-flex-fields::default.schedule.to'),
                                                ])
                                            </div>
                                        </div>
                                    </template>

                                    <div
                                        class="fff-schedule-field__slot-actions"
                                        x-show="canRemoveSlot(@js($day))"
                                        x-cloak
                                    >
                                        <button
                                            type="button"
                                            class="fff-schedule-field__icon-btn"
                                            x-on:click="removeSlot(@js($day), slotIndex)"
                                            x-bind:disabled="! isInteractive"
                                            x-bind:aria-label="labels.removeSlot"
                                        >
                                            <x-filament::icon :icon="GravityIcon::TrashBin" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </template>
                                </div>

                                <div class="fff-schedule-field__slot-toolbar">
                                    <button
                                        type="button"
                                        class="fff-schedule-field__action-btn"
                                        x-on:click="addSlot(@js($day))"
                                        x-bind:disabled="! canAddSlot(@js($day))"
                                        x-bind:aria-label="labels.addSlot"
                                    >
                                        <x-filament::icon :icon="GravityIcon::Plus" class="h-3.5 w-3.5" />
                                        <span x-text="labels.addSlot"></span>
                                    </button>

                                    <button
                                        type="button"
                                        class="fff-schedule-field__action-btn"
                                        x-on:click="addBreak(@js($day))"
                                        x-bind:disabled="! canAddSlot(@js($day))"
                                        x-bind:aria-label="labels.addBreak"
                                    >
                                        <x-filament::icon :icon="GravityIcon::Clock" class="h-3.5 w-3.5" />
                                        <span x-text="labels.addBreak"></span>
                                    </button>
                                </div>

                                <p
                                    class="fff-schedule-field__day-error"
                                    x-show="dayValidationErrorMessage(@js($day))"
                                    x-text="dayValidationErrorMessage(@js($day))"
                                    role="alert"
                                    x-cloak
                                ></p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-dynamic-component>
