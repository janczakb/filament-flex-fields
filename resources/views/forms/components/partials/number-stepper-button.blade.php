<button
    type="button"
    class="fff-number-stepper__button"
    x-on:click="{{ $action }}()"
    x-bind:disabled="! {{ $canProperty }}"
    x-bind:aria-disabled="! {{ $canProperty }}"
    aria-label="{{ $label }}"
>
    @if ($icon)
        <x-filament::icon :icon="$icon" class="fff-number-stepper__icon" />
    @elseif ($action === 'decrement')
        <svg class="fff-number-stepper__icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M4 10a.75.75 0 0 1 .75-.75h10.5a.75.75 0 0 1 0 1.5H4.75A.75.75 0 0 1 4 10Z" clip-rule="evenodd" />
        </svg>
    @else
        <svg class="fff-number-stepper__icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
        </svg>
    @endif
</button>
