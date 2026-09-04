@php
    /** @var string $todoVar Alpine item variable name (item|child) */
    $todoVar = $todoVar ?? 'item';
    $instanceId = $instanceId ?? 'todo';
@endphp
<span class="fff-todo-list-field__checkbox" aria-hidden="true">
    <input
        type="checkbox"
        class="fff-todo-list-field__checkbox-input"
        tabindex="-1"
        x-bind:checked="isTodoDone({{ $todoVar }}.id)"
        x-effect="$el.checked = isTodoDone({{ $todoVar }}.id)"
        :disabled="disabled || {{ $todoVar }}.disabled"
        @click.stop.prevent="toggle({{ $todoVar }}.id, $event)"
    />
    <svg class="fff-todo-list-field__checkbox-svg" viewBox="0 0 21 18">
        <symbol :id="'fff-todo-tick-path-{{ $instanceId }}-' + {{ $todoVar }}.id" viewBox="0 0 21 18">
            <path
                d="M5.22003 7.26C5.72003 7.76 7.57 9.7 8.67 11.45C12.2 6.05 15.65 3.5 19.19 1.69"
                fill="none"
                stroke-width="2.25"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </symbol>
        <defs>
            <mask :id="'fff-todo-tick-mask-{{ $instanceId }}-' + {{ $todoVar }}.id">
                <use
                    class="fff-todo-list-field__tick mask"
                    :href="'#fff-todo-tick-path-{{ $instanceId }}-' + {{ $todoVar }}.id"
                />
            </mask>
        </defs>
        <path
            class="fff-todo-list-field__shape"
            d="M1.08722 4.13374C1.29101 2.53185 2.53185 1.29101 4.13374 1.08722C5.50224 0.913124 7.25112 0.75 9 0.75C10.7489 0.75 12.4978 0.913124 13.8663 1.08722C15.4681 1.29101 16.709 2.53185 16.9128 4.13374C17.0869 5.50224 17.25 7.25112 17.25 9C17.25 10.7489 17.0869 12.4978 16.9128 13.8663C16.709 15.4681 15.4682 16.709 13.8663 16.9128C12.4978 17.0869 10.7489 17.25 9 17.25C7.25112 17.25 5.50224 17.0869 4.13374 16.9128C2.53185 16.709 1.29101 15.4681 1.08722 13.8663C0.913124 12.4978 0.75 10.7489 0.75 9C0.75 7.25112 0.913124 5.50224 1.08722 4.13374Z"
        />
        <use
            class="fff-todo-list-field__tick"
            :href="'#fff-todo-tick-path-{{ $instanceId }}-' + {{ $todoVar }}.id"
            stroke="currentColor"
        />
        <path
            class="fff-todo-list-field__tick-fill"
            :mask="'url(#fff-todo-tick-mask-{{ $instanceId }}-' + {{ $todoVar }}.id + ')'"
            d="M4.03909 0.343217C5.42566 0.166822 7.20841 0 9 0C10.7916 0 12.5743 0.166822 13.9609 0.343217C15.902 0.590152 17.4098 2.09804 17.6568 4.03909C17.8332 5.42566 18 7.20841 18 9C18 10.7916 17.8332 12.5743 17.6568 13.9609C17.4098 15.902 15.902 17.4098 13.9609 17.6568C12.5743 17.8332 10.7916 18 9 18C7.20841 18 5.42566 17.8332 4.03909 17.6568C2.09805 17.4098 0.590152 15.902 0.343217 13.9609C0.166822 12.5743 0 10.7916 0 9C0 7.20841 0.166822 5.42566 0.343217 4.03909C0.590151 2.09805 2.09804 0.590152 4.03909 0.343217Z"
        />
    </svg>
    <svg class="fff-todo-list-field__lines" viewBox="0 0 11 11">
        <path d="M5.88086 5.89441L9.53504 4.26746" />
        <path d="M5.5274 8.78838L9.45391 9.55161" />
        <path d="M3.49371 4.22065L5.55387 0.79198" />
    </svg>
</span>
