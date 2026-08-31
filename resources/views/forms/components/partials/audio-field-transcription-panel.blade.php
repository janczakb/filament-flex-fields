@php
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
    use Filament\Support\Enums\IconSize;

    $settingsVisible = $settingsVisible ?? true;
@endphp

<div
    class="fff-audio-field__stt-actions"
    x-ref="sttSettingsBoundary"
>
    <div class="fff-audio-field__stt-toolbar">
        <button
            type="button"
            class="fff-audio-field__stt-button"
            x-on:click="transcribeAudio()"
            x-bind:disabled="! canTranscribe"
        >
            <span x-show="! transcribing" x-text="labels.transcription.transcribe"></span>
            <span x-show="transcribing" x-cloak x-text="labels.transcription.transcribing"></span>
        </button>

        @if ($settingsVisible)
            <div
                class="fff-video-field__settings"
                x-on:click.outside="onSettingsMenuClickOutside($event)"
            >
                <div
                    class="fff-audio-field__settings-menu"
                    data-fff-settings-menu
                    x-ref="sttSettingsMenu"
                    hidden
                    x-cloak
                    x-bind:hidden="! settingsOpen && settingsMenuPopoverPhase !== 'ending' ? '' : null"
                    x-bind:data-starting-style="settingsMenuPopoverPhase === 'starting' ? '' : null"
                    x-bind:data-ending-style="settingsMenuPopoverPhase === 'ending' ? '' : null"
                    x-on:wheel.stop
                    role="menu"
                    x-bind:aria-label="labels.transcription.settings_title"
                >
                    <div class="fff-audio-field__settings-viewport" data-menu-viewport>
                        <div
                            class="fff-audio-field__settings-panel"
                            x-ref="sttSettingsPanelRoot"
                            data-menu-root-view
                            data-menu-view
                            x-bind:data-menu-view-state="isSettingsRootInactive() ? 'inactive' : 'active'"
                            x-bind:data-direction="settingsViewDirection"
                            role="none"
                        >
                            <div class="fff-audio-field__settings-group">
                                <button
                                    type="button"
                                    class="fff-audio-field__settings-item fff-audio-field__settings-item--submenu"
                                    x-on:click="openSettingsView('model')"
                                    role="menuitem"
                                >
                                    <span class="fff-audio-field__settings-item-label" x-text="labels.transcription.model_hint"></span>
                                    <span class="fff-audio-field__settings-item-hint">
                                        <span class="fff-audio-field__settings-item-hint-label" x-text="selectedModelLabel"></span>
                                        <span class="fff-audio-field__settings-item-chevron" aria-hidden="true">
                                            {{ \Filament\Support\generate_icon_html(GravityIcon::ChevronRight, size: IconSize::ExtraSmall) }}
                                        </span>
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    class="fff-audio-field__settings-item fff-audio-field__settings-item--submenu"
                                    x-on:click="openSettingsView('language')"
                                    x-bind:disabled="! sttMultilingual"
                                    role="menuitem"
                                >
                                    <span class="fff-audio-field__settings-item-label" x-text="labels.transcription.language_hint"></span>
                                    <span class="fff-audio-field__settings-item-hint">
                                        <span class="fff-audio-field__settings-item-hint-label" x-text="selectedLanguageLabel"></span>
                                        <span class="fff-audio-field__settings-item-chevron" aria-hidden="true">
                                            {{ \Filament\Support\generate_icon_html(GravityIcon::ChevronRight, size: IconSize::ExtraSmall) }}
                                        </span>
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    class="fff-audio-field__settings-item fff-audio-field__settings-item--submenu"
                                    x-on:click="openSettingsView('task')"
                                    x-bind:disabled="! sttMultilingual"
                                    role="menuitem"
                                >
                                    <span class="fff-audio-field__settings-item-label" x-text="labels.transcription.task_hint"></span>
                                    <span class="fff-audio-field__settings-item-hint">
                                        <span class="fff-audio-field__settings-item-hint-label" x-text="selectedTaskLabel"></span>
                                        <span class="fff-audio-field__settings-item-chevron" aria-hidden="true">
                                            {{ \Filament\Support\generate_icon_html(GravityIcon::ChevronRight, size: IconSize::ExtraSmall) }}
                                        </span>
                                    </span>
                                </button>

                                <div class="fff-audio-field__settings-item fff-audio-field__settings-item--switch" role="none">
                                    <span class="fff-audio-field__settings-item-label" x-text="labels.transcription.multilingual"></span>
                                    <div @class(['fff-switch', 'fff-switch--sm', 'fff-switch--inline'])>
                                        <button
                                            type="button"
                                            class="fff-switch__control bg-gray-200 dark:bg-gray-700"
                                            role="switch"
                                            x-bind:aria-checked="sttMultilingual ? 'true' : 'false'"
                                            x-bind:data-checked="sttMultilingual ? 'true' : 'false'"
                                            x-bind:class="sttMultilingual ? 'bg-primary-600 dark:bg-primary-500' : 'bg-gray-200 dark:bg-gray-700'"
                                            x-on:click="toggleSttMultilingual()"
                                        >
                                            <span class="fff-switch__thumb"></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="fff-audio-field__settings-item fff-audio-field__settings-item--switch" role="none">
                                    <span class="fff-audio-field__settings-item-label" x-text="labels.transcription.quantized"></span>
                                    <div class="fff-switch fff-switch--sm fff-switch--inline">
                                        <button
                                            type="button"
                                            class="fff-switch__control bg-gray-200 dark:bg-gray-700"
                                            role="switch"
                                            x-bind:aria-checked="sttQuantized ? 'true' : 'false'"
                                            x-bind:data-checked="sttQuantized ? 'true' : 'false'"
                                            x-bind:class="sttQuantized ? 'bg-primary-600 dark:bg-primary-500' : 'bg-gray-200 dark:bg-gray-700'"
                                            x-on:click="toggleSttQuantized()"
                                        >
                                            <span class="fff-switch__thumb"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="fff-audio-field__settings-panel"
                            x-ref="sttSettingsPanelModel"
                            data-submenu
                            data-menu-view
                            x-bind:data-open="getSettingsSubmenuPhase('model') !== 'hidden' ? '' : null"
                            x-bind:hidden="getSettingsSubmenuPhase('model') === 'hidden' ? '' : null"
                            x-bind:data-direction="settingsViewDirection"
                            x-bind:data-starting-style="getSettingsSubmenuPhase('model') === 'entering' ? '' : null"
                            x-bind:data-ending-style="getSettingsSubmenuPhase('model') === 'exiting' ? '' : null"
                            role="none"
                        >
                            <button type="button" class="fff-audio-field__settings-back" x-on:click="openSettingsView('root')">
                                <span class="fff-audio-field__settings-item-chevron fff-audio-field__settings-item-chevron--back" aria-hidden="true">
                                    {{ \Filament\Support\generate_icon_html(GravityIcon::ChevronLeft, size: IconSize::ExtraSmall) }}
                                </span>
                                <span x-text="labels.transcription.model_hint"></span>
                            </button>

                            <div class="fff-audio-field__settings-separator" aria-hidden="true"></div>

                            <div class="fff-audio-field__settings-group" role="radiogroup">
                                <template x-for="model in transcriptionModelOptions" :key="model.id">
                                    <button
                                        type="button"
                                        class="fff-audio-field__settings-item"
                                        x-on:click="selectSttModel(model.id)"
                                        role="menuitemradio"
                                        x-bind:aria-checked="sttModel === model.id ? 'true' : 'false'"
                                    >
                                        <span x-text="model.label"></span>
                                        <span class="fff-audio-field__settings-item-indicator" x-show="sttModel === model.id" aria-hidden="true">
                                            {{ \Filament\Support\generate_icon_html(GravityIcon::Check, size: IconSize::ExtraSmall) }}
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div
                            class="fff-audio-field__settings-panel"
                            x-ref="sttSettingsPanelLanguage"
                            data-submenu
                            data-menu-view
                            x-bind:data-open="getSettingsSubmenuPhase('language') !== 'hidden' ? '' : null"
                            x-bind:hidden="getSettingsSubmenuPhase('language') === 'hidden' ? '' : null"
                            x-bind:data-direction="settingsViewDirection"
                            x-bind:data-starting-style="getSettingsSubmenuPhase('language') === 'entering' ? '' : null"
                            x-bind:data-ending-style="getSettingsSubmenuPhase('language') === 'exiting' ? '' : null"
                            role="none"
                        >
                            <button type="button" class="fff-audio-field__settings-back" x-on:click="openSettingsView('root')">
                                <span class="fff-audio-field__settings-item-chevron fff-audio-field__settings-item-chevron--back" aria-hidden="true">
                                    {{ \Filament\Support\generate_icon_html(GravityIcon::ChevronLeft, size: IconSize::ExtraSmall) }}
                                </span>
                                <span x-text="labels.transcription.language_hint"></span>
                            </button>

                            <div class="fff-audio-field__settings-separator" aria-hidden="true"></div>

                            <div class="fff-audio-field__settings-group" role="radiogroup">
                                <template x-for="language in transcriptionLanguages" :key="language.code ?? 'auto'">
                                    <button
                                        type="button"
                                        class="fff-audio-field__settings-item"
                                        x-on:click="selectSttLanguage(language.code)"
                                        role="menuitemradio"
                                        x-bind:aria-checked="(sttLanguage ?? '') === (language.code ?? '') ? 'true' : 'false'"
                                    >
                                        <span x-text="language.label"></span>
                                        <span
                                            class="fff-audio-field__settings-item-indicator"
                                            x-show="(sttLanguage ?? '') === (language.code ?? '')"
                                            aria-hidden="true"
                                        >
                                            {{ \Filament\Support\generate_icon_html(GravityIcon::Check, size: IconSize::ExtraSmall) }}
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div
                            class="fff-audio-field__settings-panel"
                            x-ref="sttSettingsPanelTask"
                            data-submenu
                            data-menu-view
                            x-bind:data-open="getSettingsSubmenuPhase('task') !== 'hidden' ? '' : null"
                            x-bind:hidden="getSettingsSubmenuPhase('task') === 'hidden' ? '' : null"
                            x-bind:data-direction="settingsViewDirection"
                            x-bind:data-starting-style="getSettingsSubmenuPhase('task') === 'entering' ? '' : null"
                            x-bind:data-ending-style="getSettingsSubmenuPhase('task') === 'exiting' ? '' : null"
                            role="none"
                        >
                            <button type="button" class="fff-audio-field__settings-back" x-on:click="openSettingsView('root')">
                                <span class="fff-audio-field__settings-item-chevron fff-audio-field__settings-item-chevron--back" aria-hidden="true">
                                    {{ \Filament\Support\generate_icon_html(GravityIcon::ChevronLeft, size: IconSize::ExtraSmall) }}
                                </span>
                                <span x-text="labels.transcription.task_hint"></span>
                            </button>

                            <div class="fff-audio-field__settings-separator" aria-hidden="true"></div>

                            <div class="fff-audio-field__settings-group" role="radiogroup">
                                <button
                                    type="button"
                                    class="fff-audio-field__settings-item"
                                    x-on:click="selectSttTask('transcribe')"
                                    role="menuitemradio"
                                    x-bind:aria-checked="sttTask === 'transcribe' ? 'true' : 'false'"
                                >
                                    <span x-text="labels.transcription.task_transcribe"></span>
                                    <span class="fff-audio-field__settings-item-indicator" x-show="sttTask === 'transcribe'" aria-hidden="true">
                                        {{ \Filament\Support\generate_icon_html(GravityIcon::Check, size: IconSize::ExtraSmall) }}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    class="fff-audio-field__settings-item"
                                    x-on:click="selectSttTask('translate')"
                                    role="menuitemradio"
                                    x-bind:aria-checked="sttTask === 'translate' ? 'true' : 'false'"
                                >
                                    <span x-text="labels.transcription.task_translate"></span>
                                    <span class="fff-audio-field__settings-item-indicator" x-show="sttTask === 'translate'" aria-hidden="true">
                                        {{ \Filament\Support\generate_icon_html(GravityIcon::Check, size: IconSize::ExtraSmall) }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    class="fff-video-field__glass-btn"
                    x-ref="sttSettingsTrigger"
                    x-on:click="toggleSettingsMenu()"
                    x-bind:aria-label="labels.transcription.settings_title"
                    x-bind:aria-expanded="settingsOpen ? 'true' : 'false'"
                    x-bind:aria-haspopup="true"
                    x-bind:class="{ 'is-active': settingsOpen }"
                >
                    {{ \Filament\Support\generate_icon_html(GravityIcon::Gear, size: IconSize::ExtraSmall) }}
                </button>
            </div>
        @endif
    </div>

    <p
        class="fff-audio-field__stt-status"
        hidden
        x-bind:hidden="transcriptionStatusLabel ? null : ''"
        x-show="transcriptionStatusLabel"
        x-cloak
        x-text="transcriptionStatusLabel"
        x-bind:class="{ 'is-error': transcriptionError }"
    ></p>

    <div
        class="fff-audio-field__transcript"
        hidden
        x-bind:hidden="transcript ? null : ''"
        x-show="transcript"
        x-cloak
    >
        <pre class="fff-audio-field__transcript-text" x-text="transcript"></pre>
    </div>
</div>
