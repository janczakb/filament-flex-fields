@php
    $statePath = $getStatePath();
    $min = $getMin();
    $max = $getMax();
    $step = $getStep();
    $isInteger = $isInteger();
    $showOutput = $shouldShowOutput();
    $suffix = $getDisplaySuffix();
    $variant = $getVariant();
    $size = $getSize();
    $isDisabled = $isDisabled();
    $label = $getLabel();
    $trackLabel = $getTrackLabel();
    $decimalPlaces = $getDecimalPlaces();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'track-slider'])
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            min: @js($min),
            max: @js($max),
            step: @js($step),
            integer: @js($isInteger),
            decimalPlaces: @js($decimalPlaces),
            suffix: @js($suffix),
            disabled: @js($isDisabled),
            isDragging: false,
            isHovered: false,
            dragValue: null,
            normalize(value) {
                const numeric = Number(value);

                if (Number.isNaN(numeric)) {
                    return this.min;
                }

                const clamped = Math.min(this.max, Math.max(this.min, numeric));

                if (this.integer) {
                    return Math.round(clamped);
                }

                if (this.decimalPlaces === null) {
                    return clamped;
                }

                return Number(clamped.toFixed(this.decimalPlaces));
            },
            get numericState() {
                return this.normalize(this.state ?? this.min);
            },
            get activeValue() {
                return this.dragValue !== null ? this.dragValue : this.numericState;
            },
            get fillPercent() {
                const range = this.max - this.min;

                if (range <= 0) {
                    return 0;
                }

                return ((this.activeValue - this.min) / range) * 100;
            },
            get displayValue() {
                const current = this.activeValue;

                let value = this.integer
                    ? String(current)
                    : (this.decimalPlaces === null
                        ? String(current)
                        : current.toFixed(this.decimalPlaces));

                return this.suffix ? `${value}${this.suffix}` : value;
            },
            valueFromClientX(clientX) {
                const rect = this.$refs.track.getBoundingClientRect();
                const ratio = Math.min(1, Math.max(0, (clientX - rect.left) / rect.width));
                const raw = this.min + (ratio * (this.max - this.min));
                const stepped = Math.round(raw / this.step) * this.step;

                return this.normalize(stepped);
            },
            setFromClientX(clientX) {
                if (this.disabled) {
                    return;
                }

                const next = this.valueFromClientX(clientX);

                this.dragValue = next;
                this.state = next;
            },
            onPointerDown(event) {
                if (this.disabled || event.button !== 0) {
                    return;
                }

                event.preventDefault();

                this.isDragging = true;
                this.setFromClientX(event.clientX);
                this.$refs.track.setPointerCapture(event.pointerId);
            },
            onPointerMove(event) {
                if (! this.isDragging || this.disabled) {
                    return;
                }

                this.setFromClientX(event.clientX);
            },
            onPointerUp(event) {
                if (! this.isDragging) {
                    return;
                }

                if (this.dragValue !== null) {
                    this.state = this.dragValue;
                }

                this.isDragging = false;
                this.dragValue = null;

                if (this.$refs.track.hasPointerCapture(event.pointerId)) {
                    this.$refs.track.releasePointerCapture(event.pointerId);
                }
            },
            decrement() {
                if (this.disabled) {
                    return;
                }

                this.state = this.normalize(this.numericState - this.step);
            },
            increment() {
                if (this.disabled) {
                    return;
                }

                this.state = this.normalize(this.numericState + this.step);
            },
        }"
        @class([
            'fff-track-slider',
            'fff-track-slider--'.$size,
            'fff-track-slider--'.$variant,
            'is-disabled' => $isDisabled,
            'is-dragging' => false,
        ])
        x-bind:class="{ 'is-dragging': isDragging }"
        role="group"
        aria-label="{{ $label }}"
    >
        <div
            x-ref="track"
            class="fff-track-slider__track"
            role="slider"
            x-bind:aria-valuemin="min"
            x-bind:aria-valuemax="max"
            x-bind:aria-valuenow="numericState"
            x-bind:aria-disabled="disabled ? 'true' : 'false'"
            x-bind:tabindex="disabled ? -1 : 0"
            x-on:pointerdown="onPointerDown($event)"
            x-on:pointermove="onPointerMove($event)"
            x-on:pointerup="onPointerUp($event)"
            x-on:pointercancel="onPointerUp($event)"
            x-on:mouseenter="isHovered = true"
            x-on:mouseleave="isHovered = false"
            x-on:keydown.arrow-left.prevent="decrement()"
            x-on:keydown.arrow-right.prevent="increment()"
            x-on:keydown.home.prevent="state = min"
            x-on:keydown.end.prevent="state = max"
        >
            <span
                class="fff-track-slider__fill"
                x-bind:style="`width: ${fillPercent}%`"
                aria-hidden="true"
            ></span>

            <span
                class="fff-track-slider__thumb"
                x-bind:class="{
                    'is-visible': isHovered || isDragging,
                    'is-dragging': isDragging,
                }"
                x-bind:style="`left: ${fillPercent}%`"
                aria-hidden="true"
            ></span>

            @if (filled($trackLabel))
                <span class="fff-track-slider__track-label">{{ $trackLabel }}</span>
            @endif

            @if ($showOutput)
                <span class="fff-track-slider__output" x-text="displayValue"></span>
            @endif
        </div>
    </div>
</x-dynamic-component>
