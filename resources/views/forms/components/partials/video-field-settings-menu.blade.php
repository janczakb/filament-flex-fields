@php
    use Filament\Support\Enums\IconSize;
@endphp

<div
    class="fff-video-field__settings"
    x-on:click.outside="onSettingsMenuClickOutside($event)"
>
    <div
        class="fff-video-field__settings-menu"
        x-ref="settingsMenu"
        data-side="top"
        x-bind:hidden="! settingsOpen && settingsMenuPopoverPhase !== 'ending' ? '' : null"
        x-bind:data-starting-style="settingsMenuPopoverPhase === 'starting' ? '' : null"
        x-bind:data-ending-style="settingsMenuPopoverPhase === 'ending' ? '' : null"
        x-on:wheel.stop
        role="menu"
        aria-label="{{ __('filament-flex-fields::default.video.settings') }}"
    >
        <div class="fff-video-field__settings-viewport" data-menu-viewport>
            <div
                class="fff-video-field__settings-panel"
                x-ref="settingsPanelRoot"
                data-menu-root-view
                data-menu-view
                x-bind:data-menu-view-state="isSettingsRootInactive() ? 'inactive' : 'active'"
                x-bind:data-direction="settingsViewDirection"
                role="none"
            >
                <div class="fff-video-field__settings-group">
                    @if ($hasQualityControl)
                        <button
                            type="button"
                            class="fff-video-field__settings-item fff-video-field__settings-item--submenu"
                            x-on:click="openSettingsView('quality')"
                            role="menuitem"
                        >
                            <span class="fff-video-field__settings-item-icon" aria-hidden="true">
                                {{ \Filament\Support\generate_icon_html($field->getQualityIcon(), size: IconSize::ExtraSmall) }}
                            </span>
                            <span class="fff-video-field__settings-item-label">{{ __('filament-flex-fields::default.video.quality') }}</span>
                            <span class="fff-video-field__settings-item-hint">
                                <span class="fff-video-field__settings-item-hint-label" x-text="selectedQualityLabel"></span>
                                <span class="fff-video-field__settings-item-chevron" aria-hidden="true">
                                    {{ \Filament\Support\generate_icon_html(\Bjanczak\FilamentFlexFields\Support\GravityIcon::ChevronRight, size: IconSize::ExtraSmall) }}
                                </span>
                            </span>
                        </button>
                    @endif

                    @if ($hasPlaybackRateControl)
                        <button
                            type="button"
                            class="fff-video-field__settings-item fff-video-field__settings-item--submenu"
                            x-on:click="openSettingsView('speed')"
                            role="menuitem"
                        >
                            <span class="fff-video-field__settings-item-icon" aria-hidden="true">
                                {{ \Filament\Support\generate_icon_html($field->getSpeedIcon(), size: IconSize::ExtraSmall) }}
                            </span>
                            <span class="fff-video-field__settings-item-label">{{ __('filament-flex-fields::default.video.speed') }}</span>
                            <span class="fff-video-field__settings-item-hint">
                                <span class="fff-video-field__settings-item-hint-label" x-text="playbackRateLabel"></span>
                                <span class="fff-video-field__settings-item-chevron" aria-hidden="true">
                                    {{ \Filament\Support\generate_icon_html(\Bjanczak\FilamentFlexFields\Support\GravityIcon::ChevronRight, size: IconSize::ExtraSmall) }}
                                </span>
                            </span>
                        </button>
                    @endif
                </div>
            </div>

            @if ($hasQualityControl)
                <div
                    class="fff-video-field__settings-panel"
                    x-ref="settingsPanelQuality"
                    data-submenu
                    data-menu-view
                        x-bind:data-open="getSettingsSubmenuPhase('quality') !== 'hidden' ? '' : null"
                        x-bind:hidden="getSettingsSubmenuPhase('quality') === 'hidden' ? '' : null"
                        x-bind:data-direction="settingsViewDirection"
                    x-bind:data-starting-style="getSettingsSubmenuPhase('quality') === 'entering' ? '' : null"
                    x-bind:data-ending-style="getSettingsSubmenuPhase('quality') === 'exiting' ? '' : null"
                    role="none"
                >
                    <button
                        type="button"
                        class="fff-video-field__settings-back"
                        x-on:click="openSettingsView('root')"
                    >
                        <span class="fff-video-field__settings-item-chevron fff-video-field__settings-item-chevron--back" aria-hidden="true">
                            {{ \Filament\Support\generate_icon_html(\Bjanczak\FilamentFlexFields\Support\GravityIcon::ChevronLeft, size: IconSize::ExtraSmall) }}
                        </span>
                        <span>{{ __('filament-flex-fields::default.video.quality') }}</span>
                    </button>

                    <div class="fff-video-field__settings-separator" aria-hidden="true"></div>

                    <div class="fff-video-field__settings-group" role="radiogroup">
                        <template x-for="option in qualityOptions" :key="option.key">
                            <button
                                type="button"
                                class="fff-video-field__settings-item"
                                    x-on:click="selectQuality(option)"
                                    role="menuitemradio"
                                x-bind:aria-checked="selectedQualityKey === option.key ? 'true' : 'false'"
                            >
                                <span x-text="option.label"></span>
                                <span
                                    class="fff-video-field__settings-item-indicator"
                                    x-show="selectedQualityKey === option.key"
                                    aria-hidden="true"
                                >
                                    {{ \Filament\Support\generate_icon_html(\Bjanczak\FilamentFlexFields\Support\GravityIcon::Check, size: IconSize::ExtraSmall) }}
                                </span>
                            </button>
                        </template>
                    </div>
                </div>
            @endif

            @if ($hasPlaybackRateControl)
                <div
                    class="fff-video-field__settings-panel"
                    x-ref="settingsPanelSpeed"
                    data-submenu
                    data-menu-view
                        x-bind:data-open="getSettingsSubmenuPhase('speed') !== 'hidden' ? '' : null"
                        x-bind:hidden="getSettingsSubmenuPhase('speed') === 'hidden' ? '' : null"
                        x-bind:data-direction="settingsViewDirection"
                    x-bind:data-starting-style="getSettingsSubmenuPhase('speed') === 'entering' ? '' : null"
                    x-bind:data-ending-style="getSettingsSubmenuPhase('speed') === 'exiting' ? '' : null"
                    role="none"
                >
                    <button
                        type="button"
                        class="fff-video-field__settings-back"
                        x-on:click="openSettingsView('root')"
                    >
                        <span class="fff-video-field__settings-item-chevron fff-video-field__settings-item-chevron--back" aria-hidden="true">
                            {{ \Filament\Support\generate_icon_html(\Bjanczak\FilamentFlexFields\Support\GravityIcon::ChevronLeft, size: IconSize::ExtraSmall) }}
                        </span>
                        <span>{{ __('filament-flex-fields::default.video.speed') }}</span>
                    </button>

                    <div class="fff-video-field__settings-separator" aria-hidden="true"></div>

                    <div class="fff-video-field__settings-group" role="radiogroup">
                        <template x-for="rate in playbackRates" :key="rate">
                            <button
                                type="button"
                                class="fff-video-field__settings-item"
                                    x-on:click="setPlaybackRate(rate)"
                                    role="menuitemradio"
                                x-bind:aria-checked="playbackRate === rate ? 'true' : 'false'"
                            >
                                <span x-text="formatPlaybackRateLabel(rate)"></span>
                                <span
                                    class="fff-video-field__settings-item-indicator"
                                    x-show="playbackRate === rate"
                                    aria-hidden="true"
                                >
                                    {{ \Filament\Support\generate_icon_html(\Bjanczak\FilamentFlexFields\Support\GravityIcon::Check, size: IconSize::ExtraSmall) }}
                                </span>
                            </button>
                        </template>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <x-filament-flex-fields::video-field-control-tip
        :label="__('filament-flex-fields::default.video.settings')"
        :enabled="$showTooltips"
    >
        <button
            type="button"
            class="fff-video-field__glass-btn"
            x-ref="settingsTrigger"
            x-on:click="toggleSettingsMenu()"
            x-bind:aria-label="@js(__('filament-flex-fields::default.video.settings'))"
            x-bind:aria-expanded="settingsOpen ? 'true' : 'false'"
            x-bind:aria-haspopup="true"
            x-bind:class="{ 'is-active': settingsOpen }"
        >
            {{ \Filament\Support\generate_icon_html($field->getSettingsIcon(), size: $toolbarIconSize) }}
        </button>
    </x-filament-flex-fields::video-field-control-tip>
</div>
