@php
    use Filament\Support\Enums\IconSize;
@endphp

<div class="fff-flex-text-input__action-item fff-flex-text-input__emoji-picker">
    <button
        type="button"
        x-ref="emojiTrigger"
        class="fff-flex-text-input__action-btn fff-flex-text-input__action-btn--emoji"
        x-bind:aria-expanded="emojiPickerOpen ? 'true' : 'false'"
        x-bind:title="emojiPickerLabel"
        x-bind:aria-label="emojiPickerLabel"
        x-on:click="toggleEmojiPicker()"
    >
        {{ \Filament\Support\generate_icon_html($getEmojiIcon(), size: IconSize::Small, attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'fff-flex-text-input__action-icon'])) }}
    </button>
</div>
