@php
    use Filament\Support\Enums\IconSize;
@endphp

<div class="fff-flex-textarea__emoji-picker">
    <button
        type="button"
        x-ref="emojiTrigger"
        class="fff-emoji-picker__trigger fff-flex-textarea__emoji-trigger"
        x-bind:aria-expanded="emojiPickerOpen ? 'true' : 'false'"
        x-bind:title="emojiPickerLabel"
        x-bind:aria-label="emojiPickerLabel"
        x-on:click="toggleEmojiPicker()"
    >
        {{ \Filament\Support\generate_icon_html($getEmojiIcon(), size: IconSize::Small, attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'fff-emoji-picker__icon'])) }}
    </button>
</div>
