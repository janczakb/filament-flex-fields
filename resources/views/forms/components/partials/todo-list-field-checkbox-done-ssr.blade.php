{{--
    Static done checkbox matching live (dark square + white in-square tick + dark tip outside).
    $tickKey must be unique on the page (e.g. item id).
--}}
@php
    $tickKey = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($tickKey ?? uniqid('todo', false)));
    $maskId = 'fff-todo-ssr-mask-'.$tickKey;
    $tickPath = 'M5.22003 7.26C5.72003 7.76 7.57 9.7 8.67 11.45C12.2 6.05 15.65 3.5 19.19 1.69';
    $shapePath = 'M1.08722 4.13374C1.29101 2.53185 2.53185 1.29101 4.13374 1.08722C5.50224 0.913124 7.25112 0.75 9 0.75C10.7489 0.75 12.4978 0.913124 13.8663 1.08722C15.4681 1.29101 16.709 2.53185 16.9128 4.13374C17.0869 5.50224 17.25 7.25112 17.25 9C17.25 10.7489 17.0869 12.4978 16.9128 13.8663C16.709 15.4681 15.4682 16.709 13.8663 16.9128C12.4978 17.0869 10.7489 17.25 9 17.25C7.25112 17.25 5.50224 17.0869 4.13374 16.9128C2.53185 16.709 1.29101 15.4681 1.08722 13.8663C0.913124 12.4978 0.75 10.7489 0.75 9C0.75 7.25112 0.913124 5.50224 1.08722 4.13374Z';
    $fillPath = 'M4.03909 0.343217C5.42566 0.166822 7.20841 0 9 0C10.7916 0 12.5743 0.166822 13.9609 0.343217C15.902 0.590152 17.4098 2.09804 17.6568 4.03909C17.8332 5.42566 18 7.20841 18 9C18 10.7916 17.8332 12.5743 17.6568 13.9609C17.4098 15.902 15.902 17.4098 13.9609 17.6568C12.5743 17.8332 10.7916 18 9 18C7.20841 18 5.42566 17.8332 4.03909 17.6568C2.09805 17.4098 0.590152 15.902 0.343217 13.9609C0.166822 12.5743 0 10.7916 0 9C0 7.20841 0.166822 5.42566 0.343217 4.03909C0.590151 2.09805 2.09804 0.590152 4.03909 0.343217Z';
@endphp
<span class="fff-todo-list-field__checkbox" aria-hidden="true">
    <span class="fff-todo-list-field__checkbox-input"></span>
    <svg
        class="fff-todo-list-field__checkbox-svg"
        viewBox="0 0 21 18"
        style="--background: var(--checkbox-active); --border: var(--checkbox-active); --checkbox-tick-offset: 0; color: var(--checkbox-active);"
    >
        <defs>
            <mask id="{{ $maskId }}">
                <path
                    class="fff-todo-list-field__tick mask"
                    d="{{ $tickPath }}"
                    fill="none"
                    stroke="#ffffff"
                    stroke-width="2.25"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-dasharray="none"
                    stroke-dashoffset="0"
                />
            </mask>
        </defs>
        <path class="fff-todo-list-field__shape" d="{{ $shapePath }}" />
        {{-- Dark tip outside the square --}}
        <path
            class="fff-todo-list-field__tick"
            d="{{ $tickPath }}"
            fill="none"
            stroke="currentColor"
            stroke-width="2.25"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        {{-- White check punched inside the square --}}
        <path
            class="fff-todo-list-field__tick-fill"
            style="opacity: 1; fill: #ffffff;"
            mask="url(#{{ $maskId }})"
            d="{{ $fillPath }}"
        />
    </svg>
</span>
