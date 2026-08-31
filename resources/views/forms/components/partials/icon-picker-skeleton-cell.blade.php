{{-- Skeleton cell — mirrors icon-picker-option layout for pixel-identical grid geometry. --}}
<div
    class="fff-icon-picker__option fff-icon-picker__option--loading fi-select-input-option"
    aria-hidden="true"
>
    <span class="fff-icon-picker__option-icon">
        <span class="fff-icon-picker__skeleton fff-icon-picker__skeleton--icon"></span>
    </span>
    <span
        class="fff-icon-picker__skeleton fff-icon-picker__skeleton--label"
        x-show="layout !== 'icons'"
    ></span>
</div>
